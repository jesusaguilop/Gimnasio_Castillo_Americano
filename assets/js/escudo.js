/* Escudo animado del GCA (ver assets/css/escudo.css) */
(function () {
    'use strict';

    var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Los leones rugen una vez al cargar (header o escudo con data-ruge) ──
       La clase se quita al terminar para que el rugido del hover vuelva a funcionar. */
    function rugirHeader() {
        // El del header, o cualquier escudo marcado con data-ruge (p. ej. el del login)
        var e = document.querySelector('.navbar-brand .escudo, .escudo[data-ruge]');
        if (!e || quieto) return;
        setTimeout(function () {
            e.classList.add('ruge');
            var der = e.querySelector('.escudo-leon.der');
            var fin = function () { e.classList.remove('ruge'); };
            if (der) der.addEventListener('animationend', fin, { once: true });
            setTimeout(fin, 1600);   // por si el evento no llega
        }, document.querySelector('.gca-intro') ? 3900 : 700);
    }

    /* ── Intro de la portada: se cierra sola, o con clic / tecla / el botón ── */
    function cerrarIntro() {
        var intro = document.querySelector('.gca-intro');
        if (!intro) return;
        var quitar = function () {
            if (intro.parentNode) intro.parentNode.removeChild(intro);
            document.documentElement.classList.remove('gca-intro-activa');
        };
        var saltar = function () {
            intro.classList.add('fuera');
            setTimeout(quitar, 520);
            document.removeEventListener('keydown', saltar);
        };
        intro.addEventListener('click', saltar);
        document.addEventListener('keydown', saltar);
        intro.addEventListener('animationend', function (ev) {
            if (ev.target === intro) quitar();
        });
        setTimeout(quitar, 4600);   // red de seguridad: nunca se queda tapando el sitio
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { cerrarIntro(); rugirHeader(); });
    } else {
        cerrarIntro(); rugirHeader();
    }
})();
