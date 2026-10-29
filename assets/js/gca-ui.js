/* ════════════════════════════════════════════════════════════
   GCA · Kit de interfaz común (estilos en assets/css/gca-ui.css)
   ------------------------------------------------------------
   window.GCA:
     GCA.toast(texto, { tipo: 'ok'|'bad'|'warn'|'info'|'aviso', titulo, ms, enlace: {texto, url} })
     GCA.confirmar(texto, { titulo, aceptar, peligro }) → Promise<boolean>
     GCA.avisar(texto, { titulo })                      → Promise
     GCA.cargando(true|false)                           cargador con el escudo
   Además, sin tocar cada página:
     · data-confirm="¿…?" en formularios, botones o enlaces pide confirmación
       con el diálogo propio (data-confirm-ok="Sí, quitar" cambia el botón).
     · window.showConfirm(msg, cb, textoBoton) usa el mismo diálogo.
     · Al navegar: barra de progreso y, si tarda, el escudo cargando.
     · Al enviar un formulario: el botón queda ocupado (evita doble envío).
       data-no-loading en el form lo desactiva (descargas, envíos en vivo).
     · Campana: un elemento con [data-gca-bell] abre el panel de
       notificaciones (data-endpoint, data-pagina).
   ════════════════════════════════════════════════════════════ */
