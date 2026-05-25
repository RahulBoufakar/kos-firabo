<div class="firabo-card animate__animated animate__fadeIn" style="max-width:700px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <h5 class="mb-0 fw-bold">Catat Pembayaran Manual</h5>
        <button wire:click="closeForm" class="btn-close"></button>
    </div>
    
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" style="font-size:14px; font-weight:500">
                Pilih Tagihan (Belum Lunas)
            </label>
            <select wire:model.live="tagihan_id" class="firabo-input">
                <option value="">-- Pilih Tagihan --</option>
                @foreach($tagihanBelumLunas as $t)
                    <option value="{{ $t->tagihan_id }}">
                        {{ $t->hunian->user->name ?? '-' }} —
                        Kamar {{ $t->hunian->kamar->nomor_kamar ?? '-' }} —
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
                Nominal Bayar <span class="text-muted fw-normal">(Otomatis)</span>
            </label>
            <input type="number" wire:model="nominal_bayar" class="firabo-input bg-light" readonly placeholder="0">
            @error('nominal_bayar')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" style="font-size:14px; font-weight:500">
                Tanggal Bayar
            </label>
            <input type="date" wire:model="tanggal_bayar" class="firabo-input">
            @error('tanggal_bayar')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" style="font-size:14px; font-weight:500">
                Metode Pembayaran
            </label>
            <select wire:model="metode_pembayaran" class="firabo-input">
                <option value="manual">Manual (Cash)</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="qris">QRIS</option>
            </select>
            @error('metode_pembayaran')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
        <button wire:click="closeForm" class="btn-firabo-outline">
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