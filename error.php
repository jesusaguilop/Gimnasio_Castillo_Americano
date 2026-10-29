<?php
/**
 * Página de error propia (404, 403, 500). La usa .htaccess (ErrorDocument)
 * y puede incluirse desde PHP: $codigoError = 404; require 'error.php';
 * Rutas absolutas: Apache la sirve en la URL que falló (p. ej. /admin/xyz).
 */
require_once __DIR__ . '/lib/asset_helper.php';

$codigo = (int) ($codigoError ?? ($_GET['c'] ?? http_response_code() ?: 404));
if (!in_array($codigo, [403, 404, 500], true)) $codigo = 404;
http_response_code($codigo);

$textos = [
    404 => ['Esta página no existe', 'Puede que el enlace esté mal escrito o que la página se haya movido. Revise la dirección o vuelva al inicio.'],
    403 => ['No tiene acceso a esta página', 'Esta sección es solo para ciertas cuentas. Si cree que debería poder entrar, inicie sesión con su usuario del colegio.'],
    500 => ['Algo salió mal de nuestro lado', 'Tuvimos un problema al mostrar la página. Intente de nuevo en un momento; si sigue pasando, avise a la administración del colegio.'],
];
[$titulo, $detalle] = $textos[$codigo];

// Si hay sesión, se ofrece volver al panel de su rol
$panel = null;
if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
    @session_start(['read_and_close' => true]);
}
$rutas = ['admin' => ['/admin/dashboard.php', 'Ir a mi panel'], 'profesor' => ['/profesores/dashboard.php', 'Ir a mi panel'], 'padre' => ['/padres/dashboard.php', 'Ir a Familia GCA']];
if (!empty($_SESSION['rol']) && isset($rutas[$_SESSION['rol']])) $panel = $rutas[$_SESSION['rol']];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="light dark">
    <title><?= $codigo ?> · <?= htmlspecialchars($titulo) ?> · Gimnasio Castillo Americano</title>
    <link rel="icon" href="/<?= asset('assets/img/favicon.ico') ?>" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg: #f6f1e6; --paper: #fffdf8; --ink: #14120e; --text: #4a4438; --muted: #6b6250; --line: #e6ddc9; --gold: #c9a24d; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f0e0b; --paper: #191712; --ink: #efe6cf; --text: #c9c0ad; --muted: #9e957f; --line: #2e2a22; }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 32px 20px;
            background:
                radial-gradient(ellipse 60% 45% at 50% 0%, rgba(201, 162, 77, .12), transparent 70%),
                var(--bg);
            color: var(--text); font-family: 'Inter', system-ui, sans-serif; line-height: 1.6;
            overflow-x: hidden;
        }
        main { position: relative; width: 100%; max-width: 560px; text-align: center; isolation: isolate; padding-top: 24px; }
        /* León del escudo original como marca de agua, detrás del código */
        .marca {
            position: absolute; z-index: -1; left: 50%; top: -30px;
            width: min(360px, 80vw); aspect-ratio: 349 / 420;
            background: url('/<?= asset('assets/img/escudo/leon-marca.png') ?>') center / contain no-repeat;
            opacity: .13; transform: translateX(-44%);
            /* El estandarte corta al león en línea recta por la izquierda: se difumina */
            -webkit-mask: linear-gradient(90deg, transparent 0%, #000 26%);
                    mask: linear-gradient(90deg, transparent 0%, #000 26%);
            animation: respira 7s ease-in-out infinite;
        }
        @media (prefers-color-scheme: dark) { .marca { opacity: .1; } }
        @keyframes respira { 0%, 100% { transform: translateX(-44%); } 50% { transform: translateX(-44%) rotate(-2deg) scale(1.02); } }
        .codigo {
            font-family: 'Playfair Display', Georgia, serif; font-weight: 700; color: var(--ink);
            font-size: clamp(76px, 18vw, 132px); line-height: 1; letter-spacing: -.03em; margin: 60px 0 0;
        }
        .codigo span { color: var(--gold); }
        .raya { width: 64px; height: 2px; background: var(--gold); margin: 22px auto; border-radius: 2px; }
        h1 { font-family: 'Playfair Display', Georgia, serif; color: var(--ink); font-size: clamp(22px, 4.6vw, 28px); line-height: 1.25; margin: 0 0 10px; }
        p { margin: 0 auto; max-width: 440px; font-size: 15px; }
        .acciones { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 28px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 11px;
            font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid var(--ink);
            background: var(--ink); color: var(--bg); cursor: pointer; font-family: inherit;
        }
        .btn.sec { background: var(--paper); color: var(--ink); border-color: var(--line); }
        .btn.sec:hover { border-color: var(--ink); }
        .btn:focus-visible, .pie:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
        .pie {
            display: inline-flex; align-items: center; gap: 10px; margin-top: 44px;
            font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); text-decoration: none;
        }
        .pie img { height: 30px; width: auto; }
        .pie:hover { color: var(--ink); }
        .oculto { position: absolute; left: -9999px; }
        @media (prefers-reduced-motion: reduce) { .marca { animation: none; } }
    </style>
</head>
<body>
<main>
    <div class="marca" aria-hidden="true"></div>
    <p class="codigo" aria-hidden="true"><?= substr((string) $codigo, 0, 1) ?><span><?= substr((string) $codigo, 1, 1) ?></span><?= substr((string) $codigo, 2) ?></p>
    <div class="raya" aria-hidden="true"></div>
    <h1><?= htmlspecialchars($titulo) ?><span class="oculto"> (error <?= $codigo ?>)</span></h1>
    <p><?= htmlspecialchars($detalle) ?></p>
    <div class="acciones">
        <?php if ($panel): ?>
            <a class="btn" href="<?= $panel[0] ?>"><i class="bi bi-grid-1x2" aria-hidden="true"></i> <?= $panel[1] ?></a>
            <a class="btn sec" href="/index.php">Sitio del colegio</a>
        <?php else: ?>
            <a class="btn" href="/index.php"><i class="bi bi-house" aria-hidden="true"></i> Ir al inicio</a>
            <?php if ($codigo === 403): ?>
                <a class="btn sec" href="/login.php">Iniciar sesión</a>
            <?php endif; ?>
        <?php endif; ?>
        <button type="button" class="btn sec" onclick="history.length > 1 ? history.back() : location.assign('/index.php')"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</button>
    </div>
    <a class="pie" href="/index.php"><img src="/<?= asset('assets/img/escudo/escudo.png') ?>" alt="" width="398" height="680"> Gimnasio Castillo Americano</a>
</main>
</body>
</html>
