</main>

<!-- FOOTER -->
<footer style="background:var(--gca-dark); color:#dcdcdc; border-top:4px solid var(--gca-gold);">
    <div class="container py-5">

        <div class="row gy-4">

            <!-- LOGO + NOMBRE -->
            <div class="col-md-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="assets/img/escudo-gca.png" alt="GCA" style="height:70px;" class="me-3">
                    <div>
                        <h5 class="mb-0 text-white fw-bold">
                            Gimnasio Castillo Americano
                        </h5>
                        <small style="color:var(--gca-gold);">
                            “Cultivar para cosechar”
                        </small>
                    </div>
                </div>

                <p style="font-size:14px; color:var(--gca-footer-text);">
                    Institución educativa comprometida con la formación integral,
                    el desarrollo humano y la excelencia académica.
                </p>
            </div>

            <!-- ENLACES -->
            <div class="col-md-4">
                <h6 class="text-uppercase fw-bold mb-3" style="color:var(--gca-gold);">
                    Enlaces institucionales
                </h6>

                <ul class="list-unstyled" style="font-size:14px;">
                    <li class="mb-2">
                        <a href="institucion.php" class="text-decoration-none text-light">
                            Institución
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="admisiones.php" class="text-decoration-none text-light">
                            Admisiones
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="noticias.php" class="text-decoration-none text-light">
                            Noticias
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="docentes.php" class="text-decoration-none text-light">
                            Docentes
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="calendario.php" class="text-decoration-none text-light">
                            Calendario
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="servicios.php" class="text-decoration-none text-light">
                            Servicios
                        </a>
                    </li>
                    <li>
                        <a href="contacto.php" class="text-decoration-none text-light">
                            Contacto
                        </a>
                    </li>
                </ul>
            </div>

            <!-- CONTACTO -->
            <div class="col-md-4">
                <h6 class="text-uppercase fw-bold mb-3" style="color:var(--gca-gold);">
                    Información de contacto
                </h6>

                <p style="font-size:14px; margin-bottom:8px;">
                    📍 Valledupar – Cesar, Colombia
                </p>
                <p style="font-size:14px; margin-bottom:8px;">
                    📞 321 654 8235
                </p>
                <p style="font-size:14px;">
                    ✉️ gimnasiocastilloamericano@gmail.com
                </p>
            </div>

        </div>

        <hr style="border-color:#333; margin:30px 0;">

        <!-- COPY -->
        <div class="text-center" style="font-size:13px; color:#aaa;">
            © <?php echo date("Y"); ?> Gimnasio Castillo Americano 
        </div>

    </div>
</footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- WHATSAPP FLOTANTE -->
<a href="https://wa.me/573216548235?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20el%20colegio"
   class="whatsapp-float"
   target="_blank"
   rel="noopener"
   aria-label="WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>

<!-- BACK TO TOP -->
<button class="back-to-top" id="backToTop" aria-label="Volver arriba">
    <i class="bi bi-chevron-up"></i>
</button>

<script>
    // Back to top
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        backToTop.classList.toggle('show', window.scrollY > 400);
    });
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

</script>
