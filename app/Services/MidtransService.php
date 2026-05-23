<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Carbon;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

/**
 * MidtransService
 *
 * Wrapper tipis di atas Midtrans PHP SDK.
 * Semua interaksi dengan Midtrans melewati class ini agar mudah di-mock saat testing.
 *
 * Instalasi SDK:
 *   composer require midtrans/midtrans-php
 *
 * .env yang dibutuhkan:
 *   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
 *   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
 *   MIDTRANS_IS_PRODUCTION=false
 */
class MidtransService
{
    public function __construct()
    {
        Config::$serverKey        = config('services.midtrans.server_key');
        Config::$clientKey        = config('services.midtrans.client_key');
        Config::$isProduction     = config('services.midtrans.is_production', false);
        Config::$isSanitized      = true;
        Config::$is3ds            = true;
    }

    /**
     * Ambil snap token yang masih aktif atau buat baru.
     *
     * Logika:
     * 1. Cek apakah tagihan sudah punya snap_token di tb_pembayaran
     *    dengan status 'pending' dan dibuat dalam 24 jam terakhir.
     * 2. Jika ada → kembalikan token lama (hemat request ke Midtrans).
     * 3. Jika tidak ada → buat Snap transaction baru, simpan token.
     *
     * @return string snap_token
     */
    public function getOrCreateSnapToken(Tagihan $tagihan): string
    {
        // Cek token aktif (≤24 jam, status pending)
        $existing = Pembayaran::where('tagihan_id', $tagihan->tagihan_id)
            ->where('status_pembayaran', 'pending')
            ->whereNotNull('snap_token')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->latest()
            ->first();

        if ($existing) {
            return $existing->snap_token;
        }

        // Buat transaction baru di Midtrans
        $params = $this->buildSnapParams($tagihan);
        $snapToken = Snap::getSnapToken($params);

        // Simpan token ke tb_pembayaran sebagai record pending
        Pembayaran::create([
            'tagihan_id'         => $tagihan->tagihan_id,
            'user_id'            => null, // null = transaksi online (bukan manual admin)
            'metode_pembayaran'  => 'online',
            'nominal_bayar'      => $tagihan->nominal,
            'tanggal_bayar'      => null,
            'status_pembayaran'  => 'pending',
            'snap_token'         => $snapToken,
            'transaction_id'     => null,
        ]);

        return $snapToken;
    }

    /**
     * Verifikasi dan ambil status transaksi dari Midtrans.
     * Digunakan saat memproses webhook callback.
     */
    public function getTransactionStatus(string $orderId): mixed
    {
        return Transaction::status($orderId);
    }

    /**
     * Susun parameter Snap sesuai format Midtrans.
     * Order ID menggunakan format: FIRABO-{tagihan_id}-{timestamp}
     * agar unik dan bisa di-trace balik ke tagihan.
     */
    private function buildSnapParams(Tagihan $tagihan): array
    {
        $hunian   = $tagihan->hunian;
        $penghuni = $hunian->penghuni;

        return [
            'transaction_details' => [
                'order_id'     => 'FIRABO-' . $tagihan->tagihan_id . '-' . time(),
                'gross_amount' => (int) $tagihan->nominal,
            ],
            'customer_details' => [
                'first_name' => $penghuni->nama_lengkap,
                'email'      => $penghuni->email,
                'phone'      => $penghuni->no_wa ?? '',
            ],
            'item_details' => [
                [
                    'id'       => 'SEWA-' . $hunian->kamar->nomor_kamar,
                    'price'    => (int) $tagihan->nominal,
                    'quantity' => 1,
                    'name'     => 'Sewa Kamar ' . $hunian->kamar->nomor_kamar
                                  . ' — ' . Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('F Y'),
                ],
            ],
            'callbacks' => [
                'finish' => route('penghuni.tagihan.index'),
            ],
        ];
    }
}