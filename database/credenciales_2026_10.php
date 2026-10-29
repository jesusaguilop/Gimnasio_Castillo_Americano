<?php
/**
 * Renueva los accesos del personal (2026-10):
 *   - crea una cuenta de administrador nueva;
 *   - cambia correo y clave de TODOS los profesores.
 *
 *   php database/credenciales_2026_10.php <credenciales.csv>             → PRUEBA: hace todo y lo deshace
 *   php database/credenciales_2026_10.php <credenciales.csv> --aplicar   → lo guarda de verdad
 *
 * CSV: tipo,id,nombre,correo_anterior,correo_nuevo,clave
 *   tipo = admin (id vacío → se crea) | profesor (id existente → se actualiza)
 *
 * A los profesores NO se les borra la fila: notas, profesor_curso_asignatura
 * y directores_grupo cuelgan de usuarios.id con ON DELETE CASCADE, así que
 * borrarlos se llevaría la carga académica y las notas. Se reemplazan
 * correo y clave en el mismo id: las credenciales viejas dejan de servir y
 * sus sesiones abiertas caen (Auth::sesionValida compara la huella de la clave).
 *
 * El CSV lleva claves en claro: va FUERA del repositorio (es público).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/env.php';

$args    = array_slice($argv, 1);
$aplicar = in_array('--aplicar', $args, true);
$csv     = array_values(array_filter($args, fn($a) => !str_starts_with($a, '--')))[0] ?? null;

if (!$csv || !is_file($csv)) {
    fwrite(STDERR, "Uso: php database/credenciales_2026_10.php <credenciales.csv> [--aplicar]" . PHP_EOL);
    exit(1);
}

$pdo = new PDO(db_dsn(), config('db.user'), (string) config('db.pass'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
]);

$fh = fopen($csv, 'r');
$cab = fgetcsv($fh, escape: '');
$cab[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cab[0]);
$filas = [];
while (($r = fgetcsv($fh, escape: '')) !== false) {
    if (count($r) === count($cab)) {
        $filas[] = array_combine($cab, $r);
    }
}
fclose($fh);

$hash = fn(string $clave) => password_hash($clave, PASSWORD_BCRYPT, ['cost' => 12]);

$profesoresBd = $pdo->query("SELECT id FROM usuarios WHERE rol = 'profesor'")->fetchAll(PDO::FETCH_COLUMN);
$idsCsv = array_map('intval', array_column(array_filter($filas, fn($f) => $f['tipo'] === 'profesor'), 'id'));
$faltan = array_diff($profesoresBd, $idsCsv);
if ($faltan) {
    fwrite(STDERR, "Profesores en la BD que no están en el CSV: " . implode(', ', $faltan) . PHP_EOL);
    exit(1);
}

$existe = $pdo->prepare("SELECT id FROM usuarios WHERE lower(email) = lower(?) AND id <> ?");
$insAdmin = $pdo->prepare(
    "INSERT INTO usuarios (nombre, email, password, rol, debe_cambiar_clave)
     VALUES (?, ?, ?, 'admin', FALSE) RETURNING id"
);
$updProfe = $pdo->prepare(
    "UPDATE usuarios
        SET email = ?, password = ?, debe_cambiar_clave = TRUE,
            reset_token = NULL, reset_expires = NULL
      WHERE id = ? AND rol = 'profesor' AND email = ?"
);

$pdo->beginTransaction();
try {
    foreach ($filas as $f) {
        $existe->execute([$f['correo_nuevo'], (int) $f['id']]);
        if ($existe->fetchColumn()) {
            throw new RuntimeException("El correo {$f['correo_nuevo']} ya lo usa otra cuenta");
        }

        if ($f['tipo'] === 'admin') {
            $insAdmin->execute([$f['nombre'], $f['correo_nuevo'], $hash($f['clave'])]);
            echo "admin creado     #" . $insAdmin->fetchColumn() . "  {$f['correo_nuevo']}" . PHP_EOL;
        } elseif ($f['tipo'] === 'profesor') {
            $updProfe->execute([$f['correo_nuevo'], $hash($f['clave']), (int) $f['id'], $f['correo_anterior']]);
            if ($updProfe->rowCount() !== 1) {
                throw new RuntimeException("No se encontró al profesor #{$f['id']} con correo {$f['correo_anterior']}");
            }
            echo "profesor #{$f['id']}  {$f['correo_anterior']} → {$f['correo_nuevo']}" . PHP_EOL;
        }
    }

    // Comprobación: cada clave nueva entra con su correo nuevo.
    $login = $pdo->prepare("SELECT password FROM usuarios WHERE email = ?");
    foreach ($filas as $f) {
        $login->execute([$f['correo_nuevo']]);
        if (!password_verify($f['clave'], (string) $login->fetchColumn())) {
            throw new RuntimeException("La clave de {$f['correo_nuevo']} no verifica");
        }
    }

    if ($aplicar) {
        $pdo->commit();
        echo PHP_EOL . "APLICADO: " . count($filas) . " cuentas." . PHP_EOL;
    } else {
        $pdo->rollBack();
        echo PHP_EOL . "PRUEBA OK (" . count($filas) . " cuentas) — nada se guardó. Repetir con --aplicar." . PHP_EOL;
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR, no se guardó nada: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
