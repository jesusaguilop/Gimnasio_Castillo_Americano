<?php
/* Barra superior común: menú, migas de pan, título, alertas, tema y usuario.
   Usa $gcaPagina, $gcaNombreAdmin, $gcaIniciales y $num_alertas_admin de sidebar.php. */
if (!isset($gcaPagina)) {
    require_once __DIR__ . '/nav.php';
    $gcaPagina = gca_admin_pagina_actual($pageTitle ?? null);
}
$gcaNombreAdmin = $gcaNombreAdmin ?? ($_SESSION["nombre"] ?? "Administrador");
$gcaIniciales = $gcaIniciales ?? 'A';
$num_alertas_admin = $num_alertas_admin ?? 0;
?>
<header class="topbar">
    <button type="button" class="tb-icon toggle-btn" onclick="toggleSidebar()" aria-label="Abrir o cerrar el menú" aria-controls="sidebar" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <div class="tb-title">
        <nav aria-label="Migas de pan">
            <ol class="tb-crumbs">
                <?php $__n = count($gcaPagina['migas']);
                foreach ($gcaPagina['migas'] as $__i => [$__t, $__u]):
                    $__ultima = $__i === $__n - 1; ?>
                    <li<?= $__ultima ? ' aria-current="page"' : '' ?>>
                        <?php if ($__u && !$__ultima): ?>
                            <a href="<?= htmlspecialchars($__u) ?>"><?= htmlspecialchars($__t) ?></a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($__t) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <h1 class="tb-h1"><?= htmlspecialchars($gcaPagina['titulo']) ?></h1>
    </div>

    <div class="tb-actions">
        <a href="alertas.php" class="tb-icon tb-bell" aria-label="Alertas<?= $num_alertas_admin > 0 ? ': ' . $num_alertas_admin . ' sin leer' : '' ?>" title="Alertas">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <?php if ($num_alertas_admin > 0): ?>
                <span class="tb-dot" aria-hidden="true"><?= $num_alertas_admin > 99 ? '99+' : $num_alertas_admin ?></span>
            <?php endif; ?>
        </a>
        <button type="button" id="darkModeToggle" class="tb-icon" aria-pressed="false" aria-label="Activar modo oscuro" title="Cambiar tema">
            <i class="bi bi-moon-stars" id="dmIcon" aria-hidden="true"></i>
        </button>
        <div class="dropdown">
            <button type="button" class="tb-user" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de usuario: <?= htmlspecialchars($gcaNombreAdmin) ?>">
                <span class="tb-avatar" aria-hidden="true"><?= htmlspecialchars($gcaIniciales ?: 'A') ?></span>
                <span class="tb-user-text">
                    <strong><?= htmlspecialchars($gcaNombreAdmin) ?></strong>
                    <small>Administrador</small>
                </span>
                <i class="bi bi-chevron-down tb-caret" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end tb-menu">
                <li class="tb-menu-head">
                    <strong><?= htmlspecialchars($gcaNombreAdmin) ?></strong>
                    <small>Sesión de administrador</small>
                </li>
                <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Resumen</a></li>
                <li><a class="dropdown-item" href="../cambiar_clave.php"><i class="bi bi-key" aria-hidden="true"></i> Cambiar contraseña</a></li>
                <li><a class="dropdown-item" href="../index.php"><i class="bi bi-globe2" aria-hidden="true"></i> Ver sitio web</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger-emphasis" href="../auth/logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Cerrar sesión</a></li>
            </ul>
        </div>
    </div>
</header>
<span id="contenido" tabindex="-1"></span>
