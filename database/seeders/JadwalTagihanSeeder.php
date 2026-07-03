<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hunian;
use App\Models\JadwalTagihan;
use Carbon\Carbon;

class JadwalTagihanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  -> Memulai generate Jadwal Tagihan...');
        
        $hunians = Hunian::where('status_hunian', 'aktif')->get();

        foreach ($hunians as $hunian) {
            $tanggalMasuk = Carbon::parse($hunian->tanggal_masuk);
            
            // Set generate di hari yang sama saat masuk, jatuh tempo 5 hari setelahnya
            $tanggalGenerate = Carbon::now()->setDay($tanggalMasuk->day);
            $tanggalJatuhTempo = (clone $tanggalGenerate)->addDays(5);

            JadwalTagihan::create([
                'hunian_id'           => $hunian->hunian_id,
                'tanggal_generate'    => $tanggalGenerate->day, 
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo->day,
                'status_jadwal'       => 'aktif',
            ]);
        }
        
        $this->command->info('  ✓ Sukses membuat data jadwal tagihan.');
    }
}