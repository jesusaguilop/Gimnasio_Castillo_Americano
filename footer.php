</main>

<!-- FOOTER (compacto y estilizado) -->
<style>
    .site-footer {
        background:
            radial-gradient(1100px 320px at 12% 0%, rgba(201,162,77,.12), transparent 60%),
            var(--gca-dark, #0f0f0f);
        color:#d6d6d6;
        border-top:3px solid var(--gca-gold, #c9a24d);
        position:relative;
    }
    .site-footer .ft-main { padding:42px 0 22px; }

    .ft-brand { display:flex; align-items:center; gap:16px; margin-bottom:14px; }
    .ft-logo {
        height:92px; width:auto;
        filter: drop-shadow(0 8px 18px rgba(0,0,0,.45));
        transition: transform .45s cubic-bezier(.22,1,.36,1);
    }
    .ft-brand:hover .ft-logo { transform: scale(1.07) rotate(-4deg); }
    .ft-title { margin:0; color:#fff; font-weight:700; font-family:'Playfair Display', serif; font-size:1.18rem; line-height:1.12; }
    .ft-tagline { color:var(--gca-gold, #c9a24d); font-size:.82rem; font-style:italic; }
    .ft-desc { font-size:13.5px; color:#b3b3b3; line-height:1.75; max-width:340px; margin:0; }

    .ft-heading {
        color:#fff; text-transform:uppercase; font-size:.8rem; letter-spacing:1.5px;
        font-weight:700; margin-bottom:16px; position:relative; padding-bottom:9px;
    }
    .ft-heading::after {
        content:''; position:absolute; left:0; bottom:0; width:34px; height:2px;
        background:linear-gradient(90deg, var(--gca-gold, #c9a24d), transparent); border-radius:2px;
    }

    .ft-links, .ft-contact { list-style:none; padding:0; margin:0; }
    .ft-links li { margin-bottom:9px; }
    .ft-links a {
        color:#c4c4c4; text-decoration:none; font-size:13.5px;
        display:inline-flex; align-items:center; gap:7px;
        transition: color .25s ease, transform .25s ease;
    }
    .ft-links a i { font-size:.68rem; color:var(--gca-gold, #c9a24d); opacity:0; transform:translateX(-6px); transition:.25s ease; }
    .ft-links a:hover { color:var(--gca-gold, #c9a24d); transform:translateX(4px); }
    .ft-links a:hover i { opacity:1; transform:translateX(0); }

    .ft-contact li { display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:#c4c4c4; margin-bottom:12px; }
    .ft-contact i { color:var(--gca-gold, #c9a24d); font-size:1rem; margin-top:1px; flex-shrink:0; }

    .ft-social { display:flex; gap:10px; margin-top:16px; }

    .ft-bottom {
        margin-top:24px; padding-top:16px; border-top:1px solid rgba(255,255,255,.08);
        display:flex; flex-wrap:wrap; gap:8px 18px; justify-content:space-between; align-items:center;
        font-size:12.5px; color:#8f8f8f;
    }
    .ft-bottom .ft-heart { color:var(--gca-gold, #c9a24d); }

    @media (max-width: 768px) {
        .site-footer .ft-main { padding:30px 0 16px; }
        .ft-brand { flex-direction:column; text-align:center; gap:10px; }
        .ft-logo { height:78px; }
        .ft-desc { margin:0 auto; }
        .ft-heading { margin-top:6px; }
        .ft-heading::after { left:50%; transform:translateX(-50%); }
        .ft-links a, .ft-contact li { justify-content:center; }
        .ft-social { justify-content:center; }
        .ft-bottom { justify-content:center; text-align:center; }
    }
</style>

<footer class="site-footer">
    <div class="container ft-main">
        <div class="row gy-4">

            <div class="col-lg-5" data-aos="fade-up">
                <div class="ft-brand">
                    <img src="assets/img/escudo-gca.png" alt="GCA" class="ft-logo">
                    <div>
                        <h5 class="ft-title">Gimnasio Castillo Americano</h5>
                        <span class="ft-tagline">“Cultivar para cosechar”</span>
                    </div>
                </div>
                <p class="ft-desc">Institución educativa comprometida con la formación integral, el desarrollo humano y la excelencia académica.</p>
                <div class="ft-social">
                    <a href="https://facebook.com" class="social-circle" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://instagram.com" class="social-circle" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://youtube.com" class="social-circle" title="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="col-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <h6 class="ft-heading">Enlaces</h6>
                <ul class="ft-links">
                    <li><a href="institucion.php"><i class="bi bi-chevron-right"></i>Institución</a></li>
                    <li><a href="admisiones.php"><i class="bi bi-chevron-right"></i>Admisiones</a></li>
                    <li><a href="noticias.php"><i class="bi bi-chevron-right"></i>Noticias</a></li>
                    <li><a href="docentes.php"><i class="bi bi-chevron-right"></i>Docentes</a></li>
                    <li><a href="calendario.php"><i class="bi bi-chevron-right"></i>Calendario</a></li>
                    <li><a href="servicios.php"><i class="bi bi-chevron-right"></i>Servicios</a></li>
                    <li><a href="contacto.php"><i class="bi bi-chevron-right"></i>Contacto</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <h6 class="ft-heading">Contacto</h6>
                <ul class="ft-contact">
                    <li><i class="bi bi-geo-alt-fill"></i><span>Valledupar – Cesar, Colombia</span></li>
                    <li><i class="bi bi-telephone-fill"></i><span>321 654 8235</span></li>
                    <li><i class="bi bi-envelope-fill"></i><span>gimnasiocastilloamericano@gmail.com</span></li>
                </ul>
            </div>

        </div>

        <div class="ft-bottom">
            <span>© <?php echo date("Y"); ?> Gimnasio Castillo Americano · Todos los derechos reservados</span>
            <span>Hecho con <span class="ft-heart">♥</span> en Valledupar</span>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true, offset: 60 });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
