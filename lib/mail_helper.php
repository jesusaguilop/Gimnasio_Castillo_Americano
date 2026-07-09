<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function enviar_boletin_por_email(string $para, string $nombre_destino, string $ruta_pdf, string $asunto_extra = ''): bool
{
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

        $mail->addAttachment($ruta_pdf);

        $appName = config('app.name', 'Gimnasio Castillo Americano');
        $mail->Subject = $asunto_extra ? "[$appName] $asunto_extra" : "Boletín Académico - $appName";
        $mail->Body    = "Adjunto encontrará el boletín académico de su estudiante.\n\n$appName";
        $mail->AltBody = strip_tags($mail->Body);

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
