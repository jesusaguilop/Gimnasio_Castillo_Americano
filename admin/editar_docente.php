<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../lib/csrf_helper.php";
require_once __DIR__ . '/../lib/imagen_helper.php';
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$docente = db()->fetch("SELECT * FROM docentes WHERE id = ?", [$id]);
if (!$docente) { header("Location: ver_docentes.php"); exit; }

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
    $foto = $docente["foto"];

    if (!$nombre) {
        $mensaje = "El nombre es obligatorio.";
        $tipo = "danger";
    } else {
        try {
            if (isset($_FILES["foto"])) {
                $nuevaFoto = guardar_imagen_subida($_FILES["foto"], "docentes", "docente");
                if ($nuevaFoto !== "") {
                    // La anterior se borra SOLO cuando la nueva ya está guardada:
                    // antes se borraba primero y un fallo dejaba al docente sin foto.
                    borrar_imagen_subida($docente["foto"]);
                    $foto = $nuevaFoto;
                }
            }

            db()->update("docentes", [
                "nombre" => $nombre,
                "especialidad" => $especialidad,
                "foto" => $foto,
                "descripcion" => $descripcion,
                "email" => $email
            ], "id = ?", [$id]);
            Session::setSuccess("Docente actualizado.");
            header("Location: ver_docentes.php");
            exit;
        } catch (Exception $e) {
            // Los errores de la imagen ya vienen redactados para el usuario; los de la
            // base de datos no (traen SQL), así que esos van al log.
            error_log("[editar_docente] " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? "No se pudo guardar. Intente de nuevo." : $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Editar Docente";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <?php include "includes/topbar.php"; ?>
        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-xl-9 col-xxl-8">
                <a href="ver_docentes.php" class="form-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Docentes del sitio</a>
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <div class="card-form p-4 p-md-5">
                    <div class="header-box">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                        <div>
                            <h4>Editar docente del sitio</h4>
                            <span>Ficha pública para la página «Docentes».</span>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" autocomplete="off">
                        <?= campo_csrf() ?>
                        <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="f_nombre">Nombre completo <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_nombre" type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($docente["nombre"]) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_email">Correo electrónico</label>
                                <input id="f_email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($docente["email"] ?? "") ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_especialidad">Especialidad / Área</label>
                                <input id="f_especialidad" type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($docente["especialidad"] ?? "") ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_foto">Foto</label>
                                <?php if ($docente["foto"]): ?>
                                    <div class="mb-2"><img src="../<?= htmlspecialchars($docente["foto"]) ?>" alt="Foto actual del docente" class="img-preview"></div>
                                <?php endif; ?>
                                <input id="f_foto" type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted">Dejar vacío para mantener la foto actual.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_descripcion">Descripción / Formación</label>
                                <textarea id="f_descripcion" name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($docente["descripcion"] ?? "") ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions mt-4">
                            <button type="submit" class="btn-gca">Guardar Cambios</button>
                            <a href="ver_docentes.php" class="btn-outline-gca">Cancelar</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
