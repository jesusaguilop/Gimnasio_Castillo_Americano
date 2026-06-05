<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$evento = db()->fetch("SELECT * FROM eventos WHERE id = ?", [$id]);
if (!$evento) { header("Location: ver_eventos.php"); exit; }

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $fecha = trim($_POST["fecha_evento"] ?? "");
    $hora = trim($_POST["hora_evento"] ?? "");
    $tipo_evento = trim($_POST["tipo"] ?? "General");
    $color = trim($_POST["color"] ?? "#c9a24d");

    if (!$titulo || !$fecha) {
        $mensaje = "El título y la fecha son obligatorios.";
        $tipo = "danger";
    } else {
        try {
            db()->update("eventos", [
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "fecha_evento" => $fecha,
                "hora_evento" => $hora ?: null,
                "tipo" => $tipo_evento,
                "color" => $color
            ], "id = ?", [$id]);
            Session::setSuccess("Evento actualizado.");
            header("Location: ver_eventos.php");
            exit;
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Editar Evento";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Editar Evento</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Eventos &nbsp;/&nbsp; Editar</p>
            </div>
        </div>
        <div class="content-area">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <div class="gca-card card-form">
                <form method="POST" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($evento["titulo"]) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha_evento" class="form-control" required value="<?= $evento["fecha_evento"] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora_evento" class="form-control" value="<?= $evento["hora_evento"] ?? "" ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-control no-ts">
                                <?php foreach (["General","Cultural","Deportivo","Académico","Reunión","Festivo"] as $t): ?>
                                <option value="<?= $t ?>" <?= $evento["tipo"] === $t ? "selected" : "" ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($evento["color"]) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($evento["descripcion"] ?? "") ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-gca">Guardar Cambios</button>
                        <a href="ver_eventos.php" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
