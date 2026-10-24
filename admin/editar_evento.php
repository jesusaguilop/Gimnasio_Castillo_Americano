<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../lib/csrf_helper.php";
checkRole('admin');

use App\Base\Session;

$id = (int)($_GET["id"] ?? 0);
$evento = db()->fetch("SELECT * FROM eventos WHERE id = ?", [$id]);
if (!$evento) { header("Location: ver_eventos.php"); exit; }

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !validar_token_csrf($_POST["_csrf_token"] ?? "")) {
    $mensaje = "Error de seguridad. Recargue la página e intente de nuevo.";
    $tipo = "danger";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            // Los errores de la imagen ya vienen redactados para el usuario; los de la
            // base de datos no (traen SQL), así que esos van al log.
            error_log("[editar_evento] " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? "No se pudo guardar. Intente de nuevo." : $e->getMessage();
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
        <?php include "includes/topbar.php"; ?>
        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-xl-9 col-xxl-8">
                <a href="ver_eventos.php" class="form-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Eventos</a>
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <div class="card-form p-4 p-md-5">
                    <div class="header-box">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        <div>
                            <h4>Editar evento</h4>
                            <span>Los cambios se ven de inmediato en el calendario.</span>
                        </div>
                    </div>
                    <form method="POST" autocomplete="off">
                        <?= campo_csrf() ?>
                        <p class="form-note mb-3"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="f_titulo">Título <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_titulo" type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($evento["titulo"]) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="f_fecha_evento">Fecha <span class="req" aria-hidden="true">*</span></label>
                                <input id="f_fecha_evento" type="date" name="fecha_evento" class="form-control" required value="<?= $evento["fecha_evento"] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="f_hora_evento">Hora</label>
                                <input id="f_hora_evento" type="time" name="hora_evento" class="form-control" value="<?= $evento["hora_evento"] ?? "" ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="f_tipo">Tipo</label>
                                <select id="f_tipo" name="tipo" class="form-select no-ts">
                                    <?php foreach (["General","Cultural","Deportivo","Académico","Reunión","Festivo"] as $t): ?>
                                    <option value="<?= $t ?>" <?= $evento["tipo"] === $t ? "selected" : "" ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="f_color">Color</label>
                                <input id="f_color" type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($evento["color"]) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="f_descripcion">Descripción</label>
                                <textarea id="f_descripcion" name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($evento["descripcion"] ?? "") ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions mt-4">
                            <button type="submit" class="btn-gca">Guardar Cambios</button>
                            <a href="ver_eventos.php" class="btn-outline-gca">Cancelar</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </main>
    <?php include "includes/footer.php"; ?>
