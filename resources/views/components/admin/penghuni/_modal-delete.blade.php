@if($showDeleteConfirm)
<div class="modal-backdrop-custom">
    <div class="modal-box" style="max-width:420px">
        <div class="modal-box-header">
            <h5 class="mb-0 fw-600 text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>Nonaktifkan Penghuni
            </h5>
            <button wire:click="$set('showDeleteConfirm', false)" class="btn-close"></button>
        </div>
        <div class="modal-box-body">
            <p class="mb-0" style="font-size:14px; color:#374151">
                Penghuni akan dinonaktifkan dan kamar akan
                <strong>dibebaskan kembali</strong>.
            </p>
        </div>
        <div class="modal-box-footer">
            <button wire:click="$set('showDeleteConfirm', false)"
                    class="btn-firabo-outline me-2">
                Batal
            </button>
            <button wire:click="delete"
                    wire:loading.attr="disabled"
                    class="btn btn-danger d-flex align-items-center gap-2">
                <span wire:loading.remove wire:target="delete">
                    <i class="bi bi-person-x me-1"></i> Ya, Nonaktifkan
                </span>
                <span wire:loading wire:target="delete">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    Memproses...
                </span>
            </button>
        </div>
    </div>
</div>
@endif