<?php

namespace Database\Seeders;

use App\Models\Kamar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

/**
 * KamarSeeder (v6)
 *
 * 24 kamar, satu tipe saja: "Kamar Standar" @ Rp 600.000/bulan.
 * WiFi tidak punya tabel sendiri — dicatat lewat kolom fasilitas.
 */
class KamarSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  -> Men-generate 24 Kamar Standar...');

        for ($i = 1; $i <= 24; $i++) {
            Kamar::create([
                'nomor_kamar'  => sprintf('A%02d', $i),
                'tipe_kamar'   => 'Kamar Standar',
                'harga_sewa'   => 600000,
                'fasilitas'    => 'kamar mandi umum, WiFi (Voucher)',
                'status_kamar' => 'tersedia',
            ]);
        }

        $this->command->info('  ✓ Sukses membuat 24 Kamar Standar.');
    }
}