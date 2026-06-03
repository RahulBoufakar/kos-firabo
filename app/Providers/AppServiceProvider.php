<?php

namespace App\Providers;

use App\Events\PembayaranBerhasil;
use App\Events\PenghuniTerdaftar;
use App\Events\TagihanDibuat;
use App\Events\TagihanJatuhTempo;
use App\Listeners\BuatHunianDanJadwalTagihan;
use App\Listeners\KirimNotifikasiJatuhTempo;
use App\Listeners\KirimNotifikasiPembayaranBerhasil;
use App\Listeners\KirimNotifikasiTagihanBaru;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //Mengaktifkan URL HTTPS secara global untuk test di ngrok.
        // URL::forceScheme('https');

        // // ── 1. Penghuni terdaftar → buat hunian + jadwal tagihan ──────────────
        // Event::listen(
        //     PenghuniTerdaftar::class,
        //     BuatHunianDanJadwalTagihan::class,
        // );
 
        // // ── 2. Tagihan baru dibuat → kirim email notifikasi ke penghuni ───────
        // Event::listen(
        //     TagihanDibuat::class,
        //     KirimNotifikasiTagihanBaru::class,
        // );
 
        // // ── 3. Tagihan hampir jatuh tempo → kirim email reminder H-1 ──────────
        // Event::listen(
        //     TagihanJatuhTempo::class,
        //     KirimNotifikasiJatuhTempo::class,
        // );
 
        // // ── 4. Pembayaran berhasil → kirim email konfirmasi ke penghuni ───────
        // // Dipicu dari: Midtrans callback (online) + Admin catat manual
        // Event::listen(
        //     PembayaranBerhasil::class,
        //     KirimNotifikasiPembayaranBerhasil::class,
        // );
    }
}
