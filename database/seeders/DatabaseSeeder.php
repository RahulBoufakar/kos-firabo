<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\KamarSeeder;
use Database\Seeders\PenghuniSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 *
 * Orkestrasi urutan seeder. Urutan PENTING karena ada foreign key:
 *
 *   1. AdminSeeder     → users (admin)
 *   2. KamarSeeder     → tb_kamar
 *   3. PenghuniSeeder  → users (penghuni) + tb_hunian
 *                        + tb_jadwal_tagihan + tb_tagihan
 *
 * Cara pakai:
 *   php artisan migrate:fresh --seed     ← reset DB + seed dari awal
 *   php artisan db:seed                  ← seed tanpa reset (hati-hati duplikat)
 *   php artisan db:seed --class=AdminSeeder  ← jalankan satu seeder saja
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('  Kos Firabo — Database Seeder (v6)');
        $this->command->info('  ==================================');

        $this->call([
            AdminSeeder::class,
            KamarSeeder::class,
            PenghuniSeeder::class,
            JadwalTagihanSeeder::class,
            TagihanDanPembayaranSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('  Selesai! 24 Kamar Standar, 18 penghuni aktif, 2 penghuni kabur (piutang).');
        $this->command->info('');

        $this->command->table(
            ['Role', 'Email', 'Password', 'Keterangan'],
            [
                ['Admin',    'admin@firabo.test',     'admin123',    'Akses penuh panel admin'],
                ['Penghuni', 'penghuni1@firabo.test', 'password123', 'Tagihan bulan ini: belum bayar'],
                ['Penghuni', 'penghuni2@firabo.test', 'password123', 'Tagihan bulan ini: terlambat'],
                ['Penghuni', 'penghuni3@firabo.test', 'password123', 'Tagihan bulan ini: lunas'],
                ['(kabur)',  'kabur1@firabo.test',    'password123', 'Piutang Rp 1.800.000 — cek Laporan Piutang'],
                ['(kabur)',  'kabur2@firabo.test',    'password123', 'Piutang Rp 1.200.000 — cek Laporan Piutang'],
            ]
        );
        $this->command->info('');
    }
}
