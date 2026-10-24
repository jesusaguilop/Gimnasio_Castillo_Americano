/* ════════════════════════════════════════════════════════════
   GCA · Panel de administración — comportamiento común
   (menú lateral, tema, confirmaciones, TomSelect y filtros de tabla)
   ════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var root = document.documentElement;
    var MOVIL = 991.98;
    function esMovil() { return window.innerWidth <= MOVIL; }
    function guardar(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    /* ── Tema claro / oscuro ── */
    function aplicarTema(oscuro) {
        root.classList.toggle('dark-mode', oscuro);
        document.body.classList.toggle('dark-mode', oscuro);
        if (oscuro) root.setAttribute('data-bs-theme', 'dark');
        else root.removeAttribute('data-bs-theme');
        var t = document.getElementById('darkModeToggle');
        var i = document.getElementById('dmIcon');
        if (t) {
            t.setAttribute('aria-pressed', String(oscuro));
            t.setAttribute('aria-label', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
        }
        if (i) i.className = oscuro ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
    aplicarTema(root.classList.contains('dark-mode'));
    var dmBtn = document.getElementById('darkModeToggle');
    if (dmBtn) {
        dmBtn.addEventListener('click', function () {
            var next = !root.classList.contains('dark-mode');
            aplicarTema(next);
            guardar('gca-dark-mode', String(next));
        });
    }

    /* ── Menú lateral: cajón en móvil, modo compacto en escritorio ── */
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');

    function sincronizarBotones() {
        var abierto = esMovil() ? root.classList.contains('sb-open') : !root.classList.contains('sb-mini');
        document.querySelectorAll('.toggle-btn').forEach(function (b) {
            b.setAttribute('aria-expanded', String(abierto));
        });
        var c = document.querySelector('.sb-collapse');
        if (c) {
            var mini = root.classList.contains('sb-mini');
            c.setAttribute('aria-pressed', String(mini));
            c.title = mini ? 'Expandir menú' : 'Contraer menú';
            var t = c.querySelector('.sb-text');
            if (t) t.textContent = mini ? 'Expandir menú' : 'Contraer menú';
        }
    }

    window.toggleSidebar = function () {
        if (esMovil()) {
            var abrir = !root.classList.contains('sb-open');
            root.classList.toggle('sb-open', abrir);
            if (overlay) overlay.classList.toggle('show', abrir);
            if (abrir && sidebar) {
                var a = sidebar.querySelector('.sb-link.is-active') || sidebar.querySelector('a, button');
                if (a) setTimeout(function () { a.focus(); }, 60);
            }
        } else {
            window.toggleSidebarMini();
            return;
        }
        sincronizarBotones();
    };
    window.toggleSidebarMini = function () {
        var mini = !root.classList.contains('sb-mini');
        root.classList.toggle('sb-mini', mini);
        guardar('gca-sidebar-mini', String(mini));
        sincronizarBotones();
    };
    window.closeSidebar = function () {
        root.classList.remove('sb-open');
        if (overlay) overlay.classList.remove('show');
        sincronizarBotones();
    };
    // Compatibilidad con el marcado anterior
    window.toggleMenu = function () {};

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root.classList.contains('sb-open')) {
            window.closeSidebar();
            var b = document.querySelector('.toggle-btn');
            if (b) b.focus();
        }
    });
    window.addEventListener('resize', function () {
        if (!esMovil() && root.classList.contains('sb-open')) window.closeSidebar();
        sincronizarBotones();
    });
    sincronizarBotones();
    // Lleva el enlace activo a la vista si el menú tiene scroll
    var act = sidebar && sidebar.querySelector('.sb-link.is-active');
    if (act && act.scrollIntoView) {
        var nav = sidebar.querySelector('.sb-nav');
        if (nav && act.offsetTop > nav.clientHeight - 60) nav.scrollTop = act.offsetTop - nav.clientHeight / 2;
    }

    /* ── Modal de confirmación ──
       onclick (no addEventListener) para que cada confirmación reemplace a la
       anterior: cancelar no deja el callback viejo enganchado. */
    window.showConfirm = function (message, callback, textoBoton) {
        var modal = document.getElementById('gcaConfirmModal');
        if (!modal) { if (window.confirm(message)) callback(); return; }
        var texto = textoBoton || 'Sí, eliminar';
        var peligro = /elimin|borr|quitar|desvincul/i.test(texto + ' ' + message);
        document.getElementById('gcaConfirmMsg').textContent = message;
        document.getElementById('gcaConfirmTitle').textContent = peligro ? 'Confirmar eliminación' : 'Confirmar acción';
        modal.classList.toggle('is-danger', peligro);
        var btn = document.getElementById('gcaConfirmBtn');
        btn.textContent = texto;
        btn.className = peligro ? 'btn-danger-gca' : 'btn-gca';
        btn.onclick = function () {
            btn.onclick = null;
            window.hideConfirm();
            callback();
        };
        modal.addEventListener('hidden.bs.modal', function () { btn.onclick = null; }, { once: true });
        modal.addEventListener('shown.bs.modal', function () {
            modal.querySelector('[data-bs-dismiss]').focus();
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: true }).show();
    };
    window.hideConfirm = function () {
        var modal = document.getElementById('gcaConfirmModal');
        if (modal && window.bootstrap) {
            var inst = bootstrap.Modal.getInstance(modal);
            if (inst) inst.hide();
        }
    };

    /* ── Filtro instantáneo de tablas ──
       - <input data-live-filter="#idTabla">: un campo existente filtra mientras se escribe.
       - Tablas .gca-table con 8 filas o más (sin data-no-filter) reciben una
         barra de filtro automática. */
    function normalizar(s) {
        return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function filasDe(tabla) {
        return Array.prototype.filter.call(tabla.tBodies[0] ? tabla.tBodies[0].rows : [], function (r) {
            return !r.classList.contains('gca-nores') && r.cells.length > 1;
        });
    }
    function enlazarFiltro(input, tabla, contador) {
        var nores = null;
        function aplicar() {
            var q = normalizar(input.value.trim());
            var filas = filasDe(tabla), vis = 0;
            filas.forEach(function (r) {
                var ok = !q || normalizar(r.textContent).indexOf(q) !== -1;
                r.hidden = !ok;
                if (ok) vis++;
            });
            if (contador) contador.textContent = q ? vis + ' de ' + filas.length : filas.length + ' registros';
            if (!vis && q && filas.length) {
                if (!nores) {
                    nores = document.createElement('tr');
                    nores.className = 'gca-nores';
                    var td = document.createElement('td');
                    td.colSpan = (tabla.tHead && tabla.tHead.rows[0]) ? tabla.tHead.rows[0].cells.length : 1;
                    td.innerHTML = '<div class="empty-state empty-state-sm"><i class="bi bi-search" aria-hidden="true"></i><p>Ningún resultado coincide con el filtro.</p></div>';
                    nores.appendChild(td);
                }
                tabla.tBodies[0].appendChild(nores);
            } else if (nores && nores.parentNode) {
                nores.parentNode.removeChild(nores);
            }
        }
        input.addEventListener('input', aplicar);
        // Si la página reemplaza las filas (p. ej. el refresco en vivo de
        // boletines), se vuelve a aplicar el filtro escrito.
        if (window.MutationObserver && tabla.tBodies[0]) {
            new MutationObserver(function (muts) {
                var ajenas = muts.some(function (m) {
                    return Array.prototype.some.call(m.addedNodes, function (n) { return n !== nores; }) ||
                           Array.prototype.some.call(m.removedNodes, function (n) { return n !== nores; });
                });
                if (ajenas) aplicar();
            }).observe(tabla.tBodies[0], { childList: true });
        }
        if (input.value) aplicar();
        else if (contador) contador.textContent = filasDe(tabla).length + ' registros';
    }

    document.querySelectorAll('[data-live-filter]').forEach(function (input) {
        var tabla = document.querySelector(input.getAttribute('data-live-filter'));
        if (!tabla) return;
        tabla.setAttribute('data-filter-bound', '1');
        var cont = input.getAttribute('data-live-count') ? document.querySelector(input.getAttribute('data-live-count')) : null;
        enlazarFiltro(input, tabla, cont);
    });

    var nFiltro = 0;
    document.querySelectorAll('table.gca-table').forEach(function (tabla) {
        if (tabla.hasAttribute('data-no-filter') || tabla.getAttribute('data-filter-bound')) return;
        if (tabla.closest('.accordion') || !tabla.tBodies[0]) return;
        if (filasDe(tabla).length < 8) return;
        nFiltro++;
        var id = 'gcaFiltro' + nFiltro;
        var barra = document.createElement('div');
        barra.className = 'table-toolbar';
        barra.innerHTML =
            '<div class="search-field">' +
            '<i class="bi bi-search" aria-hidden="true"></i>' +
            '<label class="visually-hidden" for="' + id + '">Filtrar esta tabla</label>' +
            '<input type="search" id="' + id + '" class="form-control" placeholder="Filtrar en la tabla…" autocomplete="off">' +
            '</div><span class="table-count" role="status" aria-live="polite"></span>';
        var antes = tabla.closest('.table-responsive') || tabla;
        antes.parentNode.insertBefore(barra, antes);
        enlazarFiltro(barra.querySelector('input'), tabla, barra.querySelector('.table-count'));
    });

    /* ── TomSelect en todos los <select> (excepto .no-ts) ── */
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.TomSelect) return;
        document.querySelectorAll('select:not(.no-ts)').forEach(function (el) {
            if (el.tomselect) return;
            new TomSelect(el, {
                // 100 dejaba fuera de la lista a más de la mitad de los
                // estudiantes (son 245); se veían solo escribiendo para buscar.
                maxOptions: 400,
                placeholder: (el.options[0] && el.options[0].text) || 'Seleccionar...',
                allowEmptyOption: true,
                onDropdownOpen: function () {
                    this.dropdown.style.maxHeight = '280px';
                    this.dropdown.style.overflowY = 'auto';
                }
            });
        });
    });
})();
