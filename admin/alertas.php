<?php
require_once __DIR__ . "/../includes/init.php";
checkRole("admin");
require_once "../lib/alertas_helper.php";
require_once "../lib/csrf_helper.php";

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
        <?php include "includes/topbar.php"; ?>

        <div class="content-area">
            <?php $noLeidas = count(array_filter($alertas, fn($x) => !$x['leido'])); ?>
            <div class="section-header">
                <h4><i class="bi bi-bell" aria-hidden="true"></i> Notificaciones
                    <?php if ($noLeidas > 0): ?><span class="gca-badge is-warn"><?= $noLeidas ?> sin leer</span><?php endif; ?>
                </h4>
                <?php if ($noLeidas > 0): ?>
                    <form method="POST">
                        <?= campo_csrf() ?>
                        <button type="submit" name="marcar_todas" class="btn-outline-gca btn-gca-sm">
                            <i class="bi bi-check2-all" aria-hidden="true"></i> Marcar todas como leídas
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (count($alertas) === 0): ?>
                <div class="gca-card empty-state">
                    <i class="bi bi-bell-slash" aria-hidden="true"></i>
                    <h3>Sin alertas</h3>
                    <p>No hay notificaciones en el sistema.</p>
                </div>
            <?php else: ?>
                <div class="stack">
                    <?php foreach ($alertas as $a):
                        $esRiesgo = $a['tipo'] === 'riesgo_academico';
                        $icono = $esRiesgo ? 'bi-exclamation-triangle' : 'bi-info-circle';
                    ?>
                        <article class="gca-card alert-card <?= $a['leido'] ? 'is-read' : 'is-unread' ?> <?= $esRiesgo ? 'is-risk' : '' ?>">
                            <span class="alert-card-ico" aria-hidden="true"><i class="bi <?= $icono ?>"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <h2>
                                    <?= htmlspecialchars($a['titulo']) ?>
                                    <?php if (!$a['leido']): ?><span class="visually-hidden"> (sin leer)</span><?php endif; ?>
                                </h2>
                                <p><?= nl2br(htmlspecialchars($a['mensaje'])) ?></p>
                                <div class="alert-card-meta">
                                    <span><i class="bi bi-clock me-1" aria-hidden="true"></i><?= date("d/m/Y H:i", strtotime($a['created_at'])) ?></span>
                                    <?php if ($esRiesgo): ?>
                                        <span class="gca-badge is-bad">Riesgo académico</span>
                                    <?php endif; ?>
                                    <?php if (!$a['leido']): ?>
                                        <form method="POST" class="d-inline">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="alerta_id" value="<?= $a['id'] ?>">
                                            <button type="submit" name="marcar_leida" class="link-btn">Marcar como leída</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="gca-badge">Leída</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include "includes/footer.php"; ?>
