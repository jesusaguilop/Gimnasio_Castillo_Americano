<?php
require_once __DIR__ . '/lib/asset_helper.php';
/**
 * Cambio de contraseña del usuario que ha iniciado sesión.
 *
 * Obligatorio cuando la cuenta tiene una clave provisional
 * (usuarios.debe_cambiar_clave = TRUE, p. ej. las cuentas de padres creadas
 * por el colegio): Auth::requireRole manda aquí hasta que se cambie.
 * También sirve para cambiarla en cualquier momento.
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/csrf_helper.php';

use App\Base\Auth;
use App\Base\Session;

if (!Auth::check() || !Auth::sesionValida()) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

$obligatorio = (bool) Session::get('debe_cambiar_clave');
$paneles = ['admin' => 'admin/dashboard.php', 'profesor' => 'profesores/dashboard.php', 'padre' => 'padres/dashboard.php'];
$panel = $paneles[userRole()] ?? 'login.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['password'] ?? '';
    $confirmacion = $_POST['password_confirm'] ?? '';
    $u = db()->fetch("SELECT email, password FROM usuarios WHERE id = ?", [userId()]);

    if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
        $error = 'La página estuvo abierta mucho tiempo. Vuelve a intentarlo.';
    } elseif (!$u || !password_verify($actual, $u['password'])) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (mb_strlen($nueva) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif (!hash_equals($nueva, $confirmacion)) {
        $error = 'Las dos contraseñas nuevas no coinciden.';
    } elseif (hash_equals($actual, $nueva)) {
        $error = 'La nueva contraseña debe ser distinta de la actual.';
    } elseif (mb_strtolower($nueva) === mb_strtolower($u['email'])) {
        $error = 'La contraseña no puede ser tu correo.';
    } else {
        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->query("UPDATE usuarios SET password = ? WHERE id = ?", [$hash, userId()]);
        try {
            db()->query("UPDATE usuarios SET debe_cambiar_clave = FALSE WHERE id = ?", [userId()]);
        } catch (Throwable $e) {
            error_log('[cambiar_clave] ' . $e->getMessage());
        }
        session_regenerate_id(true);
        Session::set('_huella', Auth::huella($hash));
        Session::set('debe_cambiar_clave', false);
        Session::setSuccess('Tu contraseña quedó actualizada.');
        header("Location: $panel");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar contraseña | Gimnasio Castillo Americano</title>
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
            <h1 class="auth-title"><?= $obligatorio ? 'Crea tu contraseña' : 'Cambiar contraseña' ?></h1>
            <p class="auth-sub"><?= htmlspecialchars(userName()) ?></p>
        </div>

        <?php if ($obligatorio && !$error): ?>
            <div class="auth-alert ok mb-4" role="status">
                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                <span>Entraste con una contraseña provisional. Para proteger tu cuenta, elige una propia antes de continuar.</span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-alert err mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="stagger" id="claveForm">
            <?= campo_csrf() ?>

            <div class="mb-3">
                <label class="auth-label" for="actual"><?= $obligatorio ? 'Contraseña provisional' : 'Contraseña actual' ?></label>
                <div class="field">
                    <input type="password" name="actual" id="actual" class="form-control"
                           placeholder="••••••••" required autofocus autocomplete="current-password">
                    <i class="bi bi-key input-icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="mb-3">
                <label class="auth-label" for="passInput">Nueva contraseña</label>
                <div class="field">
                    <input type="password" name="password" id="passInput" class="form-control"
                           placeholder="••••••••" minlength="8" required
                           autocomplete="new-password" aria-describedby="passHint">
                    <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                    <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="passInput">
                        <i class="bi bi-eye-slash" id="passIcon" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="auth-hint mt-2 mb-0" id="passHint">Al menos 8 caracteres. Mejor una frase que solo tú recuerdes.</p>
            </div>

            <div class="mb-4">
                <label class="auth-label" for="passConfirm">Repite la nueva contraseña</label>
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
            <?php if ($obligatorio): ?>
                <a href="auth/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Salir</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($panel) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al panel</a>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
(function () {
    var t = document.getElementById('togglePass');
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
