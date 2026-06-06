<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio Castillo Americano | Portal Institucional</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            background-color: var(--gca-gray);
            font-family: 'Inter', "Segoe UI", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6,
        .display-5, .display-4, .display-3, .display-2, .display-1,
        .fw-bold, .fw-900, .section-title, .news-title {
            font-family: 'Playfair Display', 'Inter', serif;
            letter-spacing: -.02em;
        }

        .navbar-brand .brand-text {
            font-family: 'Playfair Display', 'Inter', serif;
            font-weight: 700;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
        }

        main {
            flex: 1;
        }

        /* TOP BAR */
        .top-bar {
            background: var(--gca-dark);
            color: #e6e6e6;
            font-size: 13px;
            border-bottom: 2px solid var(--gca-gold);
        }

        /* NAVBAR */
        .navbar {
            background: #ffffff;
            border-bottom: 3px solid var(--gca-gold);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: var(--gca-dark);
        }

        .navbar-brand img {
            height: 48px;
        }
        .mobile-logo { display: none; }

        .navbar-nav .nav-link {
            color: #333;
            font-weight: 500;
            padding: 10px 10px;
            font-size: 13.5px;
            position: relative;
            white-space: nowrap;
        }

        .navbar-nav .nav-link:hover {
            color: var(--gca-gold);
        }

        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gca-gold);
            transition: .3s;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after {
            width: 70%;
        }

        /* BOTÓN LOGIN */
        .btn-gca {
            background: var(--gca-gold);
            color: #000;
            font-weight: 600;
            border-radius: 30px;
            padding: 8px 22px;
            border: none;
        }

        .btn-gca:hover {
            background: #b8933f;
            color: #000;
        }

        /* ─── Mobile Navbar ─── */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: fixed;
                top: 0; right: -100%;
                width: 300px;
                height: 100vh;
                height: 100dvh;
                background: linear-gradient(180deg, #0f0f0f 0%, #1a1a2e 100%);
                padding: 80px 24px 24px;
                box-shadow: -10px 0 40px rgba(0,0,0,.3);
                transition: right .35s cubic-bezier(.22,1,.36,1);
                z-index: 1050;
                display: flex;
                flex-direction: column;
                overflow-y: auto;
                margin-top: 0;
                border-left: 3px solid var(--gca-gold);
            }
            .navbar-collapse.show,
            .navbar-collapse.collapsing {
                right: 0;
            }
            .navbar-nav {
                flex-direction: column;
                gap: 4px;
            }
            .navbar-nav .nav-link {
                color: #ddd;
                padding: 14px 18px;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 500;
                transition: all .25s ease;
            }
            .navbar-nav .nav-link:hover {
                background: rgba(201,162,77,.1);
                color: var(--gca-gold);
                transform: translateX(6px);
            }
            .navbar-nav .nav-link::after {
                display: none;
            }
            .navbar .btn-gca {
                margin-top: 16px;
                width: 100%;
                text-align: center;
                padding: 12px;
                font-size: 14px;
            }
            .navbar-brand img {
                height: 40px;
            }
            .navbar-toggler {
                border: none;
                padding: 8px;
                z-index: 1060;
                position: relative;
            }
            .navbar-toggler:focus {
                box-shadow: none;
            }
            .navbar-toggler-icon {
                background-image: none;
                width: 26px;
                height: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .navbar-toggler-icon::before,
            .navbar-toggler-icon::after,
            .navbar-toggler-icon span {
                content: '';
                display: block;
                width: 100%;
                height: 2.5px;
                background: #333;
                border-radius: 4px;
                transition: all .3s ease;
            }
            .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon span {
                opacity: 0;
            }
            .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon::before {
                transform: translateY(9px) rotate(45deg);
            }
            .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon::after {
                transform: translateY(-9px) rotate(-45deg);
            }
            .navbar-collapse .close-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 1040; /* behind the off-canvas panel (1050) */
                opacity: 0;
                transition: opacity .35s ease;
                pointer-events: none;
            }
            .navbar-collapse.show .close-overlay,
            .navbar-collapse.collapsing .close-overlay {
                opacity: 1;
                pointer-events: auto;
                z-index: 1040;
            }
            .navbar-collapse .nav-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 28px;
                padding-bottom: 20px;
                border-bottom: 1px solid rgba(255,255,255,.06);
            }
            .navbar-collapse .nav-header img {
                height: 40px;
                width: auto;
            }
            .navbar-collapse .nav-header span {
                color: #fff;
                font-weight: 700;
                font-size: 14px;
                font-family: 'Playfair Display', 'Inter', serif;
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                font-size: 11px;
            }
            .top-bar__ribbon-inner {
                padding: 0 8px;
            }
            .navbar-brand {
                gap: 8px;
                top: 50%;
            }
            .navbar-brand img {
                height: 36px;
            }
            /* mobile logo: hide small shield and brand text, show centered wide logo */
            .mobile-logo { display: block; max-width: 62%; height: auto; }
            .navbar-brand > img:first-child { display: none; }
            .navbar-brand .brand-text { display: none; }
            .navbar .container { position: relative; }
            .navbar-brand { position: absolute; left: 50%; transform: translate(-50%, -50%); }
            /* put toggler on the left for easier thumb reach, keep z-index above overlay */
            .navbar-toggler { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); z-index: 1060; }
            /* hide desktop login button on small screens (moved into collapse) */
            .navbar .btn-gca { display: none; }
        }

        /* CINTA ROTATIVA (RIBBON) */
        .top-bar__ribbon {
            overflow: hidden;
            width: 100%;
        }

        .top-bar__ribbon-inner {
            display: flex;
            align-items: center;
            gap: 32px;
            width: max-content;
            padding: 0 16px;
            flex-shrink: 0;
            animation: ribbonScroll 30s linear infinite;
            will-change: transform;
        }

        .top-bar__ribbon-inner:hover {
            animation-play-state: paused;
        }

        .top-bar__item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            color: #e6e6e6;
            font-size: 13px;
        }

        .top-bar__sep {
            color: var(--gca-gold);
            opacity: 0.4;
            font-weight: 300;
        }

        .top-bar__lema {
            font-weight: 700;
            color: var(--gca-gold);
            letter-spacing: 0.5px;
        }

        @keyframes ribbonScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        @media (max-width: 576px) {
            .top-bar__item { font-size: 11px; }
            .top-bar__ribbon-inner { gap: 20px; animation-duration: 20s; }
        }
    </style>
