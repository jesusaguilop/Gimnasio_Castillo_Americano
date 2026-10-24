<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");

require_once "../lib/csrf_helper.php";
require_once "../lib/mail_helper.php";
require_once "../lib/alertas_helper.php";

/**
 * Al publicar un boletín del 3er período se avisa al padre si el estudiante
 * va en riesgo. Antes esa alerta salía al guardar notas, antes de publicar.
 */
function alertarRiesgoPublicados(PDO $conexion, array $publicados): void
{
    foreach ($publicados as $p) {
        if ((string) $p["periodo"] === "3" && $p["curso_id"]) {
            try {
                verificarRiesgoAcademico($conexion, (int) $p["estudiante_id"], (int) $p["curso_id"], "3", (int) $p["year"]);
            } catch (\Throwable $e) {
                error_log("[enviar_boletin] alerta de riesgo: " . $e->getMessage());
            }
        }
    }
}

$mensaje = "";
$tipo = "";

/* ── Filtros (curso / período / año) ──
 * Por defecto: período y año activos. "todos" quita el filtro de período;
 * curso_id = 0 es "Todos los cursos". Se usan en la lista, en el poll en vivo
 * y en "Publicar todos", para que publique exactamente lo que se ve. */
$cfg = $conexion->query("SELECT clave, valor FROM configuraciones WHERE clave IN ('periodo_activo','anio_activo')")
    ->fetchAll(PDO::FETCH_KEY_PAIR);
$periodo_activo = (string) ($cfg['periodo_activo'] ?? '1');
$anio_activo = (int) ($cfg['anio_activo'] ?? date('Y'));

function boletinFiltros(array $src, string $periodo_activo, int $anio_activo): array
{
    $periodo = (string) ($src['periodo'] ?? $periodo_activo);
    if ($periodo !== 'todos' && !in_array($periodo, ['1', '2', '3', '4'], true)) {
        $periodo = $periodo_activo;
    }
    $year = (int) ($src['year'] ?? $anio_activo);
    if ($year < 2000 || $year > 2100) {
        $year = $anio_activo;
    }
    return [
        'curso_id' => max(0, (int) ($src['curso_id'] ?? 0)),
        'periodo'  => $periodo,
        'year'     => $year,
    ];
}

function boletinFiltrosQs(array $f): string
{
    return http_build_query(['curso_id' => $f['curso_id'], 'periodo' => $f['periodo'], 'year' => $f['year']]);
}

/** WHERE + parámetros para el filtro actual (sobre boletines_pdf bp / estudiantes e) */
function boletinFiltrosWhere(array $f): array
{
    $w = ['bp.year = ?'];
    $p = [$f['year']];
    if ($f['periodo'] !== 'todos') {
        $w[] = 'bp.periodo = ?';
        $p[] = $f['periodo'];
    }
    if ($f['curso_id'] > 0) {
        $w[] = 'e.curso_id = ?';
        $p[] = $f['curso_id'];
    }
    return [implode(' AND ', $w), $p];
}

/** Campos ocultos para que cada acción vuelva al mismo filtro */
function boletinFiltrosCampos(array $f): string
{
    return '<input type="hidden" name="curso_id" value="' . (int) $f['curso_id'] . '">'
        . '<input type="hidden" name="periodo" value="' . htmlspecialchars($f['periodo']) . '">'
        . '<input type="hidden" name="year" value="' . (int) $f['year'] . '">';
}

// Mensaje de la acción anterior (patrón POST → redirect → GET: al recargar
// con F5 no se repite la acción ni se reenvía el correo al padre)
if (isset($_SESSION["boletin_flash"])) {
    [$mensaje, $tipo] = $_SESSION["boletin_flash"];
    unset($_SESSION["boletin_flash"]);
}

