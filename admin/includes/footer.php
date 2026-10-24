<!-- ── Modal de confirmación (showConfirm) ── -->
<div class="modal fade gca-modal" id="gcaConfirmModal" tabindex="-1" aria-hidden="true" role="alertdialog" aria-labelledby="gcaConfirmTitle" aria-describedby="gcaConfirmMsg">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="gca-modal-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
                <h2 class="gca-modal-title" id="gcaConfirmTitle">Confirmar acción</h2>
                <p id="gcaConfirmMsg" class="gca-modal-msg">¿Seguro?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-gca" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-danger-gca" id="gcaConfirmBtn">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="../assets/js/admin.js?v=<?= $__gcaVer ?? '1' ?>"></script>

</body>

</html>
