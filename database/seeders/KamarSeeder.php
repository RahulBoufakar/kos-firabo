<?php

namespace Database\Seeders;

use App\Models\Kamar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

/**
 * KamarSeeder
 *
 * Membuat 8 kamar dengan variasi tipe dan harga.
 * Distribusi status:
 *   - 5 kamar 'tersedia'  → bisa dipilih saat registrasi testing
 *   - 2 kamar 'terisi'    → akan diisi oleh PenghuniSeeder
 *   - 1 kamar 'nonaktif'  → untuk test bahwa kamar nonaktif tidak muncul di selector
 */
class KamarSeeder extends Seeder
{
    public function run(): void
    {
        
        $faker = Faker::create('id_ID');
        
        $this->command->info('  -> Men-generate 30 data kamar dummy...');
        // Buat 30 kamar dummy
        for ($i = 1; $i <= 30; $i++) {
            $tipe = $faker->randomElement(['Standar', 'Eksklusif', 'VIP']);
            $harga = match($tipe) {
                'Standar' => 600000,
                'Eksklusif' => 1000000,
                'VIP' => 1500000,
            };

            Kamar::create([
                'nomor_kamar'  => $faker->unique()->bothify('?-##'), // Contoh: A-01, B-12
                'tipe_kamar'   => $tipe,
                'harga_sewa'   => $harga,
                'fasilitas'    => $faker->sentence(4),
                'status_kamar' => 'tersedia',
            ]);
        }
        $this->command->info('  ✓ Sukses membuat data kamar.');
    }
}
