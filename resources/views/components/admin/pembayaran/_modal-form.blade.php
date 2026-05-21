@if($showModal)
<div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-box-header">
            <h5 class="mb-0 fw-600">Catat Pembayaran Manual</h5>
            <button wire:click="$set('showModal', false)" class="btn-close"></button>
        </div>
        <div class="modal-box-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Tagihan
                    </label>
                    <select wire:model.live="tagihan_id" class="firabo-input">
                        <option value="">Pilih Tagihan</option>
                        @foreach($tagihanBelumLunas as $t)
                            <option value="{{ $t->tagihan_id }}">
                                {{ $t->hunian->user->name ?? '-' }} —
                                {{ $t->hunian->kamar->nomor_kamar ?? '-' }} —
                                Rp {{ number_format($t->nominal, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @error('tagihan_id')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Nominal Bayar
                    </label>
                    <input type="number" wire:model.live="nominal_bayar"
                           class="firabo-input" placeholder="1500000">
                    @error('nominal_bayar')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Tanggal Bayar
                    </label>
                    <input type="date" wire:model.live="tanggal_bayar" class="firabo-input">
                    @error('tanggal_bayar')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Metode
                    </label>
                    <select wire:model.live="metode_pembayaran" class="firabo-input">
                        <option value="manual">Manual (Cash)</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-box-footer">
            <button wire:click="$set('showModal', false)" class="btn-firabo-outline me-2">
                Batal
            </button>
            <button wire:click="save" wire:loading.attr="disabled" class="btn-firabo">
                <span wire:loading.remove wire:target="save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
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