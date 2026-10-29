<?php
$pageTitle = 'Institución';
$pageDesc = 'Misión, visión, valores e himno del Gimnasio Castillo Americano, colegio en Valledupar.';
include "header.php";
?>

<div class="hm-wrap">

    <!-- BANNER -->
    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title">Nuestra <span class="grad">Institución</span></h1>
            <p class="hm-ph-sub">Educación con carácter, valores sólidos y excelencia académica.</p>
        </div>
    </section>

    <!-- HISTORIA -->
    <section class="hm-section">
        <div class="container">
            <div class="hm-head" data-aos="fade-up">
                <span class="hm-kicker hm-kicker-center">Nuestra historia</span>
                <h2 class="hm-h2">Formando seres íntegros</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-9" data-aos="fade-up" data-aos-delay="100">
                    <p class="hm-p" style="text-align:center;font-size:1.1rem;">
                        El <strong>Gimnasio Castillo Americano</strong> nace con la firme convicción
                        de formar seres humanos íntegros, capaces de enfrentar los retos
                        académicos y sociales del mundo actual, fundamentados en principios,
                        disciplina y responsabilidad.
                    </p>
                    <p class="hm-p" style="text-align:center;font-size:1.1rem;">
                        Nuestra institución combina la tradición educativa con herramientas
                        pedagógicas modernas, promoviendo un ambiente de aprendizaje
                        estructurado, innovador y orientado al desarrollo integral de cada estudiante.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- MISIÓN / VISIÓN / VALORES -->
    <section class="hm-section hm-section-cream">
        <div class="container">
            <div class="hm-head" data-aos="fade-up">
                <span class="hm-kicker hm-kicker-center">Nuestro norte</span>
                <h2 class="hm-h2">Misión, Visión y Valores</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="hm-feature center">
                        <div class="hm-feature-icon"><i class="bi bi-bullseye" aria-hidden="true"></i></div>
                        <h3>Misión</h3>
                        <p>Formar estudiantes íntegros, responsables y críticos, con una sólida preparación académica y valores éticos que les permitan aportar positivamente a la sociedad.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="hm-feature center">
                        <div class="hm-feature-icon"><i class="bi bi-eye-fill" aria-hidden="true"></i></div>
                        <h3>Visión</h3>
                        <p>Ser reconocidos como una institución educativa de excelencia, líder en innovación académica, calidad humana y compromiso social.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="hm-feature center">
                        <div class="hm-feature-icon"><i class="bi bi-gem" aria-hidden="true"></i></div>
                        <h3>Valores</h3>
                        <p>Respeto, disciplina, responsabilidad, honestidad, compromiso y trabajo en equipo como pilares fundamentales de nuestra comunidad educativa.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HIMNO -->
    <section class="hm-himno" id="himno" aria-labelledby="tHimno">
        <div class="container">
            <div class="hm-himno-grid">
                <div class="hm-himno-lado" data-aos="fade-up">
                    <img class="hm-himno-escudo" src="<?= asset('assets/img/escudo/escudo.png') ?>" alt="" width="398" height="680">
                    <span class="hm-kicker hm-kicker-light">Símbolos institucionales</span>
                    <h2 class="hm-himno-titulo" id="tHimno" style="margin-bottom:26px">Himno del Gimnasio Castillo Americano</h2>

                    <div class="hm-player" id="himnoPlayer">
                        <audio id="himnoAudio" preload="metadata" src="<?= asset('assets/audio/himno-gca.mp3') ?>"></audio>
                        <button type="button" class="hm-player-btn" id="himnoPlay" aria-label="Escuchar el himno">
                            <i class="bi bi-play-fill" aria-hidden="true"></i>
                        </button>
                        <div class="hm-player-info">
                            <strong>Escuchar el himno</strong>
                            <input type="range" id="himnoSeek" min="0" max="232" step="1" value="0" aria-label="Posición del himno">
                            <div class="hm-player-t"><span id="himnoAct">0:00</span><span id="himnoDur">3:52</span></div>
                        </div>
                    </div>
                    <div class="hm-himno-links">
                        <a href="<?= asset('assets/audio/himno-gca.mp3') ?>" download="Himno Gimnasio Castillo Americano.mp3"><i class="bi bi-download" aria-hidden="true"></i> Descargar audio</a>
                    </div>

                    <figure class="hm-coro">
                        <figcaption>Coro <small>se canta después de cada estrofa</small></figcaption>
                        <p>Gimnasio Castillo Americano,<br>
                        cuna noble de fe y formación,<br>
                        “Cultivar para cosechar” es nuestro llamado,<br>
                        sembramos hoy para un mañana mejor.</p>
                        <p>Gimnasio Castillo Americano,<br>
                        protagonistas del aprendizaje somos hoy,<br>
                        con inteligencia emocional y espiritual,<br>
                        y la fuerza de la innovación.</p>
                    </figure>
                </div>

                <ol class="hm-estrofas" data-aos="fade-up" data-aos-delay="100" aria-label="Estrofas del himno">
                    <li>
                        <span class="hm-estrofa-n" aria-hidden="true">I</span>
                        <p>En las aulas del saber florece<br>
                        nuestro sueño de crecer y avanzar,<br>
                        Gimnasio Castillo Americano,<br>
                        faro eterno que nos guía a triunfar.</p>
                        <span class="hm-estrofa-coro">Coro</span>
                    </li>
                    <li>
                        <span class="hm-estrofa-n" aria-hidden="true">II</span>
                        <p>Con respeto y disciplina forjamos<br>
                        el carácter que habrá de brillar,<br>
                        responsables, firmes y honestos,<br>
                        listos siempre para aportar.</p>
                        <span class="hm-estrofa-coro">Coro</span>
                    </li>
                    <li>
                        <span class="hm-estrofa-n" aria-hidden="true">III</span>
                        <p>Dios es nuestro guía y Rey,<br>
                        luz que orienta nuestro caminar,<br>
                        con inteligencia artificial y conciencia,<br>
                        preparamos el futuro sin cesar.</p>
                        <span class="hm-estrofa-coro">Coro</span>
                    </li>
                    <li>
                        <span class="hm-estrofa-n" aria-hidden="true">IV</span>
                        <p>Inteligencia emocional nos fortalece,<br>
                        inteligencia espiritual nos da visión,<br>
                        Gimnasio Castillo Americano,<br>
                        educando con fe y pasión.</p>
                        <span class="hm-estrofa-coro">Coro</span>
                    </li>
                </ol>
            </div>
        </div>
    </section>
    <script>
    (function () {
        var a = document.getElementById('himnoAudio'), b = document.getElementById('himnoPlay'),
            s = document.getElementById('himnoSeek'), act = document.getElementById('himnoAct'),
            dur = document.getElementById('himnoDur'), caja = document.getElementById('himnoPlayer');
        if (!a) return;
        function mmss(t) { t = Math.max(0, Math.floor(t || 0)); return Math.floor(t / 60) + ':' + ('0' + t % 60).slice(-2); }
        function pinta() {
            var on = !a.paused;
            b.querySelector('i').className = on ? 'bi bi-pause-fill' : 'bi bi-play-fill';
            b.setAttribute('aria-label', on ? 'Pausar el himno' : 'Escuchar el himno');
            caja.classList.toggle('sonando', on);
        }
        b.addEventListener('click', function () { if (a.paused) a.play(); else a.pause(); });
        a.addEventListener('play', pinta);
        a.addEventListener('pause', pinta);
        a.addEventListener('ended', function () { a.currentTime = 0; pinta(); });
        a.addEventListener('loadedmetadata', function () { s.max = Math.floor(a.duration); dur.textContent = mmss(a.duration); });
        a.addEventListener('timeupdate', function () {
            if (document.activeElement !== s) s.value = Math.floor(a.currentTime);
            act.textContent = mmss(a.currentTime);
            s.style.setProperty('--p', (a.currentTime / (a.duration || 232) * 100) + '%');
        });
        s.addEventListener('input', function () { a.currentTime = +s.value; });
    })();
    </script>

    <!-- BLOQUE DESTACADO -->
    <section class="hm-cta">
        <span class="hm-cta-glow"></span>
        <div class="container" data-aos="fade-up">
            <span class="hm-kicker hm-kicker-center hm-kicker-light">Nuestra filosofía</span>
            <h2 class="hm-cta-title">Cultivar para Cosechar</h2>
            <p class="hm-cta-sub">
                En el Gimnasio Castillo Americano educamos con vocación,
                sembramos conocimiento con valores y construimos futuro
                a través de la excelencia académica.
            </p>
            <a href="index.php" class="hm-btn hm-btn-primary hm-btn-lg"><i class="bi bi-arrow-left-short" aria-hidden="true"></i> Volver al inicio</a>
        </div>
    </section>

</div>

<?php include "footer.php"; ?>
