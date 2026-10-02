<?php
session_start();
require_once "../config/database.php";
require_once __DIR__ . "/../lib/csrf_helper.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

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
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Administradores</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Administradores</p>
            </div>
        </div>

        <div class="content-area">
            <div class="gca-card p-4">
                <div class="section-header">
                    <h4><i class="bi bi-shield-check"></i> Administradores registrados</h4>
                    <a href="crear_admin.php" class="btn-gca btn-gca-sm">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Admin
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="row g-2 mb-3 align-items-end" role="search">
                    <div class="col-md-6">
                        <label class="form-label fw-medium small mb-1" for="f_q">Buscar por nombre o correo</label>
                        <input id="f_q" type="search" name="q" class="form-control form-control-sm"
                            placeholder="Escriba un nombre o correo..."
                            value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-gca btn-gca-sm flex-fill">
                            <i class="bi bi-search" aria-hidden="true"></i> Buscar
                        </button>
                        <?php if ($busqueda !== ""): ?>
                            <a href="ver_admins.php" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="small text-muted mb-2" role="status">
                    <?= count($admins) ?> resultado(s)<?= $busqueda !== "" ? ' para «' . htmlspecialchars($busqueda) . '»' : '' ?>
                </p>

                <div class="table-responsive">
                    <table class="table gca-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Email</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($a["nombre"]) ?></span></td>
                                    <td><?= htmlspecialchars($a["email"]) ?></td>
                                    <td class="text-end">
                                        <a href="editar_admin.php?id=<?= $a["id"] ?>" class="btn-action btn-edit me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if ($a["id"] != $_SESSION["id"]): ?>
                                            <form method="POST" style="display:inline" id="delForm<?= $a["id"] ?>">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="eliminar_id" value="<?= $a["id"] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="showConfirm('¿Seguro que deseas eliminar este administrador?',()=>document.getElementById('delForm<?= $a["id"] ?>').submit());">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($admins) === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4"><?= $busqueda !== "" ? "No se encontraron administradores con esa búsqueda." : "No hay administradores registrados." ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>