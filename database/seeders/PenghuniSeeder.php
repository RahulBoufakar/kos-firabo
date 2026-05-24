<?php

namespace Database\Seeders;

use App\Models\Hunian;
use App\Models\JadwalTagihan;
use App\Models\Kamar;
use App\Models\Tagihan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PenghuniSeeder
 *
 * Membuat 2 akun penghuni dengan kondisi yang berbeda untuk keperluan testing:
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │ Penghuni 1 — Budi Santoso (penghuni1@firabo.test)                  │
 * │   Kamar  : A03 (Standar, Rp 800.000)                               │
 * │   Skenario: tagihan bulan ini BELUM BAYAR → test Midtrans payment  │
 * │   Jadwal : generate tgl 1 tiap bulan, jatuh tempo +7 hari          │
 * ├─────────────────────────────────────────────────────────────────────┤
 * │ Penghuni 2 — Sari Dewi (penghuni2@firabo.test)                     │
 * │   Kamar  : B03 (Deluxe, Rp 1.200.000)                              │
 * │   Skenario: tagihan bulan lalu TERLAMBAT + bulan ini belum bayar   │
 * │   Jadwal : generate tgl 5 tiap bulan, jatuh tempo +7 hari          │
 * └─────────────────────────────────────────────────────────────────────┘
 *
 * Password semua akun: password123
 */
