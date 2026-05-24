{{--
    _modal-delete.blade.php
    Popup konfirmasi delete kamar — dikontrol sepenuhnya Alpine.
    Livewire delete() dipanggil HANYA setelah user konfirmasi.

    Fix masalah posisi:
    Backdrop dibuat sebagai overlay tipis (hanya background + pointer events).
    Flex centering diletakkan di div DALAM yang tidak disentuh x-show,
    sehingga Alpine tidak bisa override display:flex menjadi display:block.

    Alpine state dari root x-data (table.blade.php):
    deletePopup.show / deletePopup.nama / tutupPopupHapus() / konfirmasiHapus()
--}}

{{-- Layer 1: Backdrop — hanya opacity, TIDAK flex --}}
<div
    x-show="deletePopup.show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-end="opacity-0"
    x-cloak
    @keydown.escape.window="tutupPopupHapus()"
    @click.self="tutupPopupHapus()"
    style="position: fixed; inset: 0;
           background: rgba(0,0,0,.45);
           backdrop-filter: blur(2px);
           z-index: 1050;"
>
    {{--
        Layer 2: Flex centering wrapper — TIDAK punya x-show.
        Karena tidak disentuh Alpine, display:flex ini selalu aktif
        selama parent (Layer 1) visible.
    --}}
    <div style="display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
                padding: 1rem;">

        {{-- Layer 3: Popup box dengan scale transition --}}
        <div
            x-show="deletePopup.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            style="background: #fff;
                   border-radius: 16px;
                   width: 100%;
                   max-width: 400px;
                   box-shadow: 0 20px 60px rgba(0,0,0,.2);
                   overflow: hidden;"
        >
            {{-- Body --}}
            <div style="padding: 1.5rem 1.5rem 0;">
                <div style="width: 48px; height: 48px; border-radius: 12px;
                            background: #fee2e2; color: #991b1b;
                            display: flex; align-items: center; justify-content: center;
                            font-size: 1.35rem; margin-bottom: 1rem;">
                    <i class="bi bi-trash3-fill"></i>
                </div>
                <h5 style="font-weight: 700; color: #111827; margin-bottom: .375rem;">
                    Delete Kamar?
                </h5>
                <p style="font-size: .875rem; color: #6b7280; margin-bottom: 0;">
                    Anda akan menghapus
                    <strong style="color: #111827;" x-text="deletePopup.nama"></strong>.
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            {{-- Footer --}}
            <div style="padding: 1.25rem 1.5rem;
                        display: flex;
                        gap: .625rem;
                        justify-content: flex-end;">
                <button class="btn-firabo-outline" @click="tutupPopupHapus()">
                    Batal
                </button>
                <button
                    @click="konfirmasiHapus()"
                    class="btn btn-danger btn-sm px-3 fw-semibold"
                >
                    <i class="bi bi-trash3 me-1"></i> Ya, Hapus
                </button>
            </div>

        </div>{{-- /popup box --}}
    </div>{{-- /flex centering --}}
</div>{{-- /backdrop --}}