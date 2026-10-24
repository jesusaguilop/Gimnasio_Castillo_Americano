<?php
/**
 * Aplica a la BD los cambios de estudiantes salidos de la PÓLIZA (lista
 * oficial del colegio), ya revisados.
 *
 *   php database/importar_estudiantes.php <cambios.csv>             → PRUEBA (se deshace)
 *   php database/importar_estudiantes.php <cambios.csv> --aplicar   → lo guarda
 *
 * CSV: accion,documento,nombre,grado,fecha_nacimiento
 *   accion = nuevo  → crea el estudiante en el curso de su grado (si el grado
 *                     tiene varios cursos, en el primero: el coordinador lo
 *                     cambia después). Si el documento ya existe, no hace nada.
 *   accion = fecha  → corrige la fecha de nacimiento del estudiante con ese documento.
 * Los documentos se comparan sin espacios ni guiones ("PPT 583" = "PPT583").
 * Todo en una transacción.
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
    fwrite(STDERR, "Uso: php database/importar_estudiantes.php <cambios.csv> [--aplicar]" . PHP_EOL);
    exit(1);
}

$pdo = new PDO(db_dsn(), config('db.user'), (string) config('db.pass'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
]);

$fh = fopen($csv, 'r');
$cab = fgetcsv($fh, escape: '');
$filas = [];
while (($r = fgetcsv($fh, escape: '')) !== false) {
    if (count($r) === count($cab)) {
        $filas[] = array_combine($cab, $r);
    }
}
fclose($fh);

$cursoDeGrado = $pdo->query("SELECT grado, MIN(id) AS curso_id FROM cursos GROUP BY grado")->fetchAll(PDO::FETCH_KEY_PAIR);
$buscar = $pdo->prepare("
    SELECT id, nombre, fecha_nacimiento FROM estudiantes
    WHERE upper(regexp_replace(documento, '[^A-Za-z0-9]', '', 'g')) = upper(regexp_replace(?, '[^A-Za-z0-9]', '', 'g'))
");
$insertar = $pdo->prepare("
    INSERT INTO estudiantes (nombre, documento, fecha_nacimiento, curso_id) VALUES (?, ?, ?, ?) RETURNING id
");
$fecha = $pdo->prepare("UPDATE estudiantes SET fecha_nacimiento = ? WHERE id = ?");

$log = [];
$pdo->beginTransaction();
try {
    foreach ($filas as $f) {
        $doc = trim($f['documento']);
        $nac = $f['fecha_nacimiento'] !== '' ? $f['fecha_nacimiento'] : null;
        if ($nac !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nac)) {
            throw new RuntimeException("Fecha inválida para $doc: $nac");
        }
        $buscar->execute([$doc]);
        $e = $buscar->fetch();

        if ($f['accion'] === 'nuevo') {
            if ($e) {
                $log[] = "ya existía: #{$e['id']} {$e['nombre']} ($doc) — sin cambios";
                continue;
            }
            $curso = $cursoDeGrado[$f['grado']] ?? null;
            if (!$curso) {
                throw new RuntimeException("No hay curso para el grado '{$f['grado']}'.");
            }
            $insertar->execute([mb_strtoupper(trim($f['nombre'])), $doc, $nac, $curso]);
            $log[] = "NUEVO #" . $insertar->fetchColumn() . " " . mb_strtoupper(trim($f['nombre'])) . " ($doc) en {$f['grado']}";
        } elseif ($f['accion'] === 'fecha') {
            if (!$e) {
                throw new RuntimeException("No existe el estudiante con documento $doc.");
            }
            $fecha->execute([$nac, $e['id']]);
            $log[] = "fecha de #{$e['id']} {$e['nombre']}: {$e['fecha_nacimiento']} → $nac";
        } else {
            throw new RuntimeException("Acción desconocida: {$f['accion']}");
        }
    }
    if ($aplicar) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $ex) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR — no se guardó nada: " . $ex->getMessage() . PHP_EOL);
    exit(1);
}

echo ($aplicar ? "APLICADO" : "PRUEBA (nada se guardó; usa --aplicar para guardar)") . PHP_EOL;
foreach ($log as $l) {
    echo "  - $l" . PHP_EOL;
}
