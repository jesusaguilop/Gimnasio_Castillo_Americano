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

<style>
    .contact-hero {
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        color: #fff;
        padding: 100px 20px 70px;
        text-align: center;
        position: relative;
        isolation: isolate;
        overflow: hidden;
    }
    .contact-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 600px 300px at 20% 80%, rgba(201,162,77,.08) 0%, transparent 70%),
            radial-gradient(ellipse 500px 400px at 80% 20%, rgba(201,162,77,.05) 0%, transparent 70%);
        z-index: 0;
    }
    .contact-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gca-gold), transparent);
    }
    .contact-hero > * { position: relative; z-index: 1; }
    .contact-hero h1 { font-weight: 900; font-size: 2.6rem; }
    .contact-hero h1::after {
        content: '';
        display: block;
        width: 70px; height: 3px;
        background: var(--gca-gold);
        margin: 16px auto 0;
        border-radius: 2px;
    }
    .contact-hero p {
        color: #bbb;
        max-width: 560px;
        margin: 18px auto 0;
        font-size: 1.1rem;
        line-height: 1.7;
    }

    .contact-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        background: #fff;
        border-radius: 18px;
        padding: 22px 26px;
        margin-bottom: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        border: 1px solid #f0ede8;
        transition: all .3s ease;
        position: relative;
    }
    .contact-card::before {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: 19px;
        background: linear-gradient(135deg, var(--gca-gold), transparent 40%, transparent 60%, var(--gca-gold));
        z-index: -1;
        opacity: 0;
        transition: opacity .4s ease;
    }
    .contact-card:hover::before { opacity: .5; }
    .contact-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(201,162,77,.1);
    }
    .contact-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--gca-gold), #d4af37);
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(201,162,77,.2);
    }
    .contact-card h6 {
        font-weight: 700;
        margin-bottom: 3px;
        font-size: 14px;
        color: #222;
    }
    .contact-card p {
        margin: 0;
        font-size: 13.5px;
        color: #666;
        line-height: 1.6;
    }
    .contact-card p a { color: #666; text-decoration: none; }
    .contact-card p a:hover { color: var(--gca-gold); }

    .social-circle {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--gca-gold), #d4af37);
        color: #000;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        text-decoration: none;
        transition: all .3s ease;
        box-shadow: 0 3px 10px rgba(201,162,77,.15);
    }
    .social-circle:hover {
        background: #b8933f;
        color: #000;
        transform: scale(1.1) translateY(-2px);
        box-shadow: 0 6px 20px rgba(201,162,77,.25);
    }

    .form-card {
        background: #fff;
        border-radius: 20px;
        padding: 36px 32px;
        box-shadow: 0 8px 30px rgba(0,0,0,.05);
        border: 1px solid #f0ede8;
        height: 100%;
    }
    .form-card h5 {
        font-weight: 800;
        font-size: 1.2rem;
        margin-bottom: 4px;
    }
    .form-card .form-subtitle {
        color: #999;
        font-size: 14px;
        margin-bottom: 24px;
    }
    .form-contact {
        border-radius: 12px;
        border: 1.5px solid #e8e4dc;
        padding: 11px 16px;
        font-size: 14px;
        transition: border-color .2s, box-shadow .2s, transform .15s;
    }
    .form-contact:focus {
        border-color: var(--gca-gold);
        box-shadow: 0 0 0 3px rgba(201,162,77,.12);
        transform: translateY(-1px);
    }
    .form-contact::placeholder { color: #bbb; }

    .map-card {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0,0,0,.05);
        border: 1px solid #f0ede8;
    }
    .map-card iframe {
        display: block;
        width: 100%;
    }

    .whatsapp-direct {
        display: flex;
        align-items: center;
        gap: 16px;
        background: linear-gradient(135deg, #f0faf4 0%, #e8f5ee 100%);
        border: 1px solid #c8e6d0;
        border-radius: 18px;
        padding: 20px 24px;
        text-decoration: none;
        transition: all .3s ease;
    }
    .whatsapp-direct:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37,211,102,.15);
    }
    .whatsapp-direct .wa-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        background: #25d366;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .whatsapp-direct .wa-info h6 {
        font-weight: 700;
        margin-bottom: 1px;
        color: #1a5a2e;
        font-size: 14px;
    }
    .whatsapp-direct .wa-info p {
        margin: 0;
        font-size: 13px;
        color: #3a8a5a;
    }
    .whatsapp-direct .wa-arrow {
        margin-left: auto;
        color: #25d366;
        font-size: 1.3rem;
        transition: transform .3s ease;
    }
    .whatsapp-direct:hover .wa-arrow {
        transform: translateX(4px);
    }

    .section-header-contact {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .section-header-contact .line {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, var(--gca-gold), transparent);
    }
    .section-header-contact h5 {
        font-weight: 700;
        white-space: nowrap;
        margin: 0;
        font-size: 1rem;
        color: #444;
    }

    @media (max-width: 768px) {
        .contact-hero { padding: 70px 20px 50px; }
        .contact-hero h1 { font-size: 1.8rem; }
        .form-card { padding: 24px 18px; }
        .contact-card { padding: 18px 20px; }
    }
