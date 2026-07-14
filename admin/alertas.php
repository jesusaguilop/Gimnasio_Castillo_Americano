<?php
session_start();
require_once "../config/database.php";
require_once "../lib/alertas_helper.php";
require_once "../lib/csrf_helper.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$usuario_id = $_SESSION["id"];

// Mark as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida'])) {
    if (validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        marcarAlertaLeida($conexion, $_POST['alerta_id']);
    }
    header("Location: alertas.php");
    exit;
}

$stmt = $conexion->prepare("
    SELECT a.*, 
           CASE WHEN a.para_usuario_id IS NOT NULL THEN u.nombre ELSE NULL END AS usuario_nombre
    FROM alertas a
    LEFT JOIN usuarios u ON a.para_usuario_id = u.id
    WHERE a.para_rol = 'admin' OR a.para_usuario_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$usuario_id]);
$alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Alertas del Sistema";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-bell"></i> Alertas del Sistema</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Notificaciones</p>
            </div>
        </div>

        <div class="content-area">
            <?php if (count($alertas) === 0): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-bell-slash" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin alertas</h4>
                    <p class="text-muted mb-0">No hay notificaciones en el sistema.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($alertas as $a):
                        $icono = $a['tipo'] === 'riesgo_academico' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
                        $color = $a['tipo'] === 'riesgo_academico' ? '#dc3545' : 'var(--gold-dark)';
                    ?>
                        <div class="gca-card p-3 <?= $a['leido'] ? '' : '' ?>" style="<?= $a['leido'] ? 'opacity:0.7;' : 'border-left:4px solid ' . $color ?>">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi <?= $icono ?>" style="font-size:20px;color:<?= $color ?>;margin-top:2px;"></i>
                                <div style="flex:1;min-width:0;">
                                    <h6 style="font-weight:700;font-size:14px;margin:0 0 2px;color:#1a1a1a;">
                                        <?= htmlspecialchars($a['titulo']) ?>
                                    </h6>
                                    <p style="font-size:13px;color:#666;margin:0 0 6px;">
                                        <?= nl2br(htmlspecialchars($a['mensaje'])) ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-3">
                                        <small style="color:#999;">
                                            <i class="bi bi-clock me-1"></i><?= date("d/m/Y H:i", strtotime($a['created_at'])) ?>
                                        </small>
                                        <?php if ($a['tipo'] === 'riesgo_academico'): ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size:10px;">Riesgo</span>
                                        <?php endif; ?>
                                        <?php if (!$a['leido']): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="alerta_id" value="<?= $a['id'] ?>">
                                                <button type="submit" name="marcar_leida" class="btn btn-sm btn-link text-decoration-none p-0"
                                                    style="font-size:11px;color:var(--gold-dark);">Marcar como leída</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
