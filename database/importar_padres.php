<?php
/**
 * Importa los padres del "Formulario de Registro de Padres" y los vincula
 * con sus hijos.
 *
 *   php database/importar_padres.php <vinculos.csv>                → PRUEBA: hace todo y lo deshace
 *   php database/importar_padres.php <vinculos.csv> --aplicar      → lo guarda de verdad
 *
 * El CSV (ya revisado) tiene una fila por vínculo:
 *   fila,estudiante_id,estudiante_bd,alumno_formulario,grado,acudiente,parentesco,email[,documento]
 * estudiante_id vacío + documento → se busca por documento (alumnos que
 * entraron con la póliza: aplicar ANTES database/importar_estudiantes.php).
 * estudiante_id y documento vacíos → se crea en el curso de su grado con
 * documento "PENDIENTE-…" para que el coordinador lo complete.
 *
 * - Un correo = una cuenta de padre. Si el correo ya existe como padre se
 *   reutiliza; si existe con otro rol (profesor/admin) se omite y se avisa.
 * - Las cuentas nuevas llevan una clave provisional aleatoria y
 *   debe_cambiar_clave = TRUE. Las claves se escriben en un CSV FUERA del
 *   repositorio (por defecto junto a la carpeta del proyecto) — no subirlo.
 * - Todo va en una transacción: o entra completo o no entra nada.
 *
 * Requiere database/acudientes.sql y database/seguridad_2026_10.sql aplicados.
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
    fwrite(STDERR, "Uso: php database/importar_padres.php <vinculos.csv> [--aplicar]" . PHP_EOL);
    exit(1);
}

$pdo = new PDO(db_dsn(), config('db.user'), (string) config('db.pass'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Igual que la app: con el pooler de Neon, el prepare nativo da
    // "transaction is aborted" en cuanto cambia el esquema.
    PDO::ATTR_EMULATE_PREPARES   => true,
]);

foreach ([
    "SELECT 1 FROM estudiante_acudientes LIMIT 1"     => 'database/acudientes.sql',
    "SELECT debe_cambiar_clave FROM usuarios LIMIT 1" => 'database/seguridad_2026_10.sql',
] as $sql => $migracion) {
    try {
        $pdo->query($sql);
    } catch (PDOException) {
        fwrite(STDERR, "Falta aplicar $migracion (php database/neon_setup.php apply $migracion)" . PHP_EOL);
        exit(1);
    }
}

/** Clave provisional legible: sin 0/O, 1/l/I. */
function clave_provisional(): string
{
    $abc = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 10; $i++) {
        $s .= $abc[random_int(0, strlen($abc) - 1)];
    }
    return 'Gca-' . $s;
}

$fh = fopen($csv, 'r');
$cab = fgetcsv($fh, escape: '');
$filas = [];
while (($r = fgetcsv($fh, escape: '')) !== false) {
    if (count($r) === count($cab)) {
        $filas[] = array_combine($cab, $r);
    }
}
fclose($fh);

