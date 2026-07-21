<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kamar;
use App\Models\Hunian;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

/**
 * PenghuniSeeder (v6)
 *
 * Membuat 20 penempatan (hunian selalu dibuat 'aktif' di sini):
 *   - 3 akun testing (skenario belum_bayar/terlambat/lunas — tidak berubah)
 *   - 15 penghuni acak
 *   - 2 kandidat "kabur" (dikonversi ke piutang oleh TagihanDanPembayaranSeeder)
 *
 * 20 sekarang, 2 nanti dibebaskan lagi → hasil akhir 18 penghuni aktif,
 * persis sesuai spesifikasi.
 *
 * no_wa wajib terisi — pakai nomor dummy dari Faker, bukan kosong/null.
 */
class PenghuniSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $this->command->info('  -> Membuat 20 penempatan penghuni (18 aktif + 2 kandidat kabur)...');

        foreach (['penghuni1@firabo.test', 'penghuni2@firabo.test', 'penghuni3@firabo.test'] as $email) {
            $kamar = Kamar::where('status_kamar', 'tersedia')->inRandomOrder()->first();
            if ($kamar) $this->buatDataPenghuni($kamar, $email, $faker->name, $faker);
        }

        for ($i = 0; $i < 15; $i++) {
            $kamar = Kamar::where('status_kamar', 'tersedia')->inRandomOrder()->first();
            if (! $kamar) break;
            $this->buatDataPenghuni($kamar, $faker->unique()->safeEmail, $faker->name, $faker);
        }

        // Kandidat kabur — masuk lebih lama (4-5 bulan) agar histori tagihannya
        // cukup panjang untuk membangun nominal piutang yang jelas.
        foreach (['kabur1@firabo.test' => 5, 'kabur2@firabo.test' => 4] as $email => $bulanMundur) {
            $kamar = Kamar::where('status_kamar', 'tersedia')->inRandomOrder()->first();
            if ($kamar) $this->buatDataPenghuni($kamar, $email, $faker->name, $faker, $bulanMundur);
        }

        $this->command->info('  ✓ Sukses membuat data penghuni & hunian.');
    }

    private function buatDataPenghuni($kamar, $email, $nama, $faker, ?int $bulanMundur = null)
    {
        $kamar->update(['status_kamar' => 'terisi']);

        $user = User::create([
            'name'        => $nama,
            'email'       => $email,
            'no_wa'       => '08' . $faker->numerify('##########'),
            'password'    => Hash::make('password123'),
            'role'        => 'penghuni',
            'status_akun' => 'aktif',
        ]);

        $tglMasukObj = $bulanMundur
            ? $faker->dateTimeBetween("-{$bulanMundur} months", '-3 months')
            : $faker->dateTimeBetween('-6 months', '-1 month');

        Hunian::create([
            'user_id'        => $user->id,
            'kamar_id'       => $kamar->kamar_id,
            'tanggal_masuk'  => Carbon::instance($tglMasukObj)->format('Y-m-d'),
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);
    }
}