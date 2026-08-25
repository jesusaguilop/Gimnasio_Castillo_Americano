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
        $mensaje = "Error: " . $e->getMessage();
        $tipo = "danger";
    }
}

$search = trim($_GET["q"] ?? "");
$sql = "SELECT * FROM docentes WHERE activo = TRUE";
$params = [];
if ($search) {
    $sql .= " AND (nombre LIKE ? OR especialidad LIKE ?)";
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
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Directorio Docentes</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Docentes</p>
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
                <h4>Plantilla Docente</h4>
                <a href="crear_docente.php" class="btn-gca btn-gca-sm">+ Nuevo Docente</a>
            </div>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Buscar docente..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-gca btn-gca-sm w-100">Buscar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table gca-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Especialidad</th>
                            <th>Email</th>
                            <th>Foto</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($docentes as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d["nombre"]) ?></td>
                            <td><?= htmlspecialchars($d["especialidad"]) ?></td>
                            <td><?= htmlspecialchars($d["email"] ?? "-") ?></td>
                            <td><?= $d["foto"] ? '<i class="bi bi-check-circle-fill" style="color:var(--gold);"></i>' : '<i class="bi bi-dash-circle" style="color:#ccc;"></i>' ?></td>
                            <td class="text-end">
                                <a href="editar_docente.php?id=<?= $d["id"] ?>" class="btn-action btn-edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" style="display:inline" id="delForm<?= $d["id"] ?>">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="eliminar_id" value="<?= $d["id"] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="showConfirm('¿Eliminar este docente?',()=>document.getElementById('delForm<?= $d["id"] ?>').submit());">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($docentes)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay docentes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
