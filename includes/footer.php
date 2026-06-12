<?php
// Compatibilidad: footer global para páginas antiguas que incluyen `includes/footer.php`
// Incluye partial del footer y carga scripts necesarios.
$partial = __DIR__ . '/../resources/views/partials/public/footer.php';
if (file_exists($partial)) {
    include $partial;
}
?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true, offset: 60 });
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('mainNavbar');
        if (navbar) {
            window.addEventListener('scroll', function() { navbar.classList.toggle('navbar-scrolled', window.scrollY > 50); });
        }
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.navbar .nav-link').forEach(function(link) {
            if (link.getAttribute('href') === currentPage) link.classList.add('active');
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
