<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$noticia = db()->fetch("SELECT * FROM noticias WHERE id = ?", [$id]);
if (!$noticia) { header("Location: ver_noticias.php"); exit; }

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg", "jpeg", "png", "webp"];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Formato no permitido.");
                }
                if ($noticia["imagen"]) {
                    $ruta_vieja = __DIR__ . "/../" . $noticia["imagen"];
                    if (file_exists($ruta_vieja)) unlink($ruta_vieja);
                }
                $nombre = "noticia_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                $destino = __DIR__ . "/../assets/img/noticias/" . $nombre;
                if (!is_dir(__DIR__ . "/../assets/img/noticias")) {
                    mkdir(__DIR__ . "/../assets/img/noticias", 0755, true);
                }
                move_uploaded_file($_FILES["imagen"]["tmp_name"], $destino);
                $imagen = "assets/img/noticias/" . $nombre;
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
            $mensaje = "Error: " . $e->getMessage();
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
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Editar Noticia</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Noticias &nbsp;/&nbsp; Editar</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <div class="gca-card card-form">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($noticia["titulo"]) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-control no-ts">
                                <?php foreach (["General","Institucional","Académico","Evento Cultural","Deportes"] as $cat): ?>
                                <option value="<?= $cat ?>" <?= $noticia["categoria"] === $cat ? "selected" : "" ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de publicación</label>
                            <input type="date" name="fecha_publicacion" class="form-control" value="<?= $noticia["fecha_publicacion"] ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Imagen</label>
                            <?php if ($noticia["imagen"]): ?>
                                <div class="mb-2"><img src="../<?= $noticia["imagen"] ?>" style="max-height:120px;border-radius:8px;"></div>
                            <?php endif; ?>
                            <input type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Dejar vacío para mantener la imagen actual.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contenido</label>
                            <textarea name="contenido" class="form-control" rows="6"><?= htmlspecialchars($noticia["contenido"] ?? "") ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-gca">Guardar Cambios</button>
                        <a href="ver_noticias.php" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
