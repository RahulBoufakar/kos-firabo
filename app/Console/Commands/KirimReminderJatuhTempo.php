<?php

namespace App\Console\Commands;

use App\Events\TagihanJatuhTempo;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * KirimReminderJatuhTempo
 *
 * Command ini dijalankan setiap hari pukul 08:00 oleh Scheduler.
 * Mencari tagihan yang:
 *  - Belum dibayar (belum_bayar atau terlambat — terlambat tidak dapat reminder)
 *  - Jatuh tempo BESOK (H-1)
 *
 * Lalu fire event TagihanJatuhTempo per tagihan → listener kirim email.
 *
 * Kenapa H-1 saja, bukan H-3 atau H-0?
 *  - H-3 terlalu jauh, penghuni mungkin lupa lagi
 *  - H-1 memberi waktu cukup untuk mempersiapkan pembayaran
 *  - H-0 biasanya sudah terlambat secara praktis
 *
 * Cara jalankan manual:
 *   php artisan tagihan:reminder
 *   php artisan tagihan:reminder --tanggal=2025-06-08   ← simulasi
 *   php artisan tagihan:reminder --dry-run              ← preview tanpa kirim
 */
class KirimReminderJatuhTempo extends Command
{
    protected $signature = 'tagihan:reminder
                            {--tanggal= : Tanggal referensi (Y-m-d), default hari ini}
                            {--dry-run  : Preview tanpa fire event / kirim email}';

    protected $description = 'Kirim reminder email H-1 untuk tagihan yang akan jatuh tempo besok';

    public function handle(): int
    {
        $tanggalHariIni = $this->option('tanggal')
            ? Carbon::parse($this->option('tanggal'))
            : Carbon::today();

        // Target: tagihan yang jatuh tempo BESOK
        $targetJatuhTempo = $tanggalHariIni->copy()->addDay()->toDateString();

        $isDryRun       = $this->option('dry-run');
        $tanggalDisplay = $tanggalHariIni->translatedFormat('d F Y');

        $this->info("====================================================");
        $this->info("  Reminder Jatuh Tempo — {$tanggalDisplay}");
        $this->line("  Target JT  : {$targetJatuhTempo}");
        $isDryRun && $this->warn("  [DRY RUN] Email tidak akan dikirim.");
        $this->info("====================================================");

        // Cari tagihan yang belum lunas dan jatuh tempo besok
        $tagihanList = Tagihan::where('status_tagihan', 'belum_bayar')
            ->where('tanggal_jatuh_tempo', $targetJatuhTempo)
            ->with(['hunian.user', 'hunian.kamar'])
            ->get();

        $this->line("Tagihan ditemukan: <comment>{$tagihanList->count()}</comment>");
        $this->newLine();

        if ($tagihanList->isEmpty()) {
            $this->info("Tidak ada tagihan yang jatuh tempo besok.");
            return self::SUCCESS;
        }

        $totalDikirim = 0;
        $totalSkip    = 0;
        $totalError   = 0;

        foreach ($tagihanList as $tagihan) {
            $penghuni = $tagihan->hunian?->user;
            $kamar    = $tagihan->hunian?->kamar;

            if (! $penghuni || blank($penghuni->email)) {
                $this->line("  <fg=yellow>SKIP</> Tagihan #{$tagihan->tagihan_id} — email penghuni kosong");
                $totalSkip++;
                continue;
            }

            $this->line(
                "  <fg=green>KIRIM</> {$penghuni->name} ({$penghuni->email})"
                . " — Kamar {$kamar->nomor_kamar}"
                . " — JT: {$targetJatuhTempo}"
            );

            if (! $isDryRun) {
                try {
                    event(new TagihanJatuhTempo($tagihan));
                    $totalDikirim++;
                } catch (\Exception $e) {
                    $this->error("  ERROR #{$tagihan->tagihan_id}: " . $e->getMessage());
                    $totalError++;
                }
            } else {
                $totalDikirim++;
            }
        }

        $this->newLine();
        $this->info("====================================================");
        $this->table(
            ['Status', 'Jumlah'],
            [
                [$isDryRun ? 'Akan dikirim' : 'Terkirim', $totalDikirim],
                ['Dilewati',                               $totalSkip],
                ['Error',                                  $totalError],
            ]
        );

        return $totalError > 0 ? self::FAILURE : self::SUCCESS;
    }
}