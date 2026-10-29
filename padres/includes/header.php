<?php
/**
 * Cabecera del portal de padres. Variables que puede fijar cada página:
 *   $pageTitle  título de la pestaña
 *   $ppActivo   'inicio' | 'avisos' (marca la sección en la navegación)
 * Abre <main>; padres/includes/footer.php lo cierra.
 */
require_once __DIR__ . '/../../lib/asset_helper.php';
require_once __DIR__ . '/../../lib/csrf_helper.php';

$ppActivo = $ppActivo ?? '';
$ppNombre = (string) ($_SESSION['nombre'] ?? 'Familia');
$ppPartes = preg_split('/\s+/', trim($ppNombre)) ?: ['F'];
$ppIni = mb_strtoupper(mb_substr($ppPartes[0], 0, 1) . (isset($ppPartes[1]) ? mb_substr($ppPartes[1], 0, 1) : ''));

// Avisos sin leer (para la campana y la pestaña "Avisos" del celular)
$ppNoLeidas = 0;
try {
    $q = $conexion->prepare("SELECT COUNT(*) FROM alertas WHERE leido = FALSE
                             AND (para_usuario_id = ? OR (para_rol = 'padre' AND para_usuario_id IS NULL))");
    $q->execute([(int) userId()]);
    $ppNoLeidas = (int) $q->fetchColumn();
} catch (Throwable $e) {}

$ppCur = fn(string $s) => $ppActivo === $s ? ' aria-current="page"' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f6f1e6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f0e0b" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Familia GCA">
    <link rel="manifest" href="../manifest.json">
    <link rel="icon" href="../<?= asset('assets/img/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="../<?= asset('assets/img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" href="../<?= asset('assets/img/apple-touch-icon.png') ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'Inicio') ?> · Familia GCA</title>
    <script>
        /* Tema antes de pintar (evita el parpadeo). Sin preferencia guardada, la del sistema. */
        (function () {
            try {
                var s = localStorage.getItem('gca-dark-mode');
                var oscuro = s === null ? matchMedia('(prefers-color-scheme: dark)').matches : s === 'true';
                if (oscuro) document.documentElement.classList.add('dark-mode');
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../<?= asset('assets/css/padres.css') ?>" rel="stylesheet">
    <link href="../<?= asset('assets/css/gca-ui.css') ?>" rel="stylesheet">
</head>
<body class="pp has-bottom-nav">
<a class="pp-saltar" href="#contenido">Saltar al contenido</a>

<header class="pp-bar">
    <div class="pp-bar-in">
        <a href="dashboard.php" class="pp-marca" aria-label="Familia GCA, inicio">
            <img src="../<?= asset('assets/img/escudo/escudo.png') ?>" alt="" width="398" height="680">
            <span><strong>Familia GCA</strong><small>Gimnasio Castillo Americano</small></span>
        </a>
        <nav class="pp-nav" aria-label="Secciones">
            <a href="dashboard.php"<?= $ppCur('inicio') ?>>Mis hijos</a>
            <a href="avisos.php"<?= $ppCur('avisos') ?>>Avisos</a>
        </nav>
        <div class="pp-acc">
            <a href="avisos.php" class="gca-bell" data-gca-bell data-endpoint="../notificaciones.php?desde_panel=1"
               data-pagina="avisos.php" data-label="Avisos" title="Avisos"
               aria-label="Avisos<?= $ppNoLeidas ? ": $ppNoLeidas sin leer" : '' ?>">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="gca-bell-n" aria-hidden="true"<?= $ppNoLeidas ? '' : ' hidden' ?>><?= $ppNoLeidas > 99 ? '99+' : $ppNoLeidas ?></span>
            </a>
            <div class="pp-cuenta">
                <button type="button" class="pp-avatar-btn" id="ppCuentaBtn" aria-haspopup="menu" aria-expanded="false" aria-controls="ppMenu">
                    <span class="pp-ini" aria-hidden="true"><?= htmlspecialchars($ppIni) ?></span>
                    <span class="nombre"><?= htmlspecialchars($ppNombre) ?></span>
                    <i class="bi bi-chevron-down" aria-hidden="true" style="font-size:11px;color:var(--pp-muted)"></i>
                    <span class="visually-hidden">Menú de la cuenta</span>
                </button>
                <ul class="pp-menu" id="ppMenu" role="menu" hidden>
                    <li class="pp-menu-head" role="none"><strong><?= htmlspecialchars($ppNombre) ?></strong><small>Acudiente</small></li>
                    <li role="none"><a role="menuitem" href="../cambiar_clave.php"><i class="bi bi-key" aria-hidden="true"></i>Cambiar contraseña</a></li>
                    <li role="none"><button type="button" role="menuitem" id="ppTema"><i class="bi bi-moon-stars" aria-hidden="true"></i><span>Modo oscuro</span></button></li>
                    <li role="none"><a role="menuitem" href="../index.php"><i class="bi bi-globe2" aria-hidden="true"></i>Sitio del colegio</a></li>
                    <li role="none"><a role="menuitem" class="salir" href="../auth/logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<main class="pp-main" id="contenido" tabindex="-1">
