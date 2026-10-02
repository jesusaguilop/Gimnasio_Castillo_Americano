<?php
// Solo sesión (para el token CSRF): esta página no necesita la base de datos
require_once __DIR__ . '/vendor/autoload.php';
\App\Base\Session::start();
require_once __DIR__ . '/lib/csrf_helper.php';

$mensaje_enviado = false;
$error_envio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])
    && !validar_token_csrf($_POST['_csrf_token'] ?? '')) {
    $error_envio = 'La página estuvo abierta mucho tiempo. Vuelve a enviar el mensaje.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar']) && trim($_POST['sitio_web'] ?? '') !== '') {
    // Campo trampa: las personas no lo ven, los robots de spam sí lo llenan.
    // Se responde como si se hubiera enviado para no darles pistas.
    $mensaje_enviado = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($nombre && $email && $mensaje && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_envio = 'Escribe un correo electrónico válido.';
    } elseif ($nombre && $email && $mensaje) {
        require_once __DIR__ . '/lib/mail_helper.php';

        // mail() no sirve en el contenedor (no hay servidor de correo), así
        // que va por el mismo canal que el resto: Brevo o SMTP.
        $destino = env('MAIL_ADMIN', '') ?: 'gimnasiocastilloamericano@gmail.com';
        $e = fn(string $s): string => nl2br(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
        $cuerpo = "<p><strong>Nombre:</strong> {$e($nombre)}<br>"
            . "<strong>Email:</strong> {$e($email)}<br>"
            . "<strong>Teléfono:</strong> {$e($telefono)}<br>"
            . "<strong>Asunto:</strong> {$e($asunto)}</p>"
            . "<p><strong>Mensaje:</strong><br>{$e($mensaje)}</p>";
        $asuntoCorreo = 'Contacto web: ' . str_replace(["\r", "\n"], ' ', $asunto);

        if (enviar_correo($destino, 'Gimnasio Castillo Americano', $asuntoCorreo, $cuerpo, '', $email)) {
            $mensaje_enviado = true;
        } else {
            $error_envio = 'No se pudo enviar el mensaje. Intenta de nuevo más tarde.';
        }
    } else {
        $error_envio = 'Completa todos los campos obligatorios (*).';
    }
}

// Si el envío falla, el formulario conserva lo escrito
$valor = fn(string $campo): string => $mensaje_enviado ? '' : htmlspecialchars($_POST[$campo] ?? '', ENT_QUOTES, 'UTF-8');

$pageTitle = 'Contacto';
$pageDesc = 'Escríbenos o llámanos: teléfono, WhatsApp, dirección y horario del Gimnasio Castillo Americano.';
include "header.php";
?>

<div class="hm-wrap">

    <!-- BANNER -->
    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title"><span class="grad">Contáctanos</span></h1>
            <p class="hm-ph-sub">Estamos aquí para ayudarte. Escríbenos, llámanos o visítanos. Te atenderemos con gusto.</p>
        </div>
    </section>

    <section class="hm-section">
        <div class="container">

            <?php if ($mensaje_enviado): ?>
            <div class="alert alert-success border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4" role="status">
                <i class="bi bi-check-circle-fill fs-4" aria-hidden="true"></i>
                <div><strong>¡Mensaje enviado!</strong> Te responderemos a la brevedad.</div>
            </div>
            <?php elseif ($error_envio): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4" aria-hidden="true"></i>
                <div><?= htmlspecialchars($error_envio) ?></div>
            </div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- COL INFO -->
                <div class="col-lg-5">
                    <div class="hm-sechead" data-aos="fade-right">
                        <h2>Información</h2><span class="line"></span>
                    </div>

                    <a href="https://wa.me/573216548235?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20el%20colegio"
                       target="_blank" rel="noopener" class="hm-wa mb-3" data-aos="fade-up" data-aos-delay="50">
                        <span class="visually-hidden">(se abre en otra pestaña)</span>
                        <div class="ic"><i class="bi bi-whatsapp" aria-hidden="true"></i></div>
                        <div><h3>WhatsApp Directo</h3><p>321 654 8235 · Respondemos en minutos</p></div>
                        <i class="bi bi-arrow-right-short arrow" aria-hidden="true"></i>
                    </a>

                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="100">
                        <div class="ic"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></div>
                        <div><h3>Dirección</h3><p>Calle 3B #23-13, Barrio Callejas II<br>Valledupar – Cesar, Colombia</p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="150">
                        <div class="ic"><i class="bi bi-telephone-fill" aria-hidden="true"></i></div>
                        <div><h3>Teléfono</h3><p><a href="tel:+573216548235">321 654 8235</a></p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="200">
                        <div class="ic"><i class="bi bi-envelope-fill" aria-hidden="true"></i></div>
                        <div><h3>Correo Electrónico</h3><p><a href="mailto:gimnasiocastilloamericano@gmail.com">gimnasiocastilloamericano@gmail.com</a></p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="250">
                        <div class="ic"><i class="bi bi-clock-fill" aria-hidden="true"></i></div>
                        <div><h3>Horario de Atención</h3><p>Lunes a Viernes: 7:00 a.m. – 2:00 p.m.<br>Sábados: 8:00 a.m. – 12:00 p.m.</p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="300">
                        <div class="ic"><i class="bi bi-globe2" aria-hidden="true"></i></div>
                        <div>
                            <h3>Síguenos</h3>
                            <div class="d-flex gap-2 mt-2">
                                <?php
                                require_once __DIR__ . '/config/env.php';
                                // WhatsApp siempre (es el canal real); el resto solo si su URL está configurada
                                $soc = ['whatsapp' => ['WhatsApp', 'https://wa.me/573216548235']];
                                foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $k => $n) {
                                    $url = (string) config('social.' . $k, '');
                                    if ($url !== '') $soc[$k] = [$n, $url];
                                }
                                foreach ($soc as $s => [$red, $url]): ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" title="<?= $red ?>" aria-label="<?= $red ?> del colegio (se abre en otra pestaña)"
                                   style="width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#1a1400;background:linear-gradient(135deg,#e7c877,#c9a24d);text-decoration:none;font-size:1rem;">
                                    <i class="bi bi-<?= $s ?>" aria-hidden="true"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COL FORMULARIO -->
                <div class="col-lg-7">
                    <div class="hm-sechead" data-aos="fade-left">
                        <span class="line"></span><h2>Envíanos un mensaje</h2>
                    </div>

                    <div class="hm-panel p-4 p-md-4" data-aos="fade-up" data-aos-delay="100" style="padding:32px !important;">
                        <p class="hm-p" style="margin-bottom:22px;">Déjanos tus datos y te contactaremos lo antes posible. Los campos con <span class="text-danger" aria-hidden="true">*</span> son obligatorios.</p>
                        <form method="POST">
                            <?= campo_csrf() ?>
                            <div class="visually-hidden" aria-hidden="true">
                                <label for="c-sitio">No llenar este campo</label>
                                <input type="text" name="sitio_web" id="c-sitio" tabindex="-1" autocomplete="off">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="hm-label" for="c-nombre">Nombre completo <span class="text-danger" aria-hidden="true">*</span></label>
                                    <input type="text" name="nombre" id="c-nombre" class="hm-input" placeholder="Tu nombre" autocomplete="name" value="<?= $valor('nombre') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label" for="c-email">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
                                    <input type="email" name="email" id="c-email" class="hm-input" placeholder="tu@correo.com" autocomplete="email" inputmode="email" value="<?= $valor('email') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label" for="c-telefono">Teléfono</label>
                                    <input type="tel" name="telefono" id="c-telefono" class="hm-input" placeholder="Opcional" autocomplete="tel" value="<?= $valor('telefono') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label" for="c-asunto">Asunto</label>
                                    <input type="text" name="asunto" id="c-asunto" class="hm-input" placeholder="¿Sobre qué nos escribes?" value="<?= $valor('asunto') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="hm-label" for="c-mensaje">Mensaje <span class="text-danger" aria-hidden="true">*</span></label>
                                    <textarea name="mensaje" id="c-mensaje" class="hm-input" rows="5" placeholder="Escribe tu mensaje aquí..." required><?= $valor('mensaje') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="enviar" class="hm-btn hm-btn-primary"><i class="bi bi-send-fill" style="font-size:1rem;" aria-hidden="true"></i> Enviar mensaje</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- MAPA -->
            <div class="hm-map mt-4" data-aos="fade-up">
                <iframe
                    src="https://www.google.com/maps?q=<?= urlencode('Gimnasio Castillo Americano, Calle 3B #23-13, Barrio Callejas II, Valledupar, Cesar, Colombia') ?>&amp;output=embed"
                    height="380" style="border:0;" allowfullscreen="" loading="lazy" title="Ubicación del colegio">
                </iframe>
            </div>

            <div class="text-center mt-4" data-aos="fade-up">
                <a href="index.php" class="hm-back"><i class="bi bi-arrow-left-short" aria-hidden="true"></i> Volver al inicio</a>
            </div>

        </div>
    </section>

</div>

<?php include "footer.php"; ?>