(function () {
    'use strict';
    if (window.GCA) return;

    var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function el(tag, cls, html) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (html != null) e.innerHTML = html;
        return e;
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var GCA = window.GCA = {};
    document.documentElement.classList.add('gca-ui');

    /* ─────────── Avisos ─────────── */
    var ICONOS = { ok: 'bi-check2-circle', bad: 'bi-x-octagon', warn: 'bi-exclamation-triangle', info: 'bi-info-circle', aviso: 'bi-bell' };
    var pila;
    GCA.toast = function (texto, o) {
        o = o || {};
        var tipo = o.tipo || 'info';
        if (!pila) {
            pila = el('div', 'gca-toasts');
            pila.setAttribute('aria-live', 'polite');
            pila.setAttribute('aria-relevant', 'additions');
            document.body.appendChild(pila);
        }
        var ms = o.ms == null ? (tipo === 'bad' ? 9000 : 6000) : o.ms;
        var t = el('div', 'gca-toast');
        t.dataset.tipo = tipo;
        t.setAttribute('role', tipo === 'bad' ? 'alert' : 'status');
        t.innerHTML = '<span class="gca-toast-ico" aria-hidden="true"><i class="bi ' + (ICONOS[tipo] || ICONOS.info) + '"></i></span>'
            + '<div class="gca-toast-txt">' + (o.titulo ? '<strong>' + esc(o.titulo) + '</strong>' : '')
            + '<p>' + esc(texto) + (o.enlace ? ' <a href="' + esc(o.enlace.url) + '">' + esc(o.enlace.texto) + '</a>' : '') + '</p></div>'
            + '<button type="button" class="gca-toast-x" aria-label="Cerrar aviso">&times;</button>'
            + (ms ? '<span class="gca-toast-t" style="animation-duration:' + ms + 'ms"></span>' : '');
        var cerrar = function () {
            if (t.classList.contains('saliendo')) return;
            t.classList.add('saliendo');
            setTimeout(function () { t.remove(); }, 240);
        };
        t.querySelector('.gca-toast-x').onclick = cerrar;
        var barra = t.querySelector('.gca-toast-t');
        if (barra) barra.addEventListener('animationend', cerrar);
        pila.appendChild(t);
        while (pila.children.length > 4) pila.firstChild.remove();
        return cerrar;
    };

    /* ─────────── Diálogo ─────────── */
    var dlg;
    function dialogo(texto, o, conCancelar) {
        o = o || {};
        return new Promise(function (resolver) {
            if (!dlg) {
                dlg = el('dialog', 'gca-dlg');
                dlg.setAttribute('aria-labelledby', 'gcaDlgT');
                dlg.setAttribute('aria-describedby', 'gcaDlgM');
                document.body.appendChild(dlg);
            }
            var peligro = !!o.peligro;
            dlg.dataset.tipo = peligro ? 'peligro' : '';
            dlg.innerHTML = '<div class="gca-dlg-body"><span class="gca-dlg-ico" aria-hidden="true"><i class="bi '
                + (peligro ? 'bi-exclamation-octagon' : (conCancelar ? 'bi-question-circle' : 'bi-info-circle')) + '"></i></span>'
                + '<div><h2 id="gcaDlgT">' + esc(o.titulo || (conCancelar ? 'Confirmar' : 'Aviso')) + '</h2>'
                + '<p id="gcaDlgM">' + esc(texto) + '</p></div></div>'
                + '<div class="gca-dlg-acc">'
                + (conCancelar ? '<button type="button" class="gca-btn" data-r="0">' + esc(o.cancelar || 'Cancelar') + '</button>' : '')
                + '<button type="button" class="gca-btn ' + (peligro ? 'gca-btn-peligro' : 'gca-btn-pri') + '" data-r="1">'
                + esc(o.aceptar || (conCancelar ? 'Aceptar' : 'Entendido')) + '</button></div>';
            var hecho = false;
            var fin = function (v) {
                if (hecho) return;
                hecho = true;
                if (dlg.open) dlg.close();
                resolver(v);
            };
            dlg.querySelectorAll('[data-r]').forEach(function (b) {
                b.onclick = function () { fin(b.dataset.r === '1'); };
            });
            dlg.oncancel = function (e) { e.preventDefault(); fin(false); };
            dlg.onclick = function (e) { if (e.target === dlg) fin(false); };   // clic fuera
            if (typeof dlg.showModal === 'function') {
                dlg.showModal();
            } else {   // navegadores sin <dialog>
                resolver(conCancelar ? window.confirm(texto) : (window.alert(texto), true));
                return;
            }
            // El foco va al botón seguro (Cancelar) en acciones peligrosas
            var foco = dlg.querySelector(peligro && conCancelar ? '[data-r="0"]' : '[data-r="1"]');
            if (foco) foco.focus();
        });
    }
    var RX_PELIGRO = /eliminar|borrar|quitar|reiniciar|desactivar|ocultar|irreversible|no se puede deshacer/i;
    GCA.confirmar = function (texto, o) {
        o = o || {};
        if (o.peligro == null) o.peligro = RX_PELIGRO.test(texto + ' ' + (o.aceptar || ''));
        return dialogo(texto, o, true);
    };
    GCA.avisar = function (texto, o) { return dialogo(texto, o, false); };

    // Compatibilidad con las páginas que ya llaman showConfirm(msg, cb, textoBoton)
    window.showConfirm = function (mensaje, cb, textoBoton) {
        GCA.confirmar(mensaje, { aceptar: textoBoton || undefined }).then(function (ok) { if (ok && cb) cb(); });
    };
    window.hideConfirm = function () { if (dlg && dlg.open) dlg.close(); };

    // data-confirm en formularios, botones y enlaces
    document.addEventListener('submit', function (e) {
        var f = e.target;
        var quien = e.submitter;
        var msg = (quien && quien.getAttribute('data-confirm')) || f.getAttribute('data-confirm');
        if (!msg || f.dataset.gcaOk === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        GCA.confirmar(msg, { aceptar: (quien && quien.getAttribute('data-confirm-ok')) || f.getAttribute('data-confirm-ok') || undefined })
            .then(function (ok) {
                if (!ok) return;
                f.dataset.gcaOk = '1';
                if (f.requestSubmit) f.requestSubmit(quien || undefined); else f.submit();
                setTimeout(function () { delete f.dataset.gcaOk; }, 0);
            });
    }, true);
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-confirm]');
        if (!a || a.dataset.gcaOk === '1') return;
        e.preventDefault();
        GCA.confirmar(a.getAttribute('data-confirm'), { aceptar: a.getAttribute('data-confirm-ok') || undefined })
            .then(function (ok) { if (ok) { navegando(); location.href = a.href; } });
    }, true);

    /* ─────────── Cargador y barra de progreso ─────────── */
    var loader, barra, tLoader, tSeguro;
    function asegurar() {
        if (loader) return;
        loader = el('div', 'gca-loader', '<div class="gca-loader-in"><span class="gca-escudo-carga"></span><span class="gca-loader-txt">Cargando</span></div>');
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        barra = el('div', 'gca-progress');
        barra.setAttribute('aria-hidden', 'true');
        document.body.appendChild(barra);
        document.body.appendChild(loader);
    }
    GCA.cargando = function (on) {
        asegurar();
        loader.classList.toggle('on', !!on);
    };
    function navegando(demoraEscudo) {
        asegurar();
        barra.classList.remove('fin');
        void barra.offsetWidth;
        barra.classList.add('on');
        clearTimeout(tLoader);
        tLoader = setTimeout(function () { GCA.cargando(true); }, demoraEscudo || 450);
        // Si la página no cambia (descarga, error de red), todo vuelve a su estado
        clearTimeout(tSeguro);
        tSeguro = setTimeout(listo, 15000);
    }
    function listo() {
        clearTimeout(tLoader);
        clearTimeout(tSeguro);
        if (!loader) return;
        GCA.cargando(false);
        barra.classList.remove('on');
        barra.classList.add('fin');
        document.querySelectorAll('.is-busy').forEach(function (b) { b.classList.remove('is-busy'); b.removeAttribute('aria-busy'); });
    }
    GCA.navegando = navegando;
    GCA.listo = listo;
    // Al volver con "atrás" el navegador restaura la página tal cual: se limpia
    window.addEventListener('pageshow', listo);

    function esNavegacionInterna(a, e) {
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
        if (a.target && a.target !== '_self') return false;
        if (a.hasAttribute('download') || a.hasAttribute('data-no-loading') || a.closest('[data-no-loading]')) return false;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return false;
        if (a.origin !== location.origin) return false;
        if (/[?&]descargar=1|\.pdf(\?|$)/i.test(href)) return false;
        if (a.pathname === location.pathname && a.search === location.search && a.hash) return false;
        if (a.getAttribute('data-bs-toggle')) return false;
        return true;
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (a && !a.hasAttribute('data-confirm') && esNavegacionInterna(a, e)) navegando();
    });
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (e.defaultPrevented || f.hasAttribute('data-no-loading') || (f.target && f.target !== '_self')) return;
        if ((f.getAttribute('action') || '').match(/descargar|\.pdf/i)) return;
        var b = e.submitter || f.querySelector('[type="submit"]');
        if (b && !b.classList.contains('is-busy')) {
            // El valor del botón pulsado (name="guardar_config", etc.) viaja igual:
            // se desactiva después de que el navegador arma el envío
            setTimeout(function () { b.classList.add('is-busy'); b.setAttribute('aria-busy', 'true'); }, 0);
        }
        navegando(700);
    });

    /* ─────────── Mensajes en línea: botón de cerrar ─────────── */
    function cerrables(raiz) {
        (raiz || document).querySelectorAll('.alert:not(.alert-dismissible):not([data-fija])').forEach(function (a) {
            if (a.querySelector('.btn-close') || a.closest('.modal, form')) return;
            var b = el('button', 'btn-close');
            b.type = 'button';
            b.setAttribute('aria-label', 'Cerrar mensaje');
            b.onclick = function () {
                a.classList.add('gca-sale');
                setTimeout(function () { a.remove(); }, 300);
            };
            a.appendChild(b);
        });
    }

    /* ─────────── Notificaciones ─────────── */
    var ICONO_TIPO = { riesgo: 'bi-graph-down-arrow', boletin: 'bi-file-earmark-check', pendiente: 'bi-hourglass-split', sistema: 'bi-info-circle' };
    function tipoVisual(t) {
        t = String(t || '');
        if (/riesgo/.test(t)) return 'riesgo';
        if (/boletin_listo|pendiente/.test(t)) return 'pendiente';
        if (/boletin/.test(t)) return 'boletin';
        return 'sistema';
    }
    function hace(iso) {
        var d = new Date(String(iso).replace(' ', 'T'));
        if (isNaN(d)) return '';
        var s = Math.max(0, (Date.now() - d.getTime()) / 1000);
        if (s < 60) return 'hace un momento';
        if (s < 3600) return 'hace ' + Math.floor(s / 60) + ' min';
        if (s < 86400) return 'hace ' + Math.floor(s / 3600) + ' h';
        if (s < 172800) return 'ayer';
        if (s < 604800) return 'hace ' + Math.floor(s / 86400) + ' días';
        return d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' });
    }
    GCA.hace = hace;

    function Campana(btn) {
        var endpoint = btn.getAttribute('data-endpoint');
        var pagina = btn.getAttribute('data-pagina') || btn.getAttribute('href');
        var badge = btn.querySelector('.gca-bell-n');
        if (!badge) {
            badge = el('span', 'gca-bell-n');
            badge.setAttribute('aria-hidden', 'true');
            badge.hidden = true;
            btn.appendChild(badge);
        }
        var panel = el('div', 'gca-np');
        panel.id = 'gcaNp' + Math.random().toString(36).slice(2, 7);
        panel.hidden = true;
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Notificaciones');
        panel.innerHTML = '<div class="gca-np-head"><h2>Notificaciones</h2><button type="button" data-todas disabled>Marcar todas como leídas</button></div>'
            + '<ul class="gca-np-list"></ul>'
            + (pagina ? '<div class="gca-np-foot"><a href="' + esc(pagina) + '">Ver todas</a></div>' : '');
        document.body.appendChild(panel);
        btn.setAttribute('aria-haspopup', 'dialog');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', panel.id);
        var lista = panel.querySelector('.gca-np-list');
        var bTodas = panel.querySelector('[data-todas]');
        var csrf = '', items = [], n = null, vistos = null;

        function pintarBadge(c) {
            badge.hidden = !c;
            badge.textContent = c > 99 ? '99+' : String(c);
            var base = btn.getAttribute('data-label') || 'Notificaciones';
            btn.setAttribute('aria-label', c ? base + ': ' + c + ' sin leer' : base);
            bTodas.disabled = !c;
        }
        function esqueleto() {
            var h = '';
            for (var i = 0; i < 3; i++) {
                h += '<li class="gca-np-sk"><span class="gca-sk gca-sk-circ"></span><span><span class="gca-sk gca-sk-linea m"></span><span class="gca-sk gca-sk-linea c"></span></span></li>';
            }
            lista.innerHTML = h;
        }
        function pintarLista() {
            if (!items.length) {
                lista.innerHTML = '<li class="gca-np-vacio"><i class="bi bi-bell-slash" aria-hidden="true"></i>No tiene notificaciones por ahora.</li>';
                return;
            }
            lista.innerHTML = items.map(function (it) {
                var tv = tipoVisual(it.tipo);
                return '<li class="gca-ni' + (it.leido ? '' : ' nueva') + '" data-tipo="' + tv + '" data-id="' + it.id + '">'
                    + '<span class="gca-ni-ico" aria-hidden="true"><i class="bi ' + ICONO_TIPO[tv] + '"></i></span>'
                    + '<div class="gca-ni-txt"><strong>' + esc(it.titulo || 'Aviso') + '</strong><p>' + esc(it.mensaje) + '</p>'
                    + '<div class="gca-ni-meta"><time datetime="' + esc(it.fecha) + '">' + hace(it.fecha) + '</time>'
                    + (it.url ? '<a href="' + esc(it.url) + '" style="color:inherit;font-weight:600">Abrir</a>' : '')
                    + (it.leido ? '' : '<button type="button" data-leer="' + it.id + '">Marcar como leída</button>')
                    + '</div></div></li>';
            }).join('');
        }
        function cargar(conLista) {
            if (conLista && !items.length) esqueleto();
            return fetch(endpoint + (endpoint.indexOf('?') < 0 ? '?' : '&') + 'json=1', { cache: 'no-store', credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (d) {
                    csrf = d.csrf || csrf;
                    items = d.items || [];
                    var antes = n;
                    n = d.no_leidas || 0;
                    pintarBadge(n);
                    if (n !== antes) document.dispatchEvent(new CustomEvent('gca:notificaciones', { detail: { no_leidas: n } }));
                    if (!panel.hidden || conLista) pintarLista();
                    // Aviso de las que llegaron mientras la página estaba abierta
                    var ids = items.filter(function (i) { return !i.leido; }).map(function (i) { return i.id; });
                    if (vistos) {
                        var nuevas = items.filter(function (i) { return !i.leido && vistos.indexOf(i.id) < 0; });
                        if (nuevas.length && antes !== null && n > antes) {
                            GCA.toast(nuevas[0].mensaje, { tipo: 'aviso', titulo: nuevas[0].titulo || 'Nueva notificación', ms: 9000 });
                            if (!quieto) {
                                btn.classList.remove('timbre');
                                void btn.offsetWidth;
                                btn.classList.add('timbre');
                            }
                        }
                    }
                    vistos = ids.concat(vistos || []);
                })
                .catch(function () {
                    if (conLista) lista.innerHTML = '<li class="gca-np-vacio"><i class="bi bi-wifi-off" aria-hidden="true"></i>No se pudieron cargar las notificaciones.</li>';
                });
        }
        function enviar(datos) {
            var fd = new FormData();
            Object.keys(datos).forEach(function (k) { fd.append(k, datos[k]); });
            fd.append('_csrf_token', csrf);
            return fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) throw new Error('no');
                    if (typeof d.no_leidas === 'number') { n = d.no_leidas; pintarBadge(n); }
                    document.dispatchEvent(new CustomEvent('gca:notificaciones', { detail: { no_leidas: n } }));
                })
                .catch(function () { GCA.toast('No se pudo actualizar la notificación. Intente de nuevo.', { tipo: 'bad' }); });
        }
        function colocar() {
            var r = btn.getBoundingClientRect();
            var w = panel.offsetWidth;
            var izq = Math.min(Math.max(12, r.right - w), window.innerWidth - w - 12);
            panel.style.left = izq + 'px';
            panel.style.top = (r.bottom + 8) + 'px';
        }
        function abrir() {
            panel.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            colocar();
            cargar(true);
            setTimeout(function () { (panel.querySelector('button:not([disabled]), a') || panel).focus(); }, 30);
        }
        function cerrar(devolverFoco) {
            if (panel.hidden) return;
            panel.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
            if (devolverFoco) btn.focus();
        }
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (panel.hidden) abrir(); else cerrar();
        });
        document.addEventListener('click', function (e) {
            if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) cerrar();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(true); });
        window.addEventListener('resize', function () { if (!panel.hidden) colocar(); });
        window.addEventListener('scroll', function () { if (!panel.hidden) colocar(); }, { passive: true });

        lista.addEventListener('click', function (e) {
            var b = e.target.closest('[data-leer]');
            if (!b) return;
            var id = +b.getAttribute('data-leer');
            items.forEach(function (i) { if (i.id === id) i.leido = true; });
            pintarLista();
            enviar({ accion: 'leer', id: id });
        });
        bTodas.addEventListener('click', function () {
            items.forEach(function (i) { i.leido = true; });
            pintarLista();
            pintarBadge(0);
            enviar({ accion: 'leer_todas' });
        });

        cargar(false);
        // Revisa cada 45 s mientras la pestaña está a la vista
        setInterval(function () { if (!document.hidden) cargar(false); }, 45000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) cargar(false); });
        document.addEventListener('gca:notificaciones-recargar', function () { cargar(!panel.hidden); });
    }

    function iniciar() {
        document.body.classList.add('gca-ui');
        cerrables();
        document.querySelectorAll('[data-gca-bell]').forEach(function (b) { Campana(b); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
