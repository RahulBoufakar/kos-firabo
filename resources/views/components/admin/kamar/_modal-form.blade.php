{{--
    _modal-form.blade.php
    Form tambah/edit kamar — ditampilkan sebagai VIEW: FORM
    di dalam table.blade.php (bukan modal overlay).

    Variabel yang tersedia dari Livewire parent:
    $isEditing, $nomor_kamar, $tipe_kamar, $harga_sewa,
    $fasilitas, $status_kamar
--}}
<div class="firabo-card">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:38px; height:38px; border-radius:9px;
                    background:var(--firabo-primary-light); color:var(--firabo-primary);
                    display:flex; align-items:center; justify-content:center;
                    font-size:1.1rem; flex-shrink:0;">
            <i class="bi {{ $isEditing ? 'bi-pencil-square' : 'bi-door-open-fill' }}"></i>
        </div>
        <div>
            <h5 class="mb-0 fw-semibold" style="color: var(--firabo-primary-dark);">
                {{ $isEditing ? 'Edit Kamar' : 'Tambah Kamar Baru' }}
            </h5>
            <small class="text-muted">
                {{ $isEditing ? 'Perbarui data kamar yang dipilih.' : 'Isi detail kamar baru.' }}
            </small>
        </div>
    </div>

    <form wire:submit="save">
        <div class="row g-3">

            {{-- Nomor Kamar --}}
            <div class="col-md-6">
                <label class="form-label-firabo">
                    Nomor Kamar <span class="text-danger">*</span>
                </label>
                <input
                    class="firabo-input @error('nomor_kamar') is-invalid @enderror"
                    type="text"
                    wire:model.live="nomor_kamar"
                    placeholder="Contoh: A01"
                >
                @error('nomor_kamar')
                    <div class="field-error mt-1">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Tipe Kamar --}}
            <div class="col-md-6">
                <label class="form-label-firabo">
                    Tipe Kamar <span class="text-danger">*</span>
                </label>
                <input
                    class="firabo-input @error('tipe_kamar') is-invalid @enderror"
                    type="text"
                    wire:model.live="tipe_kamar"
                    placeholder="Contoh: Standar, Deluxe, VIP"
                >
                @error('tipe_kamar')
                    <div class="field-error mt-1">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Harga Sewa --}}
            <div class="col-md-6">
                <label class="form-label-firabo">
                    Harga Sewa / Bulan <span class="text-danger">*</span>
                </label>
                <div class="input-icon-wrap">
                    <i class="bi bi-cash-stack"></i>
                    <input
                        class="firabo-input @error('harga_sewa') is-invalid @enderror"
                        type="number"
                        wire:model.live="harga_sewa"
                        placeholder="800000"
                        min="0"
                    >
                </div>
                @error('harga_sewa')
                    <div class="field-error mt-1">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Status — hanya tampil saat edit --}}
            @if ($isEditing)
                <div class="col-md-6">
                    <label class="form-label-firabo">Status Kamar</label>
            
                    @if ($kamarSedangDihuni)
                        {{-- Kamar sedang dihuni: dropdown dikunci total, hanya
                            menampilkan "Terisi" sebagai informasi. Tidak wire:model —
                            nilai $status_kamar sudah 'terisi' dari openEdit() dan
                            memang tidak boleh diubah lewat form ini. --}}
                        <select class="firabo-input" disabled
                                style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;">
                            <option selected>Terisi</option>
                        </select>
                        <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
                            <i class="bi bi-lock-fill me-1"></i>
                            Tidak dapat diubah — kamar masih memiliki penghuni aktif.
                            Nonaktifkan penghuninya lebih dulu lewat menu Penghuni.
                        </div>
                    @else
                        {{-- Kamar tidak sedang dihuni: dropdown aktif, tapi opsi
                            "Terisi" TIDAK ditampilkan sama sekali — status itu hanya
                            boleh terbentuk otomatis saat ada penghuni menempati kamar. --}}
                        <select class="firabo-input" wire:model.live="status_kamar">
                            <option value="tersedia">Tersedia</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
                            Status "Terisi" hanya terbentuk otomatis saat ada penghuni
                            yang menempati kamar ini.
                        </div>
                    @endif
                </div>
            @endif

            {{-- Fasilitas --}}
            <div class="col-12">
                <label class="form-label-firabo">Fasilitas</label>
                <textarea
                    class="firabo-input"
                    wire:model.live="fasilitas"
                    rows="3"
                    placeholder="Contoh: Kasur, lemari, AC, kamar mandi dalam..."
                    style="resize: vertical;"
                ></textarea>
            </div>

        </div>

        {{-- Footer --}}
        <div class="d-flex gap-2 justify-content-end mt-4">
            <button
                type="button"
                class="btn-firabo-outline"
                wire:click="cancelForm"
            >
                <i class="bi bi-arrow-left me-1"></i> Batal
            </button>
            <button
                type="submit"
                class="btn-firabo"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Kamar' }}
                </span>
                <span wire:loading wire:target="save">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>

</div>