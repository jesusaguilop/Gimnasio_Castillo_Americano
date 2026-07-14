<?php
session_start();
$tz = new DateTimeZone('America/Bogota');
$h = (int)(new DateTime('now', $tz))->format('H');
$saludo = $h < 12 ? 'Buenos días' : ($h < 18 ? 'Buenas tardes' : 'Buenas noches');
$iconoSaludo = $h < 18 ? 'bi-sun' : 'bi-moon-stars';

$error = $_GET['error'] ?? '';
$oldRol = $_GET['rol'] ?? '';
$oldEmail = htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Gimnasio Castillo Americano</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0b1622 0%, #162236 40%, #1b2a45 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* ── Fondo decorativo ── */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 20% 50%, rgba(212,175,55,.06) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 50%, rgba(212,175,55,.04) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .bg-circle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .bg-circle-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(212,175,55,.08) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .bg-circle-2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(100,140,200,.06) 0%, transparent 70%);
            bottom: -50px; left: -80px;
        }

        /* ── Card principal ── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 36px 36px;
            box-shadow:
                0 30px 80px rgba(0,0,0,.4),
                0 0 0 1px rgba(255,255,255,.06);
            animation: cardIn .7s cubic-bezier(.22,1,.36,1);
        }

        @keyframes cardIn {
            0% { opacity: 0; transform: translateY(30px) scale(.97); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Logo ── */
        .logo-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f8f6f0, #fff);
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-wrap img { height: 60px; width: auto; }

        .login-title {
            font-weight: 800;
            font-size: 22px;
            color: #0d1b2a;
            letter-spacing: -.3px;
        }
        .login-sub {
            font-size: 13px;
            color: #7a7f8a;
            font-weight: 500;
        }
        .login-greeting {
            font-size: 13px;
            color: #9a9faa;
            margin-top: 2px;
            font-weight: 400;
        }

        /* ── Formulario ── */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #2c3344;
            margin-bottom: 5px;
        }

        .input-group-custom {
            position: relative;
        }
        .input-group-custom .form-control,
        .input-group-custom .form-select {
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid #e2e5ea;
            padding: 0 14px 0 44px;
            font-size: 14px;
            font-weight: 500;
            color: #1a2233;
            background: #f8f9fc;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus {
            border-color: #c9a24d;
            box-shadow: 0 0 0 4px rgba(201,162,77,.15);
            background: #fff;
        }
        .input-group-custom .form-control.is-invalid,
        .input-group-custom .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 4px rgba(220,53,69,.1);
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a7b5;
            font-size: 18px;
            pointer-events: none;
            transition: color .2s;
        }
        .input-group-custom .form-control:focus ~ .input-icon,
        .input-group-custom .form-select:focus ~ .input-icon {
            color: #c9a24d;
        }

        .input-group-custom .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0a7b5;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
            transition: color .2s;
            z-index: 5;
        }
        .input-group-custom .toggle-pass:hover { color: #2c3344; }

        /* ── Select flecha personalizada ── */
        .input-group-custom .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23a0a7b5' d='M1.41 0 6 4.58 10.59 0 12 1.41l-6 6-6-6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        /* ── Botón ── */
        .btn-login {
            height: 48px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            background: linear-gradient(135deg, #c9a24d, #b8922e);
            border: none;
            color: #0d1b2a;
            transition: transform .15s, box-shadow .2s, opacity .2s;
            position: relative;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(201,162,77,.35);
            opacity: .95;
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .btn-login:disabled {
            opacity: .7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-login .spinner-border {
            width: 18px; height: 18px;
            border-width: 2px;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* ── Alertas ── */
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            animation: shakeX .5s ease;
        }
        .alert-custom.alert-danger {
            background: #fef2f2;
            color: #b91c1c;
        }

        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            10%, 50%, 90% { transform: translateX(-6px); }
            30%, 70% { transform: translateX(6px); }
        }

        /* ── Links ── */
        .links a {
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            transition: color .2s;
        }
        .links a:hover { color: #c9a24d; }

        /* ── Tom Select (login role) ── */
    .ts-wrapper .ts-control {
        border-radius: 12px;
        padding: 0 14px 0 44px;
        border: 1.5px solid #e2e5ea;
        font-size: 14px;
        min-height: 48px;
        display: flex;
        align-items: center;
        background: #f8f9fc;
        font-family: 'Inter', sans-serif;
        box-shadow: none;
        transition: border-color .2s, box-shadow .2s;
        color: #1a2233;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #c9a24d;
        box-shadow: 0 0 0 4px rgba(201,162,77,.15);
        background: #fff;
    }
    .ts-wrapper .ts-control:hover {
        border-color: #ccc;
    }
    /* El campo de rol (y su desplegable) queda por encima de los campos de abajo */
    .input-group-custom.role-field { z-index: 20; }
    .ts-wrapper.dropdown-active, .ts-wrapper.focus { position: relative; z-index: 30; }
    .ts-wrapper .ts-dropdown {
        border: 1px solid #eee;
        border-radius: 12px;
        box-shadow: 0 8px 40px rgba(0,0,0,.16);
        margin-top: 4px;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
        background: #ffffff;          /* fondo sólido: no deja ver los campos detrás */
        z-index: 100;                 /* por encima de íconos (z-index:10) y campos */
    }
    .ts-wrapper .ts-dropdown .option {
        padding: 12px 14px;
        font-size: 14px;
        border-bottom: 1px solid #f5f5f5;
        transition: background .12s;
        background: #ffffff;
        color: #1a2233;
    }
    .ts-wrapper .ts-dropdown .option:last-child {
        border-bottom: none;
    }
    .ts-wrapper .ts-dropdown .option.active {
        background: rgba(201,162,77,.08);
    }
    .ts-wrapper .ts-dropdown .option.selected {
        background: rgba(201,162,77,.15);
        font-weight: 600;
    }
    .ts-wrapper .ts-dropdown .option.selected::after {
        content: ' ✓';
        color: #b8922e;
        font-weight: 700;
    }
    .dropdown-active .ts-control {
        border-radius: 12px 12px 0 0;
    }
    /* Ajustar icono para que no solape con Tom Select */
    .input-group-custom .input-icon {
        z-index: 10;
    }
    .ts-wrapper .ts-control .item {
        color: #1a2233;
        font-weight: 500;
    }

    /* ── Responsive ── */
        @media (max-width: 480px) {
            .login-card { padding: 28px 20px 24px; border-radius: 18px; }
            .login-title { font-size: 19px; }
            .logo-wrap { width: 64px; height: 64px; }
            .logo-wrap img { height: 44px; }
        }
    </style>
    <!-- ── Glow-up del login ── -->
    <style>
        /* Acento dorado superior recortado a las esquinas redondeadas (sin "orejitas") */
        .login-card { position: relative; overflow: hidden; }
        .login-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #c9a24d, #e6c76e, #c9a24d);
        }
        /* La contraseña no debe pasar por debajo del ícono del ojo */
        #passInput { padding-right: 46px; }
        /* Los íconos se vuelven dorados al enfocar el campo */
        .input-group-custom:focus-within .input-icon { color: #c9a24d; }

        .logo-wrap { animation: logoFloat 4s ease-in-out infinite; }
        @keyframes logoFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        .bg-circle-1 { animation: floatA 15s ease-in-out infinite; }
        .bg-circle-2 { animation: floatB 19s ease-in-out infinite; }
        @keyframes floatA { 0%,100% { transform: translate(0,0); } 50% { transform: translate(-32px,22px); } }
        @keyframes floatB { 0%,100% { transform: translate(0,0); } 50% { transform: translate(26px,-20px); } }

        .btn-login { overflow: hidden; }
        .btn-login::before {
            content: ''; position: absolute; top: 0; left: -130%;
            width: 55%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,.5), transparent);
            transform: skewX(-20deg); transition: left .6s ease;
        }
        .btn-login:hover::before { left: 150%; }

        /* (Se quitó la animación por campo: el transform residual creaba contextos
            de apilamiento que dejaban el desplegable de rol debajo de los campos.) */

        @media (prefers-reduced-motion: reduce) {
            .logo-wrap, .bg-circle-1, .bg-circle-2 { animation: none !important; }
            .btn-login::before { display: none; }
        }
    </style>
</head>
<body>

<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>

<div class="login-wrapper">

    <div class="login-card">

        <!-- ─── HEADER ─── -->
        <div class="text-center mb-4">
            <div class="logo-wrap">
                <img src="assets/img/escudo-gca.png" alt="Escudo GCA">
            </div>
            <h1 class="login-title">Sistema Académico</h1>
            <p class="login-sub">Gimnasio Castillo Americano</p>
            <p class="login-greeting"><i class="bi <?= $iconoSaludo ?> me-1"></i><?= $saludo ?>, ingresa tus credenciales</p>
        </div>

        <!-- ─── ERROR ─── -->
        <?php if ($error): ?>
            <div class="alert alert-custom alert-danger d-flex align-items-center gap-2 mb-4" id="errorAlert">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:16px;"></i>
                <span>
                    <?= match ($error) {
                        'empty'  => 'Completa todos los campos.',
                        default => 'Credenciales incorrectas. Verifica e intenta de nuevo.',
                    } ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- ─── FORM ─── -->
        <form action="auth/login_procesar.php" method="POST" id="loginForm" novalidate>

            <div class="mb-3">
                <label class="form-label" for="rolSelect">Tipo de usuario</label>
                <div class="input-group-custom role-field">
                    <i class="bi bi-person-badge input-icon"></i>
                    <select name="rol" id="rolSelect" class="form-select" required>
                        <option value="">Selecciona tu rol</option>
                        <option value="admin" <?= $oldRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="profesor" <?= $oldRol === 'profesor' ? 'selected' : '' ?>>Profesor</option>
                        <option value="padre" <?= $oldRol === 'padre' ? 'selected' : '' ?>>Padre de familia</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="emailInput">Correo electrónico</label>
                <div class="input-group-custom">
                    <i class="bi bi-envelope input-icon"></i>
                    <input
                        type="email"
                        name="correo"
                        id="emailInput"
                        class="form-control"
                        placeholder="usuario@correo.com"
                        value="<?= $oldEmail ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="passInput">Contraseña</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock input-icon"></i>
                    <input
                        type="password"
                        name="password"
                        id="passInput"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" class="toggle-pass" id="togglePass" tabindex="-1" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye-slash" id="passIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100" id="submitBtn">
                <span id="btnText">Iniciar sesión</span>
                <span id="btnSpinner" class="d-none"><span class="spinner-border spinner-border-sm"></span> Ingresando…</span>
            </button>
        </form>

        <!-- ─── LINKS ─── -->
        <div class="links text-center mt-4 pt-1">
            <a href="forgot_password.php" class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-shield-exclamation"></i> ¿Olvidaste tu contraseña?
            </a>
            <br>
            <a href="index.php" class="text-muted d-inline-flex align-items-center gap-1 mt-1">
                <i class="bi bi-arrow-left"></i> Volver al sitio web
            </a>
        </div>

    </div>
</div>

<script>
(function() {
    // ── Password toggle ──
    const passInput = document.getElementById('passInput');
    const toggleBtn = document.getElementById('togglePass');
    const passIcon  = document.getElementById('passIcon');

    toggleBtn.addEventListener('click', function() {
        const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passInput.setAttribute('type', type);
        passIcon.classList.toggle('bi-eye');
        passIcon.classList.toggle('bi-eye-slash');
    });

    // ── Loading spinner on submit ──
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    form.addEventListener('submit', function() {
        // Simple client validation
        const rol = document.getElementById('rolSelect').value;
        const email = document.getElementById('emailInput').value.trim();
        const pass = passInput.value.trim();

        if (!rol || !email || !pass) {
            return; // browser will show native validation
        }

        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
    });

    // ── Auto-dismiss error alert ──
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        setTimeout(function() {
            errorAlert.style.transition = 'opacity .4s';
            errorAlert.style.opacity = '0';
            setTimeout(function() { errorAlert.remove(); }, 500);
        }, 5000);
    }
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#rolSelect', {
        maxOptions: 10,
        placeholder: 'Selecciona tu rol',
        allowEmptyOption: true,
    });
});
</script>

</body>
</html>
