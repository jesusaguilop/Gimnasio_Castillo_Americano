<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
require_once "../lib/csrf_helper.php";
require_once "../lib/mail_helper.php";

$mensaje = "";
$tipo = "";

// Find boletines for students without a parent email sent
$boletines = $conexion->query("
    SELECT bp.id, bp.estudiante_id, bp.ruta_pdf, bp.periodo, bp.year,
           e.nombre AS estudiante_nombre,
           u.id AS padre_id, u.nombre AS padre_nombre, u.email AS padre_email
    FROM boletines_pdf bp
    JOIN estudiantes e ON bp.estudiante_id = e.id
    LEFT JOIN usuarios u ON e.padre_id = u.id
    WHERE e.padre_id IS NOT NULL AND u.email IS NOT NULL AND u.email != ''
    ORDER BY bp.year DESC, bp.periodo, e.nombre
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enviar"])) {
    if (!validar_token_csrf($_POST["_csrf_token"] ?? "")) {
        $mensaje = "Error de seguridad.";
        $tipo = "danger";
    } else {
        $boletin_id = (int)$_POST["boletin_id"];
        $stmt = $conexion->prepare("SELECT bp.*, e.nombre AS estudiante_nombre, u.nombre AS padre_nombre, u.email
            FROM boletines_pdf bp
            JOIN estudiantes e ON bp.estudiante_id = e.id
            JOIN usuarios u ON e.padre_id = u.id
            WHERE bp.id = ?");
        $stmt->execute([$boletin_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($b && $b["email"]) {
            $ruta = realpath(__DIR__ . "/../" . $b["ruta_pdf"]);
            if ($ruta && file_exists($ruta)) {
                $ok = enviar_boletin_por_email($b["email"], $b["padre_nombre"], $ruta, "Boletín de {$b['estudiante_nombre']}");
                if ($ok) {
                    $mensaje = "Boletín enviado a {$b['padre_nombre']} ({$b['email']}).";
                    $tipo = "success";
                } else {
                    $mensaje = "Error al enviar el correo. Revise el log.";
                    $tipo = "danger";
                }
            } else {
                $mensaje = "El archivo PDF no existe en: " . ($b["ruta_pdf"] ?? "?");
                $tipo = "danger";
            }
        } else {
            $mensaje = "No se encontró el boletín o el padre no tiene email.";
            $tipo = "danger";
        }
    }
}

$pageTitle = "Enviar Boletines";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5>Enviar Boletines a Padres</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Boletines</p>
            </div>
        </div>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="gca-card p-4">

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table gca-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Periodo</th>
                                        <th>Año</th>
                                        <th>Padre</th>
                                        <th>Email</th>
                                        <th style="width:100px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($boletines) === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No hay boletines generados con padres asignados.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($boletines as $b): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($b['estudiante_nombre']) ?></td>
                                                <td><?= htmlspecialchars($b['periodo']) ?></td>
                                                <td><?= htmlspecialchars($b['year']) ?></td>
                                                <td><?= htmlspecialchars($b['padre_nombre'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($b['padre_email'] ?? '—') ?></td>
                                                <td>
                                                    <form method="POST">
                                                        <?= campo_csrf() ?>
                                                        <input type="hidden" name="boletin_id" value="<?= $b['id'] ?>">
                                                        <button type="submit" name="enviar" class="btn btn-sm btn-gca"
                                                            onclick="return confirm('¿Enviar boletín a <?= htmlspecialchars($b['padre_nombre'] ?? '') ?>?')">
                                                            <i class="bi bi-send me-1"></i>Enviar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
</body>
</html>
