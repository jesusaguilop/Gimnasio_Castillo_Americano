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
        <?php include "includes/topbar.php"; ?>
        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-newspaper" aria-hidden="true"></i> Noticias del sitio <span class="count-pill"><?= count($noticias) ?></span></h4>
                    <a href="crear_noticia.php" class="btn-gca"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva noticia</a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <?php if ($success = Session::getSuccess()): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar noticias">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar noticias</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control" placeholder="Título o categoría..."
                                value="<?= htmlspecialchars($search) ?>" data-live-filter="#tablaNoticias" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($search !== ""): ?>
                            <a href="ver_noticias.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaNoticias">
                        <thead>
                            <tr>
                                <th scope="col">Título</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Fecha</th>
                                <th scope="col" class="text-center">Imagen</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noticias as $n): ?>
                            <tr>
                                <td class="cell-strong"><?= htmlspecialchars($n["titulo"]) ?></td>
                                <td><span class="gca-badge is-gold"><?= htmlspecialchars($n["categoria"]) ?></span></td>
                                <td class="text-nowrap"><?= date("d/m/Y", strtotime($n["fecha_publicacion"])) ?></td>
                                <td class="text-center"><?= $n["imagen"]
                                    ? '<span class="gca-badge is-ok"><i class="bi bi-image" aria-hidden="true"></i> Sí</span>'
                                    : '<span class="gca-badge">No</span>' ?></td>
                                <td class="text-end"><div class="row-actions">
                                    <a href="editar_noticia.php?id=<?= $n["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar la noticia <?= htmlspecialchars($n["titulo"]) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form method="POST" id="delForm<?= $n["id"] ?>">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="eliminar_id" value="<?= $n["id"] ?>">
                                        <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar la noticia <?= htmlspecialchars($n["titulo"]) ?>"
                                            onclick="showConfirm('¿Eliminar esta noticia?',()=>document.getElementById('delForm<?= $n["id"] ?>').submit());">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($noticias)): ?>
                            <tr><td colspan="5"><div class="empty-state empty-state-sm"><i class="bi bi-newspaper" aria-hidden="true"></i><h3>No hay noticias</h3><p>Las noticias que publique aparecerán en el sitio web del colegio.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
