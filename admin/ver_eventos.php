<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../lib/csrf_helper.php";
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

// Borrado por POST + token CSRF. Antes se disparaba con un GET
// (ver_*.php?id=N&confirmar=1): cualquier enlace o <img> que cargara
// el admin estando logueado podía eliminar registros.
$puedeEliminar = $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_id"]);
if ($puedeEliminar && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intenta de nuevo.";
    $puedeEliminar = false;
}
if ($puedeEliminar) {
    try {
        db()->delete("eventos", "id = ?", [(int) $_POST["eliminar_id"]]);
        Session::setSuccess("Evento eliminado.");
    } catch (Exception $e) {
        error_log("[ver_eventos] " . $e->getMessage());
        $mensaje = "No se pudo eliminar. Intente de nuevo.";
        $tipo = "danger";
    }
}

$search = trim($_GET["q"] ?? "");
$sql = "SELECT * FROM eventos WHERE activo = TRUE";
$params = [];
if ($search) {
    $sql .= " AND (titulo ILIKE ? OR tipo ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY fecha_evento DESC";
$eventos = db()->fetchAll($sql, $params);

$pageTitle = "Eventos";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <?php include "includes/topbar.php"; ?>
        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-calendar-event" aria-hidden="true"></i> Calendario de eventos <span class="count-pill"><?= count($eventos) ?></span></h4>
                    <a href="crear_evento.php" class="btn-gca"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo evento</a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <?php if ($success = Session::getSuccess()): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar eventos">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar eventos</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control" placeholder="Título o tipo..."
                                value="<?= htmlspecialchars($search) ?>" data-live-filter="#tablaEventos" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($search !== ""): ?>
                            <a href="ver_eventos.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaEventos">
                        <thead>
                            <tr>
                                <th scope="col">Título</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Hora</th>
                                <th scope="col">Tipo</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $e): ?>
                            <tr>
                                <td class="cell-strong">
                                    <span class="event-dot" style="background:<?= htmlspecialchars($e['color']) ?>;" aria-hidden="true"></span>
                                    <?= htmlspecialchars($e["titulo"]) ?>
                                </td>
                                <td class="text-nowrap"><?= date("d/m/Y", strtotime($e["fecha_evento"])) ?></td>
                                <td class="text-nowrap"><?= $e["hora_evento"] ? date("h:i A", strtotime($e["hora_evento"])) : "—" ?></td>
                                <td><span class="gca-badge is-gold"><?= htmlspecialchars($e["tipo"]) ?></span></td>
                                <td class="text-end"><div class="row-actions">
                                    <a href="editar_evento.php?id=<?= $e["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar el evento <?= htmlspecialchars($e["titulo"]) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form method="POST" id="delForm<?= $e["id"] ?>">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="eliminar_id" value="<?= $e["id"] ?>">
                                        <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar el evento <?= htmlspecialchars($e["titulo"]) ?>"
                                            onclick="showConfirm('¿Eliminar este evento?',()=>document.getElementById('delForm<?= $e["id"] ?>').submit());">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($eventos)): ?>
                            <tr><td colspan="5"><div class="empty-state empty-state-sm"><i class="bi bi-calendar-event" aria-hidden="true"></i><h3>No hay eventos</h3><p>Los eventos que cree aparecerán en el calendario del sitio web.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
