<?php

namespace App\Listeners;

use App\Events\TagihanDibuat;
use App\Mail\NotifikasiTagihanBaru;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * KirimNotifikasiTagihanBaru
 *
 * Mendengarkan event TagihanDibuat dan mengirim email notifikasi
 * ke alamat email penghuni yang bersangkutan.
 *
 * Error handling: kegagalan pengiriman email di-log tapi TIDAK
 * melempar exception — agar tidak memblokir proses generate tagihan.
 */
class KirimNotifikasiTagihanBaru
{
    public function handle(TagihanDibuat $event): void
    {
        $tagihan = $event->tagihan;

        // Load relasi yang dibutuhkan Mailable jika belum ter-load
        $tagihan->loadMissing(['hunian.user', 'hunian.kamar']);

        $penghuni = $tagihan->hunian?->user;

        if (! $penghuni || blank($penghuni->email)) {
            Log::warning('[Email] TagihanDibuat — skip, email penghuni kosong', [
                'tagihan_id' => $tagihan->tagihan_id,
            ]);
            return;
        }

        try {
            Mail::to($penghuni->email)->queue(new NotifikasiTagihanBaru($tagihan));

            Log::info('[Email] Notifikasi tagihan baru terkirim', [
                'tagihan_id' => $tagihan->tagihan_id,
                'email'      => $penghuni->email,
            ]);
        } catch (\Exception $e) {
            // Log error tapi jangan rethrow — generate tagihan harus tetap jalan
            Log::error('[Email] Gagal kirim notifikasi tagihan baru', [
                'tagihan_id' => $tagihan->tagihan_id,
                'email'      => $penghuni->email,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}