$cursoDeGrado = $pdo->query("
    SELECT grado, MIN(id) AS curso_id, COUNT(*) AS n FROM cursos GROUP BY grado
")->fetchAll(PDO::FETCH_UNIQUE);

$usuarios = $pdo->query("SELECT lower(email) AS email, id, rol, nombre FROM usuarios")->fetchAll(PDO::FETCH_UNIQUE);

$stats = ['cuentas_nuevas' => 0, 'cuentas_existentes' => 0, 'estudiantes_nuevos' => 0,
          'vinculos_nuevos' => 0, 'vinculos_ya_estaban' => 0, 'omitidos' => 0];
$credenciales = [];
$avisos = [];
$padreDeEmail = [];

$pdo->beginTransaction();
try {
    $insUsuario = $pdo->prepare("
        INSERT INTO usuarios (nombre, email, password, rol, debe_cambiar_clave)
        VALUES (?, ?, ?, 'padre', TRUE) RETURNING id
    ");
    $insEstudiante = $pdo->prepare("
        INSERT INTO estudiantes (nombre, documento, curso_id) VALUES (?, ?, ?) RETURNING id
    ");
    $insVinculo = $pdo->prepare("
        INSERT INTO estudiante_acudientes (estudiante_id, padre_id, parentesco)
        VALUES (?, ?, ?) ON CONFLICT DO NOTHING
    ");
    $setPrincipal = $pdo->prepare("UPDATE estudiantes SET padre_id = COALESCE(padre_id, ?) WHERE id = ?");
    $existeEst = $pdo->prepare("SELECT id FROM estudiantes WHERE id = ?");
    $porDocumento = $pdo->prepare("
        SELECT id FROM estudiantes
        WHERE upper(regexp_replace(documento, '[^A-Za-z0-9]', '', 'g')) = upper(regexp_replace(?, '[^A-Za-z0-9]', '', 'g'))
    ");
    $buscaNuevo = $pdo->prepare("SELECT id FROM estudiantes WHERE upper(nombre) = upper(?) AND curso_id = ?");

    $estudianteNuevo = [];   // nombre del formulario → id creado (por si hay hermanos/2 acudientes)

    foreach ($filas as $f) {
        $email = strtolower(trim($f['email']));
        $tag = "Fila {$f['fila']}";

        // ── Cuenta del padre ──
        if (!isset($padreDeEmail[$email])) {
            $u = $usuarios[$email] ?? null;
            if ($u && $u['rol'] !== 'padre') {
                $avisos[] = "$tag: $email ya es una cuenta de {$u['rol']} ({$u['nombre']}); no se vincula. Crear otra cuenta de padre con otro correo si hace falta.";
                $padreDeEmail[$email] = null;
            } elseif ($u) {
                $padreDeEmail[$email] = (int) $u['id'];
                $stats['cuentas_existentes']++;
            } else {
                $clave = clave_provisional();
                $insUsuario->execute([$f['acudiente'], $email, password_hash($clave, PASSWORD_DEFAULT)]);
                $padreDeEmail[$email] = (int) $insUsuario->fetchColumn();
                $stats['cuentas_nuevas']++;
                $credenciales[] = [$f['acudiente'], $email, str_ends_with($email, '.invalid') ? '(sin correo real: pedirlo)' : $clave];
            }
        }
        $padreId = $padreDeEmail[$email];
        if (!$padreId) {
            $stats['omitidos']++;
            continue;
        }

        // ── Estudiante ──
        $estId = (int) $f['estudiante_id'];
        if ($estId) {
            $existeEst->execute([$estId]);
            if (!$existeEst->fetchColumn()) {
                throw new RuntimeException("$tag: el estudiante #$estId ya no existe en la BD.");
            }
        } elseif (($f['documento'] ?? '') !== '') {
            // Estudiante que entró después con la póliza (importar_estudiantes.php)
            $porDocumento->execute([$f['documento']]);
            $estId = (int) $porDocumento->fetchColumn();
            if (!$estId) {
                throw new RuntimeException("$tag: no existe el estudiante con documento {$f['documento']}. "
                    . "Aplica primero database/importar_estudiantes.php.");
            }
        } else {
            $curso = $cursoDeGrado[$f['grado']] ?? null;
            if (!$curso) {
                throw new RuntimeException("$tag: no hay curso para el grado '{$f['grado']}'.");
            }
            $nombre = mb_strtoupper($f['alumno_formulario']);
            $estId = $estudianteNuevo[$nombre] ?? 0;
            if (!$estId) {
                $buscaNuevo->execute([$nombre, $curso['curso_id']]);   // re-ejecución: no duplicar
                $estId = (int) $buscaNuevo->fetchColumn();
            }
            if (!$estId) {
                $insEstudiante->execute([$nombre, 'PENDIENTE-' . strtoupper(bin2hex(random_bytes(3))), $curso['curso_id']]);
                $estId = (int) $insEstudiante->fetchColumn();
                $stats['estudiantes_nuevos']++;
                $avisos[] = "$tag: estudiante NUEVO #$estId «{$nombre}» en {$f['grado']} — completar documento y revisar curso.";
            }
            $estudianteNuevo[$nombre] = $estId;
        }

        // ── Vínculo ──
        $insVinculo->execute([$estId, $padreId, mb_substr($f['parentesco'], 0, 30) ?: null]);
        $stats[$insVinculo->rowCount() ? 'vinculos_nuevos' : 'vinculos_ya_estaban']++;
        $setPrincipal->execute([$padreId, $estId]);
    }

    if ($aplicar) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR — no se guardó nada: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo ($aplicar ? "APLICADO" : "PRUEBA (nada se guardó; usa --aplicar para guardar)") . PHP_EOL;
foreach ($stats as $k => $v) {
    printf("  %-22s %d%s", $k, $v, PHP_EOL);
}
if ($avisos) {
    echo PHP_EOL . "Avisos:" . PHP_EOL . '  - ' . implode(PHP_EOL . '  - ', $avisos) . PHP_EOL;
}

if ($aplicar && $credenciales) {
    $out = dirname(__DIR__, 2) . '/padres_credenciales_' . date('Y-m-d_His') . '.csv';
    $o = fopen($out, 'w');
    fwrite($o, "\xEF\xBB\xBF");   // BOM: Excel abre bien las tildes
    fputcsv($o, ['Acudiente', 'Correo (usuario)', 'Clave provisional'], escape: '');
    foreach ($credenciales as $c) {
        fputcsv($o, $c, escape: '');
    }
    fclose($o);
    echo PHP_EOL . "Claves provisionales en: $out" . PHP_EOL
       . "(fuera del repositorio; entrégalas por un canal privado y bórralo después)" . PHP_EOL;
}
