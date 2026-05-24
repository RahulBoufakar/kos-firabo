<?php

namespace App\Console\Commands;

use App\Models\Hunian;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ForceGenerateTagihan extends Command
{
    /**
     * Signature:
     * argumen utama   : hunian_id (wajib)
     * opsi --nominal  : (opsional) untuk mengetes angka tagihan custom
     * opsi --jt       : (opsional) jarak hari jatuh tempo
     */
    protected $signature = 'tagihan:test 
                            {hunian_id : ID dari hunian yang ingin dibuatkan tagihan}
                            {--nominal= : Override nominal tagihan (opsional)}
                            {--jt=3 : Jarak hari jatuh tempo dari hari ini (default: 3)}';

    protected $description = 'Force create tagihan baru untuk keperluan testing (bypass semua validasi jadwal)';

    public function handle(): int
    {
        $hunianId = $this->argument('hunian_id');
        
        // 1. Cari data hunian
        $hunian = Hunian::with(['kamar', 'user'])->find($hunianId);

        if (! $hunian) {
            $this->error("❌ Hunian dengan ID {$hunianId} tidak ditemukan!");
            return self::FAILURE;
        }

        // 2. CARI JADWAL AKTIF (Tambahan perbaikan)
        // Kita butuh jadwal_id yang valid karena database menolak nilai null
        $jadwal = \App\Models\JadwalTagihan::where('hunian_id', $hunianId)
                    ->where('status_jadwal', 'aktif')
                    ->first();

        if (! $jadwal) {
            $this->error("❌ Gagal: Hunian ini tidak memiliki jadwal tagihan yang aktif!");
            $this->line("   Pastikan hunian_id {$hunianId} sudah punya jadwal sebelum di-testing.");
            return self::FAILURE;
        }

        // 3. Tentukan nominal (prioritas: opsi --nominal -> harga sewa kamar)
        $nominal = $this->option('nominal') 
            ? (int) $this->option('nominal') 
            : $hunian->kamar->harga_sewa;

        // 4. Tentukan tanggal
        $tanggalHariIni = Carbon::today();
        $tanggalJatuhTempo = Carbon::today()->addDays((int) $this->option('jt'));

        // 5. Force Create Tagihan
        try {
            $tagihan = Tagihan::create([
                'hunian_id'           => $hunian->hunian_id,
                'jadwal_id'           => $jadwal->jadwal_id, // <-- SEKARANG MENGGUNAKAN ID VALID
                'nominal'             => $nominal,
                'tanggal_tagihan'     => $tanggalHariIni->toDateString(),
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo->toDateString(),
                'status_tagihan'      => 'belum_bayar',
            ]);

            $this->info("✅ Tagihan TESTING berhasil dibuat!");
            $this->line("   Penghuni    : {$hunian->user->name}");
            $this->line("   Kamar       : {$hunian->kamar->nomor_kamar}");
            $this->line("   Jadwal ID   : {$jadwal->jadwal_id}");
            $this->line("   Nominal     : Rp " . number_format($nominal, 0, ',', '.'));
            $this->line("   Jatuh Tempo : {$tanggalJatuhTempo->format('d/m/Y')}");
            $this->line("   Tagihan ID  : {$tagihan->tagihan_id}"); // Sesuaikan jika primary key-nya id

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Gagal membuat tagihan: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}