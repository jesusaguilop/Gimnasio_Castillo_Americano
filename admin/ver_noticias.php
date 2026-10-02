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
        $noticia = db()->fetch("SELECT * FROM noticias WHERE id = ?", [(int) $_POST["eliminar_id"]]);
        if ($noticia && $noticia["imagen"]) {
            $ruta = __DIR__ . "/../" . $noticia["imagen"];
            if (file_exists($ruta)) unlink($ruta);
        }
        db()->delete("noticias", "id = ?", [(int) $_POST["eliminar_id"]]);
        Session::setSuccess("Noticia eliminada.");
    } catch (Exception $e) {
        error_log("[ver_noticias] " . $e->getMessage());
        $mensaje = "No se pudo eliminar. Intente de nuevo.";
        $tipo = "danger";
    }
}

$search = trim($_GET["q"] ?? "");
$sql = "SELECT * FROM noticias WHERE activo = TRUE";
$params = [];
if ($search) {
    $sql .= " AND (titulo ILIKE ? OR categoria ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY fecha_publicacion DESC";
$noticias = db()->fetchAll($sql, $params);

$pageTitle = "Noticias";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Gestión de Noticias</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Noticias</p>
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
                <h4>Todas las Noticias</h4>
                <a href="crear_noticia.php" class="btn-gca btn-gca-sm">+ Nueva Noticia</a>
            </div>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Buscar noticias..." value="<?= htmlspecialchars($search) ?>">
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
                            <th scope="col">Categoría</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Imagen</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n["titulo"]) ?></td>
                            <td><span class="badge-gca" style="font-size:11px;"><?= htmlspecialchars($n["categoria"]) ?></span></td>
                            <td><?= date("d/m/Y", strtotime($n["fecha_publicacion"])) ?></td>
                            <td><?= $n["imagen"] ? '<i class="bi bi-check-circle-fill" style="color:var(--gold);"></i>' : '<i class="bi bi-dash-circle" style="color:#ccc;"></i>' ?></td>
                            <td class="text-end">
                                <a href="editar_noticia.php?id=<?= $n["id"] ?>" class="btn-action btn-edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline" id="delForm<?= $n["id"] ?>">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="eliminar_id" value="<?= $n["id"] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="showConfirm('¿Eliminar esta noticia?',()=>document.getElementById('delForm<?= $n["id"] ?>').submit());">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($noticias)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay noticias registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
