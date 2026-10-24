<?php
require_once __DIR__ . '/nav.php';

$gcaPagina = gca_admin_pagina_actual($pageTitle ?? null);
$gcaNombreAdmin = $_SESSION["nombre"] ?? "Administrador";
$gcaIniciales = '';
foreach (preg_split('/\s+/', trim($gcaNombreAdmin)) as $__p) {
    if ($__p !== '' && mb_strlen($gcaIniciales) < 2) {
        $gcaIniciales .= mb_strtoupper(mb_substr($__p, 0, 1));
    }
}

// Alertas sin leer (mismo criterio que alertas.php). Se calcula una vez aquí
// y la barra superior reutiliza el valor.
$num_alertas_admin = 0;
try {
    $stmtA = $conexion->prepare("SELECT COUNT(*) FROM alertas WHERE (para_rol = 'admin' OR para_usuario_id = ?) AND leido = FALSE");
    $stmtA->execute([(int) ($_SESSION["id"] ?? 0)]);
    $num_alertas_admin = (int) $stmtA->fetchColumn();
} catch (Exception $e) {}
?>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<nav id="sidebar" aria-label="Menú del panel">
    <div class="sb-brand">
        <a href="dashboard.php" class="sb-brand-link" aria-label="Ir al resumen del panel">
            <img src="../assets/img/logo_gca.png" alt="" class="sb-logo" onerror="this.style.display='none'">
            <span class="sb-brand-text">
                <strong>Castillo Americano</strong>
                <small>Administración</small>
            </span>
        </a>
        <button type="button" class="sb-close d-lg-none" onclick="closeSidebar()" aria-label="Cerrar menú">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sb-nav">
        <?php foreach (gca_admin_nav() as $gi => [$grupo, $items]): ?>
            <div class="sb-group">
                <div class="sb-group-label" id="sbg-<?= $gi ?>"><?= htmlspecialchars($grupo) ?></div>
                <ul class="sb-list" aria-labelledby="sbg-<?= $gi ?>">
                    <?php foreach ($items as [$href, $texto, $icono]):
                        $activo = $href === $gcaPagina['activo']; ?>
                        <li>
                            <a href="<?= $href ?>" class="sb-link<?= $activo ? ' is-active' : '' ?>"
                               <?= $activo ? 'aria-current="page"' : '' ?> title="<?= htmlspecialchars($texto) ?>">
                                <i class="bi <?= $icono ?>" aria-hidden="true"></i>
                                <span class="sb-text"><?= htmlspecialchars($texto) ?></span>
                                <?php if ($href === 'alertas.php' && $num_alertas_admin > 0): ?>
                                    <span class="sb-count"><?= $num_alertas_admin ?><span class="visually-hidden"> sin leer</span></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="sb-foot">
        <a href="../index.php" class="sb-link" title="Ver el sitio web público">
            <i class="bi bi-globe2" aria-hidden="true"></i>
            <span class="sb-text">Ver sitio web</span>
            <i class="bi bi-box-arrow-up-right sb-ext" aria-hidden="true"></i>
        </a>
        <button type="button" class="sb-link sb-collapse d-none d-lg-flex" onclick="toggleSidebarMini()"
                aria-pressed="false" title="Contraer menú">
            <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            <span class="sb-text">Contraer menú</span>
        </button>
    </div>
</nav>
