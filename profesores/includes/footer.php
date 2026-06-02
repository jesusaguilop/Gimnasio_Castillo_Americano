    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select:not(.no-ts)').forEach(function(el) {
                new TomSelect(el, { maxOptions: 100, placeholder: el.options[0]?.text || 'Seleccionar...', allowEmptyOption: true });
            });
        });
    </script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('open');
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
            overlay.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const overlay = document.getElementById('overlay');
            sidebar.classList.remove('open');
            sidebar.classList.add('collapsed');
            main.classList.add('expanded');
            overlay.classList.remove('show');
        }

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
    function showConfirm(message, callback) {
        const modal = document.getElementById('gcaConfirmModal');
        if (!modal) return;
        document.getElementById('gcaConfirmMsg').textContent = message;
        const btn = document.getElementById('gcaConfirmBtn');
        const handler = function() { callback(); hideConfirm(); };
        btn.removeEventListener('click', handler);
        btn.addEventListener('click', handler);
        new bootstrap.Modal(modal, { backdrop: 'static', keyboard: false }).show();
    }
    function hideConfirm() {
        const modal = document.getElementById('gcaConfirmModal');
        if (modal) bootstrap.Modal.getInstance(modal)?.hide();
    }
</script>

<!-- ── Confirm Modal ── -->
<div class="modal fade" id="gcaConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--border-color);box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-body text-center py-5 px-4">
                <div class="mb-3" style="font-size:48px;color:var(--gold);">
                    <i class="bi bi-exclamation-triangle-fill"></i>
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
