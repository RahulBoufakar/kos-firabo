<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\JadwalTagihan;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TagihanDanPembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $this->command->info('  -> Memulai generate histori Tagihan & Pembayaran (4 Bulan)...');

        $jadwals = JadwalTagihan::with(['hunian.user', 'hunian.kamar'])->get();
        $jumlahBulanMundur = 3; // Men-generate 3 bulan ke belakang + bulan ini (Total 4 bulan)
        $hariIni = Carbon::now();

        foreach ($jadwals as $jadwal) {
            $kamar = $jadwal->hunian->kamar;
            $email = $jadwal->hunian->user->email ?? '';
            $tanggalMasuk = Carbon::parse($jadwal->hunian->tanggal_masuk);

            // Looping dari 3 bulan lalu (i=3) sampai bulan ini (i=0)
            for ($i = $jumlahBulanMundur; $i >= 0; $i--) {
                $targetBulan = Carbon::now()->subMonths($i);

                // LOGIKA CERDAS: Jangan buat tagihan jika bulan target lebih lama dari tanggal masuk penghuni
                if ($targetBulan->format('Y-m') < $tanggalMasuk->format('Y-m')) {
                    continue; // Skip ke iterasi bulan berikutnya
                }

                // 1. Tentukan Tanggal Generate & Jatuh Tempo berdasarkan Jadwal
                // Gabungkan Tahun-Bulan dari $targetBulan dengan Hari dari jadwal
                $tanggalGenerate = Carbon::create($targetBulan->year, $targetBulan->month, $jadwal->tanggal_generate);
                
                // Jatuh tempo: Gunakan format bulan target, tapi ambil harinya dari jadwal
                $tanggalJatuhTempo = Carbon::create($targetBulan->year, $targetBulan->month, $jadwal->tanggal_jatuh_tempo);
                
                // Jika hari jatuh tempo lebih kecil dari hari generate (contoh: generate tgl 28, jatuh tempo tgl 3)
                // Artinya jatuh tempo ada di bulan berikutnya
                if ($jadwal->tanggal_jatuh_tempo < $jadwal->tanggal_generate) {
                    $tanggalJatuhTempo->addMonth();
                }

                // 2. Tentukan Status Tagihan (Historis vs Bulan Ini)
                $statusTagihan = 'lunas'; // Default untuk bulan-bulan lama (asumsi semua lunas di masa lalu)

                // Jika ini adalah bulan iterasi TERAKHIR (Bulan Ini / i = 0)
                if ($i === 0) {
                    // Skenario khusus akun testing Anda HANYA BERLAKU di bulan saat ini
                    if ($email === 'penghuni1@firabo.test') {
                        $statusTagihan = 'belum_bayar';
                    } elseif ($email === 'penghuni2@firabo.test') {
                        $statusTagihan = 'terlambat';
                    } elseif ($email === 'penghuni3@firabo.test') {
                        $statusTagihan = 'lunas';
                    } else {
                        // Untuk penghuni random, status bulan ini kita buat acak realistis
                        // Logika: Jika jatuh tempo sudah lewat, paksa jadi 'terlambat', jika belum, 'belum_bayar'
                        if ($hariIni->gt($tanggalJatuhTempo)) {
                            $statusTagihan = $faker->randomElement(['lunas', 'terlambat', 'lunas']);
                        } else {
                            $statusTagihan = $faker->randomElement(['lunas', 'belum_bayar']);
                        }
                    }
                } else {
                    // Beri 5% kemungkinan ada penghuni random yang nunggak dari bulan-bulan lalu
                    if ($email !== 'penghuni1@firabo.test' && $email !== 'penghuni2@firabo.test' && $email !== 'penghuni3@firabo.test') {
                        if (rand(1, 100) <= 5) {
                            $statusTagihan = 'terlambat';
                        }
                    }
                }

                // 3. Insert Tagihan
                $idTagihan = DB::table('tb_tagihan')->insertGetId([
                    'hunian_id'           => $jadwal->hunian_id,
                    'jadwal_id'           => $jadwal->jadwal_id,
                    'nominal'             => $kamar->harga_sewa,
                    'status_tagihan'      => $statusTagihan,
                    'tanggal_tagihan'     => $tanggalGenerate->format('Y-m-d'),
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'), 
                    'created_at'          => $tanggalGenerate, // Set created_at sesuai tanggal historis
                    'updated_at'          => $tanggalGenerate,
                ]);

                // 4. Insert Pembayaran (Hanya jika status lunas)
                if ($statusTagihan === 'lunas') {
                    // Bayar secara acak antara hari H sampai H+5 (atau sebelum jatuh tempo)
                    $hariRandom = rand(0, 5);
                    $tanggalBayar = (clone $tanggalGenerate)->addDays($hariRandom);
                    
                    DB::table('tb_pembayaran')->insert([
                        'tagihan_id'        => $idTagihan,
                        'tanggal_bayar'     => $tanggalBayar->format('Y-m-d'),
                        'nominal_bayar'     => $kamar->harga_sewa,
                        'status_pembayaran' => 'sukses',
                        'metode_pembayaran' => $faker->randomElement(['qris', 'bank_transfer', 'gopay', 'dana', 'tunai']),
                        'transaction_id'    => 'FIRABO-' . $idTagihan . '-' . $tanggalBayar->timestamp, // Simulasi Order ID unik
                        'created_at'        => $tanggalBayar, // Sinkronkan waktu sistem
                        'updated_at'        => $tanggalBayar,
                    ]);
                }
            }
        }

        $this->command->info('  ✓ Sukses membuat histori tagihan untuk beberapa bulan ke belakang.');
    }
}