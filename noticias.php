<?php include "header.php"; ?>

<style>
    .news-title {
        font-weight: 800;
        letter-spacing: .5px;
    }
</style>

<div class="container my-5">

    <!-- TÍTULO -->
    <div class="text-center mb-5">
        <h1 class="news-title">Noticias Institucionales</h1>
        <p class="text-muted" style="max-width:700px;margin:auto;">
            Conoce las actividades, eventos y momentos destacados
            del Gimnasio Castillo Americano.
        </p>
    </div>

    <!-- NOTICIAS -->
    <div class="row g-4">

        <!-- NOTICIA 1 -->
        <div class="col-md-4">
            <div class="card news-card h-100">

                <img src="assets/img/colegio.png"
                     class="card-img-top"
                     alt="Inicio del año escolar">

                <div class="card-body">
                    <span class="badge-gca">Institucional</span>

                    <h5 class="card-title">Inicio del Año Escolar</h5>
                    <p class="card-text text-muted">
                        Dimos la bienvenida a estudiantes y docentes para el nuevo
                        año académico, reafirmando nuestro compromiso con la
                        formación integral y la excelencia educativa.
                    </p>
                </div>

                <div class="card-footer text-center">
                    Publicado: 15 de enero de 2026
                </div>
            </div>
        </div>

        <!-- NOTICIA 2 (VIDEO) -->
        <div class="col-md-4">
            <div class="card news-card h-100">

                <video class="card-img-top" controls muted>
                    <source src="assets/videos/semanac.mp4" type="video/mp4">
                    Tu navegador no soporta video HTML5.
                </video>

                <div class="card-body">
                    <span class="badge-gca">Evento Cultural</span>

                    <h5 class="card-title">Semana Cultural</h5>
                    <p class="card-text text-muted">
                        Jornadas llenas de arte, deporte y cultura que fortalecen
                        la convivencia, el talento y la identidad institucional
                        de nuestros estudiantes.
                    </p>
                </div>

                <div class="card-footer text-center">
                    Publicado: 20 de febrero de 2026
                </div>
            </div>
        </div>

        <!-- NOTICIA 3 (EJEMPLO FUTURA) -->
        <div class="col-md-4">
            <div class="card news-card h-100">

                <img src="assets/img/imagen9.png"
                     class="card-img-top"
                     alt="Actividad académica">

                <div class="card-body">
                    <span class="badge-gca">Académico</span>

                    <h5 class="card-title">Fortalecimiento Académico</h5>
                    <p class="card-text text-muted">
                        Implementamos nuevas estrategias pedagógicas orientadas
                        al desarrollo del pensamiento crítico y la excelencia académica.
                    </p>
                </div>

                <div class="card-footer text-center">
                    Publicado: 05 de marzo de 2026
                </div>
            </div>
        </div>

    </div>

    <!-- BOTÓN -->
    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-outline-dark px-4">
            Volver al inicio
        </a>
    </div>

</div>

<?php include "footer.php"; ?>
