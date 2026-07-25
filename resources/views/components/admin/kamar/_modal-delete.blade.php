{{--
    _modal-delete.blade.php (kamar) — revisi
    Pesan konfirmasi sekarang dinamis mengikuti kondisi kamar:
    - punyaHistori true  -> "akan dinonaktifkan" (bukan dihapus permanen)
    - punyaHistori false -> "akan dihapus permanen" (perilaku lama)

    Catatan: kasus "kamar sedang dihuni" TIDAK bisa sampai ke modal ini
    lewat alur normal karena tombolnya sudah disabled di client. Validasi
    utamanya ada di server (Livewire delete()) yang menolak dengan toast
    error kalau tetap dipaksa lewat devtools/manipulasi wire:click.

    Alpine state dari root x-data (table.blade.php):
    deletePopup.show / deletePopup.nama / deletePopup.punyaHistori /
    tutupPopupHapus() / konfirmasiHapus()
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
    @keydown.escape.window="tutupPopupHapus()"
    @click.self="tutupPopupHapus()"
    style="position: fixed; inset: 0;
           background: rgba(0,0,0,.45);
           backdrop-filter: blur(2px);
           z-index: 1050;"
>
    <div style="display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
                padding: 1rem;">

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
                   max-width: 420px;
                   box-shadow: 0 20px 60px rgba(0,0,0,.2);
                   overflow: hidden;"
        >
            {{-- Body --}}
            <div style="padding: 1.5rem 1.5rem 0;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px;
                           display: flex; align-items: center; justify-content: center;
                           font-size: 1.35rem; margin-bottom: 1rem;"
                    :style="deletePopup.punyaHistori
                        ? 'background:#fef3c7; color:#92400e;'
                        : 'background:#fee2e2; color:#991b1b;'"
                >
                    <i class="bi" :class="deletePopup.punyaHistori ? 'bi-archive-fill' : 'bi-trash3-fill'"></i>
                </div>

                <h5 style="font-weight: 700; color: #111827; margin-bottom: .375rem;"
                    x-text="deletePopup.punyaHistori ? 'Nonaktifkan Kamar?' : 'Hapus Kamar?'">
                </h5>

                {{-- Pesan dinamis: nonaktif (punya histori) vs hapus permanen (bersih) --}}
                <p style="font-size: .875rem; color: #6b7280; margin-bottom: 0;" x-show="deletePopup.punyaHistori" x-cloak>
                    <strong style="color: #111827;" x-text="deletePopup.nama"></strong>
                    pernah memiliki riwayat penghuni. Untuk menjaga riwayat tagihan &amp;
                    pembayaran tetap utuh, kamar ini <strong>tidak akan dihapus permanen</strong> —
                    statusnya akan diubah menjadi <strong>Nonaktif</strong> dan tidak lagi bisa
                    dipilih untuk penghuni baru.
                </p>
                <p style="font-size: .875rem; color: #6b7280; margin-bottom: 0;" x-show="!deletePopup.punyaHistori" x-cloak>
                    Anda akan menghapus
                    <strong style="color: #111827;" x-text="deletePopup.nama"></strong>
                    secara permanen. Tindakan ini tidak dapat dibatalkan.
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
                    class="btn btn-sm px-3 fw-semibold"
                    :class="deletePopup.punyaHistori ? 'btn-warning text-dark' : 'btn-danger'"
                >
                    <i class="bi" :class="deletePopup.punyaHistori ? 'bi-archive me-1' : 'bi-trash3 me-1'"></i>
                    <span x-text="deletePopup.punyaHistori ? 'Ya, Nonaktifkan' : 'Ya, Hapus'"></span>
                </button>
            </div>

        </div>{{-- /popup box --}}
    </div>{{-- /flex centering --}}
</div>{{-- /backdrop --}}