<?php

namespace App\Events;

use App\Models\Pembayaran;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PembayaranBerhasil
 *
 * Di-fire dari dua tempat:
 * 1. Penghuni\PembayaranController@callback — saat webhook Midtrans masuk
 *    dengan transaction_status = 'settlement' atau 'capture+accept'
 * 2. Admin\PembayaranController (Livewire table) — saat admin catat manual
 *    dengan status_pembayaran = 'sukses'
 *
 * Listener akan mengirim email konfirmasi pembayaran ke penghuni,
 * sekaligus memberikan transparansi untuk pembayaran yang dicatat admin.
 */
class PembayaranBerhasil
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Pembayaran $pembayaran,
    ) {}
}