<?php

namespace App\Events;

use App\Models\Tagihan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TagihanJatuhTempo
 *
 * Di-fire oleh Artisan command `tagihan:reminder` setiap hari pukul 08:00.
 * Command mencari tagihan yang jatuh tempo BESOK (H-1) dan belum dibayar,
 * lalu fire event ini untuk setiap tagihan yang ditemukan.
 *
 * Kenapa H-1 dan bukan H-0?
 * Memberi penghuni waktu untuk mempersiapkan pembayaran sebelum tenggat.
 * Notifikasi di hari H biasanya sudah terlambat secara praktis.
 */
class TagihanJatuhTempo
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tagihan $tagihan,
    ) {}
}