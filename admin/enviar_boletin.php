<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/csrf_helper.php";
require_once "../lib/mail_helper.php";

$mensaje = "";
$tipo = "";

/* ── Acciones (POST, con CSRF) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad. Intenta de nuevo.";
        $tipo = "danger";
    } else {
        $accion = $_POST["accion"] ?? "";
        $bid = (int)($_POST["boletin_id"] ?? 0);

        if ($accion === "publicar" && $bid) {
            $conexion->prepare("UPDATE boletines_pdf SET publicado = TRUE, publicado_en = now() WHERE id = ?")->execute([$bid]);
            $mensaje = "Boletín publicado: ya es visible para el padre.";
            $tipo = "success";
        } elseif ($accion === "ocultar" && $bid) {
            $conexion->prepare("UPDATE boletines_pdf SET publicado = FALSE, publicado_en = NULL WHERE id = ?")->execute([$bid]);
            $mensaje = "Boletín ocultado: ya no es visible para el padre.";
            $tipo = "success";
        } elseif ($accion === "publicar_todos") {
            $n = $conexion->query("UPDATE boletines_pdf SET publicado = TRUE, publicado_en = now() WHERE publicado = FALSE")->rowCount();
            $mensaje = "$n boletín(es) publicado(s).";
            $tipo = "success";
        } elseif ($accion === "enviar" && $bid) {
            $stmt = $conexion->prepare("SELECT bp.ruta_pdf, e.nombre AS estudiante_nombre, u.nombre AS padre_nombre, u.email
                FROM boletines_pdf bp JOIN estudiantes e ON bp.estudiante_id = e.id
                JOIN usuarios u ON e.padre_id = u.id WHERE bp.id = ?");
            $stmt->execute([$bid]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b && $b["email"]) {
                $ruta = realpath(__DIR__ . "/../" . $b["ruta_pdf"]);
                if ($ruta && file_exists($ruta) && enviar_boletin_por_email($b["email"], $b["padre_nombre"], $ruta, "Boletín de {$b['estudiante_nombre']}")) {
                    $mensaje = "Boletín enviado por correo a {$b['padre_nombre']} ({$b['email']}).";
                    $tipo = "success";
                } else {
                    $mensaje = "No se pudo enviar el correo (revisa la configuración de correo).";
                    $tipo = "danger";
                }
            } else {
                $mensaje = "El padre no tiene correo registrado.";
                $tipo = "danger";
            }
        }
    }
}

/* ── Datos + render de filas (compartido por HTML y por el poll en vivo) ── */
function boletinesFilas(PDO $conexion): array
{
    return $conexion->query("
        SELECT bp.id, bp.periodo, bp.year, bp.ruta_pdf, bp.paz_y_salvo, bp.publicado,
               to_char(bp.publicado_en, 'DD/MM/YYYY HH24:MI') AS publicado_en,
               e.nombre AS estudiante, c.grado, c.nombre AS curso_nombre,
               u.nombre AS padre_nombre, u.email AS padre_email
        FROM boletines_pdf bp
        JOIN estudiantes e ON bp.estudiante_id = e.id
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN usuarios u ON e.padre_id = u.id
        ORDER BY bp.publicado ASC, bp.year DESC, bp.periodo, e.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function boletinesTbody(array $filas): string
{
    if (!$filas) {
        return '<tr><td colspan="8" class="text-center text-muted py-4">No hay boletines generados todavía. Los directores de grupo los generan desde su panel.</td></tr>';
    }
    $h = '';
    foreach ($filas as $b) {
        $curso = trim(ucfirst((string)($b['grado'] ?? '')) . ' ' . (string)($b['curso_nombre'] ?? '')) ?: '—';
        $paz = $b['paz_y_salvo']
            ? '<span class="badge rounded-pill" style="background:#e8f5e9;color:#2e7d32;">Sí</span>'
            : '<span class="badge rounded-pill" style="background:#fdeaea;color:#c62828;">No</span>';
        $pub = $b['publicado'];
        $estado = $pub
            ? '<span class="badge rounded-pill" style="background:#e8f5e9;color:#2e7d32;"><i class="bi bi-eye-fill"></i> Publicado</span>'
            : '<span class="badge rounded-pill" style="background:#fff3e0;color:#e65100;"><i class="bi bi-hourglass-split"></i> Pendiente</span>';
        $padre = htmlspecialchars($b['padre_nombre'] ?? '—');
        if (!empty($b['padre_email'])) $padre .= '<br><small class="text-muted">' . htmlspecialchars($b['padre_email']) . '</small>';

        $acc = '';
        // Ver PDF
        if (!empty($b['ruta_pdf'])) {
            $acc .= '<a href="../' . htmlspecialchars($b['ruta_pdf']) . '" target="_blank" class="btn-action btn-edit" title="Ver PDF"><i class="bi bi-file-earmark-pdf"></i></a> ';
        }
        // Publicar / Ocultar
        $tog = $pub ? 'ocultar' : 'publicar';
        $togBtn = $pub
            ? '<button type="submit" class="btn btn-sm" style="background:#fdeaea;color:#c62828;border:none;border-radius:8px;font-size:12px;font-weight:600;padding:5px 12px;"><i class="bi bi-eye-slash"></i> Ocultar</button>'
            : '<button type="submit" class="btn-gca btn-gca-sm"><i class="bi bi-send-check"></i> Publicar</button>';
        $acc .= '<form method="POST" style="display:inline-block;margin:2px;">' . campo_csrf()
            . '<input type="hidden" name="accion" value="' . $tog . '"><input type="hidden" name="boletin_id" value="' . (int)$b['id'] . '">' . $togBtn . '</form>';
        // Enviar por correo (opcional)
        if (!empty($b['padre_email'])) {
            $acc .= '<form method="POST" style="display:inline-block;margin:2px;">' . campo_csrf()
                . '<input type="hidden" name="accion" value="enviar"><input type="hidden" name="boletin_id" value="' . (int)$b['id'] . '">'
                . '<button type="submit" class="btn btn-sm btn-outline-gca" title="Enviar por correo" onclick="return confirm(\'¿Enviar por correo al padre?\')"><i class="bi bi-envelope"></i></button></form>';
        }

        $h .= '<tr>'
            . '<td class="fw-medium">' . htmlspecialchars($b['estudiante']) . '</td>'
            . '<td>' . htmlspecialchars($curso) . '</td>'
            . '<td class="text-center">' . htmlspecialchars($b['periodo']) . '</td>'
            . '<td class="text-center">' . htmlspecialchars($b['year']) . '</td>'
            . '<td class="text-center">' . $paz . '</td>'
            . '<td>' . $padre . '</td>'
            . '<td class="text-center">' . $estado . '</td>'
            . '<td class="text-center" style="white-space:nowrap;">' . $acc . '</td>'
            . '</tr>';
    }
    return $h;
}

$filas = boletinesFilas($conexion);
$pendientes = count(array_filter($filas, fn($b) => !$b['publicado']));

/* ── Poll en vivo ── */
if (isset($_GET["json"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "tbody"      => boletinesTbody($filas),
        "pendientes" => $pendientes,
        "total"      => count($filas),
    ]);
    exit;
}

$pageTitle = "Gestión de Boletines";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Gestión de Boletines</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Boletines &nbsp;/&nbsp; Publicación al padre</p>
            </div>
        </div>

        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="ti ti-file-certificate"></i> Boletines generados</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span id="pendBadge" class="badge rounded-pill px-3 py-2" style="background:#fff3e0;color:#e65100;font-size:12px;<?= $pendientes ? '' : 'display:none;' ?>">
                            <i class="bi bi-hourglass-split"></i> <span id="pendNum"><?= $pendientes ?></span> pendiente(s)
                        </span>
                        <span class="live-dot" title="Actualización en vivo" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#2e7d32;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#2e7d32;display:inline-block;animation:pulse 1.4s infinite;"></span> EN VIVO
                        </span>
                        <form method="POST" onsubmit="return confirm('¿Publicar TODOS los boletines pendientes? Se harán visibles a los padres.');">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="publicar_todos">
                            <button type="submit" class="btn-gca btn-gca-sm"><i class="bi bi-send-check"></i> Publicar todos</button>
                        </form>
                    </div>
                </div>

                <div class="alert" style="background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.25);color:#6b5a24;font-size:13px;border-radius:12px;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    El director de grupo genera los boletines y quedan <strong>Pendientes</strong>. Aquí tú decides el
                    <strong>paso al padre</strong>: al <strong>Publicar</strong>, el boletín aparece en el portal del padre.
                    El <strong>paz y salvo</strong> lo marca el director como referencia.
                </div>

                <div class="table-responsive">
                    <table class="table gca-table mb-0">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th class="text-center">Período</th>
                                <th class="text-center">Año</th>
                                <th class="text-center">Paz y salvo</th>
                                <th>Padre</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" style="width:200px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="boletinesBody"><?= boletinesTbody($filas) ?></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.25}}</style>
    <script>
    (function () {
        // Actualización en vivo: refleja boletines recién generados por los directores
        // y cambios de estado, sin recargar la página.
        var body = document.getElementById('boletinesBody');
        var pendNum = document.getElementById('pendNum');
        var pendBadge = document.getElementById('pendBadge');
        setInterval(function () {
            fetch('enviar_boletin.php?json=1', { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (typeof d.tbody === 'string' && !document.querySelector('#boletinesBody form:focus-within')) {
                        body.innerHTML = d.tbody;
                    }
                    pendNum.textContent = d.pendientes;
                    pendBadge.style.display = d.pendientes > 0 ? '' : 'none';
                })
                .catch(function () {});
        }, 8000);
    })();
    </script>

    <?php include "includes/footer.php"; ?>
</body>
</html>
