<?php

namespace Database\Seeders;

use App\Models\Kamar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

/**
 * KamarSeeder
 *
 * Catatan: Docblock menyebutkan 8 kamar dengan berbagai status, 
 * namun kode di bawah diatur untuk men-generate 30 kamar dengan status 'tersedia'.
 */
class KamarSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
                
        $this->command->info('  -> Men-generate 30 data kamar dummy terstruktur...');

        // Definisikan aturan main untuk tiap tipe kamar
        $kategoriKamar = [
            [
                'tipe' => 'Standar', 
                'prefix' => 'A', 
                'harga' => 600000, 
                'min_fasilitas' => 1, 
                'max_fasilitas' => 2
            ],
            [
                'tipe' => 'Eksklusif', 
                'prefix' => 'B', 
                'harga' => 1000000, 
                'min_fasilitas' => 2, 
                'max_fasilitas' => 4
            ],
            [
                'tipe' => 'VIP', 
                'prefix' => 'C', 
                'harga' => 1500000, 
                'min_fasilitas' => 3, 
                'max_fasilitas' => 5
            ],
        ];

        $availableFasilitas = ['AC', 'TV', 'Kamar Mandi Dalam', 'WiFi', 'Lemari', 'Meja Belajar'];
        $maxAvailable = count($availableFasilitas);

        // Loop utama untuk setiap kategori (3 kali)
        foreach ($kategoriKamar as $kategori) {
            
            // Loop sekunder untuk membuat 10 kamar per kategori
            for ($i = 1; $i <= 10; $i++) {
                
                // Format penomoran: sprintf('%02d', 1) akan menghasilkan "01"
                // Gabungkan prefix (A/B/C) dengan nomor (01-10) -> A01, A02, dst.
                $nomorKamar = sprintf('%s%02d', $kategori['prefix'], $i);
                
                // Tentukan jumlah fasilitas yang akan di-generate untuk kamar ini
                $minCount = max(1, min($kategori['min_fasilitas'], $maxAvailable));
                $maxCount = max($minCount, min($kategori['max_fasilitas'], $maxAvailable));
                $count = $faker->numberBetween($minCount, $maxCount);
                
                // Ambil fasilitas secara acak sejumlah $count
                $fasilitasPilihan = $faker->randomElements($availableFasilitas, $count);

                Kamar::create([
                    'nomor_kamar'  => $nomorKamar,
                    'tipe_kamar'   => $kategori['tipe'],
                    'harga_sewa'   => $kategori['harga'],
                    'fasilitas'    => implode(', ', $fasilitasPilihan),
                    'status_kamar' => 'tersedia',
                ]);
            }
        }

        $this->command->info('  ✓ Sukses membuat 30 data kamar (10 Standar, 10 Eksklusif, 10 VIP).');
    } 
}