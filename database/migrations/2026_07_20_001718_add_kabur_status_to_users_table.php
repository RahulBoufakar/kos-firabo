<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN status_akun ENUM('aktif', 'nonaktif', 'kabur') NOT NULL DEFAULT 'aktif'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Turunkan dulu semua 'kabur' ke 'nonaktif' sebelum enum lama diberlakukan lagi,
        // supaya rollback tidak gagal karena ada nilai yang tidak dikenal enum lama.
        DB::statement("UPDATE users SET status_akun = 'nonaktif' WHERE status_akun = 'kabur'");
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN status_akun ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif'"
        );
    }
};
