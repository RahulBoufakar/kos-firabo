{{-- _modal-delete.blade.php — Alpine popup konfirmasi nonaktifkan penghuni --}}
{{--
    Menggunakan 3-layer pattern agar Alpine tidak merusak flex centering:
    Layer 1: Backdrop   (x-show) — overlay gelap
    Layer 2: Flex wrap  (tanpa x-show) — centering container
    Layer 3: Popup box  (x-show + scale transition) — konten popup
--}}

{{-- Layer 1: Backdrop --}}
<div
    x-show="deletePopup.show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-end="opacity-0"
    x-cloak
    style="position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1050;"
    @click.self="tutupPopupHapus()"
>
    {{-- Layer 2: Flex centering (TIDAK ada x-show di sini) --}}
    <div style="display:flex; align-items:center; justify-content:center; height:100%;">

        {{-- Layer 3: Popup box --}}
        <div
            x-show="deletePopup.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 scale-95"
            style="
                background:#fff;
                border-radius:14px;
                box-shadow:0 20px 60px rgba(0,0,0,.18);
                padding:1.75rem;
                max-width:420px;
                width:calc(100% - 2rem);
            "
            @click.stop
        >
            {{-- Icon --}}
            <div style="
                width:52px; height:52px; border-radius:12px;
                background:#fef2f2;
                display:flex; align-items:center; justify-content:center;
                margin-bottom:1.25rem;
            ">
                <i class="bi bi-person-x" style="font-size:1.4rem; color:#dc2626;"></i>
            </div>

            {{-- Judul --}}
            <div style="font-weight:600; font-size:1rem; color:#1e293b; margin-bottom:.5rem;">
                Nonaktifkan Penghuni?
            </div>

            {{-- Deskripsi --}}
            <div style="font-size:.875rem; color:#64748b; margin-bottom:.25rem;">
                Anda akan menonaktifkan:
            </div>
            <div style="
                font-weight:600; font-size:.9rem; color:#1e293b;
                margin-bottom:.75rem;
            " x-text="deletePopup.nama"></div>
            <div style="font-size:.82rem; color:#94a3b8; margin-bottom:1.5rem; line-height:1.5;">
                Kamar akan dikembalikan ke status <strong>tersedia</strong> dan
                hunian akan ditandai selesai. Akun penghuni tidak akan dihapus.
            </div>

            {{-- Tombol --}}
            <div class="d-flex gap-2 justify-content-end">
                <button
                    @click="tutupPopupHapus()"
                    class="btn-firabo-outline"
                >
                    Batal
                </button>
                <button
                    @click="konfirmasiHapus()"
                    style="
                        background:#dc2626; color:#fff;
                        border:none; border-radius:8px;
                        padding:.5rem 1.25rem;
                        font-size:.875rem; font-weight:500;
                        cursor:pointer;
                        transition:background .15s;
                    "
                    onmouseover="this.style.background='#b91c1c'"
                    onmouseout="this.style.background='#dc2626'"
                >
                    <i class="bi bi-person-x me-1"></i>Ya, Nonaktifkan
                </button>
            </div>
        </div>

    </div>
</div>