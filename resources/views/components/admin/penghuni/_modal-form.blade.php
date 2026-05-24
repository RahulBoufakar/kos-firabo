{{-- _modal-form.blade.php — form tambah / edit penghuni --}}
<div class="firabo-card" style="max-width:600px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="
            width:40px; height:40px; border-radius:10px;
            background:var(--firabo-primary-light);
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        ">
            <i class="bi bi-person-fill" style="color:var(--firabo-primary); font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-weight:600; font-size:.95rem; color:#1e293b;">
                {{ $isEditing ? 'Edit Data Penghuni' : 'Tambah Penghuni Baru' }}
            </div>
            <div style="font-size:.8rem; color:#64748b;">
                {{ $isEditing ? 'Perbarui informasi akun dan kamar penghuni.' : 'Isi data penghuni dan pilih kamar yang tersedia.' }}
            </div>
        </div>
    </div>

    {{-- Field: Nama --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Nama Lengkap <span style="color:#dc2626;">*</span>
        </label>
        <div class="input-icon-wrap">
            <i class="bi bi-person"></i>
            <input
                type="text"
                wire:model.live="name"
                class="firabo-input @error('name') is-invalid @enderror"
                placeholder="Nama lengkap penghuni"
            >
        </div>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- Field: Email --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Email <span style="color:#dc2626;">*</span>
        </label>
        <div class="input-icon-wrap">
            <i class="bi bi-envelope"></i>
            <input
                type="email"
                wire:model.live="email"
                class="firabo-input @error('email') is-invalid @enderror"
                placeholder="email@contoh.com"
            >
        </div>
        @error('email') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- Field: No. WhatsApp --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Nomor WhatsApp <span style="color:#dc2626;">*</span>
        </label>
        <div class="input-icon-wrap">
            <i class="bi bi-whatsapp"></i>
            <input
                type="text"
                wire:model.live="no_wa"
                class="firabo-input @error('no_wa') is-invalid @enderror"
                placeholder="08xxxxxxxxxx"
            >
        </div>
        @error('no_wa') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- Field: Password --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Password
            @if(!$isEditing) <span style="color:#dc2626;">*</span> @endif
        </label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock"></i>
            <input
                type="password"
                wire:model.live="password"
                class="firabo-input @error('password') is-invalid @enderror"
                placeholder="{{ $isEditing ? 'Kosongkan jika tidak ingin ubah password' : 'Minimal 6 karakter' }}"
            >
        </div>
        @error('password') <div class="field-error">{{ $message }}</div> @enderror
        @if($isEditing)
            <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
                Biarkan kosong jika tidak ingin mengubah password.
            </div>
        @endif
    </div>

    {{-- Field: Kamar (tambah baru DAN edit — saat edit bisa pindah kamar) --}}
    <div class="mb-4">
        <label class="form-label-firabo">
            Kamar <span style="color:#dc2626;">*</span>
        </label>
        <select
            wire:model.live="kamar_id"
            class="firabo-input @error('kamar_id') is-invalid @enderror"
            style="height:42px;"
        >
            <option value="">-- Pilih kamar --</option>
            @foreach($kamarTersedia as $kamar)
                <option value="{{ $kamar->kamar_id }}">
                    {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
                    (Rp {{ number_format($kamar->harga_sewa, 0, ',', '.') }}/bln)
                    @if($isEditing && (string)$kamar->kamar_id === $kamar_id_asal)
                        — Kamar Sekarang
                    @endif
                </option>
            @endforeach
        </select>
        @error('kamar_id') <div class="field-error">{{ $message }}</div> @enderror

        @if($isEditing && $kamar_id && $kamar_id !== $kamar_id_asal)
            {{-- Peringatan pindah kamar — muncul dinamis saat pilihan berubah --}}
            <div style="
                margin-top:8px;
                background:#fffbeb;
                border:1px solid #fcd34d;
                border-radius:8px;
                padding:.625rem .875rem;
                font-size:.8rem;
                color:#92400e;
                display:flex; align-items:flex-start; gap:.5rem;
            ">
                <i class="bi bi-arrow-left-right" style="flex-shrink:0; margin-top:1px;"></i>
                <span>
                    Penghuni akan dipindahkan ke kamar ini. Hunian lama akan ditutup,
                    tagihan yang belum dibayar tetap harus dilunasi.
                    Jadwal tagihan akan disalin dari pengaturan sebelumnya.
                </span>
            </div>
        @elseif($kamarTersedia->isEmpty())
            <div style="font-size:.78rem; color:#f59e0b; margin-top:4px;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Tidak ada kamar tersedia saat ini.
            </div>
        @endif
    </div>

    {{-- Action buttons --}}
    <div class="d-flex gap-2 justify-content-end">
        <button
            wire:click="cancelForm"
            class="btn-firabo-outline"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            Batal
        </button>
        <button
            wire:click="save"
            class="btn-firabo"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">
                <i class="bi bi-check-lg me-1"></i>
                {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Penghuni' }}
            </span>
            <span wire:loading wire:target="save">
                <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
            </span>
        </button>
    </div>

</div>

<style>
.field-error { font-size:.8rem; color:#dc2626; margin-top:4px; }
</style>