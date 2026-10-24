<?php
/**
 * Limpieza acordada con el colegio (2026-10-23):
 *   1. Borra las cuentas de PRUEBA de padres (pepito1@gmail.com — su hash
 *      estaba publicado en GitHub —, padre@gca.edu.co y padre.prueba@gca.edu.co).
 *   2. Borra TODOS los boletines generados hasta hoy (registros + PDF del disco
 *      local): se hicieron con el cálculo viejo.
 *   3. Genera un boletín de PRUEBA con el código nuevo, comprueba que el PDF
 *      existe y lo BORRA (registro y archivo).
 *
 *   php database/limpieza_2026_10.php              → PRUEBA (muestra qué haría, no cambia nada)
 *   php database/limpieza_2026_10.php --aplicar    → lo hace
 *
 * Requiere database/acudientes.sql aplicado ANTES: con la FK vieja (ON DELETE
 * CASCADE) borrar padre.prueba borraría a los estudiantes vinculados a ella.
 * Los PDF del volumen de Railway no se tocan desde aquí: al no tener registro
 * quedan inaccesibles (boletin_descargar.php solo entrega lo que está en la BD).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

$aplicar = in_array('--aplicar', $argv, true);
$pdo = App\Base\Database::connection();
$raiz = dirname(__DIR__);

$fk = $pdo->query("SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conname = 'fk_estudiantes_padre'")->fetchColumn();
if (!$fk || !str_contains($fk, 'SET NULL')) {
    fwrite(STDERR, "Primero aplica database/acudientes.sql (la FK de padre_id todavía borra en cascada)." . PHP_EOL);
    exit(1);
}

// ── 1. Cuentas de prueba ──
$correos = ['pepito1@gmail.com', 'padre@gca.edu.co', 'padre.prueba@gca.edu.co'];
$ph = implode(',', array_fill(0, count($correos), '?'));
$st = $pdo->prepare("SELECT u.id, u.email, u.rol,
        (SELECT string_agg(e.nombre, ', ') FROM estudiante_acudientes ea JOIN estudiantes e ON e.id = ea.estudiante_id
          WHERE ea.padre_id = u.id) AS hijos
    FROM usuarios u WHERE lower(u.email) IN ($ph)");
$st->execute($correos);
$cuentas = $st->fetchAll(PDO::FETCH_ASSOC);
echo "Cuentas de prueba:" . PHP_EOL;
foreach ($cuentas as $c) {
    if ($c['rol'] !== 'padre') {
        fwrite(STDERR, "  {$c['email']} no es de rol padre ({$c['rol']}): no se toca." . PHP_EOL);
        continue;
    }
    echo "  - #{$c['id']} {$c['email']}" . ($c['hijos'] ? " (se desvincula de: {$c['hijos']}; los estudiantes NO se borran)" : '') . PHP_EOL;
}

// ── 2. Boletines existentes ──
$boletines = $pdo->query("SELECT b.id, b.ruta_pdf, b.periodo, b.year, b.publicado, e.nombre
    FROM boletines_pdf b JOIN estudiantes e ON e.id = b.estudiante_id ORDER BY b.id")->fetchAll(PDO::FETCH_ASSOC);
echo PHP_EOL . "Boletines a borrar: " . count($boletines) . PHP_EOL;
foreach ($boletines as $b) {
    echo "  - #{$b['id']} {$b['nombre']} P{$b['periodo']} {$b['year']}" . ($b['publicado'] ? ' (publicado)' : '') . PHP_EOL;
}

if (!$aplicar) {
    echo PHP_EOL . "PRUEBA: no se cambió nada. Usa --aplicar." . PHP_EOL;
    exit(0);
}

$pdo->beginTransaction();
$idsPadre = array_column(array_filter($cuentas, fn($c) => $c['rol'] === 'padre'), 'id');
if ($idsPadre) {
    $pdo->prepare("DELETE FROM usuarios WHERE rol = 'padre' AND id IN (" . implode(',', array_fill(0, count($idsPadre), '?')) . ")")
        ->execute($idsPadre);
}
$pdo->exec("DELETE FROM boletines_pdf");
$pdo->commit();
foreach ($boletines as $b) {
    $f = $raiz . '/' . ltrim($b['ruta_pdf'], '/');
    if (is_file($f)) unlink($f);
}
echo PHP_EOL . "Borradas " . count($idsPadre) . " cuenta(s) y " . count($boletines) . " boletín(es)." . PHP_EOL;

// ── 3. Boletín de prueba: generar, comprobar y borrar ──
require_once $raiz . '/lib/boletin_generator.php';
$anio = (int) ($pdo->query("SELECT valor FROM configuraciones WHERE clave = 'anio_activo'")->fetchColumn() ?: date('Y'));
$cand = $pdo->prepare("SELECT n.estudiante_id, n.periodo, count(*) AS n FROM notas n WHERE n.anio = ?
    GROUP BY 1, 2 ORDER BY 3 DESC LIMIT 1");
$cand->execute([$anio]);
$c = $cand->fetch(PDO::FETCH_ASSOC);
if (!$c) {
    echo "No hay notas en $anio para generar un boletín de prueba." . PHP_EOL;
    exit(0);
}
$admin = (int) $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
$r = generarBoletinEstudiante($pdo, (int) $c['estudiante_id'], (string) $c['periodo'], $admin, 'v2', $anio);
if (empty($r['ok'])) {
    fwrite(STDERR, "El boletín de prueba NO se generó: " . ($r['motivo'] ?? 'motivo desconocido') . PHP_EOL);
    exit(1);
}
$archivo = $raiz . '/' . ltrim($r['ruta'], '/');
$tam = is_file($archivo) ? filesize($archivo) : 0;
$copia = dirname($raiz) . '/boletin_prueba_' . date('Ymd_His') . '.pdf';
if ($tam) copy($archivo, $copia);
echo "Boletín de prueba: estudiante #{$c['estudiante_id']}, P{$c['periodo']}, promedio {$r['promedio']}, PDF de $tam bytes" . PHP_EOL
   . "  copia para revisarlo: $copia (fuera del repo; bórrala después)" . PHP_EOL;

$pdo->prepare("DELETE FROM boletines_pdf WHERE estudiante_id = ? AND periodo = ? AND year = ?")
    ->execute([(int) $c['estudiante_id'], (string) $c['periodo'], $anio]);
if (is_file($archivo)) unlink($archivo);
echo "  …y borrado (registro y archivo). Boletines en la BD ahora: "
   . $pdo->query("SELECT count(*) FROM boletines_pdf")->fetchColumn() . PHP_EOL;
exit($tam > 1000 ? 0 : 1);
