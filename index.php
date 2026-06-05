<?php include "header.php"; ?>

<!-- HERO -->
<section class="hero d-flex align-items-center text-center"
         style="
            background:
                linear-gradient(rgba(8,10,20,.72), rgba(8,10,20,.72)),
                url('assets/img/colegio.png');
            background-size:cover;
            background-position:center;
            color:white;
         ">

    <div class="container hero-content">
        <img src="assets/img/escudo-gca.png"
             alt="Gimnasio Castillo Americano"
             style="height:120px;"
             class="mb-4 hero-escudo">

        <h1 class="display-5 fw-bold mb-3 hero-title">
            BIENVENIDOS A NUESTRO CASTILLO
        </h1>

        <p class="lead mb-4" style="max-width:760px;margin:auto;">
            Educación con carácter, valores y excelencia académica.
        </p>

        <a href="login.php"
           class="btn btn-lg px-5 py-3 btn-gca hero-btn">
            Acceso al Sistema Académico
        </a>
    </div>
</section>

<!-- SECCIÓN IDENTIDAD -->
<section class="container my-5" data-aos="fade-up">
    <div class="row align-items-center g-5">

        <div class="col-md-6" data-aos="fade-right" data-aos-delay="100">
            <h2 class="fw-bold mb-3">Nuestra Identidad</h2>
            <p class="text-muted">
                El Gimnasio Castillo Americano es una institución comprometida
                con la formación integral de niños y jóvenes, basada en valores,
                disciplina y excelencia.
            </p>
            <p class="text-muted">
                Nuestro proyecto educativo fortalece el liderazgo, la
                responsabilidad y el pensamiento crítico como pilares del
                desarrollo humano.
            </p>
        </div>

        <div class="col-md-6" data-aos="fade-left" data-aos-delay="200">
            <img src="assets/img/img5.png"
                 class="img-fluid rounded-4 shadow"
                 alt="Identidad institucional">
        </div>

    </div>
</section>

<!-- VALORES INSTITUCIONALES -->
<section style="background:#f5f7fa;" class="py-5" data-aos="fade-up">
    <div class="container">

        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Nuestros Principios</h2>
            <p class="text-muted">
                La base de nuestra formación educativa
            </p>
        </div>

        <div class="row g-4 text-center">

           <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
    <div class="card h-100 p-4 border-0 shadow-sm text-center">

        <img 
            src="assets/img/colegio.png"
            class="img-fluid rounded mb-3"
            alt="Valores y Disciplina"
        >

        <h5 class="fw-bold">Valores y Disciplina</h5>
        <p class="text-muted">
            Formamos estudiantes íntegros,
            responsables y comprometidos con la sociedad.
        </p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <img src= "assets/img/img2.png"
                         class="img-fluid rounded mb-3"
                         alt="Excelencia">
                    <h5 class="fw-bold">Excelencia Académica</h5>
                    <p class="text-muted">
                        Procesos pedagógicos sólidos,
                        acompañamiento permanente y calidad educativa.
                    </p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <img src="assets/img/img3.png"
                         class="img-fluid rounded mb-3"
                         alt="Comunidad">
                    <h5 class="fw-bold">Comunidad Educativa</h5>
                    <p class="text-muted">
                        Trabajo conjunto entre familia,
                        institución y estudiantes.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- MENSAJE INSTITUCIONAL -->
<section class="py-5"
         style="
            background:
                linear-gradient(rgba(15,23,42,.85), rgba(15,23,42,.85)),
                url('https://images.unsplash.com/photo-1519452575417-564c1401ecc0');
            background-size:cover;
            background-position:center;
            color:white;
         "
         data-aos="fade-up">
    <div class="container text-center">

        <h2 class="fw-bold mb-3" data-aos="fade-up">
            Cultivar para Cosechar
        </h2>

        <p class="mb-4" style="max-width:760px;margin:auto;color:#e5e7eb;" data-aos="fade-up" data-aos-delay="100">
            Educamos con visión de futuro, formando personas
            capaces de transformar su entorno con valores y conocimiento.
        </p>

        <a href="login.php"
           class="btn btn-lg px-5 py-3 btn-gca"
           data-aos="fade-up" data-aos-delay="200">
            Ingresar al Sistema
        </a>

    </div>
</section>

<?php include "footer.php"; ?>

<script>
// Animated counters: count up when visible
document.addEventListener('DOMContentLoaded', function(){
    const counters = document.querySelectorAll('.counter-num');
    if (!counters.length) return;

    const runCounter = (el) => {
        const target = +el.getAttribute('data-target') || 0;
        const duration = 1400; // ms
        const start = performance.now();
        const initial = +el.textContent.replace(/[^0-9]/g,'') || 0;

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.floor(initial + (target - initial) * easeOutCubic(progress));
            el.textContent = value;
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        };
        requestAnimationFrame(step);
    };

    function easeOutCubic(t){ return 1 - Math.pow(1 - t, 3); }

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const num = entry.target;
                runCounter(num);
                obs.unobserve(num);
            }
        });
    }, {threshold: 0.6});

    counters.forEach(c => io.observe(c));
});
</script>
