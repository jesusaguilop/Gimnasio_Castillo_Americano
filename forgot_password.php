<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Gimnasio Castillo Americano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">

<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo">
                <img src="assets/img/escudo-gca.png" alt="Escudo GCA">
            </div>
            <h1 class="auth-title">Recuperar contraseña</h1>
            <p class="auth-sub">Gimnasio Castillo Americano</p>
            <p class="auth-hint"><i class="bi bi-shield-lock me-1"></i>Te enviaremos un enlace para restablecerla</p>
        </div>

        <?php if (isset($_GET["ok"])): ?>
            <div class="auth-alert ok mb-4">
                <i class="bi bi-check-circle-fill"></i>
                <span>Si el correo existe, se enviará un enlace de recuperación.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET["error"])): ?>
            <div class="auth-alert err mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Ocurrió un error. Intenta nuevamente.</span>
            </div>
        <?php endif; ?>

        <form action="auth/forgot_password_procesar.php" method="POST" class="stagger">
            <div class="mb-4">
                <label class="auth-label" for="emailInput">Correo electrónico</label>
                <div class="field">
                    <input type="email" name="email" id="emailInput" class="form-control"
                           placeholder="usuario@correo.com" required autofocus>
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-auth">
                <i class="bi bi-envelope-check"></i> Enviar enlace de recuperación
            </button>
        </form>

        <div class="auth-links text-center mt-4 pt-1">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
        </div>

    </div>
</div>

</body>
</html>
