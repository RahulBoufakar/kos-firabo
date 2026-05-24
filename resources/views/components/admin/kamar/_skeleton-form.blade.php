{{--
    _skeleton-form.blade.php
    Ditampilkan selama 700ms saat user klik Tambah/Edit,
    sebelum form benar-benar dimuat. Memberikan kesan loading
    yang halus alih-alih langsung "loncat" ke form kosong.

    Dipanggil dari table.blade.php di VIEW: SKELETON TRANSISI.
    Tidak membutuhkan variable dari Livewire.
--}}
<div class="firabo-card" style="padding: 2rem;">

    {{-- Header skeleton --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="skeleton" style="width:38px; height:38px; border-radius:9px; flex-shrink:0;"></div>
        <div>
            <div class="skeleton skeleton-text" style="width: 180px; margin-bottom: 6px;"></div>
            <div class="skeleton skeleton-text" style="width: 260px;"></div>
        </div>
    </div>

    {{-- Field rows — 2 kolom, 2 baris --}}
    <div class="row g-3">
        @for ($i = 0; $i < 4; $i++)
            <div class="col-md-6">
                <div class="skeleton skeleton-text" style="width: 90px; margin-bottom: 8px;"></div>
                <div class="skeleton" style="width: 100%; height: 42px; border-radius: 8px;"></div>
            </div>
        @endfor

        {{-- Textarea fasilitas --}}
        <div class="col-12">
            <div class="skeleton skeleton-text" style="width: 70px; margin-bottom: 8px;"></div>
            <div class="skeleton" style="width: 100%; height: 80px; border-radius: 8px;"></div>
        </div>
    </div>

    {{-- Button row --}}
    <div class="d-flex gap-2 justify-content-end mt-4">
        <div class="skeleton" style="width: 90px; height: 38px; border-radius: 8px;"></div>
        <div class="skeleton" style="width: 130px; height: 38px; border-radius: 8px;"></div>
    </div>

</div>