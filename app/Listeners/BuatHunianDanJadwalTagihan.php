<?php

namespace App\Listeners;

use App\Events\PenghuniTerdaftar;
use App\Models\Hunian;
use App\Models\JadwalTagihan;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BuatHunianDanJadwalTagihan
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * Alur kerja:
     * 1. Buat record tb_hunian dengan tanggal_masuk = hari ini
     * 2. Update status_kamar menjadi 'terisi'
     * 3. Buat tb_jadwal_tagihan:
     *    - tanggal_generate = tanggal hari ini dalam bulan (1–28, di-cap agar aman di semua bulan)
     *    - tanggal_jatuh_tempo = 7 (hari setelah generate)
     *    - status_jadwal = aktif
     */
    public function handle(PenghuniTerdaftar $event): void
    {
        $penghuni = $event->penghuni;
        $kamar    = $event->kamar;
        $today    = Carbon::today();
 
        // --- 1. Buat Hunian ---
        $hunian = Hunian::create([
            'user_id'        => $penghuni->id,
            'kamar_id'       => $kamar->kamar_id,
            'tanggal_masuk'  => $today,
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);
 
        // --- 2. Tandai kamar sebagai terisi ---
        $kamar->update(['status_kamar' => 'terisi']);
 
        // --- 3. Buat Jadwal Tagihan ---
        // Batasi tanggal_generate maksimal 28 agar aman di bulan Februari
        $tanggalGenerate = min($today->day, 28);
 
        JadwalTagihan::create([
            'hunian_id'           => $hunian->hunian_id,
            'tanggal_generate'    => $tanggalGenerate,
            'tanggal_jatuh_tempo' => 7,   // 7 hari setelah tanggal generate
            'status_jadwal'       => 'aktif',
        ]);
    }
}
