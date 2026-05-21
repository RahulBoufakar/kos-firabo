@if($showModal)
<div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
    <div class="modal-box" style="max-width:580px">
        <div class="modal-box-header">
            <h5 class="mb-0 fw-600">
                {{ $isEditing ? 'Edit Penghuni' : 'Tambah Penghuni Baru' }}
            </h5>
            <button wire:click="$set('showModal', false)" class="btn-close"></button>
        </div>
        <div class="modal-box-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">Nama Lengkap</label>
                    <input type="text" wire:model.live="name"
                           class="firabo-input" placeholder="Budi Santoso">
                    @error('name')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">Email</label>
                    <input type="email" wire:model.live="email"
                           class="firabo-input" placeholder="budi@email.com">
                    @error('email')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">No. WhatsApp</label>
                    <input type="text" wire:model.live="no_wa"
                           class="firabo-input" placeholder="08123456789">
                    @error('no_wa')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Password {{ $isEditing ? '(kosongkan jika tidak diubah)' : '' }}
                    </label>
                    <input type="password" wire:model.live="password"
                           class="firabo-input" placeholder="••••••••">
                    @error('password')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">Kamar</label>
                    <select wire:model.live="kamar_id" class="firabo-input">
                        <option value="">Pilih Kamar</option>
                        @foreach($kamarTersedia as $kamar)
                            <option value="{{ $kamar->kamar_id }}">
                                {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
                            </option>
                        @endforeach
                    </select>
                    @error('kamar_id')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">Tanggal Masuk</label>
                    <input type="date" wire:model.live="tanggal_masuk" class="firabo-input">
                    @error('tanggal_masuk')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Tanggal Generate (1–28)
                    </label>
                    <input type="number" wire:model.live="tanggal_generate"
                           class="firabo-input" min="1" max="28">
                    <small class="text-muted" style="font-size:11px">
                        Tanggal dalam bulan tagihan di-generate
                    </small>
                    @error('tanggal_generate')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Jatuh Tempo (hari)
                    </label>
                    <input type="number" wire:model.live="tanggal_jatuh_tempo"
                           class="firabo-input" min="1" max="30">
                    <small class="text-muted" style="font-size:11px">
                        Jarak hari dari tanggal generate
                    </small>
                    @error('tanggal_jatuh_tempo')
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
                    {{ $isEditing ? 'Simpan' : 'Tambah Penghuni' }}
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