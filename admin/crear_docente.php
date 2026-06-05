<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg", "jpeg", "png", "webp"];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Formato de imagen no permitido (jpg, png, webp).");
                }
                $nombre_foto = "docente_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                $destino = __DIR__ . "/../assets/img/docentes/" . $nombre_foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $destino);
                $foto = "assets/img/docentes/" . $nombre_foto;
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
            $mensaje = "Error: " . $e->getMessage();
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
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Registrar Docente</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Docentes &nbsp;/&nbsp; Nuevo</p>
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
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Especialidad / Área</label>
                            <input type="text" name="especialidad" class="form-control" placeholder="Ej: Matemáticas, Ciencias...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción / Formación</label>
                            <textarea name="descripcion" class="form-control" rows="4" placeholder="Breve descripción del docente..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-gca w-100 mt-4">Registrar Docente</button>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
