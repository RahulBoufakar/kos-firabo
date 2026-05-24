<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Gunakan firstOrCreate agar seeder aman dijalankan berulang kali
        // tanpa membuat duplikat akun admin
        User::firstOrCreate(
            ['email' => 'admin@firabo.test'],
            [
                'name' => 'Administrator Firabo',
                'no_wa'        => '081234567000',
                'password'     => Hash::make('admin123'),
                'role'         => 'admin',
                'status_akun'  => 'aktif',
            ]
        );
 
        $this->command->info('  ✓ AdminSeeder: admin@firabo.test (password: admin123)');
    }
}
