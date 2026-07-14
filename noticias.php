<?php
require_once __DIR__ . '/includes/init.php';

$noticias = db()->fetchAll(
    "SELECT * FROM noticias WHERE activo = TRUE ORDER BY fecha_publicacion DESC"
);

include "header.php";
?>

<div class="hm-wrap">

    <!-- BANNER -->
    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title">Noticias <span class="grad">Institucionales</span></h1>
            <p class="hm-ph-sub">Conoce las actividades, eventos y momentos destacados del Gimnasio Castillo Americano.</p>
        </div>
    </section>

    <section class="hm-section">
        <div class="container">

            <?php if (empty($noticias)): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <i class="bi bi-newspaper" style="font-size:3rem;color:#d8cfbb;"></i>
                <p class="hm-p mt-3">No hay noticias publicadas aún.</p>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php $i = 0; foreach ($noticias as $n): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                    <article class="hm-card h-100 d-flex flex-column">
                        <div class="hm-card-media">
                            <?php if ($n["imagen"]): ?>
                            <img src="<?= htmlspecialchars($n["imagen"]) ?>" alt="<?= htmlspecialchars($n["titulo"]) ?>" loading="lazy"
                                 onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                            <div class="hm-card-ph d-none" aria-hidden="true"><i class="bi bi-image"></i></div>
                            <?php else: ?>
                            <div class="hm-card-ph" aria-hidden="true"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                            <?php if (!empty($n["categoria"])): ?>
                            <span class="hm-card-badge"><?= htmlspecialchars($n["categoria"]) ?></span>
                            <?php endif; ?>
                            <span class="hm-card-date"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= date("d M Y", strtotime($n["fecha_publicacion"])) ?></span>
                        </div>
                        <div class="hm-card-body tight flex-grow-1">
                            <h3 class="hm-card-title"><?= htmlspecialchars($n["titulo"]) ?></h3>
                            <?php if ($n["contenido"]): ?>
                            <p class="hm-card-text hm-clamp-4"><?= nl2br(htmlspecialchars($n["contenido"])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="hm-card-foot">
                            <small><i class="bi bi-clock"></i> Publicado <?= date("d \\d\\e F \\d\\e Y", strtotime($n["fecha_publicacion"])) ?></small>
                        </div>
                    </article>
                </div>
                <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="index.php" class="hm-back"><i class="bi bi-arrow-left-short"></i> Volver al inicio</a>
            </div>

        </div>
    </section>

</div>

<?php include "footer.php"; ?>
