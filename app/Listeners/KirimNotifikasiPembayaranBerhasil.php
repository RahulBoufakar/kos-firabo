<?php

namespace App\Listeners;

use App\Events\PembayaranBerhasil;
use App\Mail\NotifikasiPembayaranBerhasil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * KirimNotifikasiPembayaranBerhasil
 *
 * Mengirim email konfirmasi ke penghuni setelah pembayaran berhasil,
 * baik via Midtrans maupun yang dicatat manual oleh admin.
 */
class KirimNotifikasiPembayaranBerhasil
{
    public function handle(PembayaranBerhasil $event): void
    {
        $pembayaran = $event->pembayaran;

        // Load semua relasi yang dibutuhkan Mailable
        $pembayaran->loadMissing([
            'tagihan.hunian.user',
            'tagihan.hunian.kamar',
            'pencatat', // admin yang mencatat (nullable untuk online)
        ]);

        $penghuni = $pembayaran->tagihan?->hunian?->user;

        if (! $penghuni || blank($penghuni->email)) {
            Log::warning('[Email] PembayaranBerhasil — skip, email penghuni kosong', [
                'pembayaran_id' => $pembayaran->pembayaran_id,
            ]);
            return;
        }

        try {
            Mail::send(new NotifikasiPembayaranBerhasil($pembayaran));

            Log::info('[Email] Konfirmasi pembayaran terkirim', [
                'pembayaran_id' => $pembayaran->pembayaran_id,
                'email'         => $penghuni->email,
                'nominal'       => $pembayaran->nominal_bayar,
                'metode'        => $pembayaran->metode_pembayaran,
            ]);
        } catch (\Exception $e) {
            Log::error('[Email] Gagal kirim konfirmasi pembayaran', [
                'pembayaran_id' => $pembayaran->pembayaran_id,
                'email'         => $penghuni->email,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}