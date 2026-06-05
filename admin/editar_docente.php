<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$docente = db()->fetch("SELECT * FROM docentes WHERE id = ?", [$id]);
if (!$docente) { header("Location: ver_docentes.php"); exit; }

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg", "jpeg", "png", "webp"];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Formato no permitido.");
                }
                if ($docente["foto"]) {
                    $ruta_vieja = __DIR__ . "/../" . $docente["foto"];
                    if (file_exists($ruta_vieja)) unlink($ruta_vieja);
                }
                $nombre_foto = "docente_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                $destino = __DIR__ . "/../assets/img/docentes/" . $nombre_foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $destino);
                $foto = "assets/img/docentes/" . $nombre_foto;
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
            $mensaje = "Error: " . $e->getMessage();
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
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Editar Docente</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Docentes &nbsp;/&nbsp; Editar</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <div class="gca-card card-form">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($docente["nombre"]) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($docente["email"] ?? "") ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Especialidad / Área</label>
                            <input type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($docente["especialidad"] ?? "") ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <?php if ($docente["foto"]): ?>
                                <div class="mb-2"><img src="../<?= $docente["foto"] ?>" style="max-height:80px;border-radius:8px;"></div>
                            <?php endif; ?>
                            <input type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Dejar vacío para mantener la foto actual.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción / Formación</label>
                            <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($docente["descripcion"] ?? "") ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-gca">Guardar Cambios</button>
                        <a href="ver_docentes.php" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
