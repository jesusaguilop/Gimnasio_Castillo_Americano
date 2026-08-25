<?php

/**
 * Solicitud de restablecimiento de contraseña.
 *
 * Manda por correo un enlace de un solo uso con vencimiento de 1 hora.
 * Responde SIEMPRE lo mismo exista o no el correo, para no revelar
 * qué direcciones están registradas en el colegio.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../lib/csrf_helper.php';
require_once __DIR__ . '/../lib/mail_helper.php';

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

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    volver('error=1');
}

// Sin SMTP no hay nada que enviar: mejor decirlo que aparentar que se envió.
if (!mail_configurado()) {
    volver('sincorreo=1');
}

$usuario = db()->fetch("SELECT id, nombre, email FROM usuarios WHERE email = ? LIMIT 1", [$email]);

if ($usuario) {
    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    db()->query(
        "UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?",
        [$token, $expira, $usuario['id']]
    );

    $base   = rtrim(config('app.url', ''), '/');
    $enlace = $base . '/reset_password.php?token=' . $token;

    if (!enviar_correo_recuperacion($usuario['email'], $usuario['nombre'], $enlace)) {
        // El envío falló (SMTP caído, credenciales malas). Se invalida el token
        // para no dejar uno vivo que nadie recibió.
        db()->query("UPDATE usuarios SET reset_token = NULL, reset_expires = NULL WHERE id = ?", [$usuario['id']]);
        volver('error=1');
    }
}

// Mismo mensaje exista o no el usuario.
volver('ok=1');
