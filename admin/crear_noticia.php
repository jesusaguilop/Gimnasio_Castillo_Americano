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
    $titulo = trim($_POST["titulo"] ?? "");
    $contenido = trim($_POST["contenido"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "General");
    $fecha = trim($_POST["fecha_publicacion"] ?? date("Y-m-d"));
    $imagen = "";

    if (!$titulo) {
        $mensaje = "El título es obligatorio.";
        $tipo = "danger";
    } else {
        try {
            if (isset($_FILES["imagen"])) {
                $imagen = guardar_imagen_subida($_FILES["imagen"], "noticias", "noticia");
            }

            db()->beginTransaction();
            db()->insert("noticias", [
                "titulo" => $titulo,
                "contenido" => $contenido,
                "imagen" => $imagen,
                "categoria" => $categoria,
                "fecha_publicacion" => $fecha
            ]);
            db()->commit();

            Session::setSuccess("Noticia creada correctamente.");
            header("Location: ver_noticias.php");
            exit;
        } catch (Exception $e) {
            db()->rollBack();
            // Los errores de la imagen ya vienen redactados para el usuario; los de la
            // base de datos no (traen SQL), así que esos van al log.
            error_log("[crear_noticia] " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? "No se pudo guardar. Intente de nuevo." : $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Noticia";
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
                <?php if ($success = Session::getSuccess()): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <div class="card-form p-4 p-md-5">
                    <div class="header-box">
                        <i class="bi bi-newspaper" aria-hidden="true"></i>
                        <div>
                            <h4>Nueva noticia</h4>
                            <span>Se publicará en la sección de noticias del sitio web.</span>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" autocomplete="off">
                        <?= campo_csrf() ?>
                        <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="f_titulo">Título <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_titulo" type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($_POST["titulo"] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_categoria">Categoría</label>
                                <select id="f_categoria" name="categoria" class="form-select no-ts">
                                    <option value="General"<?= ($_POST["categoria"] ?? "") === "General" ? " selected" : "" ?>>General</option>
                                    <option value="Institucional"<?= ($_POST["categoria"] ?? "") === "Institucional" ? " selected" : "" ?>>Institucional</option>
                                    <option value="Académico"<?= ($_POST["categoria"] ?? "") === "Académico" ? " selected" : "" ?>>Académico</option>
                                    <option value="Evento Cultural"<?= ($_POST["categoria"] ?? "") === "Evento Cultural" ? " selected" : "" ?>>Evento Cultural</option>
                                    <option value="Deportes"<?= ($_POST["categoria"] ?? "") === "Deportes" ? " selected" : "" ?>>Deportes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="f_fecha_publicacion">Fecha de publicación</label>
                                <input id="f_fecha_publicacion" type="date" name="fecha_publicacion" class="form-control" value="<?= htmlspecialchars($_POST['fecha_publicacion'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_imagen">Imagen</label>
                                <input id="f_imagen" type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_contenido">Contenido</label>
                                <textarea id="f_contenido" name="contenido" class="form-control" rows="6"><?= htmlspecialchars($_POST["contenido"] ?? '') ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-gca w-100 mt-4">Publicar Noticia</button>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
