<?php

namespace Database\Seeders;

use App\Models\Kamar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        $kamar = [
            // ── Tersedia (bisa dipilih saat register) ──
            [
                'nomor_kamar'  => 'A01',
                'tipe_kamar'   => 'Standar',
                'harga_sewa'   => 800_000,
                'fasilitas'    => 'Kasur, lemari, kipas angin, kamar mandi dalam',
                'status_kamar' => 'tersedia',
            ],
            [
                'nomor_kamar'  => 'A02',
                'tipe_kamar'   => 'Standar',
                'harga_sewa'   => 800_000,
                'fasilitas'    => 'Kasur, lemari, kipas angin, kamar mandi dalam',
                'status_kamar' => 'tersedia',
            ],
            [
                'nomor_kamar'  => 'B01',
                'tipe_kamar'   => 'Deluxe',
                'harga_sewa'   => 1_200_000,
                'fasilitas'    => 'Kasur, lemari, AC, TV, kamar mandi dalam, balkon',
                'status_kamar' => 'tersedia',
            ],
            [
                'nomor_kamar'  => 'B02',
                'tipe_kamar'   => 'Deluxe',
                'harga_sewa'   => 1_200_000,
                'fasilitas'    => 'Kasur, lemari, AC, TV, kamar mandi dalam, balkon',
                'status_kamar' => 'tersedia',
            ],
            [
                'nomor_kamar'  => 'C01',
                'tipe_kamar'   => 'VIP',
                'harga_sewa'   => 1_800_000,
                'fasilitas'    => 'Kasur spring bed, lemari besar, AC, TV 32", dapur kecil, kamar mandi dalam',
                'status_kamar' => 'tersedia',
            ],
 
            // ── Terisi (akan dihuni oleh seeder penghuni) ──
            [
                'nomor_kamar'  => 'A03',
                'tipe_kamar'   => 'Standar',
                'harga_sewa'   => 800_000,
                'fasilitas'    => 'Kasur, lemari, kipas angin, kamar mandi dalam',
                'status_kamar' => 'terisi',
            ],
            [
                'nomor_kamar'  => 'B03',
                'tipe_kamar'   => 'Deluxe',
                'harga_sewa'   => 1_200_000,
                'fasilitas'    => 'Kasur, lemari, AC, TV, kamar mandi dalam, balkon',
                'status_kamar' => 'terisi',
            ],
 
            // ── Nonaktif (untuk memastikan tidak muncul di selector) ──
            [
                'nomor_kamar'  => 'C02',
                'tipe_kamar'   => 'VIP',
                'harga_sewa'   => 1_800_000,
                'fasilitas'    => 'Dalam renovasi',
                'status_kamar' => 'nonaktif',
            ],
        ];
 
        foreach ($kamar as $data) {
            Kamar::create($data);
        }
 
        $this->command->info('  ✓ KamarSeeder: 8 kamar dibuat (5 tersedia, 2 terisi, 1 nonaktif)');
    }
}
