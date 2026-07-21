<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\JadwalTagihan;
use App\Models\Hunian;
use App\Models\Kamar;
use App\Models\User;
use App\Models\Tagihan;
use Carbon\Carbon;
use Faker\Factory as Faker;

/**
 * TagihanDanPembayaranSeeder (v6)
 *
 * 1. Generate histori tagihan+pembayaran untuk semua hunian aktif (20),
 *    tersebar dari bulan masuk masing-masing s/d bulan berjalan — supaya
 *    laporan bulanan/tahunan tidak menumpuk di bulan ini saja.
 * 2. Untuk 2 kandidat "kabur": paksa beberapa bulan terakhir belum dibayar,
 *    lalu konversi ke piutang — meniru persis logic admin->delete() di
 *    components/admin/penghuni/table.blade.php.
 */
class TagihanDanPembayaranSeeder extends Seeder
{
    // email => jumlah bulan terakhir yang sengaja dipaksa nunggak
    private array $kaburScenario = [
        'kabur1@firabo.test' => 3, // 3 x 600.000 = Rp 1.800.000
        'kabur2@firabo.test' => 2, // 2 x 600.000 = Rp 1.200.000
    ];

    public function run(): void
    {
        $faker   = Faker::create('id_ID');
        $hariIni = Carbon::now();
        $this->command->info('  -> Generate histori tagihan & pembayaran (tersebar sepanjang ' . $hariIni->year . ')...');

        $jadwals = JadwalTagihan::where('status_jadwal', 'aktif')
            ->with(['hunian.user', 'hunian.kamar'])
            ->get();

        foreach ($jadwals as $jadwal) {
            $hunian       = $jadwal->hunian;
            $kamar        = $hunian->kamar;
            $email        = $hunian->user->email ?? '';
            $bulanTunggak = $this->kaburScenario[$email] ?? 0;

            $awalTahun    = Carbon::create($hariIni->year, 1, 1);
            $tanggalMasuk = Carbon::parse($hunian->tanggal_masuk);
            $mulaiDari    = $tanggalMasuk->greaterThan($awalTahun) ? $tanggalMasuk : $awalTahun;

            $bulanList = [];
            $cursor    = $mulaiDari->copy()->startOfMonth();
            while ($cursor->lte($hariIni)) {
                $bulanList[] = $cursor->copy();
                $cursor->addMonth();
            }
            $totalBulan = count($bulanList);

            foreach ($bulanList as $idx => $targetBulan) {
                $sisaDariSekarang = $totalBulan - 1 - $idx; // 0 = bulan berjalan

                $tanggalGenerate   = Carbon::create($targetBulan->year, $targetBulan->month, $jadwal->tanggal_generate);
                $tanggalJatuhTempo = Carbon::create($targetBulan->year, $targetBulan->month, $jadwal->tanggal_jatuh_tempo);
                if ($jadwal->tanggal_jatuh_tempo < $jadwal->tanggal_generate) {
                    $tanggalJatuhTempo->addMonth();
                }

                $statusTagihan = $this->tentukanStatus(
                    $email, $bulanTunggak, $sisaDariSekarang, $hariIni, $tanggalJatuhTempo, $faker
                );

                $idTagihan = DB::table('tb_tagihan')->insertGetId([
                    'hunian_id'           => $hunian->hunian_id,
                    'jadwal_id'           => $jadwal->jadwal_id,
                    'nominal'             => $kamar->harga_sewa,
                    'status_tagihan'      => $statusTagihan,
                    'tanggal_tagihan'     => $tanggalGenerate->format('Y-m-d'),
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'),
                    'created_at'          => $tanggalGenerate,
                    'updated_at'          => $tanggalGenerate,
                ]);

                if ($statusTagihan === 'lunas') {
                    $tanggalBayar = $tanggalGenerate->copy()->addDays($faker->numberBetween(0, 5));
                    DB::table('tb_pembayaran')->insert([
                        'tagihan_id'        => $idTagihan,
                        'tanggal_bayar'     => $tanggalBayar->format('Y-m-d'),
                        'nominal_bayar'     => $kamar->harga_sewa,
                        'status_pembayaran' => 'sukses',
                        'metode_pembayaran' => $faker->randomElement(['qris', 'bank_transfer', 'gopay', 'dana', 'tunai']),
                        'transaction_id'    => 'FIRABO-' . $idTagihan . '-' . $tanggalBayar->timestamp,
                        'created_at'        => $tanggalBayar,
                        'updated_at'        => $tanggalBayar,
                    ]);
                }
            }
        }

        $this->command->info('  ✓ Histori tagihan tersebar sepanjang tahun ' . $hariIni->year . '.');

        $this->konversiPenghuniKabur();
    }

