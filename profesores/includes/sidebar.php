<?php
$nombre = $_SESSION["nombre"] ?? "Profesor";
$hora = (int) date("H");
if ($hora < 12) $saludo = "Buenos días";
elseif ($hora < 18) $saludo = "Buenas tardes";
else $saludo = "Buenas noches";

// Periodo activo from cached config
$periodo_sidebar = getConfig('periodo_activo') ?? '1';

$currentFile = basename($_SERVER["PHP_SELF"]);
$isActive = fn($file) => $currentFile === $file ? 'active' : '';
?>

<aside id="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/img/logo_gca.png" alt="GCA">
        <div class="sidebar-logo-text">
            <span>Docente GCA</span>
            <small>Panel del Profesor</small>
        </div>
    </div>

    <div class="admin-badge">
        <div class="admin-avatar">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="admin-info">
            <span><?= htmlspecialchars($nombre) ?></span>
            <small>Profesor</small>
            <span class="greeting-badge"><?= $saludo ?></span>
        </div>
    </div>

    <div style="margin:0 18px 8px;display:flex;align-items:center;gap:8px;padding:6px 12px;background:rgba(212,175,55,0.06);border-radius:8px;border:1px solid rgba(212,175,55,0.1);">
        <i class="bi bi-calendar3" style="color:var(--gold);font-size:11px;"></i>
        <span style="color:var(--text-muted);font-size:11px;">Período activo:</span>
        <span style="color:var(--gold);font-weight:700;font-size:12px;"><?= htmlspecialchars($periodo_sidebar) ?></span>
    </div>

    <?php
    // Campana + "¿es director de grupo?" en UNA consulta, cacheada 60 s en la sesión
    // (el menú sale en todas las páginas: antes eran 2 consultas por página).
    // alertas.php borra la caché al marcar una alerta como leída.
    $sb = $_SESSION['_sidebar_prof'] ?? null;
    if (!is_array($sb) || ($sb['t'] ?? 0) < time() - 60 || ($sb['uid'] ?? null) !== ($_SESSION['id'] ?? null)) {
        $sb = ['t' => time(), 'uid' => $_SESSION['id'] ?? null, 'alertas' => 0, 'director' => false];
        try {
            $stmtA = $conexion->prepare("
                SELECT (SELECT COUNT(*) FROM alertas
                         WHERE (para_usuario_id = ? OR (para_rol = 'profesor' AND para_usuario_id IS NULL))
                           AND leido = FALSE) AS alertas,
                       EXISTS (SELECT 1 FROM directores_grupo WHERE profesor_id = ?) AS director
            ");
            $stmtA->execute([$_SESSION['id'], $_SESSION['id']]);
            $row = $stmtA->fetch(PDO::FETCH_ASSOC);
            $sb['alertas'] = (int) $row['alertas'];
            $sb['director'] = (bool) $row['director'];
            $_SESSION['_sidebar_prof'] = $sb;
        } catch (Exception $e) {}
    }
    $num_alertas_prof = $sb['alertas'];
    ?>
    <a href="alertas.php" style="display:flex;align-items:center;gap:10px;margin:4px 18px 8px;padding:9px 14px;border-radius:10px;text-decoration:none;color:var(--text-sidebar-muted);font-size:13px;font-weight:500;transition:all.2s;border:1px solid rgba(212,175,55,0.08);background:rgba(212,175,55,0.03);"
       onmouseover="this.style.background='rgba(212,175,55,0.08)'" onmouseout="this.style.background='rgba(212,175,55,0.03)'">
        <i class="bi bi-bell" style="font-size:15px;"></i>
        Notificaciones
        <?php if ($num_alertas_prof > 0): ?>
            <span style="margin-left:auto;background:#dc3545;color:#fff;font-size:10px;font-weight:700;padding:1px 8px;border-radius:20px;"><?= $num_alertas_prof ?></span>
        <?php endif; ?>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>

        <a href="dashboard.php" class="nav-link-item <?= $isActive('dashboard.php') ?>">
            <i class="nav-icon ti ti-layout-dashboard"></i>
            Dashboard
        </a>

        <div class="nav-section-label">Académico</div>

        <a href="registrar_notas.php" class="nav-link-item <?= $isActive('registrar_notas.php') ?>">
            <i class="nav-icon ti ti-pencil"></i>
            Registrar Notas
        </a>

        <a href="ver_promedio.php" class="nav-link-item <?= $isActive('ver_promedio.php') ?>">
            <i class="nav-icon ti ti-chart-bar"></i>
            Ver Promedios
        </a>

        <?php if ($sb['director']): ?>
        <div class="nav-section-label">Dirección de Grupo</div>

        <a href="direccion_grupo.php" class="nav-link-item <?= $isActive('direccion_grupo.php') ?>">
            <i class="nav-icon ti ti-eye"></i>
            Vista Consolidada
        </a>
        <a href="resultados_anio.php" class="nav-link-item <?= $isActive('resultados_anio.php') ?>">
            <i class="nav-icon ti ti-award"></i>
            Resultados del Año
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <button id="darkModeToggle" class="btn-logout mb-2" style="border-style:dashed;" aria-label="Modo oscuro">
            <i class="bi bi-moon-stars" id="dmIcon"></i>
            <span id="dmLabel">Modo oscuro</span>
        </button>
        <a href="../auth/logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar sesión
        </a>
    </div>
</aside>
