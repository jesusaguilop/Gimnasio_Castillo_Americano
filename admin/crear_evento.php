<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('admin');

use App\Base\Session;

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
            db()->insert("eventos", [
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "fecha_evento" => $fecha,
                "hora_evento" => $hora ?: null,
                "tipo" => $tipo_evento,
                "color" => $color
            ]);
            Session::setSuccess("Evento creado correctamente.");
            header("Location: ver_eventos.php");
            exit;
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo = "danger";
        }
    }
}

$pageTitle = "Crear Evento";
include "includes/header.php";
?>
<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>
    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Crear Evento</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Eventos &nbsp;/&nbsp; Nuevo</p>
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
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha_evento" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora (opcional)</label>
                            <input type="time" name="hora_evento" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-control no-ts">
                                <option value="General">General</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Deportivo">Deportivo</option>
                                <option value="Académico">Académico</option>
                                <option value="Reunión">Reunión</option>
                                <option value="Festivo">Festivo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color" value="#c9a24d">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-gca w-100 mt-4">Crear Evento</button>
                </form>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
</body>
</html>
