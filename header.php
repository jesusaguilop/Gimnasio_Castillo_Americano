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
    <link rel="stylesheet" href="assets/css/landing.css">

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

        .navbar-nav .nav-link {
            color: #333;
            font-weight: 500;
            padding: 10px 14px;
            position: relative;
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
                background: #fff;
                padding: 16px;
                border-radius: 16px;
                box-shadow: 0 12px 40px rgba(0,0,0,.12);
                margin-top: 8px;
            }
            .navbar-nav .nav-link {
                padding: 12px 14px;
                border-radius: 10px;
            }
            .navbar-nav .nav-link:hover {
                background: #f5f5f5;
            }
            .navbar-nav .nav-link::after {
                display: none;
            }
            .navbar .btn-gca {
                margin-top: 8px;
                width: 100%;
                text-align: center;
            }
            .navbar-brand img {
                height: 40px;
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                font-size: 11px;
            }
            .top-bar .container {
                padding: 0 8px;
            }
            .navbar-brand {
                gap: 8px;
            }
            .navbar-brand img {
                height: 36px;
            }
        }
    </style>
</head>
<body>

<!-- BARRA SUPERIOR -->
<div class="top-bar py-2">
    <div class="container d-flex justify-content-between">
        <span>📞 321 654 8235 | ✉️ gimnasiocastilloamericano@gmail.com</span>
        <span>Educación con excelencia</span>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/escudo-gca.png" alt="Gimnasio Castillo Americano">
            <span class="brand-text">Gimnasio Castillo Americano</span>
        </a>

        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="institucion.php">Institución</a></li>
                <li class="nav-item"><a class="nav-link" href="nosotros.php">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="noticias.php">Noticias</a></li>
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
