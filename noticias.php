<?php
require_once __DIR__ . '/includes/init.php';

$noticias = db()->fetchAll(
    "SELECT * FROM noticias WHERE activo = TRUE ORDER BY fecha_publicacion DESC"
);

include "header.php";
?>

<style>
    .news-page-hero {
        background: linear-gradient(135deg, #0f0f0f, #1a1a2e);
        color: #fff;
        padding: 80px 20px 56px;
        text-align: center;
        position: relative;
        isolation: isolate;
    }
    .news-page-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gca-gold), transparent);
    }
    .news-page-hero h1 { font-weight: 900; font-size: 2.4rem; }
    .news-page-hero h1::after {
        content: '';
        display: block;
        width: 70px; height: 3px;
        background: var(--gca-gold);
        margin: 14px auto 0;
        border-radius: 2px;
    }
    .news-page-hero p { color: #aaa; max-width: 560px; margin: 14px auto 0; font-size: 1.05rem; }
</style>

<div class="news-page-hero" data-aos="fade-down">
    <h1>Noticias Institucionales</h1>
    <p>Conoce las actividades, eventos y momentos destacados del Gimnasio Castillo Americano.</p>
</div>

<div class="container my-5">

    <?php if (empty($noticias)): ?>
    <div class="text-center py-5" data-aos="fade-up">
        <i class="bi bi-newspaper" style="font-size:3rem;color:#ccc;"></i>
        <p class="text-muted mt-3">No hay noticias publicadas aún.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($noticias as $n): ?>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= 100 * ($loop ?? 0) ?>">
            <div class="card news-card h-100">
                <div class="news-img-wrap">
                    <?php if ($n["imagen"]): ?>
                    <img src="<?= htmlspecialchars($n["imagen"]) ?>"
                         class="card-img-top noticia-img"
                         alt="<?= htmlspecialchars($n["titulo"]) ?>"
                         loading="lazy">
                    <?php else: ?>
                    <div class="noticia-img d-flex align-items-center justify-content-center" style="background:#f0f0f0;">
                        <i class="bi bi-image" style="font-size:2.5rem;color:#ccc;"></i>
                    </div>
                    <?php endif; ?>
                    <span class="badge-gca"><?= htmlspecialchars($n["categoria"]) ?></span>
                    <span class="news-date">
                        <i class="bi bi-calendar3"></i>
                        <?= date("d M Y", strtotime($n["fecha_publicacion"])) ?>
                    </span>
                </div>

                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= htmlspecialchars($n["titulo"]) ?></h5>
                    <?php if ($n["contenido"]): ?>
                    <p class="card-text flex-grow-1"><?= nl2br(htmlspecialchars($n["contenido"])) ?></p>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <i class="bi bi-clock"></i> Publicado <?= date("d \\d\\e F \\d\\e Y", strtotime($n["fecha_publicacion"])) ?>
                </div>
            </div>
        </div>
        <?php $loop = ($loop ?? 0) + 1; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="text-center mt-5" data-aos="fade-up">
        <a href="index.php" class="btn btn-outline-dark px-4" style="border-radius:40px;">
            Volver al inicio
        </a>
    </div>

</div>

<?php include "footer.php"; ?>
