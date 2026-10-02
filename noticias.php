<?php
require_once __DIR__ . '/includes/init.php';

$noticias = db()->fetchAll(
    "SELECT * FROM noticias WHERE activo = TRUE ORDER BY fecha_publicacion DESC"
);

/* Fechas en español: date("F"/"M") sale en inglés ("July"). */
if (!function_exists('fecha_es')) {
    function fecha_es(string $fecha, bool $corta = false): string
    {
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $t = strtotime($fecha);
        $mes = $meses[(int) date('n', $t)];
        return $corta
            ? date('d', $t) . ' ' . ucfirst(mb_substr($mes, 0, 3)) . ' ' . date('Y', $t)
            : date('j', $t) . ' de ' . $mes . ' de ' . date('Y', $t);
    }
}

$pageTitle = 'Noticias';
$pageDesc = 'Noticias y novedades del Gimnasio Castillo Americano.';
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
                <i class="bi bi-newspaper" style="font-size:3rem;color:#d8cfbb;" aria-hidden="true"></i>
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
                            <div class="hm-card-ph d-none" aria-hidden="true"><i class="bi bi-image" aria-hidden="true"></i></div>
                            <?php else: ?>
                            <div class="hm-card-ph" aria-hidden="true"><i class="bi bi-image" aria-hidden="true"></i></div>
                            <?php endif; ?>
                            <?php if (!empty($n["categoria"])): ?>
                            <span class="hm-card-badge"><?= htmlspecialchars($n["categoria"]) ?></span>
                            <?php endif; ?>
                            <span class="hm-card-date"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= fecha_es($n["fecha_publicacion"], true) ?></span>
                        </div>
                        <div class="hm-card-body tight flex-grow-1">
                            <h3 class="hm-card-title"><?= htmlspecialchars($n["titulo"]) ?></h3>
                            <?php if ($n["contenido"]): $largo = mb_strlen($n["contenido"]) > 220; ?>
                            <p class="hm-card-text <?= $largo ? 'hm-clamp-4' : '' ?>" id="noticia-<?= (int) $n["id"] ?>"><?= nl2br(htmlspecialchars($n["contenido"])) ?></p>
                            <?php if ($largo): ?>
                            <button type="button" class="hm-more" aria-expanded="false" aria-controls="noticia-<?= (int) $n["id"] ?>"
                                    onclick="var p=document.getElementById(this.getAttribute('aria-controls')),abierto=p.classList.toggle('hm-clamp-4')===false;this.setAttribute('aria-expanded',abierto);this.firstChild.nodeValue=abierto?'Leer menos ':'Leer más ';">Leer más <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="hm-card-foot">
                            <small><i class="bi bi-clock" aria-hidden="true"></i> Publicado el <?= fecha_es($n["fecha_publicacion"]) ?></small>
                        </div>
                    </article>
                </div>
                <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="index.php" class="hm-back"><i class="bi bi-arrow-left-short" aria-hidden="true"></i> Volver al inicio</a>
            </div>

        </div>
    </section>

</div>

<?php include "footer.php"; ?>
