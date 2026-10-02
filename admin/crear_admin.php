<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once __DIR__ . "/../lib/csrf_helper.php";

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $pass1 = $_POST["password"] ?? "";
    $pass2 = $_POST["password2"] ?? "";

    if ($nombre === "" || $email === "" || $pass1 === "" || $pass2 === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido.";
        $tipo = "danger";
    } elseif (strlen($pass1) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipo = "warning";
    } elseif ($pass1 !== $pass2) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo = "warning";
    } else {
        try {
            $ver = $conexion->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $ver->execute([":email" => $email]);

            if ($ver->fetch()) {
                $mensaje = "Este correo ya se encuentra registrado.";
                $tipo = "warning";
            } else {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (nombre, email, password, rol)
                        VALUES (:nombre, :email, :password, 'admin')";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ":nombre" => $nombre,
                    ":email" => $email,
                    ":password" => $hash
                ]);

                $_SESSION["success_msg"] = "Administrador creado correctamente.";
                header("Location: ver_admins.php");
                exit;
            }
        } catch (PDOException $e) {
            $mensaje = "Error al crear el administrador.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Administrador";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>

    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Crear Administrador</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Nuevo Administrador</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-form p-4 p-md-5">

                        <div class="header-box">
                            <img src="../assets/img/logo_gca.png" alt="GCA">
                            <h4>Registro de Administrador</h4>
                            <span>Gimnasio Castillo Americano</span>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <?= campo_csrf() ?>
                            <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_nombre">Nombre completo <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_nombre" type="text" name="nombre" class="form-control"
                                    value="<?= htmlspecialchars($_POST["nombre"] ?? "") ?>" required
                                    placeholder="Ej. Juan Pérez">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_email">Correo electrónico <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_email" type="email" name="email" class="form-control" placeholder="admin@gca.edu.co"
                                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="f_password">Contraseña <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_password" type="password" name="password" minlength="8" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium" for="f_password2">Confirmar contraseña <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_password2" type="password" name="password2" minlength="8" class="form-control" required>
                            </div>

                            <button type="submit" class="btn-gca w-100">
                                <i class="bi bi-check-circle me-2"></i>Registrar Administrador
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>