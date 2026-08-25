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
if ($puedeEliminar && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Intenta de nuevo.";
    $puedeEliminar = false;
}
if ($puedeEliminar) {
    $id = (int) $_POST["eliminar_id"];

    if ($id == $_SESSION["id"]) {
        $mensaje = "No puedes eliminar tu propia cuenta.";
    } else {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'admin'");
        $stmt->execute([":id" => $id]);
        $mensaje = "Administrador eliminado correctamente";
    }
}

$admins = $conexion
    ->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'admin'")
    ->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestión de Administradores";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Administradores</h5>
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
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table gca-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th class="text-end">Acciones</th>
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
                                <tr><td colspan="3" class="text-center text-muted py-4">No hay administradores registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>