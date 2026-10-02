<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../lib/csrf_helper.php";
require_once __DIR__ . '/../lib/imagen_helper.php';
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $especialidad = trim($_POST["especialidad"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $foto = "";

    if (!$nombre) {
        $mensaje = "El nombre es obligatorio.";
        $tipo = "danger";
    } else {
        try {
            if (isset($_FILES["foto"])) {
                // Si la imagen falla (p.ej. pesa más de lo permitido), esto lanza
                // una excepción con el motivo en vez de guardar el docente sin foto.
                $foto = guardar_imagen_subida($_FILES["foto"], "docentes", "docente");
            }

            db()->insert("docentes", [
                "nombre" => $nombre,
                "especialidad" => $especialidad,
                "foto" => $foto,
                "descripcion" => $descripcion,
                "email" => $email
            ]);
            Session::setSuccess("Docente registrado correctamente.");
            header("Location: ver_docentes.php");
            exit;
        } catch (Exception $e) {
            // Los errores de la imagen ya vienen redactados para el usuario; los de la
            // base de datos no (traen SQL), así que esos van al log.
            error_log("[crear_docente] " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? "No se pudo guardar. Intente de nuevo." : $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Docente";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <div class="topbar">
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1">Registrar Docente</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Docentes &nbsp;/&nbsp; Nuevo</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <div class="gca-card card-form">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <?= campo_csrf() ?>
                    <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="f_nombre">Nombre completo *</label>
                            <input id="f_nombre" type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($_POST["nombre"] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="f_email">Correo electrónico</label>
                            <input id="f_email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST["email"] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="f_especialidad">Especialidad / Área</label>
                            <input id="f_especialidad" type="text" name="especialidad" class="form-control" placeholder="Ej: Matemáticas, Ciencias..." value="<?= htmlspecialchars($_POST["especialidad"] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="f_foto">Foto</label>
                            <input id="f_foto" type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="f_descripcion">Descripción / Formación</label>
                            <textarea id="f_descripcion" name="descripcion" class="form-control" rows="4" placeholder="Breve descripción del docente..."><?= htmlspecialchars($_POST["descripcion"] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-gca w-100 mt-4">Registrar Docente</button>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
