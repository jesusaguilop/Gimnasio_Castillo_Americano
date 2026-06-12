<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gimnasio Castillo Americano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <style>
        /* Simplified header styles for a normal navbar */
        .top-bar { background: var(--gca-dark); color: #e6e6e6; font-size: 13px; border-bottom: 2px solid var(--gca-gold); }
        .top-bar__ribbon-inner { display:flex; gap:20px; align-items:center; }
        .navbar { background: #fff; border-bottom: 3px solid var(--gca-gold); }
        .navbar-brand { display:flex; align-items:center; gap:12px; }
        .navbar-brand img { height:48px; }
        .brand-text { font-family: 'Playfair Display', serif; font-weight:700; }
        .navbar-nav { margin-left: auto; margin-right: 20px; }
        .navbar-nav .nav-link { color:#333; font-weight:500; padding: 8px 12px; }
        .btn-gca { background:var(--gca-gold); color:#000; border-radius:24px; padding:8px 18px; border:none; font-weight:600; }
        @media (max-width:576px){ .navbar-brand img{height:40px;} .brand-text{display:none;} }
    </style>
</head>
<body>

<div class="top-bar py-2">
    <div class="container">
        <div class="top-bar__ribbon-inner">
            <span>📞 321 654 8235</span>
            <span style="color:var(--gca-gold); opacity:.6">|</span>
            <span>✉️ gimnasiocastilloamericano@gmail.com</span>
            <span style="color:var(--gca-gold); opacity:.6">|</span>
            <span style="color:var(--gca-gold); font-weight:700">"Educación con excelencia"</span>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/escudo-gca.png" alt="GCA">
            <span class="brand-text">Gimnasio Castillo Americano</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-icon">☰</span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="institucion.php">Institución</a></li>
                <li class="nav-item"><a class="nav-link" href="admisiones.php">Admisiones</a></li>
                <li class="nav-item"><a class="nav-link" href="noticias.php">Noticias</a></li>
                <li class="nav-item"><a class="nav-link" href="docentes.php">Docentes</a></li>
                <li class="nav-item"><a class="nav-link" href="calendario.php">Calendario</a></li>
                <li class="nav-item"><a class="nav-link" href="servicios.php">Servicios</a></li>
                <li class="nav-item"><a class="nav-link" href="contacto.php">Contacto</a></li>
            </ul>
            <a href="login.php" class="btn btn-gca">Acceso al Sistema</a>
        </div>
    </div>
</nav>

<main>