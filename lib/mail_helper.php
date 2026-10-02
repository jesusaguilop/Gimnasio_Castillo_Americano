<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * ¿Hay correo configurado? Vale la API de Brevo (BREVO_API_KEY) o un SMTP
 * con usuario y contraseña. Sin ninguno no se puede enviar nada, así que
 * conviene avisarlo en vez de fallar en silencio.
 */
function mail_configurado(): bool
{
    if (config('mail.brevo_key') !== '') {
        return true;
    }
    return config('mail.host') !== '' && config('mail.username') !== '' && config('mail.password') !== '';
}

/**
 * Envío genérico. Devuelve false y deja el motivo en el log de errores.
 *
 * Si hay BREVO_API_KEY se usa la API HTTP de Brevo: Railway bloquea el
 * SMTP saliente en los planes Free/Hobby, pero no HTTPS.
 *
 * @param string $adjunto  Ruta de un archivo a adjuntar (opcional).
 * @param string $reply_to Correo al que irán las respuestas (opcional).
 */
function enviar_correo(
    string $para,
    string $nombre_destino,
    string $asunto,
    string $cuerpo_html,
    string $adjunto = '',
    string $reply_to = ''
): bool {
    if (!mail_configurado()) {
        error_log('[mail_helper] Correo sin configurar: falta BREVO_API_KEY o MAIL_HOST/MAIL_USERNAME/MAIL_PASSWORD');
        return false;
    }

    $texto = strip_tags(str_replace(['<br>', '</p>'], "\n", $cuerpo_html));

    if (config('mail.brevo_key') !== '') {
        return enviar_correo_brevo($para, $nombre_destino, $asunto, $cuerpo_html, $texto, $adjunto, $reply_to);
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = config('mail.host');
        $mail->SMTPAuth   = true;
        $mail->Username   = config('mail.username');
        $mail->Password   = config('mail.password');
        $mail->SMTPSecure = config('mail.encryption');
        $mail->Port       = config('mail.port');

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(config('mail.from'), config('mail.from_name'));
        $mail->addAddress($para, $nombre_destino);

        if ($reply_to !== '') {
            $mail->addReplyTo($reply_to);
        }

        if ($adjunto !== '' && is_file($adjunto)) {
            $mail->addAttachment($adjunto);
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = $texto;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("[mail_helper] Error enviando correo a $para: " . $e->getMessage());
        return false;
    } catch (\Throwable $e) {
        error_log("[mail_helper] Error inesperado: " . $e->getMessage());
        return false;
    }
}

/**
 * Envío por la API transaccional de Brevo (POST /v3/smtp/email).
 * El remitente (MAIL_FROM_ADDRESS) tiene que estar verificado en Brevo.
 */
function enviar_correo_brevo(
    string $para,
    string $nombre_destino,
    string $asunto,
    string $cuerpo_html,
    string $texto,
    string $adjunto,
    string $reply_to
): bool {
    $datos = [
        'sender'      => ['email' => config('mail.from'), 'name' => config('mail.from_name')],
        'to'          => [['email' => $para, 'name' => $nombre_destino !== '' ? $nombre_destino : $para]],
        'subject'     => $asunto,
        'htmlContent' => $cuerpo_html,
        'textContent' => $texto,
    ];

    if ($reply_to !== '') {
        $datos['replyTo'] = ['email' => $reply_to];
    }

    if ($adjunto !== '' && is_file($adjunto)) {
        $datos['attachment'] = [[
            'name'    => basename($adjunto),
            'content' => base64_encode((string) file_get_contents($adjunto)),
        ]];
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . config('mail.brevo_key'),
        ],
        CURLOPT_POSTFIELDS     => json_encode($datos, JSON_UNESCAPED_UNICODE),
    ]);

    $respuesta = curl_exec($ch);
    $codigo    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($codigo >= 200 && $codigo < 300) {
        return true;
    }

    error_log("[mail_helper] Brevo rechazó el correo a $para (HTTP $codigo): " . ($error !== '' ? $error : (string) $respuesta));
    return false;
}

function enviar_boletin_por_email(string $para, string $nombre_destino, string $ruta_pdf, string $asunto_extra = ''): bool
{
    $appName = config('app.name', 'Gimnasio Castillo Americano');

    return enviar_correo(
        $para,
        $nombre_destino,
        $asunto_extra ? "[$appName] $asunto_extra" : "Boletín Académico - $appName",
        "<p>Adjunto encontrará el boletín académico de su estudiante.</p><p>$appName</p>",
        $ruta_pdf
    );
}

/**
 * Correo de recuperación de contraseña con el enlace de un solo uso.
 */
function enviar_correo_recuperacion(string $para, string $nombre_destino, string $enlace): bool
{
    $appName = config('app.name', 'Gimnasio Castillo Americano');
    $enlaceEscapado = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');

    $cuerpo = "<p>Hola " . htmlspecialchars($nombre_destino, ENT_QUOTES, 'UTF-8') . ",</p>"
        . "<p>Recibimos una solicitud para restablecer tu contraseña en $appName.</p>"
        . "<p><a href=\"$enlaceEscapado\">Restablecer mi contraseña</a></p>"
        . "<p>Si el enlace no funciona, copia esta dirección en tu navegador:<br>$enlaceEscapado</p>"
        . "<p>El enlace vence en 1 hora y solo se puede usar una vez. "
        . "Si no pediste este cambio, puedes ignorar este mensaje.</p>"
        . "<p>$appName</p>";

    return enviar_correo($para, $nombre_destino, "Restablecer tu contraseña - $appName", $cuerpo);
}
