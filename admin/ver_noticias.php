<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

if (isset($_GET["id"]) && isset($_GET["confirmar"])) {
    try {
        $noticia = db()->fetch("SELECT * FROM noticias WHERE id = ?", [$_GET["id"]]);
        if ($noticia && $noticia["imagen"]) {
            $ruta = __DIR__ . "/../" . $noticia["imagen"];
            if (file_exists($ruta)) unlink($ruta);
        }
        db()->delete("noticias", "id = ?", [$_GET["id"]]);
        Session::setSuccess("Noticia eliminada.");
    } catch (Exception $e) {
        $mensaje = "Error al eliminar: " . $e->getMessage();
        $tipo = "danger";
    }
}

$search = trim($_GET["q"] ?? "");
$sql = "SELECT * FROM noticias WHERE activo = TRUE";
$params = [];
if ($search) {
    $sql .= " AND (titulo LIKE ? OR categoria LIKE ?)";
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
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Gestión de Noticias</h5>
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
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Imagen</th>
                            <th class="text-end">Acciones</th>
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
                                <a href="ver_noticias.php?id=<?= $n["id"] ?>&confirmar=1"
                                   onclick="event.preventDefault();showConfirm('¿Eliminar esta noticia?',()=>window.location.href=this.href);"
                                   class="btn-action btn-delete"><i class="bi bi-trash"></i></a>
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
</body>
</html>
