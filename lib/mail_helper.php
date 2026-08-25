<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * ¿Hay SMTP configurado? Sin usuario y contraseña no se puede enviar nada,
 * así que conviene avisarlo en vez de fallar en silencio.
 */
function mail_configurado(): bool
{
    return config('mail.host') !== '' && config('mail.username') !== '' && config('mail.password') !== '';
}

/**
 * Envío genérico. Devuelve false y deja el motivo en el log de errores.
 *
 * @param string $adjunto Ruta de un archivo a adjuntar (opcional).
 */
function enviar_correo(
    string $para,
    string $nombre_destino,
    string $asunto,
    string $cuerpo_html,
    string $adjunto = ''
): bool {
    if (!mail_configurado()) {
        error_log('[mail_helper] SMTP sin configurar: faltan MAIL_HOST/MAIL_USERNAME/MAIL_PASSWORD en .env');
        return false;
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

        if ($adjunto !== '' && is_file($adjunto)) {
            $mail->addAttachment($adjunto);
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $cuerpo_html));

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