/* ── Acciones (POST, con CSRF) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intenta de nuevo.";
        $tipo = "danger";
    } else {
        $accion = $_POST["accion"] ?? "";
        $bid = (int)($_POST["boletin_id"] ?? 0);
        $fPost = boletinFiltros($_POST, $periodo_activo, $anio_activo);

        if ($accion === "publicar" && $bid) {
            $up = $conexion->prepare("
                UPDATE boletines_pdf bp SET publicado = TRUE, publicado_en = now()
                FROM estudiantes e
                WHERE e.id = bp.estudiante_id AND bp.id = ?
                RETURNING bp.estudiante_id, bp.periodo, bp.year, e.curso_id
            ");
            $up->execute([$bid]);
            alertarRiesgoPublicados($conexion, $up->fetchAll(PDO::FETCH_ASSOC));
            $mensaje = "Boletín publicado: ya es visible para el padre.";
            $tipo = "success";
        } elseif ($accion === "ocultar" && $bid) {
            $conexion->prepare("UPDATE boletines_pdf SET publicado = FALSE, publicado_en = NULL WHERE id = ?")->execute([$bid]);
            $mensaje = "Boletín ocultado: ya no es visible para el padre.";
            $tipo = "success";
        } elseif ($accion === "publicar_todos") {
            // Solo los pendientes del filtro que el admin está viendo
            [$where, $params] = boletinFiltrosWhere($fPost);
            $up = $conexion->prepare("
                UPDATE boletines_pdf bp SET publicado = TRUE, publicado_en = now()
                FROM estudiantes e
                WHERE e.id = bp.estudiante_id AND bp.publicado = FALSE AND $where
                RETURNING bp.estudiante_id, bp.periodo, bp.year, e.curso_id
            ");
            $up->execute($params);
            $publicados = $up->fetchAll(PDO::FETCH_ASSOC);
            $n = count($publicados);
            alertarRiesgoPublicados($conexion, $publicados);
            $mensaje = $n ? "$n boletín(es) publicado(s): ya son visibles para los padres." : "No había boletines pendientes en este filtro.";
            $tipo = $n ? "success" : "warning";
        } elseif ($accion === "enviar" && $bid) {
            // A todos los acudientes del estudiante con correo válido
            $stmt = $conexion->prepare("SELECT bp.ruta_pdf, e.nombre AS estudiante_nombre, u.nombre AS padre_nombre, u.email
                FROM boletines_pdf bp JOIN estudiantes e ON bp.estudiante_id = e.id
                JOIN estudiante_acudientes ea ON ea.estudiante_id = e.id
                JOIN usuarios u ON u.id = ea.padre_id
                WHERE bp.id = ? AND u.email NOT LIKE '%.invalid'");
            $stmt->execute([$bid]);
            $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ruta = $destinos ? realpath(__DIR__ . "/../" . $destinos[0]["ruta_pdf"]) : false;
            if (!$destinos) {
                $mensaje = "El estudiante no tiene acudientes con correo registrado.";
                $tipo = "danger";
            } elseif (!$ruta || !file_exists($ruta)) {
                $mensaje = "No se encontró el PDF del boletín: hay que volver a generarlo.";
                $tipo = "danger";
            } else {
                $ok = [];
                foreach ($destinos as $b) {
                    if (enviar_boletin_por_email($b["email"], $b["padre_nombre"], $ruta, "Boletín de {$b['estudiante_nombre']}")) {
                        $ok[] = "{$b['padre_nombre']} ({$b['email']})";
                    }
                }
                $mensaje = $ok ? "Boletín enviado por correo a " . implode(", ", $ok) . "."
                               : "No se pudo enviar el correo (revisa la configuración de correo).";
                $tipo = $ok ? "success" : "danger";
            }
        }
    }

    $_SESSION["boletin_flash"] = [$mensaje, $tipo];
    header("Location: enviar_boletin.php?" . boletinFiltrosQs(boletinFiltros($_POST, $periodo_activo, $anio_activo)));
    exit;
}

/* ── Datos + render de filas (compartido por HTML y por el poll en vivo) ── */
function boletinesFilas(PDO $conexion, array $f): array
{
    [$where, $params] = boletinFiltrosWhere($f);
    $stmt = $conexion->prepare("
        SELECT bp.id, bp.periodo, bp.year, bp.ruta_pdf, bp.paz_y_salvo, bp.publicado,
               to_char(bp.publicado_en, 'DD/MM/YYYY HH24:MI') AS publicado_en,
               e.nombre AS estudiante, c.grado, c.nombre AS curso_nombre,
               ac.padre_nombre, ac.padre_email
        FROM boletines_pdf bp
        JOIN estudiantes e ON bp.estudiante_id = e.id
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN LATERAL (
            SELECT string_agg(u.nombre, ', ' ORDER BY u.nombre) AS padre_nombre,
                   string_agg(u.email,  ', ' ORDER BY u.nombre) AS padre_email
            FROM estudiante_acudientes ea JOIN usuarios u ON u.id = ea.padre_id
            WHERE ea.estudiante_id = e.id
        ) ac ON TRUE
        WHERE $where
        ORDER BY bp.publicado ASC, bp.year DESC, bp.periodo, e.nombre
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function boletinesTbody(array $filas, array $f): string
{
    if (!$filas) {
        return '<tr><td colspan="8" class="text-center text-muted py-4">No hay boletines con este filtro. Los directores de grupo los generan desde su panel; prueba con otro período o curso.</td></tr>';
    }
    $campos = boletinFiltrosCampos($f);
    $h = '';
    foreach ($filas as $b) {
        $curso = trim(ucfirst((string)($b['grado'] ?? '')) . ' ' . (string)($b['curso_nombre'] ?? '')) ?: '—';
        $paz = $b['paz_y_salvo']
            ? '<span class="gca-badge is-ok">Sí</span>'
            : '<span class="gca-badge is-bad">No</span>';
        $pub = $b['publicado'];
        $estado = $pub
            ? '<span class="gca-badge is-ok"><i class="bi bi-eye" aria-hidden="true"></i> Publicado</span>'
            : '<span class="gca-badge is-warn"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Pendiente</span>';
        $padre = htmlspecialchars($b['padre_nombre'] ?? '—');
        if (!empty($b['padre_email'])) $padre .= '<small class="cell-sub">' . htmlspecialchars($b['padre_email']) . '</small>';

        $acc = '';
        // Ver PDF
        if (!empty($b['ruta_pdf'])) {
            $acc .= '<a href="../boletin_descargar.php?id=' . (int) $b['id'] . '" target="_blank" class="btn-action btn-edit" title="Ver PDF" aria-label="Ver PDF del boletín de ' . htmlspecialchars($b['estudiante']) . '"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></a> ';
        }
        // Publicar / Ocultar
        $tog = $pub ? 'ocultar' : 'publicar';
        $togBtn = $pub
            ? '<button type="submit" class="btn-soft-danger"><i class="bi bi-eye-slash" aria-hidden="true"></i> Ocultar</button>'
            : '<button type="submit" class="btn-gca btn-gca-sm"><i class="bi bi-send-check" aria-hidden="true"></i> Publicar</button>';
        $acc .= '<form method="POST" class="d-inline-flex">' . campo_csrf() . $campos
            . '<input type="hidden" name="accion" value="' . $tog . '"><input type="hidden" name="boletin_id" value="' . (int)$b['id'] . '">' . $togBtn . '</form>';
        // Enviar por correo (opcional)
        if (!empty($b['padre_email'])) {
            $acc .= '<form method="POST" class="d-inline-flex">' . campo_csrf() . $campos
                . '<input type="hidden" name="accion" value="enviar"><input type="hidden" name="boletin_id" value="' . (int)$b['id'] . '">'
                . '<button type="submit" class="btn-action" title="Enviar por correo" aria-label="Enviar por correo al padre de ' . htmlspecialchars($b['estudiante']) . '" onclick="return confirm(\'¿Enviar el boletín por correo al padre?\')"><i class="bi bi-envelope" aria-hidden="true"></i></button></form>';
        }

        $h .= '<tr>'
            . '<td class="cell-strong">' . htmlspecialchars($b['estudiante']) . '</td>'
            . '<td>' . htmlspecialchars($curso) . '</td>'
            . '<td class="text-center">' . htmlspecialchars($b['periodo']) . '</td>'
            . '<td class="text-center">' . htmlspecialchars($b['year']) . '</td>'
            . '<td class="text-center">' . $paz . '</td>'
            . '<td>' . $padre . '</td>'
            . '<td class="text-center">' . $estado . '</td>'
            . '<td class="text-end"><div class="row-actions">' . $acc . '</div></td>'
            . '</tr>';
    }
    return $h;
}

$filtro = boletinFiltros($_GET, $periodo_activo, $anio_activo);
$filas = boletinesFilas($conexion, $filtro);
$pendientes = count(array_filter($filas, fn($b) => !$b['publicado']));
$publicados = count($filas) - $pendientes;

/* ── Poll en vivo (con el mismo filtro) ── */
if (isset($_GET["json"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "tbody"      => boletinesTbody($filas, $filtro),
        "pendientes" => $pendientes,
        "publicados" => $publicados,
        "total"      => count($filas),
    ]);
    exit;
}

$cursos = $conexion->query("SELECT id, grado, nombre FROM cursos ORDER BY nivel, grado, nombre")->fetchAll(PDO::FETCH_ASSOC);
$anios = $conexion->query("SELECT DISTINCT year FROM boletines_pdf ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($anio_activo, array_map('intval', $anios), true)) {
    array_unshift($anios, $anio_activo);
}

// Texto exacto de lo que publicará "Publicar todos"
$cursoNombre = 'todos los cursos';
foreach ($cursos as $c) {
    if ((int) $c['id'] === $filtro['curso_id']) {
        $cursoNombre = 'curso ' . trim(ucfirst((string) $c['grado']) . ' ' . $c['nombre']);
    }
}
$periodoTexto = $filtro['periodo'] === 'todos' ? 'de todos los períodos' : 'del período ' . $filtro['periodo'];
$textoPublicar = "¿Publicar los $pendientes boletín(es) pendientes $periodoTexto de {$filtro['year']} ($cursoNombre)? Se harán visibles para los padres.";

$pageTitle = "Gestión de Boletines";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>

            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Boletines generados</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span id="pendBadge" class="gca-badge is-warn is-lg" role="status">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i> <span id="pendNum"><?= $pendientes ?></span> pendiente(s)
                        </span>
                        <span class="gca-badge is-ok is-lg">
                            <i class="bi bi-eye" aria-hidden="true"></i> <span id="pubNum"><?= $publicados ?></span> publicado(s)
                        </span>
                        <span class="live-dot" title="La tabla se actualiza sola cada pocos segundos">
                            <span class="live-pulse" aria-hidden="true"></span> En vivo
                        </span>
                        <form method="POST" id="formPublicarTodos" data-texto="<?= htmlspecialchars($textoPublicar) ?>"
                              onsubmit="event.preventDefault(); var f=this; showConfirm(f.dataset.texto, function(){ f.submit(); }, 'Sí, publicar');">
                            <?= campo_csrf() ?>
                            <?= boletinFiltrosCampos($filtro) ?>
                            <input type="hidden" name="accion" value="publicar_todos">
                            <button type="submit" class="btn-gca btn-gca-sm" id="btnPublicarTodos" <?= $pendientes ? '' : 'disabled' ?>>
                                <i class="bi bi-send-check" aria-hidden="true"></i> <span>Publicar pendientes (<span id="pendNum2"><?= $pendientes ?></span>)</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="alert alert-gca alert-icon" role="note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i><div>
                    El director de grupo genera los boletines y quedan <strong>Pendientes</strong>. Aquí tú decides el
                    <strong>paso al padre</strong>: al <strong>Publicar</strong>, el boletín aparece en el portal del padre.
                    El <strong>paz y salvo</strong> lo marca el director como referencia.
                </div></div>

                <form method="GET" class="filter-bar" aria-label="Filtrar boletines">
                    <div class="ff ff-grow">
                        <label for="f_curso" class="form-label">Curso</label>
                        <select id="f_curso" name="curso_id" class="form-select">
                            <option value="0">Todos los cursos</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $filtro['curso_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(trim(ucfirst((string) $c['grado']) . ' ' . $c['nombre'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ff">
                        <label for="f_periodo" class="form-label">Período</label>
                        <select id="f_periodo" name="periodo" class="form-select">
                            <?php foreach (['1', '2', '3', '4'] as $pp): ?>
                                <option value="<?= $pp ?>" <?= $filtro['periodo'] === $pp ? 'selected' : '' ?>>
                                    Período <?= $pp ?><?= $pp === $periodo_activo ? ' (activo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="todos" <?= $filtro['periodo'] === 'todos' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    <div class="ff">
                        <label for="f_year" class="form-label">Año</label>
                        <select id="f_year" name="year" class="form-select">
                            <?php foreach ($anios as $aa): ?>
                                <option value="<?= (int) $aa ?>" <?= (int) $aa === $filtro['year'] ? 'selected' : '' ?>><?= (int) $aa ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
                        <a href="enviar_boletin.php" class="btn-outline-gca">Período activo</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table gca-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Estudiante</th>
                                <th scope="col">Curso</th>
                                <th scope="col" class="text-center">Período</th>
                                <th scope="col" class="text-center">Año</th>
                                <th scope="col" class="text-center">Paz y salvo</th>
                                <th scope="col">Padre</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="boletinesBody"><?= boletinesTbody($filas, $filtro) ?></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
    (function () {
        // Actualización en vivo: refleja boletines recién generados por los directores
        // y cambios de estado, sin recargar la página.
        var body = document.getElementById('boletinesBody');
        var pendNum = document.getElementById('pendNum');
        var pendNum2 = document.getElementById('pendNum2');
        var pubNum = document.getElementById('pubNum');
        var btnTodos = document.getElementById('btnPublicarTodos');
        var formTodos = document.getElementById('formPublicarTodos');
        var pollUrl = 'enviar_boletin.php?json=1&<?= boletinFiltrosQs($filtro) ?>';
        var plantilla = <?= json_encode($textoPublicar, JSON_UNESCAPED_UNICODE) ?>;
        var pendInicial = <?= (int) $pendientes ?>;
        setInterval(function () {
            fetch(pollUrl, { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    // No reemplazar la tabla si el foco está dentro (botón, enlace "Ver PDF"…):
                    // se perdería la posición del teclado o del lector de pantalla.
                    if (typeof d.tbody === 'string' && !body.contains(document.activeElement)) {
                        body.innerHTML = d.tbody;
                    }
                    pendNum.textContent = d.pendientes;
                    pendNum2.textContent = d.pendientes;
                    pubNum.textContent = d.publicados;
                    btnTodos.disabled = d.pendientes === 0;
                    // El aviso de confirmación dice el número actual
                    formTodos.dataset.texto = plantilla.replace('los ' + pendInicial + ' bolet', 'los ' + d.pendientes + ' bolet');
                })
                .catch(function () {});
        }, 8000);
    })();
    </script>

    <?php include "includes/footer.php"; ?>
