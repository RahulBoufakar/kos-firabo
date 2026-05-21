@if($showModal)
<div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
    <div class="modal-box">
        <div class="modal-box-header">
            <h5 class="mb-0 fw-600">
                {{ $isEditing ? 'Edit Kamar' : 'Tambah Kamar Baru' }}
            </h5>
            <button wire:click="$set('showModal', false)" class="btn-close"></button>
        </div>

        <div class="modal-box-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Nomor Kamar
                    </label>
                    <input type="text" wire:model.live="nomor_kamar"
                           class="firabo-input" placeholder="A-101">
                    @error('nomor_kamar')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Tipe Kamar
                    </label>
                    <input type="text" wire:model.live="tipe_kamar"
                           class="firabo-input" placeholder="Standard AC">
                    @error('tipe_kamar')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Harga Sewa/Bulan
                    </label>
                    <input type="number" wire:model.live="harga_sewa"
                           class="firabo-input" placeholder="1500000">
                    @error('harga_sewa')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Status
                    </label>
                    <select wire:model.live="status_kamar" class="firabo-input">
                        <option value="tersedia">Tersedia</option>
                        <option value="terisi">Terisi</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                    @error('status_kamar')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Fasilitas
                    </label>
                    <textarea wire:model.live="fasilitas" class="firabo-input"
                              rows="3" placeholder="AC, WiFi, Kamar Mandi Dalam..."></textarea>
                    @error('fasilitas')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="modal-box-footer">
            <button wire:click="$set('showModal', false)" class="btn-firabo-outline me-2">
                Batal
            </button>
            <button wire:click="save" wire:loading.attr="disabled" class="btn-firabo">
                <span wire:loading.remove wire:target="save">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Kamar' }}
                </span>
                <span wire:loading wire:target="save">
                    <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                    Menyimpan...
                </span>
            </button>
        </div>
    </div>
</div>
@endif