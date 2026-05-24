<?php

namespace App\Console\Commands;

use App\Models\JadwalTagihan;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * GenerateTagihanBulanan
 *
 * Command ini dijalankan otomatis oleh Laravel Scheduler setiap hari.
 * Tugasnya: membaca semua jadwal tagihan aktif, lalu membuat tagihan
 * baru untuk hunian yang tanggal_generate-nya jatuh hari ini.
 *
 * Cara jalankan manual untuk testing:
 *   php artisan tagihan:generate
 *   php artisan tagihan:generate --tanggal=2025-05-01   ← simulasi tanggal tertentu
 *   php artisan tagihan:generate --dry-run              ← preview tanpa simpan ke DB
 */
class GenerateTagihanBulanan extends Command
{
    /**
     * Nama dan signature command.
     * --tanggal : override tanggal hari ini (format Y-m-d), berguna untuk testing
     * --dry-run : jalankan tanpa menyimpan ke database, hanya tampilkan preview
     */
    protected $signature = 'tagihan:generate
                            {--tanggal= : Tanggal target (Y-m-d), default hari ini}
                            {--dry-run  : Preview saja tanpa simpan ke database}';
 
    protected $description = 'Generate tagihan bulanan untuk semua hunian aktif sesuai jadwal';
 
    public function handle(): int
    {
        // ── 1. Tentukan tanggal referensi ──────────────────────────────────
        // Bisa di-override via --tanggal untuk keperluan testing / backfill
        $tanggalHariIni = $this->option('tanggal')
            ? Carbon::parse($this->option('tanggal'))
            : Carbon::today();
 
        $isDryRun       = $this->option('dry-run');
        $tanggalDisplay = $tanggalHariIni->translatedFormat('d F Y');
 
        $this->info("====================================================");
        $this->info("  Generate Tagihan Bulanan — {$tanggalDisplay}");
        $isDryRun && $this->warn("  [DRY RUN] Tidak ada data yang akan disimpan.");
        $this->info("====================================================");
 
        // ── 2. Ambil semua jadwal tagihan yang aktif ───────────────────────
        // Eager load hunian (dengan kamar untuk dapat harga sewa) agar
        // tidak terjadi N+1 query saat looping
        $jadwalList = JadwalTagihan::where('status_jadwal', 'aktif')
            ->with(['hunian.kamar', 'hunian.user'])
            ->get();
 
        $this->line("Jadwal aktif ditemukan: <comment>{$jadwalList->count()}</comment>");
        $this->newLine();
 
        $totalDibuat  = 0;
        $totalDilewat = 0;
        $totalError   = 0;
 
        // ── 3. Loop tiap jadwal ────────────────────────────────────────────
        foreach ($jadwalList as $jadwal) {
            $hunian = $jadwal->hunian;
 
            // Skip jika hunian tidak aktif (penghuni sudah keluar)
            if (! $hunian || $hunian->status_hunian !== 'aktif') {
                $this->line("  <fg=gray>SKIP</> Jadwal #{$jadwal->jadwal_id} — hunian tidak aktif");
                $totalDilewat++;
                continue;
            }
 
            // ── 3a. Cek apakah hari ini adalah tanggal_generate jadwal ini ──
            // tanggal_generate adalah angka 1–28, dibandingkan dengan tanggal
            // dalam bulan dari $tanggalHariIni
            if ($tanggalHariIni->day !== (int) $jadwal->tanggal_generate) {
                // Bukan hari generate — lewati diam-diam (tidak perlu log)
                $totalDilewat++;
                continue;
            }
 
            $kamar    = $hunian->kamar;
            $penghuni = $hunian->user;
 
            // ── 3b. Cek duplikasi — tagihan bulan ini sudah ada? ───────────
            // Cegah generate dua kali di bulan yang sama untuk hunian yang sama
            // Cek berdasarkan hunian_id + bulan + tahun dari tanggal_tagihan
            $sudahAda = Tagihan::where('hunian_id', $hunian->hunian_id)
                ->whereYear('tanggal_tagihan', $tanggalHariIni->year)
                ->whereMonth('tanggal_tagihan', $tanggalHariIni->month)
                ->exists();
 
            if ($sudahAda) {
                $this->line(
                    "  <fg=yellow>SKIP</> Kamar {$kamar->nomor_kamar} "
                    . "({$penghuni->name}) — tagihan bulan ini sudah ada"
                );
                $totalDilewat++;
                continue;
            }
 
            // ── 3c. Hitung tanggal jatuh tempo ────────────────────────────
            // tanggal_jatuh_tempo di jadwal adalah JARAK HARI dari tanggal generate
            // contoh: generate tgl 5, jatuh_tempo = 7 → jatuh tempo tgl 12
            $tanggalJatuhTempo = $tanggalHariIni->copy()
                ->addDays((int) $jadwal->tanggal_jatuh_tempo);
 
            // ── 3d. Nominal dari harga sewa kamar saat ini ────────────────
            $nominal = $kamar->harga_sewa;
 
            // ── 3e. Simpan tagihan (kecuali dry-run) ──────────────────────
            $this->line(
                "  <fg=green>BUAT</> Kamar {$kamar->nomor_kamar} "
                . "({$penghuni->name}) "
                . "— Rp " . number_format($nominal, 0, ',', '.')
                . " | JT: {$tanggalJatuhTempo->format('d/m/Y')}"
            );
 
            if (! $isDryRun) {
                try {
                    Tagihan::create([
                        'hunian_id'           => $hunian->hunian_id,
                        'jadwal_id'           => $jadwal->jadwal_id,
                        'nominal'             => $nominal,
                        'tanggal_tagihan'     => $tanggalHariIni->toDateString(),
                        'tanggal_jatuh_tempo' => $tanggalJatuhTempo->toDateString(),
                        'status_tagihan'      => 'belum_bayar',
                    ]);
 
                    $totalDibuat++;
                } catch (\Exception $e) {
                    $this->error(
                        "  ERROR Kamar {$kamar->nomor_kamar}: " . $e->getMessage()
                    );
                    $totalError++;
                }
            } else {
                // Dry run — hitung saja tanpa simpan
                $totalDibuat++;
            }
        }
 
        // ── 4. Ringkasan hasil ────────────────────────────────────────────
        $this->newLine();
        $this->info("====================================================");
        $this->info("  Selesai" . ($isDryRun ? " [DRY RUN]" : ""));
        $this->table(
            ['Status', 'Jumlah'],
            [
                [$isDryRun ? 'Akan dibuat' : 'Berhasil dibuat', $totalDibuat],
                ['Dilewati',                                     $totalDilewat],
                ['Error',                                        $totalError],
            ]
        );
 
        // Return code: 0 = sukses, 1 = ada error
        return $totalError > 0 ? self::FAILURE : self::SUCCESS;
    }
}