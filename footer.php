</main>

<!-- FOOTER (diseño modernizado, mismos colores) -->
<footer class="site-footer" role="contentinfo">
    <style>
        .site-footer { background: var(--gca-dark); color: var(--gca-footer-text); border-top:4px solid var(--gca-gold); }
        .site-footer .footer-inner { max-width:1100px; margin:0 auto; padding:48px 20px; }
        .footer-grid { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:28px; align-items:start; }
        .footer-brand { display:flex; gap:16px; align-items:flex-start; }
        .footer-brand img{ height:64px; width:auto; border-radius:8px; }
        .footer-brand h5{ margin:0; color:#fff; font-weight:800; font-size:1.05rem; }
        .footer-tag { color:var(--gca-gold); font-size:0.9rem; }
        .footer-about { margin-top:12px; color:var(--gca-footer-text); line-height:1.6; font-size:0.95rem; }

        .footer-links h6, .footer-contact h6 { color:var(--gca-gold); font-weight:800; margin-bottom:12px; }
        .footer-links ul { list-style:none; padding:0; margin:0; display:grid; grid-template-columns:repeat(1, minmax(0,1fr)); gap:8px; }
        .footer-links a{ color:inherit; text-decoration:none; opacity:0.95; transition:opacity .18s ease, transform .18s ease; }
        .footer-links a:hover{ opacity:1; transform:translateX(4px); }

        .socials { display:flex; gap:10px; margin-top:12px; }
        .socials a{ display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:8px; background:rgba(255,255,255,0.03); color:var(--gca-gold); text-decoration:none; }

        .contact-line{ display:flex; gap:12px; align-items:center; font-size:0.95rem; color:var(--gca-footer-text); }

        .footer-bottom{ margin-top:28px; border-top:1px solid rgba(255,255,255,0.04); padding-top:18px; display:flex; justify-content:space-between; align-items:center; font-size:0.9rem; color:#bdbdbd; }

        /* Responsive */
        @media (max-width:900px){ .footer-grid{ grid-template-columns: 1fr; } .footer-bottom{ flex-direction:column; gap:10px; align-items:flex-start; } }
    </style>

    <div class="footer-inner">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="assets/img/escudo-gca.png" alt="GCA">
                    <div>
                        <h5>Gimnasio Castillo Americano</h5>
                        <div class="footer-tag">“Cultivar para cosechar”</div>
                    </div>
                </div>
                <p class="footer-about">Institución educativa comprometida con la formación integral, el desarrollo humano y la excelencia académica.</p>

                <div class="socials" aria-hidden="false">
                    <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="footer-links">
                <h6>Enlaces institucionales</h6>
                <ul>
                    <li><a href="institucion.php">Institución</a></li>
                    <li><a href="admisiones.php">Admisiones</a></li>
                    <li><a href="noticias.php">Noticias</a></li>
                    <li><a href="docentes.php">Docentes</a></li>
                    <li><a href="calendario.php">Calendario</a></li>
                    <li><a href="servicios.php">Servicios</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                </ul>
            </div>

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


