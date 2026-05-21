<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'       => 'Admin Firabo',
            'email'      => 'admin@firabo.com',
            'no_wa'      => '08123456789',
            'role'       => 'admin',
            'status_akun'=> 'aktif',
            'password'   => Hash::make('admin123'),
        ]);
    }
}