    private function tentukanStatus(
        string $email, int $bulanTunggak, int $sisaDariSekarang,
        Carbon $hariIni, Carbon $tanggalJatuhTempo, $faker
    ): string {
        $status = '';

        // 1. Tentukan status awal berdasarkan skenario
        if ($bulanTunggak > 0 && $sisaDariSekarang < $bulanTunggak) {
            $status = $sisaDariSekarang === 0 ? 'belum_bayar' : 'terlambat';
        } elseif ($sisaDariSekarang === 0 && $email === 'penghuni1@firabo.test') {
            $status = 'belum_bayar';
        } elseif ($sisaDariSekarang === 0 && $email === 'penghuni2@firabo.test') {
            $status = 'terlambat';
        } elseif ($sisaDariSekarang === 0 && $email === 'penghuni3@firabo.test') {
            $status = 'lunas';
        } else {
            // Fluktuasi realistis: mayoritas lunas, sedikit terlambat/belum_bayar
            $status = $faker->randomElement(['lunas', 'lunas', 'lunas', 'lunas', 'lunas', 'terlambat', 'belum_bayar']);
        }

        // 2. VALIDASI MUTLAK TERHADAP TANGGAL JATUH TEMPO
        if ($status !== 'lunas') {
            if ($hariIni->gt($tanggalJatuhTempo)) {
                // Jika hari ini SUDAH MELEWATI jatuh tempo, status wajib 'terlambat'
                $status = 'terlambat';
            } else {
                // Jika hari ini BELUM MELEWATI (atau pas) jatuh tempo, status wajib 'belum_bayar'
                // Karena secara realita tidak mungkin ada orang 'terlambat' sebelum waktunya
                $status = 'belum_bayar';
            }
        }

        return $status;
    }

    /**
     * Meniru logic Livewire penghuni->delete(): kamar dibebaskan, hunian
     * ditutup, jadwal nonaktif, tagihan belum lunas → 'piutang', akun → 'kabur'.
     */
    private function konversiPenghuniKabur(): void
    {
        $this->command->info('  -> Mengonversi 2 penghuni menjadi status kabur/piutang...');

        foreach (array_keys($this->kaburScenario) as $email) {
            $user = User::where('email', $email)->first();
            if (! $user) continue;

            $hunian = Hunian::where('user_id', $user->id)
                ->where('status_hunian', 'aktif')
                ->with('jadwalTagihan')
                ->first();
            if (! $hunian) continue;

            $totalPiutang = Tagihan::where('hunian_id', $hunian->hunian_id)
                ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
                ->sum('nominal');

            Tagihan::where('hunian_id', $hunian->hunian_id)
                ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
                ->update(['status_tagihan' => 'piutang']);

            Kamar::where('kamar_id', $hunian->kamar_id)->update(['status_kamar' => 'tersedia']);

            $hunian->update(['status_hunian' => 'selesai', 'tanggal_keluar' => now()]);
            $hunian->jadwalTagihan?->update(['status_jadwal' => 'nonaktif']);
            $user->update(['status_akun' => 'kabur']);

            $this->command->line("     [!] {$user->name} ({$email}) — piutang Rp " . number_format($totalPiutang, 0, ',', '.'));
        }
    }
}