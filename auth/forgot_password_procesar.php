<?php

/**
 * Solicitud de restablecimiento de contraseña.
 *
 * Manda por correo un enlace de un solo uso con vencimiento de 1 hora.
 * Responde SIEMPRE lo mismo exista o no el correo (y aunque el envío falle),
 * para no revelar qué direcciones están registradas en el colegio.
 *
 * En la BD se guarda el SHA-256 del token, no el token: quien lea la tabla
 * no puede usar los enlaces vigentes.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../lib/csrf_helper.php';
require_once __DIR__ . '/../lib/mail_helper.php';

use App\Controllers\AuthController;

function volver(string $estado): never
{
    header("Location: ../forgot_password.php?$estado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('error=1');
}

if (!validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    volver('error=1');
}

$email = mb_strtolower(trim($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    volver('error=1');
}

// Sin servicio de correo no hay nada que enviar: mejor decirlo que aparentar que se envió.
if (!mail_configurado()) {
    volver('sincorreo=1');
}

/* Límite: 3 solicitudes por correo y 10 por IP cada hora. Evita usar el
   formulario para llenar de correos a alguien o agotar la cuota de Brevo
   (que también envía los boletines). Se reutiliza login_intentos con el
   prefijo "reset:". Si la tabla aún no existe, no limita. */
$ip = AuthController::ipCliente();
try {
    $n = db()->fetch(
        "SELECT COUNT(*) FILTER (WHERE email = ?) AS por_email,
                COUNT(*) FILTER (WHERE ip = ?)    AS por_ip
         FROM login_intentos
         WHERE creado_en > now() - interval '1 hour' AND email LIKE 'reset:%'
           AND (email = ? OR ip = ?)",
        ["reset:$email", $ip, "reset:$email", $ip]
    );
    if ($n && ($n['por_email'] >= 3 || $n['por_ip'] >= 10)) {
        volver('ok=1');   // misma respuesta: no se revela nada
    }
    db()->query("INSERT INTO login_intentos (ip, email) VALUES (?, ?)", [$ip, mb_substr("reset:$email", 0, 255)]);
} catch (Throwable $e) {
    error_log('[recuperación] límite no disponible: ' . $e->getMessage());
}

$usuario = db()->fetch("SELECT id, nombre, email FROM usuarios WHERE lower(email) = ? LIMIT 1", [$email]);

if ($usuario) {
    $token = bin2hex(random_bytes(32));

    // El vencimiento se calcula en la BD: antes se calculaba con la hora de
    // Bogotá en PHP y se comparaba con NOW() en UTC, y el enlace nacía vencido.
    db()->query(
        "UPDATE usuarios SET reset_token = ?, reset_expires = now() + interval '1 hour' WHERE id = ?",
        [hash('sha256', $token), $usuario['id']]
    );

    $enlace = rtrim(app_url_base(), '/') . '/reset_password.php?token=' . $token;

    if (!enviar_correo_recuperacion($usuario['email'], $usuario['nombre'], $enlace)) {
        // El envío falló: se invalida el token para no dejar uno vivo que nadie
        // recibió. Al usuario se le responde igual que siempre.
        error_log('[recuperación] no se pudo enviar el correo al usuario #' . $usuario['id']);
        db()->query("UPDATE usuarios SET reset_token = NULL, reset_expires = NULL WHERE id = ?", [$usuario['id']]);
    }
}

// Mismo mensaje exista o no el usuario.
volver('ok=1');
