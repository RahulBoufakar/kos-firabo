{{-- _skeleton-form.blade.php — skeleton card 700ms sebelum form edit muncul --}}
<div class="firabo-card" style="max-width:600px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="skeleton" style="width:40px; height:40px; border-radius:10px;"></div>
        <div>
            <div class="skeleton skeleton-text" style="width:180px; height:16px; margin-bottom:6px;"></div>
            <div class="skeleton skeleton-text" style="width:260px; height:12px;"></div>
        </div>
    </div>

    {{-- Info strip --}}
    <div class="skeleton" style="width:100%; height:48px; border-radius:10px; margin-bottom:1.5rem;"></div>

    {{-- Field: Tanggal Generate --}}
    <div class="mb-3">
        <div class="skeleton skeleton-text" style="width:120px; height:13px; margin-bottom:8px;"></div>
        <div class="skeleton" style="width:100%; height:42px; border-radius:8px;"></div>
        <div class="skeleton skeleton-text" style="width:200px; height:11px; margin-top:6px;"></div>
    </div>

    {{-- Field: Jatuh Tempo --}}
    <div class="mb-4">
        <div class="skeleton skeleton-text" style="width:110px; height:13px; margin-bottom:8px;"></div>
        <div class="skeleton" style="width:100%; height:42px; border-radius:8px;"></div>
        <div class="skeleton skeleton-text" style="width:180px; height:11px; margin-top:6px;"></div>
    </div>

    {{-- Buttons --}}
    <div class="d-flex gap-2 justify-content-end">
        <div class="skeleton" style="width:80px; height:38px; border-radius:8px;"></div>
        <div class="skeleton" style="width:120px; height:38px; border-radius:8px;"></div>
    </div>
</div>