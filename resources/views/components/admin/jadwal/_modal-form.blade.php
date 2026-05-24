{{-- _modal-form.blade.php — form edit jadwal tagihan --}}
{{--
    Catatan: Jadwal TIDAK punya form "tambah" — jadwal selalu dibuat otomatis
    oleh sistem saat registrasi penghuni. Admin hanya bisa mengubah jadwal
    yang sudah ada. Karena itu tidak ada $isEditing / openCreate().
--}}

@php
    $jadwal = $editingId ? \App\Models\JadwalTagihan::with(['hunian.user', 'hunian.kamar'])->find($editingId) : null;
@endphp

<div class="firabo-card" style="max-width:600px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="
            width:40px; height:40px; border-radius:10px;
            background:var(--firabo-primary-light);
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        ">
            <i class="bi bi-calendar-check" style="color:var(--firabo-primary); font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-weight:600; font-size:.95rem; color:#1e293b;">Edit Jadwal Tagihan</div>
            @if($jadwal)
                <div style="font-size:.8rem; color:#64748b;">
                    {{ $jadwal->hunian->user->name ?? '-' }} &mdash;
                    Kamar {{ $jadwal->hunian->kamar->nomor_kamar ?? '-' }}
                </div>
            @endif
        </div>
    </div>

    {{-- Info strip --}}
    <div style="
        background:var(--firabo-primary-light);
        border:1px solid var(--firabo-border);
        border-radius:10px;
        padding:.75rem 1rem;
        margin-bottom:1.5rem;
        font-size:.82rem;
        color:#374151;
        display:flex; align-items:flex-start; gap:.5rem;
    ">
        <i class="bi bi-info-circle" style="color:var(--firabo-primary); flex-shrink:0; margin-top:1px;"></i>
        <span>
            Jadwal ini dibuat otomatis saat penghuni mendaftar.
            Perubahan akan berlaku mulai siklus tagihan berikutnya.
        </span>
    </div>
    {{-- Field: Status Jadwal --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Status Jadwal
            <span style="color:#dc2626;">*</span>
        </label>
        <select
            wire:model.live="status_jadwal"
            class="firabo-input @error('status_jadwal') is-invalid @enderror"
        >
            <option value="">-- Pilih Status Jadwal --</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        @error('status_jadwal')
            <div class="field-error">{{ $message }}</div>
        @enderror
        <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
            Jika nonaktif, sistem akan berhenti membuat tagihan otomatis untuk bulan berikutnya.
        </div>
    </div>

    {{-- Field: Tanggal Generate --}}
    <div class="mb-3">
        <label class="form-label-firabo">
            Tanggal Generate
            <span style="color:#dc2626;">*</span>
        </label>
        <input
            type="number"
            wire:model.live="tanggal_generate"
            class="firabo-input @error('tanggal_generate') is-invalid @enderror"
            placeholder="1 – 28"
            min="1"
            max="28"
        >
        @error('tanggal_generate')
            <div class="field-error">{{ $message }}</div>
        @enderror
        <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
            Tagihan di-generate setiap bulan pada tanggal ini. Maksimal 28 agar aman di bulan Februari.
        </div>
    </div>

    {{-- Field: Jatuh Tempo --}}
    <div class="mb-4">
        <label class="form-label-firabo">
            Jatuh Tempo (hari)
            <span style="color:#dc2626;">*</span>
        </label>
        <input
            type="number"
            wire:model.live="tanggal_jatuh_tempo"
            class="firabo-input @error('tanggal_jatuh_tempo') is-invalid @enderror"
            placeholder="1 – 30"
            min="1"
            max="30"
        >
        @error('tanggal_jatuh_tempo')
            <div class="field-error">{{ $message }}</div>
        @enderror
        <div style="font-size:.78rem; color:#94a3b8; margin-top:4px;">
            Jatuh tempo dihitung dari tanggal generate + jumlah hari ini.
        </div>
    </div>

    {{-- Preview jatuh tempo — dihitung dengan Carbon agar overflow bulan & tahun kabisat ditangani --}}
    @if($tanggal_generate && $tanggal_jatuh_tempo && (int)$tanggal_generate >= 1 && (int)$tanggal_generate <= 28 && (int)$tanggal_jatuh_tempo >= 1)
        @php
            // Gunakan bulan ini sebagai referensi. Jika tanggal_generate sudah
            // lewat di bulan ini, pakai bulan depan agar preview lebih relevan.
            $tglGenerate  = (int) $tanggal_generate;
            $hariJatuhTempo = (int) $tanggal_jatuh_tempo;
            $sekarang     = \Carbon\Carbon::today();
            $refBulan     = $sekarang->day > $tglGenerate
                            ? $sekarang->copy()->addMonthNoOverflow()
                            : $sekarang->copy();

            // Bangun tanggal generate yang valid untuk bulan referensi
            // (Carbon::createSafe mencegah tanggal tidak valid, fallback ke akhir bulan)
            $tglGenerateDate = \Carbon\Carbon::createSafe(
                $refBulan->year,
                $refBulan->month,
                $tglGenerate
            );
            // createSafe mengembalikan false jika tanggal tidak valid (misal 30 Feb)
            // Fallback: gunakan hari terakhir bulan itu
            if (!$tglGenerateDate) {
                $tglGenerateDate = $refBulan->copy()->endOfMonth()->startOfDay();
            }

            $tglJatuhTempo = $tglGenerateDate->copy()->addDays($hariJatuhTempo);

            $labelGenerate   = $tglGenerateDate->translatedFormat('j F Y');
            $labelJatuhTempo = $tglJatuhTempo->translatedFormat('j F Y');
            $beda            = $tglGenerateDate->month !== $tglJatuhTempo->month
                               || $tglGenerateDate->year !== $tglJatuhTempo->year;
        @endphp
        <div style="
            background:#f8fafc;
            border:1px solid var(--firabo-border);
            border-radius:8px;
            padding:.625rem 1rem;
            margin-bottom:1.25rem;
            font-size:.82rem;
            color:#475569;
            display:flex; align-items:flex-start; gap:.5rem;
        ">
            <i class="bi bi-lightning-charge" style="color:var(--firabo-primary); flex-shrink:0; margin-top:1px;"></i>
            <span>
                Contoh siklus berikutnya: generate <strong>{{ $labelGenerate }}</strong>,
                jatuh tempo <strong>{{ $labelJatuhTempo }}</strong>
                @if($beda)
                    <span style="color:var(--firabo-primary); font-weight:500;">(bulan berikutnya)</span>
                @endif
            </span>
        </div>
    @endif

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
                <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
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