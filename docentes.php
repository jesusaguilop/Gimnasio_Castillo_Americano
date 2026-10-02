<?php
require_once __DIR__ . '/includes/init.php';

$docentes = db()->fetchAll(
    "SELECT * FROM docentes WHERE activo = TRUE ORDER BY nombre ASC"
);

$pageTitle = 'Docentes';
$pageDesc = 'Conoce al equipo docente del Gimnasio Castillo Americano.';
include "header.php";
?>

<div class="hm-wrap">

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
                    <i class="bi bi-people" style="font-size:3rem;color:#c9c3b6;" aria-hidden="true"></i>
                    <p class="mt-3" style="color:#6b7280;">El directorio docente estará disponible próximamente.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 justify-content-center">
                    <?php $i = 0; foreach ($docentes as $d):
                        $nombre = (string) $d['nombre'];
                        $esp    = (string) ($d['especialidad'] ?? '');
                        $desc   = (string) ($d['descripcion'] ?? '');
                        $email  = (string) ($d['email'] ?? '');
                        $foto   = (string) ($d['foto'] ?? '');
                        $video  = (string) ($d['video_url'] ?? '');
                    ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 100 ?>">
                            <article class="hm-teacher">
                                <div class="hm-teacher-top">
                                    <?php if ($foto !== ''): ?>
                                        <img src="<?= htmlspecialchars($foto) ?>" alt="" class="hm-teacher-img" loading="lazy"
                                             onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                        <div class="hm-teacher-ph d-none" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                                    <?php else: ?>
                                        <div class="hm-teacher-ph" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="hm-teacher-body">
                                    <h2 class="hm-teacher-name"><?= htmlspecialchars($nombre) ?></h2>
                                    <?php if ($esp !== ''): ?>
                                        <div><span class="spec"><?= htmlspecialchars($esp) ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($desc !== ''): ?>
                                        <p><?= htmlspecialchars($desc) ?></p>
                                    <?php endif; ?>
                                    <span class="hm-teacher-more" aria-hidden="true">Ver perfil <i class="bi bi-arrow-right"></i></span>
                                </div>
                                <!-- Botón invisible que cubre la tarjeta: accesible con teclado y lector de pantalla -->
                                <button type="button" class="hm-teacher-link"
                                        data-bs-toggle="modal" data-bs-target="#docModal"
                                        data-nombre="<?= htmlspecialchars($nombre) ?>"
                                        data-especialidad="<?= htmlspecialchars($esp) ?>"
                                        data-descripcion="<?= htmlspecialchars($desc) ?>"
                                        data-email="<?= htmlspecialchars($email) ?>"
                                        data-foto="<?= htmlspecialchars($foto) ?>"
                                        data-video="<?= htmlspecialchars($video) ?>">
                                    <span class="visually-hidden">Ver perfil de <?= htmlspecialchars($nombre) ?></span>
                                </button>
                            </article>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ─── Perfil del docente ─── -->
<div class="modal fade hm-dm" id="docModal" tabindex="-1" aria-labelledby="dmNombre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px;">
        <div class="modal-content">
            <div class="hm-dm-media">
                <span class="hm-dm-glow"></span>
                <button type="button" class="hm-dm-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
                <div class="hm-dm-mediaHolder" id="dmMedia"></div>
                <h2 class="hm-dm-name" id="dmNombre"></h2>
                <span class="hm-dm-spec" id="dmEsp"></span>
            </div>
            <div class="hm-dm-body">
                <div id="dmDescWrap">
                    <div class="lbl">Sobre el docente</div>
                    <p class="desc" id="dmDesc"></p>
                </div>
                <a href="#" class="hm-dm-email" id="dmEmail">
                    <i class="bi bi-envelope-fill" aria-hidden="true"></i> <span></span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('docModal');
    if (!modal) return;
    var media = document.getElementById('dmMedia');

    // Solo se aceptan YouTube, Vimeo o archivos .mp4/.webm; nunca HTML arbitrario
    function videoNodo(url) {
        var u;
        try { u = new URL(url, location.href); } catch (e) { return null; }
        if (u.protocol !== 'https:' && u.protocol !== 'http:') return null;
        var host = u.hostname.replace(/^www\.|^m\./, ''), src = null, m;
        if (host === 'youtu.be') {
            src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(u.pathname.slice(1));
        } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
            if (u.searchParams.get('v')) src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(u.searchParams.get('v'));
            else if ((m = u.pathname.match(/^\/(?:embed|shorts)\/([\w-]+)/))) src = 'https://www.youtube-nocookie.com/embed/' + m[1];
        } else if (host === 'vimeo.com' || host === 'player.vimeo.com') {
            if ((m = u.pathname.match(/(\d+)/))) src = 'https://player.vimeo.com/video/' + m[1];
        }
        var box = document.createElement('div');
        box.className = 'hm-dm-video';
        if (src) {
            var f = document.createElement('iframe');
            f.src = src;
            f.title = 'Video de presentación';
            f.allow = 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            f.allowFullscreen = true;
            f.loading = 'lazy';
            box.appendChild(f);
            return box;
        }
        if (/\.(mp4|webm)$/i.test(u.pathname)) {
            var v = document.createElement('video');
            v.src = u.href;
            v.controls = true;
            v.preload = 'metadata';
            box.appendChild(v);
            return box;
        }
        return null;
    }

    function fotoNodo(foto) {
        var ph = document.createElement('div');
        ph.className = 'hm-dm-photo ph';
        ph.setAttribute('aria-hidden', 'true');
        ph.innerHTML = '<i class="bi bi-person-fill"></i>';
        if (!foto) return ph;
        var img = document.createElement('img');
        img.className = 'hm-dm-photo';
        img.alt = '';
        img.src = foto;
        img.onerror = function () { img.replaceWith(ph); };
        return img;
    }

    modal.addEventListener('show.bs.modal', function (e) {
        var d = e.relatedTarget ? e.relatedTarget.dataset : {};
        media.textContent = '';
        media.appendChild((d.video && videoNodo(d.video)) || fotoNodo(d.foto));

        document.getElementById('dmNombre').textContent = d.nombre || '';
        var esp = document.getElementById('dmEsp');
        esp.textContent = d.especialidad || '';
        esp.hidden = !d.especialidad;

        document.getElementById('dmDesc').textContent = d.descripcion || '';
        document.getElementById('dmDescWrap').hidden = !d.descripcion;

        var mail = document.getElementById('dmEmail');
        mail.hidden = !d.email;
        mail.href = d.email ? 'mailto:' + d.email : '#';
        mail.querySelector('span').textContent = d.email || '';
    });

    // Al cerrar se quita el video para que no siga sonando
    modal.addEventListener('hidden.bs.modal', function () { media.textContent = ''; });
})();
</script>

<?php include "footer.php"; ?>
