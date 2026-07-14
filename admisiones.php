<?php include "header.php"; ?>

<div class="hm-wrap">

    <!-- BANNER -->
    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title"><span class="grad">Admisiones</span></h1>
            <p class="hm-ph-sub">Todo lo que necesitas saber para formar parte del Gimnasio Castillo Americano. Te acompañamos en cada paso del proceso.</p>
        </div>
    </section>

    <section class="hm-section">
        <div class="container">

            <!-- REQUISITOS -->
            <div class="mb-5" data-aos="fade-up">
                <span class="hm-kicker">Antes de empezar</span>
                <h2 class="hm-h2">Requisitos de Ingreso</h2>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="hm-panel">
                            <?php $requisitos = [
                                "Documento de identidad del estudiante (original y copia)",
                                "Registro civil de nacimiento",
                                "Fotografías tamaño documento (2)",
                                "Certificado de estudios anteriores",
                                "Fotocopia del documento del acudiente",
                                "Formulario de inscripción diligenciado",
                            ]; ?>
                            <?php foreach ($requisitos as $i => $r): ?>
                            <div class="hm-numitem">
                                <span class="hm-num"><?= $i + 1 ?></span>
                                <span class="t"><?= $r ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="hm-infopanel">
                            <h4>Proceso de Matrícula</h4>
                            <p>El proceso de admisión se realiza de forma presencial en nuestras instalaciones. Agenda una cita para conocer nuestro colegio y recibir información detallada sobre nuestros programas educativos.</p>
                            <a href="contacto.php" class="hm-btn hm-btn-primary">Agendar Cita <i class="bi bi-arrow-right-short"></i></a>
                            <?php
                            $pdf_path_rel = 'assets/docs/formulario_inscripcion.pdf';
                            if (file_exists(__DIR__ . '/' . $pdf_path_rel)): ?>
                            <a class="hm-download mt-3" href="<?= $pdf_path_rel ?>" target="_blank" rel="noopener noreferrer">
                                <div class="ic"><i class="bi bi-download"></i></div>
                                <div class="info"><h6>Formulario de Inscripción</h6><small>Descargar formato en PDF</small></div>
                                <span class="go">Descargar</span>
                            </a>
                            <?php else: ?>
                            <div class="hm-download mt-3">
                                <div class="ic"><i class="bi bi-file-earmark-pdf"></i></div>
                                <div class="info"><h6>Formulario de Inscripción</h6><small class="text-danger">PDF no disponible — súbelo a assets/docs/formulario_inscripcion.pdf</small></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORMATO DE INSCRIPCIÓN -->
            <div class="mb-5" data-aos="fade-up">
                <span class="hm-kicker">Descargas</span>
                <h2 class="hm-h2">Formato de Inscripción</h2>
                <p class="hm-p" style="max-width:640px;">Descarga el formulario de inscripción, complétalo y preséntalo en la secretaría del colegio junto con los demás documentos.</p>
                <div class="row g-3">
                    <?php $fmt_rel = 'assets/docs/formulario_inscripcion.pdf';
                    if (file_exists(__DIR__ . '/' . $fmt_rel)): ?>
                    <div class="col-md-6">
                        <a class="hm-download" href="<?= $fmt_rel ?>" target="_blank" rel="noopener noreferrer">
                            <div class="ic"><i class="bi bi-file-earmark-pdf"></i></div>
                            <div class="info"><h6>Formulario de Inscripción (PDF)</h6><small>Descargar</small></div>
                            <span class="go">Descargar</span>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="hm-download">
                            <div class="ic"><i class="bi bi-file-earmark-pdf"></i></div>
                            <div class="info"><h6>Formulario de Inscripción</h6><small class="text-danger">PDF no disponible. Coloca el archivo en <strong>assets/docs/formulario_inscripcion.pdf</strong></small></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PASOS -->
            <div class="mb-5" data-aos="fade-up">
                <div class="hm-head">
                    <span class="hm-kicker hm-kicker-center">Paso a paso</span>
                    <h2 class="hm-h2">Pasos para la Admisión</h2>
                </div>
                <div class="row g-4">
                    <?php
                    $pasos = [
                        ["Solicitud de Información", "Comunícate con nosotros para recibir información sobre nuestros niveles educativos y disponibilidad de cupos.", "bi bi-info-circle"],
                        ["Visita Institucional", "Conoce nuestras instalaciones y nuestro proyecto educativo en una visita guiada personalizada.", "bi bi-building"],
                        ["Entrega de Documentos", "Presenta la documentación requerida en la secretaría del colegio.", "bi bi-file-earmark-text"],
                        ["Entrevista", "Entrevista con el estudiante y su acudiente para conocer sus expectativas.", "bi bi-chat-dots"],
                        ["Matrícula", "Formaliza la matrícula y recibe la bienvenida a la familia Castillo Americano.", "bi bi-star"],
                    ];
                    foreach ($pasos as $i => $p): ?>
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                        <div class="hm-feature center">
                            <div class="hm-feature-icon"><i class="<?= $p[2] ?>"></i></div>
                            <h4><?= $p[0] ?></h4>
                            <p><?= $p[1] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- CTA -->
        <div class="container">
            <div class="hm-cta hm-cta-rounded" data-aos="fade-up">
                <span class="hm-cta-glow"></span>
                <div class="hm-cta-inner px-3">
                    <span class="hm-kicker hm-kicker-center hm-kicker-light">Únete a la familia</span>
                    <h2 class="hm-cta-title">¿Listo para ser parte de nuestra familia?</h2>
                    <p class="hm-cta-sub">En el Gimnasio Castillo Americano formamos líderes con valores, excelencia académica y visión global. Te esperamos.</p>
                    <a href="contacto.php" class="hm-btn hm-btn-primary hm-btn-lg">Solicitar Información <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
    </section>

</div>

<?php include "footer.php"; ?>
