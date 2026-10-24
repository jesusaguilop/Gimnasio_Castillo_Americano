<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../lib/csrf_helper.php";
require_once __DIR__ . '/../lib/imagen_helper.php';
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$noticia = db()->fetch("SELECT * FROM noticias WHERE id = ?", [$id]);
if (!$noticia) { header("Location: ver_noticias.php"); exit; }

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $contenido = trim($_POST["contenido"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "General");
    $fecha = trim($_POST["fecha_publicacion"] ?? date("Y-m-d"));
    $imagen = $noticia["imagen"];

    if (!$titulo) {
        $mensaje = "El título es obligatorio.";
        $tipo = "danger";
    } else {
        try {
            if (isset($_FILES["imagen"])) {
                $nuevaImagen = guardar_imagen_subida($_FILES["imagen"], "noticias", "noticia");
                if ($nuevaImagen !== "") {
                    borrar_imagen_subida($noticia["imagen"]);
                    $imagen = $nuevaImagen;
                }
            }

            db()->update("noticias", [
                "titulo" => $titulo,
                "contenido" => $contenido,
                "imagen" => $imagen,
                "categoria" => $categoria,
                "fecha_publicacion" => $fecha
            ], "id = ?", [$id]);

            Session::setSuccess("Noticia actualizada.");
            header("Location: ver_noticias.php");
            exit;
        } catch (Exception $e) {
            // Los errores de la imagen ya vienen redactados para el usuario; los de la
            // base de datos no (traen SQL), así que esos van al log.
            error_log("[editar_noticia] " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? "No se pudo guardar. Intente de nuevo." : $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Editar Noticia";
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
                <a href="ver_noticias.php" class="form-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Noticias</a>
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <div class="card-form p-4 p-md-5">
                    <div class="header-box">
                        <i class="bi bi-newspaper" aria-hidden="true"></i>
                        <div>
                            <h4>Editar noticia</h4>
                            <span>Los cambios se ven de inmediato en el sitio web.</span>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" autocomplete="off">
                        <?= campo_csrf() ?>
                        <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="f_titulo">Título <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_titulo" type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($noticia["titulo"]) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_categoria">Categoría</label>
                                <select id="f_categoria" name="categoria" class="form-select no-ts">
                                    <?php foreach (["General","Institucional","Académico","Evento Cultural","Deportes"] as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $noticia["categoria"] === $cat ? "selected" : "" ?>><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_fecha_publicacion">Fecha de publicación</label>
                                <input id="f_fecha_publicacion" type="date" name="fecha_publicacion" class="form-control" value="<?= $noticia["fecha_publicacion"] ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_imagen">Imagen</label>
                                <?php if ($noticia["imagen"]): ?>
                                    <div class="mb-2"><img src="../<?= htmlspecialchars($noticia["imagen"]) ?>" alt="Imagen actual de la noticia" class="img-preview"></div>
                                <?php endif; ?>
                                <input id="f_imagen" type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted">Dejar vacío para mantener la imagen actual.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_contenido">Contenido</label>
                                <textarea id="f_contenido" name="contenido" class="form-control" rows="6"><?= htmlspecialchars($noticia["contenido"] ?? "") ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions mt-4">
                            <button type="submit" class="btn-gca">Guardar Cambios</button>
                            <a href="ver_noticias.php" class="btn-outline-gca">Cancelar</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
