<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * INSTRUKSI:
 * Tambahkan atau replace seluruh isi routes/console.php dengan file ini.
 *
 * Laravel 11+ tidak lagi menggunakan Kernel.php untuk scheduler.
 * Semua jadwal didefinisikan langsung di routes/console.php ini.
 *
 * Agar scheduler berjalan di server production, tambahkan cron berikut:
 *   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
 *
 * Untuk testing di local:
 *   php artisan schedule:work       ← jalankan scheduler tiap menit (foreground)
 *   php artisan schedule:list       ← lihat daftar semua jadwal terdaftar
 *   php artisan schedule:run        ← paksa jalankan semua jadwal yang jatuh tempo sekarang
 */
 
// ── 1. Generate Tagihan Bulanan ────────────────────────────────────────────
//
// Dijalankan setiap hari pukul 00:05.
// Pemilihan 00:05 (bukan 00:00) memberi jeda agar server stabil
// setelah pergantian hari sebelum mulai proses batch.
//
// Logic di dalam command:
//   - Baca semua tb_jadwal_tagihan dengan status 'aktif'
//   - Jika tanggal_generate == hari ini → buat tagihan baru
//   - Cek duplikasi sebelum insert (idempotent)
Schedule::command('tagihan:generate')
    ->dailyAt('00:05')
    ->withoutOverlapping()   // Cegah dua instance berjalan bersamaan
    ->runInBackground()      // Tidak memblokir scheduler untuk job lain
    ->name('Generate Tagihan Bulanan') // Nama untuk memudahkan identifikasi di schedule:list
    ->appendOutputTo(storage_path('logs/tagihan-generate.log')); //hasil log untuk debugging
 
// ── 2. Update Status Terlambat ─────────────────────────────────────────────
//
// Dijalankan setiap hari pukul 00:10, SETELAH generate tagihan.
// Ini memastikan tagihan yang baru di-generate pada hari yang sama
// dengan jatuh tempo (edge case) langsung terdeteksi.
//
// Logic di dalam command:
//   - Cari tagihan dengan status 'belum_bayar' + jatuh tempo < hari ini
//   - Update status_tagihan → 'terlambat'
Schedule::command('tagihan:update-terlambat')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tagihan-terlambat.log'));