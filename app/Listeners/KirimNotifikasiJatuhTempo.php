<?php

namespace App\Listeners;

use App\Events\TagihanJatuhTempo;
use App\Mail\NotifikasiJatuhTempo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * KirimNotifikasiJatuhTempo
 *
 * Mengirim email reminder H-1 ke penghuni yang tagihan-nya
 * akan jatuh tempo besok dan belum dibayar.
 */
class KirimNotifikasiJatuhTempo
{
    public function handle(TagihanJatuhTempo $event): void
    {
        $tagihan = $event->tagihan;

        $tagihan->loadMissing(['hunian.user', 'hunian.kamar']);

        $penghuni = $tagihan->hunian?->user;

        if (! $penghuni || blank($penghuni->email)) {
            Log::warning('[Email] TagihanJatuhTempo — skip, email penghuni kosong', [
                'tagihan_id' => $tagihan->tagihan_id,
            ]);
            return;
        }

        try {
            Mail::to($penghuni->email)->queue(new NotifikasiJatuhTempo($tagihan));
            //Mail::send(new NotifikasiJatuhTempo($tagihan));

            Log::info('[Email] Reminder jatuh tempo terkirim', [
                'tagihan_id' => $tagihan->tagihan_id,
                'email'      => $penghuni->email,
                'jatuh_tempo'=> $tagihan->tanggal_jatuh_tempo,
            ]);
        } catch (\Exception $e) {
            Log::error('[Email] Gagal kirim reminder jatuh tempo', [
                'tagihan_id' => $tagihan->tagihan_id,
                'email'      => $penghuni->email,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}