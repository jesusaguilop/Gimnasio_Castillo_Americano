<?php
require_once __DIR__ . '/nav.php';
$__gcaCss = __DIR__ . '/../../assets/css/admin.css';
$__gcaJs  = __DIR__ . '/../../assets/js/admin.js';
$__gcaVer = (int) @filemtime($__gcaCss) . '-' . (int) @filemtime($__gcaJs);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel') ?> | GCA Admin</title>
    <link rel="icon" href="../assets/img/logo_gca.png">
    <script>
        /* Tema y estado del menú antes de pintar (evita el parpadeo).
           Sin preferencia guardada se sigue la del sistema operativo. */
        (function () {
            var d = document.documentElement;
            try {
                var s = localStorage.getItem('gca-dark-mode');
                var dark = s === null ? window.matchMedia('(prefers-color-scheme: dark)').matches : s === 'true';
                if (dark) { d.classList.add('dark-mode'); d.setAttribute('data-bs-theme', 'dark'); }
                if (localStorage.getItem('gca-sidebar-mini') === 'true') d.classList.add('sb-mini');
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css?v=<?= $__gcaVer ?>" rel="stylesheet">
</head>
