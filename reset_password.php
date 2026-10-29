<?php
require_once __DIR__ . '/lib/asset_helper.php';
require_once "config/database.php";

$token = $_GET["token"] ?? "";

$stmt = $conexion->prepare("
    SELECT id FROM usuarios
    WHERE reset_token = :token AND reset_expires > NOW()
");
// En la BD se guarda el SHA-256 del token, no el token
$stmt->execute([":token" => hash("sha256", (string) $token)]);
$tokenValido = $token !== "" && (bool) $stmt->fetch();

$errorReset = $_GET["error"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva contraseña | Gimnasio Castillo Americano</title>
<meta name="robots" content="noindex">
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
            <h1 class="auth-title">Nueva contraseña</h1>
            <p class="auth-sub">Gimnasio Castillo Americano</p>
        </div>

        <?php if (!$tokenValido): ?>
            <div class="auth-alert err mb-4" role="alert">
                <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
                <span>El enlace es inválido o ha expirado. Solicita uno nuevo.</span>
            </div>
            <div class="auth-links text-center mt-2">
                <a href="forgot_password.php"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Solicitar otro enlace</a>
            </div>
        <?php else: ?>
            <?php if ($errorReset !== ""): ?>
                <div class="auth-alert err mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <span><?= $errorReset === "distintas"
                        ? "Las dos contraseñas no coinciden. Escríbelas de nuevo."
                        : "La contraseña debe tener al menos 8 caracteres." ?></span>
                </div>
            <?php endif; ?>

            <p class="auth-hint text-center mb-4" id="passHint"><i class="bi bi-key me-1" aria-hidden="true"></i>Elige una contraseña de al menos 8 caracteres</p>

            <form action="auth/reset_password_procesar.php" method="POST" class="stagger" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label class="auth-label" for="passInput">Nueva contraseña</label>
                    <div class="field">
                        <input type="password" name="password" id="passInput" class="form-control"
                               placeholder="••••••••" minlength="8" required autofocus
                               autocomplete="new-password" aria-describedby="passHint">
                        <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="passInput">
                            <i class="bi bi-eye-slash" id="passIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="auth-label" for="passConfirm">Repite la contraseña</label>
                    <div class="field">
                        <input type="password" name="password_confirm" id="passConfirm" class="form-control"
                               placeholder="••••••••" minlength="8" required autocomplete="new-password">
                        <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i> Guardar contraseña
                </button>
            </form>

            <div class="auth-links text-center mt-4 pt-1">
                <a href="login.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al inicio de sesión</a>
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
        var visible = type === 'text';
        t.setAttribute('aria-pressed', visible ? 'true' : 'false');
        t.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });

    // Las dos contraseñas deben coincidir (mensaje nativo del navegador)
    var confirmar = document.getElementById('passConfirm');
    var revisar = function () {
        confirmar.setCustomValidity(confirmar.value && confirmar.value !== pass.value ? 'Las contraseñas no coinciden.' : '');
    };
    pass.addEventListener('input', revisar);
    confirmar.addEventListener('input', revisar);
})();
</script>

</body>
</html>
