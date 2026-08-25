<?php

/**
 * Mueve a assets/uploads/ los archivos que la aplicación escribe en tiempo
 * de ejecución, y actualiza las rutas guardadas en la base de datos.
 *
 *   php database/migrar_subidas.php          → muestra qué haría (sin tocar nada)
 *   php database/migrar_subidas.php --aplicar → lo hace
 *
 * Motivo: en el hosting el disco del contenedor se borra en cada despliegue.
 * Juntando todo bajo assets/uploads se puede montar encima UN volumen
 * persistente (Railway solo permite uno por servicio).
 *
 * Es idempotente: lo ya migrado se salta.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/env.php';

$aplicar = in_array('--aplicar', array_slice($argv, 1), true);
$raiz    = dirname(__DIR__);

echo $aplicar ? "APLICANDO CAMBIOS" : "SIMULACIÓN (usa --aplicar para hacerlo de verdad)";
echo PHP_EOL . str_repeat('-', 64) . PHP_EOL;

// (columna, tabla, prefijo viejo, subcarpeta nueva)
$migraciones = [
    ['boletines_pdf', 'ruta_pdf', 'assets/boletines/',    'boletines'],
    ['docentes',      'foto',     'assets/img/docentes/', 'docentes'],
    ['noticias',      'imagen',   'assets/img/noticias/', 'noticias'],
];

try {
    $pdo = new PDO(db_dsn(), config('db.user'), config('db.pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'No se pudo conectar: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$movidos = 0;
$faltantes = 0;
$actualizados = 0;

foreach ($migraciones as [$tabla, $columna, $prefijoViejo, $subcarpeta]) {
    echo PHP_EOL . strtoupper("$tabla.$columna") . PHP_EOL;

    $filas = $pdo->query(
        "SELECT id, $columna AS ruta FROM $tabla
         WHERE $columna IS NOT NULL AND $columna <> '' ORDER BY id"
    )->fetchAll();

    if (!$filas) {
        echo "  (sin filas)" . PHP_EOL;
        continue;
    }

    foreach ($filas as $fila) {
        $rutaVieja = ltrim((string) $fila['ruta'], '/');

        if (str_starts_with($rutaVieja, 'assets/uploads/')) {
            echo "  #{$fila['id']} ya migrado" . PHP_EOL;
            continue;
        }
        if (!str_starts_with($rutaVieja, $prefijoViejo)) {
            echo "  #{$fila['id']} ruta inesperada, se deja igual: $rutaVieja" . PHP_EOL;
            continue;
        }

        // assets/boletines/2026/1/x.pdf → boletines/2026/1/x.pdf
        $resto     = substr($rutaVieja, strlen($prefijoViejo));
        $rutaNueva = url_subidas($subcarpeta) . '/' . $resto;

        $origen  = $raiz . '/' . $rutaVieja;
        $destino = $raiz . '/' . $rutaNueva;

        $existe = is_file($origen);
        if (!$existe && !is_file($destino)) {
            $faltantes++;
            echo "  #{$fila['id']} FALTA el archivo ($rutaVieja) — se actualiza la ruta igual" . PHP_EOL;
        }

        if ($aplicar) {
            if ($existe) {
                $dirDestino = dirname($destino);
                if (!is_dir($dirDestino) && !mkdir($dirDestino, 0755, true) && !is_dir($dirDestino)) {
                    fwrite(STDERR, "  no se pudo crear $dirDestino" . PHP_EOL);
                    continue;
                }
                if (rename($origen, $destino)) {
                    $movidos++;
                } else {
                    fwrite(STDERR, "  no se pudo mover $rutaVieja" . PHP_EOL);
                    continue;
                }
            }

            $up = $pdo->prepare("UPDATE $tabla SET $columna = ? WHERE id = ?");
            $up->execute([$rutaNueva, $fila['id']]);
            $actualizados++;
        }

        echo "  #{$fila['id']} $rutaVieja" . PHP_EOL;
        echo "        → $rutaNueva" . ($existe ? '' : '  (solo la ruta, el archivo no está)') . PHP_EOL;
    }
}

echo PHP_EOL . str_repeat('=', 64) . PHP_EOL;
if ($aplicar) {
    echo "Archivos movidos: $movidos · rutas actualizadas: $actualizados";
    if ($faltantes) {
        echo " · archivos que no estaban: $faltantes";
    }
    echo PHP_EOL;
    echo "Los boletines que falten se regeneran desde el panel del director." . PHP_EOL;
} else {
    echo "Nada modificado. Vuelve a ejecutarlo con --aplicar." . PHP_EOL;
}
