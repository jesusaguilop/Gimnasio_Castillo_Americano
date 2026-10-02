<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select:not(.no-ts)').forEach(function(el) {
            new TomSelect(el, {
                // 100 dejaba fuera de la lista a más de la mitad de los
                // estudiantes (son 245); se veían solo escribiendo para buscar.
                maxOptions: 400,
                placeholder: el.options[0]?.text || 'Seleccionar...',
                allowEmptyOption: true,
                onDropdownOpen: function() {
                    this.dropdown.style.maxHeight = '280px';
                    this.dropdown.style.overflowY = 'auto';
                }
            });
        });
    });
</script>
<script>
    function toggleMenu(el) {
        const submenu = el.nextElementSibling;
        if (!submenu || !submenu.classList.contains('nav-submenu')) return;

        const isOpen = submenu.classList.contains('open');
        document.querySelectorAll('.nav-submenu.open').forEach(s => s.classList.remove('open'));
        document.querySelectorAll('.nav-parent.open').forEach(p => {
            p.classList.remove('open');
            if (p.hasAttribute('aria-expanded')) p.setAttribute('aria-expanded', 'false');
        });
        if (!isOpen) {
            submenu.classList.add('open');
            el.classList.add('open');
        }
        el.setAttribute('aria-expanded', String(!isOpen));
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main');
        const overlay = document.getElementById('overlay');
        let abierto;
        if (window.innerWidth <= 768) {
            abierto = sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        } else {
            abierto = !sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }
        document.querySelectorAll('.toggle-btn').forEach(b => b.setAttribute('aria-expanded', String(abierto)));
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('show');
        document.querySelectorAll('.toggle-btn').forEach(b => b.setAttribute('aria-expanded', 'false'));
    }

    // Estado inicial del botón de menú (en móvil el menú arranca cerrado)
    document.querySelectorAll('.toggle-btn').forEach(b => {
        b.setAttribute('aria-expanded', String(window.innerWidth > 768));
    });

    // Highlight active sub-item and open parent menu
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);

    document.querySelectorAll('.nav-sub-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active-item');
            link.style.color = '#d4af37';
            link.style.fontWeight = '600';

            const submenu = link.closest('.nav-submenu');
            if (submenu) {
                submenu.classList.add('open');
                const parent = submenu.previousElementSibling;
                if (parent && parent.classList.contains('nav-parent')) {
                    parent.classList.add('open');
                    parent.classList.add('active');
                    if (parent.hasAttribute('aria-expanded')) parent.setAttribute('aria-expanded', 'true');
                }
                link.setAttribute('aria-current', 'page');
            }
        }
    });

    // ── Dark Mode Toggle ──
    (function() {
        function applyDark(dark) {
            if (dark) {
                document.body.classList.add('dark-mode');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                document.documentElement.removeAttribute('data-bs-theme');
            }
        }

        const isDark = localStorage.getItem('gca-dark-mode') === 'true';
        applyDark(isDark);

        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;
        const icon = document.getElementById('dmIcon');
        const label = document.getElementById('dmLabel');

        function updateUI() {
            const dark = document.body.classList.contains('dark-mode');
            icon.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
            label.textContent = dark ? 'Modo claro' : 'Modo oscuro';
            toggle.setAttribute('aria-pressed', String(dark));
        }

        updateUI();
        toggle.addEventListener('click', function() {
            const next = !document.body.classList.contains('dark-mode');
            applyDark(next);
            localStorage.setItem('gca-dark-mode', next);
            updateUI();
        });
    })();

    // ── Confirm Modal ──
    // onclick (y no addEventListener) para que cada confirmación reemplace a
    // la anterior: antes, cancelar dejaba el callback viejo enganchado y se
    // ejecutaba junto con el siguiente que sí se confirmaba.
    function showConfirm(message, callback, textoBoton) {
        const modal = document.getElementById('gcaConfirmModal');
        if (!modal) return;
        document.getElementById('gcaConfirmMsg').textContent = message;
        const btn = document.getElementById('gcaConfirmBtn');
        btn.textContent = textoBoton || 'Sí, eliminar';
        btn.onclick = function() {
            btn.onclick = null;
            hideConfirm();
            callback();
        };
        modal.addEventListener('hidden.bs.modal', function() { btn.onclick = null; }, { once: true });
        modal.addEventListener('shown.bs.modal', function() {
            modal.querySelector('[data-bs-dismiss]').focus();
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: true }).show();
    }
    function hideConfirm() {
        const modal = document.getElementById('gcaConfirmModal');
        if (modal) bootstrap.Modal.getInstance(modal)?.hide();
    }
</script>

<!-- ── Confirm Modal ── -->
<div class="modal fade" id="gcaConfirmModal" tabindex="-1" aria-hidden="true" role="alertdialog" aria-labelledby="gcaConfirmMsg">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--border-color);box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-body text-center py-5 px-4">
                <div class="mb-3" style="font-size:48px;color:var(--gold);">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                </div>
                <p id="gcaConfirmMsg" class="mb-4" style="font-size:16px;font-weight:500;color:var(--text-primary);line-height:1.5;">¿Seguro?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;border:1.5px solid var(--border-color);color:var(--text-secondary);font-weight:500;background:transparent;">Cancelar</button>
                    <button type="button" class="btn px-4 py-2" id="gcaConfirmBtn" style="border-radius:10px;border:none;background:#dc3545;color:#fff;font-weight:600;">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

</body>

</html>