class PenghuniSeeder extends Seeder
{
    public function run(): void
    {
        $today    = Carbon::today();
        $password = Hash::make('password123');
 
        // ══════════════════════════════════════════════════════════════════
        //  PENGHUNI 1 — Budi Santoso
        //  Skenario: tagihan bulan ini ada dan BELUM BAYAR
        //  Tujuan testing: tombol "Bayar Sekarang" + alur Midtrans Snap
        // ══════════════════════════════════════════════════════════════════
 
        $budi = User::create([
            'name' => 'Budi Santoso',
            'email'        => 'penghuni1@firabo.test',
            'no_wa'        => '081234567001',
            'password'     => $password,
            'role'         => 'penghuni',
            'status_akun'  => 'aktif',
        ]);
 
        $kamarA03 = Kamar::where('nomor_kamar', 'A03')->first();
 
        // Hunian Budi — masuk 3 bulan lalu agar ada riwayat yang realistis
        $hunianBudi = Hunian::create([
            'user_id'        => $budi->id,
            'kamar_id'       => $kamarA03->kamar_id,
            'tanggal_masuk'  => $today->copy()->subMonths(3)->startOfMonth(),
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);
 
        // Jadwal tagihan Budi: generate tgl 1 tiap bulan, JT +7 hari
        $jadwalBudi = JadwalTagihan::create([
            'hunian_id'           => $hunianBudi->hunian_id,
            'tanggal_generate'    => 1,
            'tanggal_jatuh_tempo' => 7,
            'status_jadwal'       => 'aktif',
        ]);
 
        // Tagihan 3 bulan lalu — LUNAS (riwayat)
        $tiga_bulan_lalu = $today->copy()->subMonths(3)->startOfMonth();
        Tagihan::create([
            'hunian_id'           => $hunianBudi->hunian_id,
            'jadwal_id'           => $jadwalBudi->jadwal_id,
            'nominal'             => $kamarA03->harga_sewa,
            'tanggal_tagihan'     => $tiga_bulan_lalu->toDateString(),
            'tanggal_jatuh_tempo' => $tiga_bulan_lalu->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'lunas',
        ]);
 
        // Tagihan 2 bulan lalu — LUNAS (riwayat)
        $dua_bulan_lalu = $today->copy()->subMonths(2)->startOfMonth();
        Tagihan::create([
            'hunian_id'           => $hunianBudi->hunian_id,
            'jadwal_id'           => $jadwalBudi->jadwal_id,
            'nominal'             => $kamarA03->harga_sewa,
            'tanggal_tagihan'     => $dua_bulan_lalu->toDateString(),
            'tanggal_jatuh_tempo' => $dua_bulan_lalu->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'lunas',
        ]);
 
        // Tagihan bulan lalu — LUNAS (riwayat)
        $bulan_lalu = $today->copy()->subMonth()->startOfMonth();
        Tagihan::create([
            'hunian_id'           => $hunianBudi->hunian_id,
            'jadwal_id'           => $jadwalBudi->jadwal_id,
            'nominal'             => $kamarA03->harga_sewa,
            'tanggal_tagihan'     => $bulan_lalu->toDateString(),
            'tanggal_jatuh_tempo' => $bulan_lalu->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'lunas',
        ]);
 
        // Tagihan bulan ini — BELUM BAYAR ← ini yang akan dipakai test Midtrans
        $bulan_ini = $today->copy()->startOfMonth();
        Tagihan::create([
            'hunian_id'           => $hunianBudi->hunian_id,
            'jadwal_id'           => $jadwalBudi->jadwal_id,
            'nominal'             => $kamarA03->harga_sewa,
            'tanggal_tagihan'     => $bulan_ini->toDateString(),
            // JT = tgl 1 + 7 hari = tgl 8
            'tanggal_jatuh_tempo' => $bulan_ini->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'belum_bayar',
        ]);
 
        $this->command->info(
            "  ✓ Penghuni 1: Budi Santoso (penghuni1@firabo.test) "
            . "— Kamar A03, 4 tagihan (3 lunas + 1 belum bayar)"
        );
 
        // ══════════════════════════════════════════════════════════════════
        //  PENGHUNI 2 — Sari Dewi
        //  Skenario: ada tagihan TERLAMBAT + tagihan bulan ini BELUM BAYAR
        //  Tujuan testing: tampilan badge terlambat, hero card merah,
        //                  hitung sisa hari minus di show tagihan
        // ══════════════════════════════════════════════════════════════════
 
        $sari = User::create([
            'name' => 'Sari Dewi',
            'email'        => 'penghuni2@firabo.test',
            'no_wa'        => '081234567002',
            'password'     => $password,
            'role'         => 'penghuni',
            'status_akun'  => 'aktif',
        ]);
 
        $kamarB03 = Kamar::where('nomor_kamar', 'B03')->first();
 
        // Hunian Sari — masuk 2 bulan lalu
        $hunianSari = Hunian::create([
            'user_id'        => $sari->id,
            'kamar_id'       => $kamarB03->kamar_id,
            'tanggal_masuk'  => $today->copy()->subMonths(2)->startOfMonth(),
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);
 
        // Jadwal tagihan Sari: generate tgl 5 tiap bulan, JT +7 hari
        $jadwalSari = JadwalTagihan::create([
            'hunian_id'           => $hunianSari->hunian_id,
            'tanggal_generate'    => 5,
            'tanggal_jatuh_tempo' => 7,
            'status_jadwal'       => 'aktif',
        ]);
 
        // Tagihan 2 bulan lalu — LUNAS (riwayat)
        $dua_bulan = $today->copy()->subMonths(2)->setDay(5);
        Tagihan::create([
            'hunian_id'           => $hunianSari->hunian_id,
            'jadwal_id'           => $jadwalSari->jadwal_id,
            'nominal'             => $kamarB03->harga_sewa,
            'tanggal_tagihan'     => $dua_bulan->toDateString(),
            'tanggal_jatuh_tempo' => $dua_bulan->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'lunas',
        ]);
 
        // Tagihan bulan lalu — TERLAMBAT ← jatuh tempo sudah lewat, belum bayar
        $bulan_lalu_tgl5 = $today->copy()->subMonth()->setDay(5);
        Tagihan::create([
            'hunian_id'           => $hunianSari->hunian_id,
            'jadwal_id'           => $jadwalSari->jadwal_id,
            'nominal'             => $kamarB03->harga_sewa,
            'tanggal_tagihan'     => $bulan_lalu_tgl5->toDateString(),
            // JT = bulan lalu tgl 12 → sudah pasti lewat
            'tanggal_jatuh_tempo' => $bulan_lalu_tgl5->copy()->addDays(7)->toDateString(),
            'status_tagihan'      => 'terlambat',
        ]);
 
        // Tagihan bulan ini — BELUM BAYAR
        // Gunakan tgl 5 bulan ini, atau tgl 5 bulan depan jika hari ini < 5
        $bulanIniTgl5 = $today->day >= 5
            ? $today->copy()->setDay(5)
            : $today->copy()->subMonth()->setDay(5);
 
        // Pastikan tidak duplikat dengan tagihan terlambat di atas
        if ($bulanIniTgl5->isSameMonth($bulan_lalu_tgl5)) {
            $bulanIniTgl5 = $today->copy()->setDay(min(5, $today->daysInMonth));
        }
 
        Tagihan::create([
            'hunian_id'           => $hunianSari->hunian_id,
            'jadwal_id'           => $jadwalSari->jadwal_id,
            'nominal'             => $kamarB03->harga_sewa,
            'tanggal_tagihan'     => $today->copy()->startOfMonth()->setDay(5)->toDateString(),
            'tanggal_jatuh_tempo' => $today->copy()->startOfMonth()->setDay(5)->addDays(7)->toDateString(),
            'status_tagihan'      => 'belum_bayar',
        ]);
 
        $this->command->info(
            "  ✓ Penghuni 2: Sari Dewi (penghuni2@firabo.test) "
            . "— Kamar B03, 3 tagihan (1 lunas + 1 terlambat + 1 belum bayar)"
        );
    }
}
