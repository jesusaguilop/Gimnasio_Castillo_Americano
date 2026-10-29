<?php
require_once __DIR__ . '/lib/asset_helper.php';
require_once __DIR__ . '/vendor/autoload.php';
\App\Base\Session::start();
require_once __DIR__ . '/lib/csrf_helper.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Gimnasio Castillo Americano</title>
    <meta name="description" content="Recupera el acceso al sistema académico del Gimnasio Castillo Americano.">
    <link rel="icon" href="<?= asset('assets/img/favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/apple-touch-icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/auth.css') ?>">
</head>
<body class="auth-body">

<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo">
                <img src="<?= asset('assets/img/escudo/escudo.png') ?>" alt="">
            </div>
            <h1 class="auth-title">Recuperar contraseña</h1>
            <p class="auth-sub">Gimnasio Castillo Americano</p>
            <p class="auth-hint"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Te enviaremos un enlace para restablecerla</p>
        </div>

        <?php if (isset($_GET["ok"])): ?>
            <div class="auth-alert ok mb-4" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>Si ese correo tiene una cuenta en el colegio, en unos minutos te llegará un enlace (revisa también Spam o Promociones). Si no llega, pide en el colegio que verifiquen con qué correo está registrada tu cuenta.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET["error"])): ?>
            <div class="auth-alert err mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <span>Ocurrió un error. Intenta nuevamente.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET["sincorreo"])): ?>
            <div class="auth-alert err mb-4" role="alert">
                <i class="bi bi-envelope-slash-fill" aria-hidden="true"></i>
                <span>El envío de correos no está configurado todavía. Comunícate con el colegio para restablecer tu contraseña.</span>
            </div>
        <?php endif; ?>

        <form action="auth/forgot_password_procesar.php" method="POST" class="stagger">
            <?= campo_csrf() ?>
            <div class="mb-4">
                <label class="auth-label" for="emailInput">Correo electrónico</label>
                <div class="field">
                    <input type="email" name="email" id="emailInput" class="form-control"
                           placeholder="usuario@correo.com" required autofocus autocomplete="email" inputmode="email">
                    <i class="bi bi-envelope input-icon" aria-hidden="true"></i>
                </div>
            </div>

            <button type="submit" class="btn-auth">
                <i class="bi bi-envelope-check" aria-hidden="true"></i> Enviar enlace de recuperación
            </button>
        </form>

        <div class="auth-links text-center mt-4 pt-1">
            <a href="login.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al inicio de sesión</a>
        </div>

    </div>
</div>

</body>
</html>
