<?php
require_once __DIR__ . '/vendor/autoload.php';
\App\Base\Session::start();
require_once __DIR__ . '/lib/csrf_helper.php';
$tz = new DateTimeZone('America/Bogota');
$h = (int)(new DateTime('now', $tz))->format('H');
$saludo = $h < 12 ? 'Buenos días' : ($h < 18 ? 'Buenas tardes' : 'Buenas noches');
$iconoSaludo = $h < 18 ? 'bi-sun' : 'bi-moon-stars';

$error = $_GET['error'] ?? '';
$resetOk = isset($_GET['reset']);
$oldEmail = htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Gimnasio Castillo Americano</title>
    <meta name="description" content="Ingreso al sistema académico del Gimnasio Castillo Americano para administradores, profesores y padres de familia.">
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="assets/img/escudo-gca.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/escudo.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="lg-body">

<main class="lg">

    <!-- ── Panel de marca: el escudo con sus leones ── -->
    <section class="lg-marca" aria-label="Gimnasio Castillo Americano">
        <a href="index.php" class="lg-volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al sitio</a>

        <div class="lg-marca__centro">
            <span class="escudo lg-escudo" data-ruge aria-hidden="true">
                <img class="escudo-base" src="assets/img/escudo/escudo-base.png" alt="" width="226" height="337">
                <img class="escudo-leon izq" src="assets/img/escudo/leon-izq.png" alt="" width="226" height="337">
                <img class="escudo-leon der" src="assets/img/escudo/leon-der.png" alt="" width="226" height="337">
            </span>
            <p class="lg-marca__kicker">Gimnasio</p>
            <p class="lg-marca__nombre">Castillo Americano</p>
            <p class="lg-marca__lema">Cultivar para cosechar</p>
        </div>

        <ul class="lg-perfiles">
            <li><i class="bi bi-people" aria-hidden="true"></i><span><strong>Padres</strong> Boletines y avisos de sus hijos</span></li>
            <li><i class="bi bi-journal-check" aria-hidden="true"></i><span><strong>Profesores</strong> Notas, logros y dirección de grupo</span></li>
            <li><i class="bi bi-shield-check" aria-hidden="true"></i><span><strong>Administración</strong> Gestión académica completa</span></li>
        </ul>
    </section>

    <!-- ── Formulario ── -->
    <section class="lg-form">
        <div class="lg-card">
            <p class="lg-saludo"><i class="bi <?= $iconoSaludo ?>" aria-hidden="true"></i> <?= $saludo ?></p>
            <h1 class="lg-titulo">Ingresa al sistema académico</h1>
            <p class="lg-sub">Usa el correo que registraste en el colegio. Te llevamos a tu panel según tu cuenta.</p>

            <?php if ($resetOk && !$error): ?>
                <div class="lg-alerta ok" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>Tu contraseña se actualizó. Ya puedes iniciar sesión.</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="lg-alerta err" id="errorAlert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <span>
                        <?= match ($error) {
                            'empty'     => 'Escribe tu correo y tu contraseña.',
                            'expirado'  => 'La página estuvo abierta mucho tiempo. Vuelve a intentarlo.',
                            'bloqueado' => 'Demasiados intentos fallidos. Espera 15 minutos o usa «¿Olvidaste tu contraseña?».',
                            'sesion'    => 'Tu sesión terminó. Vuelve a iniciar sesión.',
                            default     => 'Correo o contraseña incorrectos. Verifica e intenta de nuevo.',
                        } ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="auth/login_procesar.php" method="POST" id="loginForm" novalidate>
                <?= campo_csrf() ?>

                <div class="lg-campo">
                    <label for="emailInput">Correo electrónico</label>
                    <div class="lg-input">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" name="correo" id="emailInput" placeholder="usuario@correo.com"
                               value="<?= $oldEmail ?>" autocomplete="username" inputmode="email"
                               aria-describedby="emailError" required <?= $oldEmail === '' ? 'autofocus' : '' ?>>
                    </div>
                    <p id="emailError" class="lg-error" hidden>Escribe tu correo.</p>
                </div>

                <div class="lg-campo">
                    <div class="lg-campo__fila">
                        <label for="passInput">Contraseña</label>
                        <a href="forgot_password.php" class="lg-link">¿La olvidaste?</a>
                    </div>
                    <div class="lg-input">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" name="password" id="passInput" placeholder="••••••••"
                               autocomplete="current-password" aria-describedby="passError mayusAviso" required
                               <?= $oldEmail !== '' ? 'autofocus' : '' ?>>
                        <button type="button" class="lg-ver" id="togglePass" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="passInput">
                            <i class="bi bi-eye-slash" id="passIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p id="passError" class="lg-error" hidden>Escribe tu contraseña.</p>
                    <p id="mayusAviso" class="lg-aviso" hidden><i class="bi bi-capslock-fill" aria-hidden="true"></i> Bloq Mayús está activado</p>
                </div>

                <button type="submit" class="lg-boton" id="submitBtn">
                    <span id="btnText">Iniciar sesión <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    <span id="btnSpinner" hidden role="status"><span class="lg-spinner" aria-hidden="true"></span> Ingresando…</span>
                </button>
            </form>

            <p class="lg-ayuda">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                ¿Primera vez? Entra con la clave que te dio el colegio, o usa
                <a href="forgot_password.php">«¿La olvidaste?»</a> con tu correo para crear la tuya.
            </p>
        </div>
    </section>
</main>

<script src="assets/js/escudo.js" defer></script>
<script>
(function () {
    var passInput = document.getElementById('passInput');
    var toggleBtn = document.getElementById('togglePass');
    var passIcon = document.getElementById('passIcon');

    toggleBtn.addEventListener('click', function () {
        var visible = passInput.type === 'password';
        passInput.type = visible ? 'text' : 'password';
        passIcon.className = 'bi ' + (visible ? 'bi-eye' : 'bi-eye-slash');
        toggleBtn.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });

    // Aviso de Bloq Mayús mientras se escribe la contraseña
    var mayus = document.getElementById('mayusAviso');
    ['keyup', 'keydown'].forEach(function (ev) {
        passInput.addEventListener(ev, function (e) {
            if (e.getModifierState) mayus.hidden = !e.getModifierState('CapsLock');
        });
    });
    passInput.addEventListener('blur', function () { mayus.hidden = true; });

    // Validación propia (el form lleva novalidate) + "Ingresando…"
    var form = document.getElementById('loginForm');
    form.addEventListener('submit', function (ev) {
        var emailEl = document.getElementById('emailInput');
        var campos = [[emailEl, 'emailError'], [passInput, 'passError']];
        var primero = null;
        campos.forEach(function (c) {
            var vacio = !c[0].value.trim();
            c[0].classList.toggle('invalido', vacio);
            if (vacio) c[0].setAttribute('aria-invalid', 'true'); else c[0].removeAttribute('aria-invalid');
            document.getElementById(c[1]).hidden = !vacio;
            if (vacio && !primero) primero = c[0];
        });
        if (primero) { ev.preventDefault(); primero.focus(); return; }

        document.getElementById('submitBtn').disabled = true;
        document.getElementById('btnText').hidden = true;
        document.getElementById('btnSpinner').hidden = false;
    });
})();
</script>
</body>
</html>