</head>
<body>

<!-- BARRA SUPERIOR - CINTA ROTATIVA -->
<div class="top-bar py-2">
    <div class="top-bar__ribbon">
        <div class="top-bar__ribbon-inner">
            <span class="top-bar__item">📞 321 654 8235</span>
            <span class="top-bar__sep">|</span>
            <span class="top-bar__item">✉️ gimnasiocastilloamericano@gmail.com</span>
            <span class="top-bar__sep">|</span>
            <span class="top-bar__item top-bar__lema">"Educación con excelencia"</span>
            <span class="top-bar__sep">|</span>
            <span class="top-bar__item">📞 321 654 8235</span>
            <span class="top-bar__sep">|</span>
            <span class="top-bar__item">✉️ gimnasiocastilloamericano@gmail.com</span>
            <span class="top-bar__sep">|</span>
            <span class="top-bar__item top-bar__lema">"Educación con excelencia"</span>
        </div>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/escudo-gca.png" alt="Gimnasio Castillo Americano">
            <img src="assets/img/logo-mobile.png" alt="Gimnasio Castillo Americano" class="mobile-logo">
            <span class="brand-text">Gimnasio Castillo Americano</span>
        </a>

        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menú">
            <span class="navbar-toggler-icon"><span></span></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <div class="close-overlay" data-bs-dismiss="collapse"></div>
            <div class="nav-header">
                <img src="assets/img/escudo-gca.png" alt="GCA">
                <span>Gimnasio Castillo Americano</span>
            </div>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="institucion.php">Institución</a></li>
                <li class="nav-item"><a class="nav-link" href="admisiones.php">Admisiones</a></li>
                <li class="nav-item"><a class="nav-link" href="noticias.php">Noticias</a></li>
                <li class="nav-item"><a class="nav-link" href="docentes.php">Docentes</a></li>
                <li class="nav-item"><a class="nav-link" href="calendario.php">Calendario</a></li>
                <li class="nav-item"><a class="nav-link" href="servicios.php">Servicios</a></li>
                <li class="nav-item"><a class="nav-link" href="contacto.php">Contacto</a></li>
            </ul>

            <a href="login.php" class="btn btn-gca">
                Acceso al Sistema
            </a>
        </div>
    </div>
</nav>

<main>