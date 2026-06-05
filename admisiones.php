<?php include "header.php"; ?>

<div class="admissions-hero" data-aos="fade-down">
    <h1>Admisiones</h1>
    <p>Todo lo que necesitas saber para formar parte del Gimnasio Castillo Americano</p>
</div>

<div class="container my-5">

    <!-- Requisitos -->
    <section class="mb-5" data-aos="fade-up">
        <h2 class="fw-bold mb-4">Requisitos de Ingreso</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <ul class="list-group list-group-flush" style="border-radius:16px;overflow:hidden;box-shadow:var(--gca-card-shadow);">
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">1</span>
                        Documento de identidad del estudiante (original y copia)
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">2</span>
                        Registro civil de nacimiento
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">3</span>
                        Fotografías tamaño documento (2)
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">4</span>
                        Certificado de estudios anteriores
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">5</span>
                        Fotocopia del documento del acudiente
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span style="width:32px;height:32px;border-radius:50%;background:var(--gca-gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">6</span>
                        Diligenciar formulario de inscripción
                    </li>
                </ul>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div style="background:#f9f9f9;border-radius:20px;padding:32px;box-shadow:var(--gca-card-shadow);">
                    <h5 class="fw-bold mb-3">Proceso de Matrícula</h5>
                    <p class="text-muted">
                        El proceso de admisión se realiza de forma presencial en
                        nuestras instalaciones. Te invitamos a agendar una cita
                        para conocer nuestras instalaciones y recibir información
                        detallada sobre nuestros programas educativos.
                    </p>
                    <a href="contacto.php" class="btn-gca">Contáctanos</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pasos -->
    <section data-aos="fade-up">
        <h2 class="fw-bold mb-4 text-center">Pasos para la Admisión</h2>
        <div class="row g-4 mt-2">
            <?php
            $pasos = [
                ["Solicitud de Información", "Comunícate con nosotros para recibir información sobre nuestros niveles educativos y disponibilidad de cupos."],
                ["Visita Institucional", "Te invitamos a conocer nuestras instalaciones y nuestro proyecto educativo en una visita guiada."],
                ["Entrega de Documentos", "Presenta la documentación requerida en la secretaría del colegio."],
                ["Entrevista", "Se realizará una entrevista con el estudiante y su acudiente para conocer sus expectativas."],
                ["Matrícula", "Formaliza la matrícula y recibe la bienvenida a la familia Castillo Americano."],
            ];
            foreach ($pasos as $i => $p):
            ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                <div class="step-card">
                    <div class="step-number"><?= $i + 1 ?></div>
                    <h5><?= $p[0] ?></h5>
                    <p><?= $p[1] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA -->
    <div class="text-center mt-5" data-aos="fade-up">
        <a href="contacto.php" class="btn-gca btn-lg px-5 py-3">Solicitar Información</a>
    </div>

</div>

<?php include "footer.php"; ?>
