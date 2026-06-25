<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kamar;
use App\Models\Hunian;
use App\Models\JadwalTagihan;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PenghuniSeeder extends Seeder
{
    public function run(): void
    {
        // Info command awal
        $this->command->info('  -> Memulai proses generate 20 penghuni aktif beserta relasinya...');

        $faker = Faker::create('id_ID');
        
        // Ambil 20 kamar yang tersedia
        $kamars = Kamar::where('status_kamar', 'tersedia')->limit(20)->get();
        
        if ($kamars->count() < 20) {
            $this->command->warn('Jumlah kamar kurang dari 20. Pastikan KamarSeeder dijalankan lebih dulu.');
            return;
        }

        foreach ($kamars as $index => $kamar) {
            // Setup 2 user pertama sesuai request untuk testing, sisanya random
            if ($index === 0) {
                $email = 'penghuni1@firabo.test';
                $kamar->update(['nomor_kamar' => 'A03', 'status_kamar' => 'terisi']);
                $statusTagihan = 'belum_bayar';
            } elseif ($index === 1) {
                $email = 'penghuni2@firabo.test';
                $kamar->update(['nomor_kamar' => 'B03', 'status_kamar' => 'terisi']);
                $statusTagihan = 'terlambat';
            } else {
                $email = $faker->unique()->safeEmail;
                $kamar->update(['status_kamar' => 'terisi']);
                $statusTagihan = $faker->randomElement(['lunas', 'lunas', 'lunas', 'belum_bayar']);
            }

            // 1. Buat User
            $user = User::create([
                'name'     => $faker->name,
                'email'    => $email,
                'password' => Hash::make('password123'),
            ]);

            // 2. Buat Hunian
            $tglMasukObj = $faker->dateTimeBetween('-5 months', '-1 month');
            $carbonMasuk = Carbon::instance($tglMasukObj);
            
            $hunian = Hunian::create([
                'user_id'        => $user->id,
                'kamar_id'       => $kamar->kamar_id,
                'tanggal_masuk'  => $carbonMasuk->format('Y-m-d'),
                'tanggal_keluar' => null,
                'status_hunian'  => 'aktif',
            ]);

            // 3. Buat Jadwal Tagihan
            // Ambil tanggal masuk untuk sinkronisasi siklus
            $tanggalGenerate = Carbon::now()->setDay($carbonMasuk->day);
            $tanggalJatuhTempo = (clone $tanggalGenerate)->addDays(5);

            $jadwal = JadwalTagihan::create([
                'hunian_id'           => $hunian->hunian_id,
                // Gunakan ->day untuk memasukkan integer (angka hari 1-31)
                'tanggal_generate'    => $tanggalGenerate->day, 
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo->day,
                'status_jadwal'       => 'aktif',
            ]);

           // 4. Buat Tagihan Dummy (Berdasarkan jadwal yang baru dibuat)
            $idTagihan = DB::table('tb_tagihan')->insertGetId([
                'hunian_id'           => $hunian->hunian_id,
                'jadwal_id'           => $jadwal->jadwal_id,
                'nominal'             => $kamar->harga_sewa,
                'status_tagihan'      => $statusTagihan,
                'tanggal_tagihan'     => $tanggalGenerate->format('Y-m-d'),
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'), 
                'created_at'          => Carbon::now(),
                'updated_at'          => Carbon::now(),
            ]);

            // 5. Buat Pembayaran (Hanya jika status lunas)
            if ($statusTagihan === 'lunas') {
                // Clone tanggal generate dan tambahkan hari acak antara 0 (hari yang sama) sampai 5 hari
                $tanggalBayar = (clone $tanggalGenerate)->addDays(rand(0, 5));

                DB::table('tb_pembayaran')->insert([
                    'tagihan_id'   => $idTagihan,
                    // Pembayaran dilakukan antara tanggal generate dan hari ini
                    'tanggal_bayar'    => $tanggalBayar->format('Y-m-d'),
                    'nominal_bayar' => $kamar->harga_sewa,
                    'metode_pembayaran'       => $faker->randomElement(['Transfer Bank', 'E-Wallet', 'Tunai']),
                    'created_at'   => Carbon::now(),
                    'updated_at'   => Carbon::now(),
                ]);
            }

            // Info command di dalam loop
            $this->command->info("     [+] Akun: {$email} | Kamar: {$kamar->nomor_kamar} | Status: {$statusTagihan}");
        }

        // Info command akhir
        $this->command->info('  ✓ Sukses membuat 20 data penghuni, hunian, jadwal, dan tagihan.');
    }
}