    <p class="pp-pie">Gimnasio Castillo Americano · Cultivar para cosechar</p>
</main>

<nav class="pp-tabs" aria-label="Secciones">
    <a href="dashboard.php"<?= $ppCur('inicio') ?>><i class="bi bi-house" aria-hidden="true"></i>Inicio</a>
    <a href="avisos.php"<?= $ppCur('avisos') ?>>
        <i class="bi bi-bell" aria-hidden="true"></i>Avisos
        <span class="n" id="ppTabN"<?= $ppNoLeidas ? '' : ' hidden' ?>><?= $ppNoLeidas > 99 ? '99+' : $ppNoLeidas ?><span class="visually-hidden"> sin leer</span></span>
    </a>
    <a href="../cambiar_clave.php"><i class="bi bi-person" aria-hidden="true"></i>Cuenta</a>
</nav>

<script src="../<?= asset('assets/js/gca-ui.js') ?>"></script>
<script>
(function () {
    // Menú de la cuenta
    var btn = document.getElementById('ppCuentaBtn'), menu = document.getElementById('ppMenu');
    function cerrar() { menu.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var abrir = menu.hidden;
        menu.hidden = !abrir;
        btn.setAttribute('aria-expanded', String(abrir));
        if (abrir) menu.querySelector('[role="menuitem"]').focus();
    });
    document.addEventListener('click', function (e) { if (!menu.contains(e.target)) cerrar(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { cerrar(); btn.focus(); } });

    // Modo oscuro (se recuerda en este equipo)
    var tema = document.getElementById('ppTema');
    function pintarTema() {
        var o = document.documentElement.classList.contains('dark-mode');
        tema.querySelector('i').className = o ? 'bi bi-sun' : 'bi bi-moon-stars';
        tema.querySelector('span').textContent = o ? 'Modo claro' : 'Modo oscuro';
    }
    tema.addEventListener('click', function () {
        var o = document.documentElement.classList.toggle('dark-mode');
        try { localStorage.setItem('gca-dark-mode', o); } catch (e) {}
        pintarTema();
    });
    pintarTema();

    // La pestaña "Avisos" del celular sigue a la campana
    var n = document.getElementById('ppTabN');
    document.addEventListener('gca:notificaciones', function (e) {
        var c = e.detail.no_leidas || 0;
        n.hidden = !c;
        n.firstChild.nodeValue = c > 99 ? '99+' : String(c);
    });
})();
</script>
</body>
</html>
