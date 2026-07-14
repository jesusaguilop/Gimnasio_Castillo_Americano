<?php
require_once "config/database.php";

$token = $_GET["token"] ?? "";

$stmt = $conexion->prepare("
    SELECT id FROM usuarios
    WHERE reset_token = :token AND reset_expires > NOW()
");
$stmt->bindParam(":token", $token);
$stmt->execute();
$tokenValido = (bool) $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva contraseña | Gimnasio Castillo Americano</title>
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
            <h1 class="auth-title">Nueva contraseña</h1>
            <p class="auth-sub">Gimnasio Castillo Americano</p>
        </div>

        <?php if (!$tokenValido): ?>
            <div class="auth-alert err mb-4">
                <i class="bi bi-x-octagon-fill"></i>
                <span>El enlace es inválido o ha expirado. Solicita uno nuevo.</span>
            </div>
            <div class="auth-links text-center mt-2">
                <a href="forgot_password.php"><i class="bi bi-arrow-repeat"></i> Solicitar otro enlace</a>
            </div>
        <?php else: ?>
            <p class="auth-hint text-center mb-4"><i class="bi bi-key me-1"></i>Elige una contraseña de al menos 6 caracteres</p>

            <form action="auth/reset_password_procesar.php" method="POST" class="stagger" id="resetForm" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-4">
                    <label class="auth-label" for="passInput">Nueva contraseña</label>
                    <div class="field">
                        <input type="password" name="password" id="passInput" class="form-control"
                               placeholder="••••••••" minlength="6" required autofocus>
                        <i class="bi bi-lock input-icon"></i>
                        <button type="button" class="toggle-pass" id="togglePass" tabindex="-1" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye-slash" id="passIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="bi bi-check2-circle"></i> Guardar contraseña
                </button>
            </form>

            <div class="auth-links text-center mt-4 pt-1">
                <a href="login.php"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
(function () {
    var t = document.getElementById('togglePass');
    if (!t) return;
    var pass = document.getElementById('passInput');
    var icon = document.getElementById('passIcon');
    t.addEventListener('click', function () {
        var type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
        pass.setAttribute('type', type);
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
})();
</script>

</body>
</html>
