<?php include "header.php"; ?>

<style>
    .adm-hero {
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        color: #fff;
        padding: 100px 20px 70px;
        text-align: center;
        position: relative;
        isolation: isolate;
        overflow: hidden;
    }
    .adm-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 600px 300px at 20% 80%, rgba(201,162,77,.08) 0%, transparent 70%),
            radial-gradient(ellipse 500px 400px at 80% 20%, rgba(201,162,77,.05) 0%, transparent 70%);
        z-index: 0;
    }
    .adm-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gca-gold), transparent);
    }
    .adm-hero > * { position: relative; z-index: 1; }
    .adm-hero h1 {
        font-weight: 900;
        font-size: 2.6rem;
    }
    .adm-hero h1::after {
        content: '';
        display: block;
        width: 70px; height: 3px;
        background: var(--gca-gold);
        margin: 16px auto 0;
        border-radius: 2px;
    }
    .adm-hero p {
        color: #bbb;
        max-width: 600px;
        margin: 18px auto 0;
        font-size: 1.1rem;
        line-height: 1.7;
    }

    .section-title {
        font-weight: 800;
        font-size: 1.6rem;
        margin-bottom: 28px;
        position: relative;
        display: inline-block;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -6px; left: 0;
        width: 50px; height: 3px;
        background: var(--gca-gold);
        border-radius: 2px;
    }
    .section-title.text-center::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .req-card {
        background: #fff;
        border-radius: 18px;
        padding: 10px 0;
        box-shadow: 0 8px 30px rgba(0,0,0,.04);
        border: 1px solid #f0ede8;
        overflow: hidden;
    }
    .req-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 24px;
        transition: background .2s ease, transform .2s ease;
        border-bottom: 1px solid #f5f4f0;
    }
    .req-item:last-child { border-bottom: none; }
    .req-item:hover {
        background: rgba(201,162,77,.04);
        transform: translateX(4px);
    }
    .req-num {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--gca-gold), #d4af37);
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 15px;
        flex-shrink: 0;
        box-shadow: 0 3px 10px rgba(201,162,77,.25);
    }
    .req-text {
        font-weight: 500;
        color: #333;
        font-size: 15px;
        line-height: 1.4;
    }

    .info-box {
        background: linear-gradient(135deg, #faf9f6 0%, #f5f3ee 100%);
        border-radius: 20px;
        padding: 36px 32px;
        border: 1px solid #f0ede8;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .info-box::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        background: linear-gradient(180deg, var(--gca-gold), #d4af37);
        border-radius: 0 2px 2px 0;
    }
    .info-box h5 {
        font-weight: 800;
        font-size: 1.15rem;
        margin-bottom: 12px;
    }
    .info-box p {
        color: #777;
        font-size: 14px;
        line-height: 1.7;
        margin-bottom: 20px;
    }

    .step-card-modern {
        background: #fff;
        border-radius: 20px;
        padding: 32px 24px 28px;
        box-shadow: 0 8px 30px rgba(0,0,0,.04);
        transition: all .4s cubic-bezier(.22,1,.36,1);
        height: 100%;
        text-align: center;
        border: 1px solid #f0ede8;
        position: relative;
        overflow: hidden;
    }
    .step-card-modern::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--gca-gold), #d4af37, var(--gca-gold));
        opacity: 0;
        transition: opacity .4s ease;
    }
    .step-card-modern:hover::before { opacity: 1; }
    .step-card-modern:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 50px rgba(0,0,0,.08);
    }
    .step-card-modern .step-icon {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(201,162,77,.12), rgba(201,162,77,.06));
        color: var(--gca-gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0 auto 18px;
        transition: all .3s ease;
    }
    .step-card-modern:hover .step-icon {
        background: linear-gradient(135deg, var(--gca-gold), #d4af37);
        color: #000;
        box-shadow: 0 6px 20px rgba(201,162,77,.3);
        transform: scale(1.06);
    }
    .step-card-modern h5 {
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 1.05rem;
    }
    .step-card-modern p {
        font-size: 14px;
        color: #888;
        line-height: 1.6;
        margin: 0;
    }

    .cta-section {
        background: linear-gradient(135deg, #0f0f0f, #1a1a2e);
        border-radius: 24px;
        padding: 56px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .cta-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 500px 200px at 50% 100%, rgba(201,162,77,.08) 0%, transparent 70%);
    }
    .cta-section > * { position: relative; z-index: 1; }
    .cta-section h3 {
        color: #fff;
        font-weight: 800;
        font-size: 1.7rem;
        margin-bottom: 12px;
    }
    .cta-section p {
        color: #aaa;
        max-width: 560px;
        margin: 0 auto 28px;
        font-size: 1rem;
        line-height: 1.7;
    }
    .cta-section .btn-gca {
        font-size: 1rem;
        padding: 14px 36px;
    }

    .download-card {
        display: flex;
        align-items: center;
        gap: 20px;
        background: #fff;
        border-radius: 18px;
        padding: 24px 28px;
        box-shadow: 0 8px 30px rgba(0,0,0,.04);
        border: 1px solid #f0ede8;
        transition: all .3s ease;
        text-decoration: none;
    }
    .download-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 40px rgba(0,0,0,.08);
    }
    .download-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(201,162,77,.12), rgba(201,162,77,.06));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--gca-gold);
        flex-shrink: 0;
    }
    .download-card .dl-info { flex: 1; }
    .download-card .dl-info h6 {
        font-weight: 700;
        margin-bottom: 2px;
        color: #222;
    }
    .download-card .dl-info small {
        color: #999;
        font-size: 12px;
    }
    .download-card .dl-btn {
        background: var(--gca-gold);
        color: #000;
        border: none;
        border-radius: 40px;
        padding: 8px 20px;
        font-weight: 600;
        font-size: 13px;
        transition: all .3s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .download-card .dl-btn:hover {
        background: #b8933f;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .adm-hero { padding: 70px 20px 50px; }
        .adm-hero h1 { font-size: 1.8rem; }
        .adm-hero p { font-size: 1rem; }
        .section-title { font-size: 1.3rem; }
        .req-item { padding: 12px 18px; }
        .req-text { font-size: 14px; }
        .step-card-modern { padding: 24px 18px 20px; }
        .cta-section { padding: 40px 24px; }
        .cta-section h3 { font-size: 1.3rem; }
        .info-box { padding: 28px 24px; }
        .download-card { flex-wrap: wrap; }
        .download-card .dl-btn { width: 100%; text-align: center; }
    }
</style>

<div class="adm-hero" data-aos="fade-down">
    <h1>Admisiones</h1>
    <p>Todo lo que necesitas saber para formar parte del Gimnasio Castillo Americano. Te acompañamos en cada paso del proceso.</p>
</div>

<div class="container my-5">

    <!-- REQUISITOS -->
    <section class="mb-5" data-aos="fade-up">
        <h2 class="section-title">Requisitos de Ingreso</h2>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="req-card">
                    <?php $requisitos = [
                        "Documento de identidad del estudiante (original y copia)",
                        "Registro civil de nacimiento",
                        "Fotografías tamaño documento (2)",
                        "Certificado de estudios anteriores",
                        "Fotocopia del documento del acudiente",
                        "Formulario de inscripción diligenciado",
                    ]; ?>
                    <?php foreach ($requisitos as $i => $r): ?>
                    <div class="req-item">
                        <span class="req-num"><?= $i + 1 ?></span>
                        <span class="req-text"><?= $r ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="info-box">
                    <h5>Proceso de Matrícula</h5>
                    <p>
                        El proceso de admisión se realiza de forma presencial en
                        nuestras instalaciones. Agenda una cita para conocer nuestro
                        colegio y recibir información detallada sobre nuestros
                        programas educativos.
                    </p>
                    <div>
                        <a href="contacto.php" class="btn-gca">Agendar Cita</a>
                    </div>
                    <div class="mt-3" id="formDownload">
                        <?php
                        $pdf_path_rel = 'assets/docs/formulario_inscripcion.pdf';
                        $pdf_path_abs = __DIR__ . '/' . $pdf_path_rel;
                        if (file_exists($pdf_path_abs)) {
                            ?>
                            <a class="download-card" href="<?= $pdf_path_rel ?>" target="_blank" rel="noopener noreferrer">
                                <div class="download-icon"><i class="bi bi-download"></i></div>
                                <div class="dl-info">
                                    <h6>Formulario de Inscripción</h6>
                                    <small>Descargar formato en PDF</small>
                                </div>
                                <div><span class="dl-btn">Descargar</span></div>
                            </a>
                            <?php
                        } else {
                            ?>
                            <div class="download-card">
                                <div class="dl-info">
                                    <h6>Formulario de Inscripción</h6>
                                    <small class="text-danger">PDF no disponible — sube el archivo a assets/docs/formulario_inscripcion.pdf</small>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FORMATO DE INSCRIPCIÓN -->
    <section class="mb-5" data-aos="fade-up">
        <h2 class="section-title">Formato de Inscripción</h2>
        <p class="text-muted mb-4" style="max-width:600px;">
            Descarga el formulario de inscripción, complétalo y preséntalo en la
            secretaría del colegio junto con los demás documentos.
        </p>
        <div class="row g-3" id="downloadsContainer">
            <?php
            // listado de formatos disponibles (actualmente solo el formulario)
            $fmt_rel = 'assets/docs/formulario_inscripcion.pdf';
            if (file_exists(__DIR__ . '/' . $fmt_rel)):
            ?>
            <div class="col-md-6">
                <a class="download-card" href="<?= $fmt_rel ?>" target="_blank" rel="noopener noreferrer">
                    <div class="download-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                    <div class="dl-info">
                        <h6>Formulario de Inscripción (PDF)</h6>
                        <small>Descargar</small>
                    </div>
                    <div><span class="dl-btn">Descargar</span></div>
                </a>
            </div>
            <?php else: ?>
            <div class="col-12">
                <div class="download-card">
                    <div class="dl-info">
                        <h6>Formulario de Inscripción</h6>
                        <small class="text-danger">PDF no disponible. Coloca el archivo en <strong>assets/docs/formulario_inscripcion.pdf</strong></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- PASOS -->
    <section class="mb-5" data-aos="fade-up">
        <h2 class="section-title text-center">Pasos para la Admisión</h2>
        <div class="row g-4 mt-2">
            <?php
            $pasos = [
                ["Solicitud de Información", "Comunícate con nosotros para recibir información sobre nuestros niveles educativos y disponibilidad de cupos.", "bi bi-info-circle"],
                ["Visita Institucional", "Conoce nuestras instalaciones y nuestro proyecto educativo en una visita guiada personalizada.", "bi bi-building"],
                ["Entrega de Documentos", "Presenta la documentación requerida en la secretaría del colegio.", "bi bi-file-earmark-text"],
                ["Entrevista", "Entrevista con el estudiante y su acudiente para conocer sus expectativas.", "bi bi-chat-dots"],
                ["Matrícula", "Formaliza la matrícula y recibe la bienvenida a la familia Castillo Americano.", "bi bi-star"],
            ];
            foreach ($pasos as $i => $p):
            ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                <div class="step-card-modern">
                    <div class="step-icon"><i class="<?= $p[2] ?>"></i></div>
                    <h5><?= $p[0] ?></h5>
                    <p><?= $p[1] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section mb-4" data-aos="fade-up">
        <h3>¿Listo para ser parte de nuestra familia?</h3>
        <p>
            En el Gimnasio Castillo Americano formamos líderes con valores,
            excelencia académica y visión global. Te esperamos.
        </p>
        <a href="contacto.php" class="btn-gca btn-lg px-5 py-3">Solicitar Información</a>
    </section>

</div>

<?php include "footer.php"; ?>
