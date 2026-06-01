<?php

namespace App\Events;

use App\Models\Tagihan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TagihanDibuat
 *
 * Di-fire oleh GenerateTagihanBulanan setelah satu tagihan baru
 * berhasil dibuat. Listener akan mengirim email notifikasi ke penghuni.
 *
 * Satu event per tagihan — bukan satu event untuk batch seluruh tagihan.
 * Ini agar email dikirim ke masing-masing penghuni yang relevan,
 * dan kegagalan satu pengiriman tidak memblokir yang lain.
 */
class TagihanDibuat
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tagihan $tagihan,
    ) {}
}