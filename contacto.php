<?php
$mensaje_enviado = false;
$error_envio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($nombre && $email && $mensaje) {
        $destino = 'gimnasiocastilloamericano@gmail.com';
        $cabeceras = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $cuerpo = "Nombre: $nombre\nEmail: $email\nTeléfono: $telefono\nAsunto: $asunto\n\nMensaje:\n$mensaje";
        if (mail($destino, "Contacto web: $asunto", $cuerpo, $cabeceras)) {
            $mensaje_enviado = true;
        } else {
            $error_envio = 'No se pudo enviar el mensaje. Intenta de nuevo más tarde.';
        }
    } else {
        $error_envio = 'Completa todos los campos obligatorios (*).';
    }
}

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
            <div class="alert alert-success border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4" data-aos="fade-down">
                <i class="bi bi-check-circle-fill fs-4"></i>
                <div><strong>¡Mensaje enviado!</strong> Te responderemos a la brevedad.</div>
            </div>
            <?php elseif ($error_envio): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4" data-aos="fade-down">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div><?= htmlspecialchars($error_envio) ?></div>
            </div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- COL INFO -->
                <div class="col-lg-5">
                    <div class="hm-sechead" data-aos="fade-right">
                        <h3>Información</h3><span class="line"></span>
                    </div>

                    <a href="https://wa.me/573216548235?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20el%20colegio"
                       target="_blank" rel="noopener" class="hm-wa mb-3" data-aos="fade-up" data-aos-delay="50">
                        <div class="ic"><i class="bi bi-whatsapp"></i></div>
                        <div><h6>WhatsApp Directo</h6><p>321 654 8235 · Respondemos en minutos</p></div>
                        <i class="bi bi-arrow-right-short arrow"></i>
                    </a>

                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="100">
                        <div class="ic"><i class="bi bi-geo-alt-fill"></i></div>
                        <div><h6>Dirección</h6><p>Calle 3B #23-13, Barrio Callejas II<br>Valledupar – Cesar, Colombia</p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="150">
                        <div class="ic"><i class="bi bi-telephone-fill"></i></div>
                        <div><h6>Teléfono</h6><p><a href="tel:+573216548235">321 654 8235</a></p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="200">
                        <div class="ic"><i class="bi bi-envelope-fill"></i></div>
                        <div><h6>Correo Electrónico</h6><p><a href="mailto:gimnasiocastilloamericano@gmail.com">gimnasiocastilloamericano@gmail.com</a></p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="250">
                        <div class="ic"><i class="bi bi-clock-fill"></i></div>
                        <div><h6>Horario de Atención</h6><p>Lunes a Viernes: 7:00 a.m. – 2:00 p.m.<br>Sábados: 8:00 a.m. – 12:00 p.m.</p></div>
                    </div>
                    <div class="hm-contact" data-aos="fade-up" data-aos-delay="300">
                        <div class="ic"><i class="bi bi-globe2"></i></div>
                        <div>
                            <h6>Síguenos</h6>
                            <div class="d-flex gap-2 mt-2">
                                <?php $soc = ['facebook','instagram','whatsapp','youtube'];
                                foreach ($soc as $s): ?>
                                <a href="#" title="<?= ucfirst($s) ?>" aria-label="<?= ucfirst($s) ?>"
                                   style="width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#1a1400;background:linear-gradient(135deg,#e7c877,#c9a24d);text-decoration:none;font-size:1rem;">
                                    <i class="bi bi-<?= $s ?>"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COL FORMULARIO -->
                <div class="col-lg-7">
                    <div class="hm-sechead" data-aos="fade-left">
                        <span class="line"></span><h3>Envíanos un mensaje</h3>
                    </div>

                    <div class="hm-panel p-4 p-md-4" data-aos="fade-up" data-aos-delay="100" style="padding:32px !important;">
                        <p class="hm-p" style="margin-bottom:22px;">Déjanos tus datos y te contactaremos lo antes posible.</p>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="hm-label">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="hm-input" placeholder="Tu nombre" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label">Correo electrónico <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="hm-input" placeholder="tu@correo.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="hm-input" placeholder="Opcional">
                                </div>
                                <div class="col-md-6">
                                    <label class="hm-label">Asunto</label>
                                    <input type="text" name="asunto" class="hm-input" placeholder="¿Sobre qué nos escribes?">
                                </div>
                                <div class="col-12">
                                    <label class="hm-label">Mensaje <span class="text-danger">*</span></label>
                                    <textarea name="mensaje" class="hm-input" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="enviar" class="hm-btn hm-btn-primary"><i class="bi bi-send-fill" style="font-size:1rem;"></i> Enviar mensaje</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- MAPA -->
            <div class="hm-map mt-4" data-aos="fade-up">
                <iframe
                    src="https://www.google.com/maps?q=Calle+3B+23-13+Barrio+Callejas+II+Semillitas+del+reino+Valledupar&output=embed"
                    height="380" style="border:0;" allowfullscreen="" loading="lazy" title="Ubicación del colegio">
                </iframe>
            </div>

            <div class="text-center mt-4" data-aos="fade-up">
                <a href="index.php" class="hm-back"><i class="bi bi-arrow-left-short"></i> Volver al inicio</a>
            </div>

        </div>
    </section>

</div>

<?php include "footer.php"; ?>
