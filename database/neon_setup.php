<?php
/**
 * Utilidad CLI para la base de datos (Neon).
 *
 *   php database/neon_setup.php test              → prueba la conexión y muestra versión
 *   php database/neon_setup.php status            → lista tablas y nº de filas
 *   php database/neon_setup.php check             → auditoría: tablas, secuencias, tipos
 *   php database/neon_setup.php check --fix       → además reajusta las secuencias SERIAL
 *   php database/neon_setup.php apply <archivo>   → ejecuta un .sql (p.ej. el esquema)
 *
 * Requiere el PHP con pdo_pgsql (winget 8.3), no el de Scoop.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/env.php';

$args = array_slice($argv, 1);
$fix  = in_array('--fix', $args, true);
$args = array_values(array_filter($args, fn($a) => $a !== '--fix'));

$cmd  = $args[0] ?? 'test';
$file = $args[1] ?? null;

$host = config('db.host');
$name = config('db.name');
$user = config('db.user');
$pass = (string) config('db.pass');

echo "Host    : $host:" . config('db.port') . PHP_EOL;
echo "BD      : $name" . PHP_EOL;
echo "Usuario : $user" . PHP_EOL;
echo "SSL     : " . config('db.sslmode') . PHP_EOL;
echo "Clave   : " . ($pass === '' ? '(vacía)' : str_repeat('*', min(strlen($pass), 12))) . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

if ($pass === '') {
    fwrite(STDERR, "ERROR: no hay contraseña. Completa DATABASE_URL en .env" . PHP_EOL);
    exit(1);
}

try {
    $pdo = new PDO(db_dsn(), $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "FALLÓ la conexión: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Conexión OK — " . $pdo->query('SELECT version()')->fetchColumn() . PHP_EOL;

if ($cmd === 'test') {
    exit(0);
}

if ($cmd === 'status') {
    $tablas = $pdo->query(
        "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!$tablas) {
        echo "La base está VACÍA (sin tablas en el esquema public)." . PHP_EOL;
        exit(0);
    }

    echo count($tablas) . " tablas:" . PHP_EOL;
    foreach ($tablas as $t) {
        $n = $pdo->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn();
        printf("  %-32s %8d filas%s", $t, $n, PHP_EOL);
    }
    exit(0);
}

if ($cmd === 'apply') {
    if (!$file || !is_file($file)) {
        fwrite(STDERR, "Uso: php database/neon_setup.php apply <archivo.sql>" . PHP_EOL);
        exit(1);
    }

    $sql = file_get_contents($file);
    echo "Ejecutando " . basename($file) . " (" . strlen($sql) . " bytes)…" . PHP_EOL;

    try {
        $pdo->exec($sql);
        echo "Listo." . PHP_EOL;
    } catch (PDOException $e) {
        fwrite(STDERR, "ERROR SQL: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    exit(0);
}

if ($cmd === 'check') {
    $problemas = [];

    // ---- 1. Tablas esperadas -------------------------------------------------
    $esperadas = [
        'usuarios', 'cursos', 'areas', 'asignaturas', 'asignatura_grado',
        'estudiantes', 'notas', 'profesor_curso_asignatura', 'logros', 'alertas',
        'boletines_pdf', 'directores_grupo', 'configuraciones', 'noticias',
        'eventos', 'docentes',
    ];

    $existentes = $pdo->query(
        "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
    )->fetchAll(PDO::FETCH_COLUMN);

    echo "TABLAS" . PHP_EOL;
    foreach ($esperadas as $t) {
        if (!in_array($t, $existentes, true)) {
            echo "  [FALTA] $t" . PHP_EOL;
            $problemas[] = "falta la tabla $t";
            continue;
        }
        $n = $pdo->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn();
        printf("  %-30s %8d filas%s", $t, $n, PHP_EOL);
    }

    $extra = array_diff($existentes, $esperadas);
    if ($extra) {
        echo "  (tablas adicionales: " . implode(', ', $extra) . ")" . PHP_EOL;
    }

    // ---- 2. Secuencias SERIAL desincronizadas -------------------------------
    // Si la importación insertó ids explícitos sin reajustar el setval, el
    // próximo INSERT choca con una clave duplicada.
    echo PHP_EOL . "SECUENCIAS (próximo id vs MAX(id))" . PHP_EOL;
    $desync = [];

    // Tablas que realmente tienen columna id (asignatura_grado, p.ej., usa PK compuesta)
    $conId = $pdo->prepare(
        "SELECT 1 FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = ? AND column_name = 'id'"
    );

    foreach ($esperadas as $t) {
        if (!in_array($t, $existentes, true)) continue;

        $conId->execute([$t]);
        if (!$conId->fetchColumn()) continue;

        $seq = $pdo->query(
            "SELECT pg_get_serial_sequence('" . $t . "', 'id')"
        )->fetchColumn();
        if (!$seq) continue; // id sin secuencia asociada

        $maxId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM "' . $t . '"')->fetchColumn();
        $row   = $pdo->query("SELECT last_value, is_called FROM $seq")->fetch();

        // Si is_called = false, nextval devuelve last_value tal cual; si no, last_value + 1
        $proximo = $row['is_called'] ? (int) $row['last_value'] + 1 : (int) $row['last_value'];

        if ($proximo <= $maxId) {
            printf("  [DESYNC] %-24s próximo id=%d  max(id)=%d%s", $t, $proximo, $maxId, PHP_EOL);
            $desync[] = [$t, $seq, $maxId];
        } else {
            printf("  OK       %-24s próximo id=%d%s", $t, $proximo, PHP_EOL);
        }
    }
    if (!$desync) {
        echo "  Todas en orden." . PHP_EOL;
    } elseif ($fix) {
        echo PHP_EOL . "  Corrigiendo (--fix):" . PHP_EOL;
        foreach ($desync as [$t, $seq, $maxId]) {
            $pdo->query("SELECT setval('$seq', $maxId)");
            printf("    %-28s → próximo id = %d%s", $t, $maxId + 1, PHP_EOL);
        }
    } else {
        echo PHP_EOL . "  Corrígelas con:  php database/neon_setup.php check --fix" . PHP_EOL;
        echo "  (equivale a:)" . PHP_EOL;
        foreach ($desync as [$t, $seq, $maxId]) {
            echo "    SELECT setval('$seq', $maxId);" . PHP_EOL;
        }
        $problemas[] = count($desync) . " secuencia(s) desincronizada(s)";
    }

    // ---- 3. Tipos que el código asume BOOLEAN ------------------------------
    echo PHP_EOL . "TIPOS BOOLEAN (el código compara con TRUE/FALSE)" . PHP_EOL;
    $bools = [
        ['alertas', 'leido'],
        ['noticias', 'activo'],
        ['eventos', 'activo'],
        ['docentes', 'activo'],
        ['boletines_pdf', 'publicado'],
        ['boletines_pdf', 'paz_y_salvo'],
    ];
    $stmt = $pdo->prepare(
        "SELECT data_type FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = ? AND column_name = ?"
    );
    foreach ($bools as [$t, $c]) {
        $stmt->execute([$t, $c]);
        $tipo = $stmt->fetchColumn();

        if ($tipo === false) {
            printf("  [FALTA]  %-28s%s", "$t.$c", PHP_EOL);
            $problemas[] = "falta la columna $t.$c";
        } elseif ($tipo !== 'boolean') {
            printf("  [MAL]    %-28s es %s, se espera boolean%s", "$t.$c", $tipo, PHP_EOL);
            $problemas[] = "$t.$c es $tipo en vez de boolean";
        } else {
            printf("  OK       %-28s boolean%s", "$t.$c", PHP_EOL);
        }
    }

    // ---- 4. Columna anio (filtrado por año escolar) -------------------------
    echo PHP_EOL . "AÑO ESCOLAR" . PHP_EOL;
    foreach ([['notas', 'anio'], ['logros', 'anio']] as [$t, $c]) {
        $stmt->execute([$t, $c]);
        $tipo = $stmt->fetchColumn();
        if ($tipo === false) {
            printf("  [FALTA]  %s.%s%s", $t, $c, PHP_EOL);
            $problemas[] = "falta $t.$c";
        } else {
            printf("  OK       %s.%s (%s)%s", $t, $c, $tipo, PHP_EOL);
        }
    }
    if (in_array('configuraciones', $existentes, true)) {
        $anio = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'anio_activo'")->fetchColumn();
        echo "  anio_activo = " . ($anio === false ? '(no configurado)' : $anio) . PHP_EOL;
    }

    // ---- Resumen ------------------------------------------------------------
    echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
    if (!$problemas) {
        echo "TODO EN ORDEN. La base de Neon está lista." . PHP_EOL;
        exit(0);
    }
    echo count($problemas) . " problema(s):" . PHP_EOL;
    foreach ($problemas as $p) {
        echo "  - $p" . PHP_EOL;
    }
    exit(1);
}

fwrite(STDERR, "Comando desconocido: $cmd (usa test | status | check | apply)" . PHP_EOL);
exit(1);
