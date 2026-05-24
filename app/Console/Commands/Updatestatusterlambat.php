<?php

namespace App\Console\Commands;

use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * UpdateStatusTerlambat
 *
 * Command ini juga dijalankan setiap hari oleh Scheduler, SETELAH
 * GenerateTagihanBulanan. Tugasnya: mengubah status tagihan yang
 * sudah melewati tanggal jatuh tempo menjadi 'terlambat'.
 *
 * Cara jalankan manual:
 *   php artisan tagihan:update-terlambat
 *   php artisan tagihan:update-terlambat --tanggal=2025-05-20
 *   php artisan tagihan:update-terlambat --dry-run
 */
class UpdateStatusTerlambat extends Command
{
    protected $signature = 'tagihan:update-terlambat
                            {--tanggal= : Tanggal referensi (Y-m-d), default hari ini}
                            {--dry-run  : Preview tanpa update ke database}';
 
    protected $description = 'Tandai tagihan belum_bayar yang sudah melewati jatuh tempo menjadi terlambat';
 
    public function handle(): int
    {
        $tanggalHariIni = $this->option('tanggal')
            ? Carbon::parse($this->option('tanggal'))
            : Carbon::today();
 
        $isDryRun       = $this->option('dry-run');
        $tanggalDisplay = $tanggalHariIni->translatedFormat('d F Y');
 
        $this->info("====================================================");
        $this->info("  Update Status Terlambat — {$tanggalDisplay}");
        $isDryRun && $this->warn("  [DRY RUN] Tidak ada data yang akan diubah.");
        $this->info("====================================================");
 
        // Cari tagihan yang:
        // 1. Statusnya masih 'belum_bayar' (bukan 'lunas' atau sudah 'terlambat')
        // 2. Tanggal jatuh tempo-nya sudah lewat (< hari ini)
        $tagihan = Tagihan::where('status_tagihan', 'belum_bayar')
            ->where('tanggal_jatuh_tempo', '<', $tanggalHariIni->toDateString())
            ->with(['hunian.kamar', 'hunian.user'])
            ->get();
 
        $this->line("Tagihan yang perlu diupdate: <comment>{$tagihan->count()}</comment>");
        $this->newLine();
 
        if ($tagihan->isEmpty()) {
            $this->info("Tidak ada tagihan yang perlu diperbarui.");
            return self::SUCCESS;
        }
 
        $totalUpdated = 0;
        $totalError   = 0;
 
        foreach ($tagihan as $t) {
            $kamar    = $t->hunian->kamar;
            $penghuni = $t->hunian->user;
            $jt       = Carbon::parse($t->tanggal_jatuh_tempo)->format('d/m/Y');
            $terlambat = Carbon::parse($t->tanggal_jatuh_tempo)->diffInDays($tanggalHariIni);
 
            $this->line(
                "  <fg=red>TERLAMBAT</> Kamar {$kamar->nomor_kamar} "
                . "({$penghuni->name}) "
                . "— JT: {$jt} ({$terlambat} hari)"
            );
 
            if (! $isDryRun) {
                try {
                    $t->update(['status_tagihan' => 'terlambat']);
                    $totalUpdated++;
                } catch (\Exception $e) {
                    $this->error("  ERROR #{$t->tagihan_id}: " . $e->getMessage());
                    $totalError++;
                }
            } else {
                $totalUpdated++;
            }
        }
 
        $this->newLine();
        $this->info("====================================================");
        $this->table(
            ['Status', 'Jumlah'],
            [
                [$isDryRun ? 'Akan diupdate' : 'Berhasil diupdate', $totalUpdated],
                ['Error',                                            $totalError],
            ]
        );
 
        return $totalError > 0 ? self::FAILURE : self::SUCCESS;
    }
}
