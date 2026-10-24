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
        $docente = db()->fetch("SELECT * FROM docentes WHERE id = ?", [(int) $_POST["eliminar_id"]]);
        if ($docente && $docente["foto"]) {
            $ruta = __DIR__ . "/../" . $docente["foto"];
            if (file_exists($ruta)) unlink($ruta);
        }
        db()->delete("docentes", "id = ?", [(int) $_POST["eliminar_id"]]);
        Session::setSuccess("Docente eliminado.");
    } catch (Exception $e) {
        error_log("[ver_docentes] " . $e->getMessage());
        $mensaje = "No se pudo eliminar. Intente de nuevo.";
        $tipo = "danger";
    }
}

$search = trim($_GET["q"] ?? "");
$sql = "SELECT * FROM docentes WHERE activo = TRUE";
$params = [];
if ($search) {
    $sql .= " AND (nombre ILIKE ? OR especialidad ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY nombre ASC";
$docentes = db()->fetchAll($sql, $params);

$pageTitle = "Docentes";
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
                    <div>
                        <h4><i class="bi bi-person-vcard" aria-hidden="true"></i> Docentes del sitio web <span class="count-pill"><?= count($docentes) ?></span></h4>
                        <p class="section-sub">Directorio público que aparece en la página «Docentes». No son las cuentas de profesores.</p>
                    </div>
                    <a href="crear_docente.php" class="btn-gca"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo docente</a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <?php if ($success = Session::getSuccess()): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar docentes">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar docente</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control" placeholder="Nombre o especialidad..."
                                value="<?= htmlspecialchars($search) ?>" data-live-filter="#tablaDocentes" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($search !== ""): ?>
                            <a href="ver_docentes.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaDocentes">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Especialidad</th>
                                <th scope="col">Correo</th>
                                <th scope="col" class="text-center">Foto</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($docentes as $d): ?>
                            <tr>
                                <td class="cell-strong"><?= htmlspecialchars($d["nombre"]) ?></td>
                                <td><?= htmlspecialchars($d["especialidad"]) ?></td>
                                <td><?= htmlspecialchars($d["email"] ?? "—") ?></td>
                                <td class="text-center"><?= $d["foto"]
                                    ? '<span class="gca-badge is-ok"><i class="bi bi-image" aria-hidden="true"></i> Sí</span>'
                                    : '<span class="gca-badge">No</span>' ?></td>
                                <td class="text-end"><div class="row-actions">
                                    <a href="editar_docente.php?id=<?= $d["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar a <?= htmlspecialchars($d["nombre"]) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form method="POST" id="delForm<?= $d["id"] ?>">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="eliminar_id" value="<?= $d["id"] ?>">
                                        <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar a <?= htmlspecialchars($d["nombre"]) ?>"
                                            onclick="showConfirm('¿Eliminar este docente?',()=>document.getElementById('delForm<?= $d["id"] ?>').submit());">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($docentes)): ?>
                            <tr><td colspan="5"><div class="empty-state empty-state-sm"><i class="bi bi-person-vcard" aria-hidden="true"></i><h3>No hay docentes en el directorio</h3><p>Agregue docentes para mostrarlos en el sitio web.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
