<?php $__page = basename($_SERVER['PHP_SELF'] ?? 'index.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gimnasio Castillo Americano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <noscript><style>[data-aos]{opacity:1 !important;transform:none !important;}</style></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <style>
        :root { --gca-gold: #c9a24d; --gca-gold-hover: #b8933f; --gca-dark: #0f0f0f; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* ─────────── Entrance keyframes ─────────── */
        @keyframes navDrop   { from { opacity:0; transform:translateY(-100%); } to { opacity:1; transform:translateY(0); } }
        @keyframes tbDrop    { from { opacity:0; transform:translateY(-100%); } to { opacity:1; transform:translateY(0); } }
        @keyframes linkIn    { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes shimmer   { to { background-position: 200% center; } }
        @keyframes brandIn   { from { opacity:0; transform:translateX(-14px); } to { opacity:1; transform:translateX(0); } }

        /* ─────────── Top bar ─────────── */
        .top-bar {
            background: var(--gca-dark);
            color: #e6e6e6;
            font-size: 13px;
            border-bottom: 2px solid var(--gca-gold);
            letter-spacing:.3px;
            animation: tbDrop .6s cubic-bezier(.22,1,.36,1) both;
            overflow: hidden;
        }
        .top-bar__ribbon-inner { display:flex; gap:18px; align-items:center; flex-wrap:wrap; }
        .top-bar .tb-item { display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .top-bar .tb-sep { color:var(--gca-gold); opacity:.5; }
        .top-bar .tb-slogan {
            font-weight:700;
            background: linear-gradient(90deg, var(--gca-gold) 20%, #fff4cf 50%, var(--gca-gold) 80%);
            background-size: 200% auto;
            -webkit-background-clip:text; background-clip:text; color:transparent;
            animation: shimmer 5s linear infinite;
        }

        /* ─────────── Navbar ─────────── */
        .site-nav {
            background:#fff;
            border:1px solid transparent;
            border-bottom:3px solid var(--gca-gold);
            position: sticky; top:0; z-index:1030;
            padding:.7rem 0;
            max-width:100%;
            animation: navDrop .7s cubic-bezier(.22,1,.36,1) both;
            transition: padding .38s cubic-bezier(.22,1,.36,1), box-shadow .38s ease,
                        background .38s ease, border-radius .38s ease, border-color .38s ease,
                        max-width .45s cubic-bezier(.22,1,.36,1), top .38s ease;
        }
        /* ── Header flotante al hacer scroll ── */
        .site-nav.scrolled {
            top:14px;
            max-width: min(1160px, calc(100% - 28px));
            margin-left:auto; margin-right:auto;
            padding:.2rem 0;
            border-radius:999px;
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(14px) saturate(150%);
            -webkit-backdrop-filter: blur(14px) saturate(150%);
            box-shadow: 0 16px 44px rgba(0,0,0,.17), 0 2px 8px rgba(0,0,0,.06);
            border:1px solid rgba(201,162,77,.4);
        }
        .site-nav.scrolled .container { padding-left:26px; padding-right:14px; }
        @media (max-width: 991.98px) {
            .site-nav.scrolled { top:10px; border-radius:18px; max-width:calc(100% - 20px); }
            .site-nav.scrolled .container { padding-left:16px; padding-right:10px; }
        }
        .navbar-brand { display:flex; align-items:center; gap:12px; animation: brandIn .7s ease .1s both; }
        .navbar-brand img { height:48px; transition: transform .5s cubic-bezier(.22,1,.36,1), height .3s ease; }
        .navbar-brand:hover img { transform: rotate(-8deg) scale(1.08); }
        .site-nav.scrolled .navbar-brand img { height:38px; }
        .brand-text {
            font-family:'Playfair Display', serif; font-weight:700; color:var(--gca-dark);
            line-height:1.05; font-size:1.05rem;
            max-width: 340px; overflow: hidden; white-space: nowrap;
            transition: max-width .38s cubic-bezier(.22,1,.36,1), opacity .3s ease, font-size .3s ease;
        }
        /* Al flotar: se oculta el nombre y queda solo el escudo junto al menú */
        .site-nav.scrolled .brand-text { max-width: 0; opacity: 0; margin: 0; }
        .site-nav.scrolled .navbar-brand { gap: 0; }

        .navbar-nav { margin-left:auto; margin-right:16px; }
        .site-nav .nav-link {
            color:#2b2b2b; font-weight:500; padding:8px 14px; position:relative;
            transition: color .25s ease;
        }
        .site-nav .nav-link::after {
            content:''; position:absolute; left:14px; right:14px; bottom:2px; height:2px;
            background:var(--gca-gold); border-radius:2px;
            transform:scaleX(0); transform-origin:left; transition: transform .3s cubic-bezier(.22,1,.36,1);
        }
        .site-nav .nav-link:hover { color:var(--gca-gold-hover); }
        .site-nav .nav-link:hover::after,
        .site-nav .nav-link.active::after { transform:scaleX(1); }
        .site-nav .nav-link.active { color:var(--gca-gold-hover); font-weight:600; }

        /* stagger de links (desktop) */
        @media (min-width: 992px) {
            .site-nav .navbar-nav > .nav-item { animation: linkIn .5s ease both; }
            .site-nav .navbar-nav > .nav-item:nth-child(1){ animation-delay:.25s; }
            .site-nav .navbar-nav > .nav-item:nth-child(2){ animation-delay:.31s; }
            .site-nav .navbar-nav > .nav-item:nth-child(3){ animation-delay:.37s; }
            .site-nav .navbar-nav > .nav-item:nth-child(4){ animation-delay:.43s; }
            .site-nav .navbar-nav > .nav-item:nth-child(5){ animation-delay:.49s; }
            .site-nav .navbar-nav > .nav-item:nth-child(6){ animation-delay:.55s; }
            .site-nav .navbar-nav > .nav-item:nth-child(7){ animation-delay:.61s; }
            .site-nav .navbar-nav > .nav-item:nth-child(8){ animation-delay:.67s; }
        }

        /* ─────────── CTA button ─────────── */
        .btn-gca {
            background:var(--gca-gold); color:#000; border-radius:24px; padding:8px 20px;
            border:none; font-weight:600; position:relative; overflow:hidden;
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
        }
        .btn-gca:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(201,162,77,.45); background:var(--gca-gold-hover); color:#000; }
        .btn-gca::before {
            content:''; position:absolute; top:0; left:-130%; width:55%; height:100%;
            background:linear-gradient(120deg, transparent, rgba(255,255,255,.55), transparent);
            transform:skewX(-20deg); transition:left .6s ease;
        }
        .btn-gca:hover::before { left:150%; }

        /* ─────────── CTA de acceso (delgado y elegante) ─────────── */
        .nav-cta {
            display:inline-flex; align-items:center; gap:8px;
            background:transparent; color:var(--gca-dark);
            border:1.5px solid var(--gca-gold);
            border-radius:40px; padding:5px 17px;
            font-weight:500; font-size:.9rem; letter-spacing:.2px;
            text-decoration:none; white-space:nowrap; line-height:1.4;
            transition: background .28s ease, color .28s ease, box-shadow .28s ease, transform .28s ease;
        }
        .nav-cta i { color:var(--gca-gold); font-size:1rem; transition: color .28s ease, transform .28s ease; }
        .nav-cta:hover {
            background:var(--gca-gold); color:#111;
            box-shadow:0 8px 20px rgba(201,162,77,.4); transform:translateY(-1px);
        }
        .nav-cta:hover i { color:#111; transform:translateX(2px); }

        /* ─────────── Hamburguesa animada ─────────── */
        .navbar-toggler { border:none; padding:6px; box-shadow:none !important; }
        .navbar-toggler:focus { box-shadow:none; }
        .hamburger { width:26px; height:20px; position:relative; display:inline-block; }
        .hamburger span {
            position:absolute; left:0; width:100%; height:3px; border-radius:3px;
            background:var(--gca-dark); transition: transform .35s ease, opacity .25s ease, top .35s ease;
        }
        .hamburger span:nth-child(1){ top:0; }
        .hamburger span:nth-child(2){ top:8.5px; }
        .hamburger span:nth-child(3){ top:17px; }
        .navbar-toggler[aria-expanded="true"] .hamburger span:nth-child(1){ top:8.5px; transform:rotate(45deg); }
        .navbar-toggler[aria-expanded="true"] .hamburger span:nth-child(2){ opacity:0; }
        .navbar-toggler[aria-expanded="true"] .hamburger span:nth-child(3){ top:8.5px; transform:rotate(-45deg); }

        /* ─────────── Menú móvil ─────────── */
        @media (max-width: 991.98px) {
            .navbar-nav { margin:8px 0 4px; }
            .site-nav .nav-link { padding:12px 6px; border-bottom:1px solid #f1f1f1; }
            .site-nav .nav-link::after { display:none; }
            .site-nav .nav-link.active { padding-left:12px; border-left:3px solid var(--gca-gold); }
            #menu .nav-cta { width:100%; justify-content:center; margin-top:12px; padding:10px 17px; }
            /* aparición en cascada al abrir */
            #menu.show .nav-item, #menu.show .navbar-nav ~ .nav-cta { animation: linkIn .4s ease both; }
            #menu.show .nav-item:nth-child(1){ animation-delay:.04s; }
            #menu.show .nav-item:nth-child(2){ animation-delay:.09s; }
            #menu.show .nav-item:nth-child(3){ animation-delay:.14s; }
            #menu.show .nav-item:nth-child(4){ animation-delay:.19s; }
            #menu.show .nav-item:nth-child(5){ animation-delay:.24s; }
            #menu.show .nav-item:nth-child(6){ animation-delay:.29s; }
            #menu.show .nav-item:nth-child(7){ animation-delay:.34s; }
            #menu.show .nav-item:nth-child(8){ animation-delay:.39s; }
        }

        /* ─────────── Top bar responsive ─────────── */
        @media (max-width: 576px) {
            .top-bar { font-size:11.5px; }
            .top-bar__ribbon-inner { justify-content:center; gap:10px; }
            .top-bar .tb-email, .top-bar .tb-sep--email { display:none; }
        }
        @media (max-width: 576px) {
            .navbar-brand img { height:40px; }
            .brand-text { font-size:.9rem; max-width:180px; }
        }

        /* ─────────── Accesibilidad ─────────── */
        @media (prefers-reduced-motion: reduce) {
            .top-bar, .site-nav, .navbar-brand, .site-nav .navbar-nav > .nav-item,
            #menu.show .nav-item, .tb-slogan { animation: none !important; }
            .navbar-brand img, .btn-gca, .site-nav .nav-link::after { transition: none !important; }
        }
    </style>
</head>
<body>

<div class="top-bar py-2">
    <div class="container">
        <div class="top-bar__ribbon-inner">
            <span class="tb-item tb-phone">📞 321 654 8235</span>
            <span class="tb-sep tb-sep--email">|</span>
            <span class="tb-item tb-email">✉️ gimnasiocastilloamericano@gmail.com</span>
            <span class="tb-sep">|</span>
            <span class="tb-item tb-slogan">"Educación con excelencia"</span>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/escudo-gca.png" alt="GCA">
            <span class="brand-text">Gimnasio Castillo Americano</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Menú">
            <span class="hamburger"><span></span><span></span><span></span></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= $__page==='index.php'?'active':'' ?>" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='institucion.php'?'active':'' ?>" href="institucion.php">Institución</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='admisiones.php'?'active':'' ?>" href="admisiones.php">Admisiones</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='noticias.php'?'active':'' ?>" href="noticias.php">Noticias</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='docentes.php'?'active':'' ?>" href="docentes.php">Docentes</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='calendario.php'?'active':'' ?>" href="calendario.php">Calendario</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='servicios.php'?'active':'' ?>" href="servicios.php">Servicios</a></li>
                <li class="nav-item"><a class="nav-link <?= $__page==='contacto.php'?'active':'' ?>" href="contacto.php">Contacto</a></li>
            </ul>
            <a href="login.php" class="nav-cta">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Acceso al Sistema</span>
            </a>
        </div>
    </div>
</nav>

<script>
(function () {
    var nav = document.querySelector('.site-nav');
    if (!nav) return;
    var toggle = function () { nav.classList.toggle('scrolled', window.scrollY > 20); };
    toggle();
    window.addEventListener('scroll', toggle, { passive: true });
})();
</script>

<main>
