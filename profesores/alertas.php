<?php
require_once __DIR__ . '/../includes/init.php';
checkRole('profesor');
require_once "../lib/alertas_helper.php";
require_once "../lib/csrf_helper.php";


$usuario_id = $_SESSION["id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida'])
    && validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    marcarAlertaLeida($conexion, $_POST['alerta_id'] ?? 0, $usuario_id, 'profesor');
    unset($_SESSION['_sidebar_prof']);   // la campana del menú se recalcula ya
    header("Location: alertas.php");
    exit;
}

$alertas = obtenerAlertas($conexion, $usuario_id, 'profesor');

$pageTitle = "Notificaciones";
include "includes/header.php";
?>

<body>
    <div id="overlay" onclick="closeSidebar()"></div>
    <?php include "includes/sidebar.php"; ?>

    <main id="main">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar menú" aria-controls="sidebar"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="breadcrumb-bar">
                <h5><i class="ti ti-bell"></i> Notificaciones</h5>
                <p>Panel del Profesor &nbsp;/&nbsp; Alertas</p>
            </div>
        </div>

        <div class="content-area">
            <?php if (count($alertas) === 0): ?>
                <div class="gca-card empty-state p-5">
                    <i class="bi bi-bell-slash" style="font-size:52px;color:#ddd;"></i>
                    <h4 style="font-family:'Cormorant Garamond',serif;color:#444;font-weight:700;">Sin notificaciones</h4>
                    <p class="text-muted mb-0">No tienes notificaciones pendientes.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($alertas as $a):
                        $icono = $a['tipo'] === 'riesgo_academico' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
                        $color = $a['tipo'] === 'riesgo_academico' ? '#dc3545' : 'var(--gold-dark)';
                    ?>
                        <div class="gca-card p-3" style="<?= $a['leido'] ? 'opacity:0.7;' : 'border-left:4px solid ' . $color ?>">
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
                                        <small style="color:var(--text-muted);">
                                            <i class="bi bi-clock me-1"></i><?= date("d/m/Y H:i", strtotime($a['created_at'])) ?>
                                        </small>
                                        <?php if ($a['tipo'] === 'riesgo_academico'): ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size:10px;">Riesgo</span>
                                        <?php endif; ?>
                                        <?php if (!$a['leido']): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="alerta_id" value="<?= (int) $a['id'] ?>">
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
