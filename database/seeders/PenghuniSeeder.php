<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kamar;
use App\Models\Hunian;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class PenghuniSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $this->command->info('  -> Memulai generate 20 Penghuni & Hunian...');

        // 1. Definisikan akun testing untuk tiap tipe kamar
        $akunTesting = [
            'Standar'   => 'penghuni1@firabo.test',
            'Eksklusif' => 'penghuni2@firabo.test',
            'VIP'       => 'penghuni3@firabo.test'
        ];

        $jumlahTerisi = 0;

        // 2. Buat penghuni testing
        foreach ($akunTesting as $tipe => $email) {
            // Cari kamar pertama yang tersedia sesuai tipe
            $kamar = Kamar::where('tipe_kamar', $tipe)->where('status_kamar', 'tersedia')->first();
            
            if ($kamar) {
                $this->buatDataPenghuni($kamar, $email, $faker->name, $faker);
                $jumlahTerisi++;
                $this->command->info("     [+] Testing Akun: {$email} menempati Kamar {$kamar->nomor_kamar} ({$tipe})");
            }
        }

        // 3. Buat penghuni random untuk sisa kuota (hingga total 20 kamar terisi)
        $sisaKuota = 20 - $jumlahTerisi;
        $sisaKamar = Kamar::where('status_kamar', 'tersedia')->limit($sisaKuota)->get();

        foreach ($sisaKamar as $kamar) {
            $this->buatDataPenghuni($kamar, $faker->unique()->safeEmail, $faker->name, $faker);
        }

        $this->command->info('  ✓ Sukses membuat data penghuni & hunian.');
    }

    // Private helper agar kode tidak berulang
    private function buatDataPenghuni($kamar, $email, $nama, $faker)
    {
        // Update status kamar
        $kamar->update(['status_kamar' => 'terisi']);

        // Buat User
        $user = User::create([
            'name'     => $nama,
            'email'    => $email,
            'password' => Hash::make('password123'),
        ]);

        // Buat Hunian
        $tglMasukObj = $faker->dateTimeBetween('-5 months', '-1 month');
        $carbonMasuk = Carbon::instance($tglMasukObj);

        Hunian::create([
            'user_id'        => $user->id,
            'kamar_id'       => $kamar->kamar_id, 
            'tanggal_masuk'  => $carbonMasuk->format('Y-m-d'),
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);
    }
}