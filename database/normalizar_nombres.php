<?php
/**
 * Pasa los nombres de los estudiantes por normalizar_nombre() (config/env.php):
 * "Gutierrez Medina Juan David". No toca tildes ni el orden de las palabras.
 *
 *   php database/normalizar_nombres.php             → PRUEBA: muestra los cambios y los deshace
 *   php database/normalizar_nombres.php --aplicar   → los guarda
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/env.php';

$aplicar = in_array('--aplicar', $argv, true);

$pdo = new PDO(db_dsn(), config('db.user'), (string) config('db.pass'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
]);

$upd = $pdo->prepare("UPDATE estudiantes SET nombre = ? WHERE id = ?");

$pdo->beginTransaction();
try {
    $n = 0;
    foreach ($pdo->query("SELECT id, nombre FROM estudiantes ORDER BY id")->fetchAll() as $e) {
        $nuevo = normalizar_nombre($e['nombre']);
        if ($nuevo !== $e['nombre']) {
            $upd->execute([$nuevo, $e['id']]);
            printf("#%-4d %-40s → %s%s", $e['id'], $e['nombre'], $nuevo, PHP_EOL);
            $n++;
        }
    }
    if ($aplicar) {
        $pdo->commit();
        echo PHP_EOL . "APLICADO: $n nombres." . PHP_EOL;
    } else {
        $pdo->rollBack();
        echo PHP_EOL . "PRUEBA: $n nombres cambiarían — nada se guardó. Repetir con --aplicar." . PHP_EOL;
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR, no se guardó nada: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