</style>

<div class="contact-hero" data-aos="fade-down">
    <h1>Contáctanos</h1>
    <p>Estamos aquí para ayudarte. Escríbenos, llámanos o visítanos. Te atenderemos con gusto.</p>
</div>

<div class="container my-5">

    <?php if ($mensaje_enviado): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4" data-aos="fade-down">
            <i class="bi bi-check-circle-fill fs-4"></i>
            <div>
                <strong>¡Mensaje enviado!</strong> Te responderemos a la brevedad.
            </div>
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

            <div class="section-header-contact" data-aos="fade-right">
                <h5>Información</h5>
                <span class="line"></span>
            </div>

            <div data-aos="fade-up" data-aos-delay="50">
                <a href="https://wa.me/573216548235?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20el%20colegio"
                   target="_blank" rel="noopener"
                   class="whatsapp-direct mb-4">
                    <div class="wa-icon"><i class="bi bi-whatsapp"></i></div>
                    <div class="wa-info">
                        <h6>WhatsApp Directo</h6>
                        <p>321 654 8235 · Respondemos en minutos</p>
                    </div>
                    <i class="bi bi-arrow-right-short wa-arrow"></i>
                </a>
            </div>

            <div class="contact-card" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <h6>Dirección</h6>
                    <p>Calle 3B #23-13, Barrio Callejas II<br>Valledupar – Cesar, Colombia</p>
                </div>
            </div>

            <div class="contact-card" data-aos="fade-up" data-aos-delay="150">
                <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                <div>
                    <h6>Teléfono</h6>
                    <p><a href="tel:+573216548235">321 654 8235</a></p>
                </div>
            </div>

            <div class="contact-card" data-aos="fade-up" data-aos-delay="200">
                <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                <div>
                    <h6>Correo Electrónico</h6>
                    <p><a href="mailto:gimnasiocastilloamericano@gmail.com">gimnasiocastilloamericano@gmail.com</a></p>
                </div>
            </div>

            <div class="contact-card" data-aos="fade-up" data-aos-delay="250">
                <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <h6>Horario de Atención</h6>
                    <p>Lunes a Viernes: 7:00 a.m. – 2:00 p.m.<br>Sábados: 8:00 a.m. – 12:00 p.m.</p>
                </div>
            </div>

            <div class="contact-card" data-aos="fade-up" data-aos-delay="300">
                <div class="contact-icon"><i class="bi bi-globe2"></i></div>
                <div>
                    <h6>Síguenos</h6>
                    <div class="d-flex gap-2 mt-2">
                        <a href="#" class="social-circle" title="Facebook" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-circle" title="Instagram" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-circle" title="WhatsApp" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a href="#" class="social-circle" title="YouTube" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>

        </div>

        <!-- COL FORMULARIO -->
        <div class="col-lg-7">

            <div class="section-header-contact" data-aos="fade-left">
                <span class="line" style="background:linear-gradient(90deg, transparent, var(--gca-gold));"></span>
                <h5>Envíanos un mensaje</h5>
            </div>

            <div class="form-card" data-aos="fade-up" data-aos-delay="100">
                <p class="form-subtitle">Déjanos tus datos y te contactaremos lo antes posible.</p>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:14px;">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control form-contact" placeholder="Tu nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:14px;">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-contact" placeholder="tu@correo.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:14px;">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control form-contact" placeholder="Opcional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:14px;">Asunto</label>
                            <input type="text" name="asunto" class="form-control form-contact" placeholder="¿Sobre qué nos escribes?">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:14px;">Mensaje <span class="text-danger">*</span></label>
                            <textarea name="mensaje" class="form-control form-contact" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="enviar" class="btn btn-gca px-5 py-2">
                                <i class="bi bi-send-fill me-2"></i>Enviar mensaje
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>

    </div>

    <!-- MAPA -->
    <div class="map-card mt-4" data-aos="fade-up">
        <iframe
            src="https://www.google.com/maps?q=Calle+3B+23-13+Barrio+Callejas+II+Semillitas+del+reino+Valledupar&output=embed"
            height="380"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            title="Ubicación del colegio">
        </iframe>
    </div>

    <div class="text-center mt-4" data-aos="fade-up">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Volver al inicio
        </a>
    </div>

</div>

<?php include "footer.php"; ?>
