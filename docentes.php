<?php
require_once __DIR__ . '/includes/init.php';

$docentes = db()->fetchAll(
    "SELECT * FROM docentes WHERE activo = TRUE ORDER BY nombre ASC"
);

include "header.php";
?>

<div class="hm-wrap">

    <!-- BANNER -->
    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title">Nuestros <span class="grad">Docentes</span></h1>
            <p class="hm-ph-sub">Conoce al equipo de profesionales que hacen posible la excelencia educativa.</p>
        </div>
    </section>

    <section class="hm-section">
        <div class="container">
            <?php if (empty($docentes)): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <i class="bi bi-people" style="font-size:3rem;color:#d8cfbb;"></i>
                <p class="hm-p mt-3">El directorio docente estará disponible próximamente.</p>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php $i = 0; foreach ($docentes as $d):
                    $nombre = $d["nombre"] ?? '';
                    $attrs = 'data-nombre="' . htmlspecialchars($nombre, ENT_QUOTES) . '"'
                        . ' data-especialidad="' . htmlspecialchars($d["especialidad"] ?? '', ENT_QUOTES) . '"'
                        . ' data-foto="' . htmlspecialchars($d["foto"] ?? '', ENT_QUOTES) . '"'
                        . ' data-descripcion="' . htmlspecialchars($d["descripcion"] ?? '', ENT_QUOTES) . '"'
                        . ' data-email="' . htmlspecialchars($d["email"] ?? '', ENT_QUOTES) . '"'
                        . ' data-video="' . htmlspecialchars($d["video_url"] ?? '', ENT_QUOTES) . '"';
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 100 ?>">
                    <div class="hm-teacher h-100">
                        <div class="hm-teacher-top">
                            <?php if ($d["foto"]): ?>
                            <img src="<?= htmlspecialchars($d["foto"]) ?>" alt="Foto de <?= htmlspecialchars($nombre) ?>" class="hm-teacher-img" loading="lazy"
                                 onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                            <div class="hm-teacher-ph d-none" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                            <?php else: ?>
                            <div class="hm-teacher-ph" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="hm-teacher-body">
                            <h5><?= htmlspecialchars($nombre) ?></h5>
                            <?php if ($d["especialidad"]): ?>
                            <div class="spec"><?= htmlspecialchars($d["especialidad"]) ?></div>
                            <?php endif; ?>
                            <?php if ($d["descripcion"]): ?>
                            <p><?= htmlspecialchars($d["descripcion"]) ?></p>
                            <?php endif; ?>
                            <span class="hm-teacher-more">Ver perfil <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                        <button type="button" class="hm-teacher-link"
                                data-bs-toggle="modal" data-bs-target="#docModal"
                                aria-label="Ver perfil de <?= htmlspecialchars($nombre, ENT_QUOTES) ?>"
                                <?= $attrs ?>></button>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<!-- MODAL PRESENTACIÓN DEL DOCENTE -->
<div class="modal fade hm-dm" id="docModal" tabindex="-1" aria-hidden="true" aria-labelledby="docModalName">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="hm-dm-media">
                <span class="hm-dm-glow"></span>
                <button type="button" class="hm-dm-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                <div class="hm-dm-mediaHolder" data-field="media"></div>
                <h3 class="hm-dm-name" id="docModalName" data-field="name"></h3>
                <span class="hm-dm-spec d-none" data-field="spec"></span>
            </div>
            <div class="hm-dm-body">
                <div class="lbl" data-field="descLbl">Sobre el docente</div>
                <p class="desc" data-field="desc"></p>
                <a class="hm-dm-email d-none" data-field="email" href="#" rel="noopener">
                    <i class="bi bi-envelope-fill" aria-hidden="true"></i> <span></span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('docModal');
    if (!modalEl) return;

    var media   = modalEl.querySelector('[data-field=media]');
    var nameEl  = modalEl.querySelector('[data-field=name]');
    var specEl  = modalEl.querySelector('[data-field=spec]');
    var descEl  = modalEl.querySelector('[data-field=desc]');
    var descLbl = modalEl.querySelector('[data-field=descLbl]');
    var emailEl = modalEl.querySelector('[data-field=email]');

    function placeholder() {
        var d = document.createElement('div');
        d.className = 'hm-dm-photo ph';
        d.innerHTML = '<i class="bi bi-person-fill"></i>';
        return d;
    }

    // Solo YouTube, Vimeo y archivos de video directos (seguro; sin inyectar HTML arbitrario)
    function buildVideo(url) {
        if (!url) return null;
        url = url.trim();
        var src = null, m, wrap, node;
        if ((m = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{11})/))) {
            src = 'https://www.youtube.com/embed/' + m[1];
        } else if ((m = url.match(/vimeo\.com\/(\d+)/))) {
            src = 'https://player.vimeo.com/video/' + m[1];
        } else if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url)) {
            wrap = document.createElement('div'); wrap.className = 'hm-dm-video';
            node = document.createElement('video'); node.controls = true; node.src = url;
            wrap.appendChild(node); return wrap;
        } else {
            return null;
        }
        wrap = document.createElement('div'); wrap.className = 'hm-dm-video';
        node = document.createElement('iframe');
        node.src = src; node.title = 'Video del docente';
        node.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        node.allowFullscreen = true;
        wrap.appendChild(node); return wrap;
    }

    modalEl.addEventListener('show.bs.modal', function (ev) {
        var t = ev.relatedTarget; if (!t) return;
        var d = t.dataset;

        nameEl.textContent = d.nombre || 'Docente';

        if (d.especialidad) { specEl.textContent = d.especialidad; specEl.classList.remove('d-none'); }
        else specEl.classList.add('d-none');

        if (d.descripcion) { descEl.textContent = d.descripcion; descEl.classList.remove('d-none'); descLbl.classList.remove('d-none'); }
        else { descEl.classList.add('d-none'); descLbl.classList.add('d-none'); }

        if (d.email) { emailEl.href = 'mailto:' + d.email; emailEl.querySelector('span').textContent = d.email; emailEl.classList.remove('d-none'); }
        else emailEl.classList.add('d-none');

        media.innerHTML = '';
        var vid = buildVideo(d.video);
        if (vid) {
            media.appendChild(vid);
        } else if (d.foto) {
            var img = document.createElement('img');
            img.className = 'hm-dm-photo'; img.src = d.foto; img.alt = 'Foto de ' + (d.nombre || 'docente');
            img.onerror = function () { media.innerHTML = ''; media.appendChild(placeholder()); };
            media.appendChild(img);
        } else {
            media.appendChild(placeholder());
        }
    });

    // Detener/limpiar el video al cerrar el modal
    modalEl.addEventListener('hidden.bs.modal', function () { media.innerHTML = ''; });
})();
</script>

<?php include "footer.php"; ?>
