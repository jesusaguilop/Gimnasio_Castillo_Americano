<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");
require_once __DIR__ . "/../lib/csrf_helper.php";

$mensaje = "";
if (isset($_SESSION["success_msg"])) {
    $mensaje = $_SESSION["success_msg"];
    unset($_SESSION["success_msg"]);
}

// Borrado por POST + token CSRF. Antes se disparaba con un GET
// (ver_*.php?id=N&confirmar=1): cualquier enlace o <img> que cargara
// el admin estando logueado podía eliminar registros.
$puedeEliminar = $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_id"]);
$tipo = "success";
if ($puedeEliminar && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intenta de nuevo.";
    $tipo = "danger";
    $puedeEliminar = false;
}
if ($puedeEliminar) {
    $id = (int) $_POST["eliminar_id"];

    if ($id == $_SESSION["id"]) {
        $mensaje = "No puedes eliminar tu propia cuenta.";
        $tipo = "warning";
    } else {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'admin'");
        $stmt->execute([":id" => $id]);
        $mensaje = $stmt->rowCount() ? "Administrador eliminado correctamente" : "No se encontró el registro.";
        $tipo = $stmt->rowCount() ? "success" : "warning";
    }
}

/* Búsqueda por nombre o correo (sin distinguir mayúsculas) */
$busqueda = trim($_GET["q"] ?? "");
$sqlLista = "SELECT id, nombre, email FROM usuarios WHERE rol = 'admin'";
$paramsLista = [];
if ($busqueda !== "") {
    $sqlLista .= " AND (nombre ILIKE ? OR email ILIKE ?)";
    $like = "%" . $busqueda . "%";
    $paramsLista = [$like, $like];
}
$stmtLista = $conexion->prepare($sqlLista . " ORDER BY nombre");
$stmtLista->execute($paramsLista);
$admins = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Administradores";
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
                    <h4><i class="bi bi-shield-lock" aria-hidden="true"></i> Administradores <span class="count-pill"><?= count($admins) ?></span></h4>
                    <a href="crear_admin.php" class="btn-gca">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo administrador
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="filter-bar" role="search" aria-label="Buscar administradores">
                    <div class="ff ff-grow">
                        <label class="form-label" for="f_q">Buscar por nombre o correo</label>
                        <div class="search-field mw-100">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="f_q" type="search" name="q" class="form-control"
                                placeholder="Escriba un nombre o correo..."
                                value="<?= htmlspecialchars($busqueda) ?>" data-live-filter="#tablaAdmins" autocomplete="off">
                        </div>
                    </div>
                    <div class="ff-actions">
                        <button type="submit" class="btn-gca"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
                        <?php if ($busqueda !== ""): ?>
                            <a href="ver_admins.php" class="btn-outline-gca"><i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="table-count mb-2" role="status">
                    <?= count($admins) ?> resultado(s)<?= $busqueda !== "" ? ' para «' . htmlspecialchars($busqueda) . '»' : '' ?>
                </p>

                <div class="table-responsive">
                    <table class="table gca-table align-middle" id="tablaAdmins">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td class="cell-strong">
                                        <?= htmlspecialchars($a["nombre"]) ?>
                                        <?php if ($a["id"] == $_SESSION["id"]): ?><span class="gca-badge is-gold ms-1">Tú</span><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a["email"]) ?></td>
                                    <td class="text-end"><div class="row-actions">
                                        <a href="editar_admin.php?id=<?= $a["id"] ?>" class="btn-action btn-edit" title="Editar" aria-label="Editar a <?= htmlspecialchars($a["nombre"]) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($a["id"] != $_SESSION["id"]): ?>
                                            <form method="POST" id="delForm<?= $a["id"] ?>">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="eliminar_id" value="<?= $a["id"] ?>">
                                                <button type="button" class="btn-action btn-delete" title="Eliminar" aria-label="Eliminar a <?= htmlspecialchars($a["nombre"]) ?>"
                                                    onclick="showConfirm('¿Seguro que deseas eliminar este administrador?',()=>document.getElementById('delForm<?= $a["id"] ?>').submit());">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($admins) === 0): ?>
                                <tr><td colspan="3"><div class="empty-state empty-state-sm"><i class="bi bi-shield-lock" aria-hidden="true"></i><p><?= $busqueda !== "" ? "No se encontraron administradores con esa búsqueda." : "No hay administradores registrados." ?></p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
