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

// Marcar todas las alertas visibles para este admin como leídas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_todas'])) {
    if (validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        $conexion->prepare("UPDATE alertas SET leido = TRUE WHERE (para_rol = 'admin' OR para_usuario_id = ?) AND leido = FALSE")
                 ->execute([$usuario_id]);
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
    LIMIT 100
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
            <button type="button" class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5 role="heading" aria-level="1"><i class="ti ti-bell" aria-hidden="true"></i> Alertas del Sistema</h5>
                <p>Panel Administrativo &nbsp;/&nbsp; Notificaciones</p>
            </div>
        </div>

        <div class="content-area">
            <?php if (count($alertas) === 0): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-bell-slash" style="font-size:52px;color:var(--text-muted);" aria-hidden="true"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:var(--text-primary);font-weight:700;">Sin alertas</h4>
                    <p class="text-muted mb-0">No hay notificaciones en el sistema.</p>
                </div>
            <?php else: ?>
                <?php if (count(array_filter($alertas, fn($x) => !$x['leido'])) > 0): ?>
                    <form method="POST" class="mb-3 text-end">
                        <?= campo_csrf() ?>
                        <button type="submit" name="marcar_todas" class="btn-outline-gca btn btn-sm">
                            <i class="bi bi-check2-all" aria-hidden="true"></i> Marcar todas como leídas
                        </button>
                    </form>
                <?php endif; ?>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($alertas as $a):
                        $icono = $a['tipo'] === 'riesgo_academico' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
                        $color = $a['tipo'] === 'riesgo_academico' ? '#dc3545' : 'var(--gold-text)';
                    ?>
                        <div class="gca-card p-3 <?= $a['leido'] ? '' : '' ?>" style="<?= $a['leido'] ? 'opacity:0.7;' : 'border-left:4px solid ' . $color ?>">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi <?= $icono ?>" style="font-size:20px;color:<?= $color ?>;margin-top:2px;" aria-hidden="true"></i>
                                <div style="flex:1;min-width:0;">
                                    <h2 style="font-weight:700;font-size:14px;margin:0 0 2px;color:var(--text-primary);">
                                        <?= htmlspecialchars($a['titulo']) ?>
                                        <?php if (!$a['leido']): ?><span class="visually-hidden"> (sin leer)</span><?php endif; ?>
                                    </h2>
                                    <p style="font-size:13px;color:var(--text-secondary);margin:0 0 6px;">
                                        <?= nl2br(htmlspecialchars($a['mensaje'])) ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-3">
                                        <small style="color:var(--text-muted);">
                                            <i class="bi bi-clock me-1" aria-hidden="true"></i><?= date("d/m/Y H:i", strtotime($a['created_at'])) ?>
                                        </small>
                                        <?php if ($a['tipo'] === 'riesgo_academico'): ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size:10px;">Riesgo</span>
                                        <?php endif; ?>
                                        <?php if (!$a['leido']): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="alerta_id" value="<?= $a['id'] ?>">
                                                <button type="submit" name="marcar_leida" class="btn btn-sm btn-link text-decoration-none p-0"
                                                    style="font-size:12px;color:var(--gold-text);">Marcar como leída</button>
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
