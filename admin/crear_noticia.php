<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg", "jpeg", "png", "webp"];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Formato de imagen no permitido (jpg, png, webp).");
                }
                $nombre = "noticia_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                $destino = __DIR__ . "/../assets/img/noticias/" . $nombre;
                if (!is_dir(__DIR__ . "/../assets/img/noticias")) {
                    mkdir(__DIR__ . "/../assets/img/noticias", 0755, true);
                }
                move_uploaded_file($_FILES["imagen"]["tmp_name"], $destino);
                $imagen = "assets/img/noticias/" . $nombre;
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
            $mensaje = "Error: " . $e->getMessage();
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
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Crear Noticia</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Noticias &nbsp;/&nbsp; Nueva</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($success = Session::getSuccess()): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <div class="gca-card card-form">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-control no-ts">
                                <option value="General">General</option>
                                <option value="Institucional">Institucional</option>
                                <option value="Académico">Académico</option>
                                <option value="Evento Cultural">Evento Cultural</option>
                                <option value="Deportes">Deportes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de publicación</label>
                            <input type="date" name="fecha_publicacion" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Imagen</label>
                            <input type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contenido</label>
                            <textarea name="contenido" class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-gca w-100 mt-4">Publicar Noticia</button>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
