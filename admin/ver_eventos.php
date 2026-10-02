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
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Gestión de Eventos</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Eventos</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($success = Session::getSuccess()): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <div class="section-header">
                <h4>Calendario de Eventos</h4>
                <a href="crear_evento.php" class="btn-gca btn-gca-sm">+ Nuevo Evento</a>
            </div>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Buscar eventos..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-gca btn-gca-sm w-100">Buscar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table gca-table">
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
                            <td>
                                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= htmlspecialchars($e['color']) ?>;margin-right:8px;vertical-align:middle;"></span>
                                <?= htmlspecialchars($e["titulo"]) ?>
                            </td>
                            <td><?= date("d/m/Y", strtotime($e["fecha_evento"])) ?></td>
                            <td><?= $e["hora_evento"] ? date("h:i A", strtotime($e["hora_evento"])) : "-" ?></td>
                            <td><span class="badge-gca" style="font-size:11px;"><?= htmlspecialchars($e["tipo"]) ?></span></td>
                            <td class="text-end">
                                <a href="editar_evento.php?id=<?= $e["id"] ?>" class="btn-action btn-edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline" id="delForm<?= $e["id"] ?>">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="eliminar_id" value="<?= $e["id"] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="showConfirm('¿Eliminar este evento?',()=>document.getElementById('delForm<?= $e["id"] ?>').submit());">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($eventos)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay eventos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
