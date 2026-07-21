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
            "ALTER TABLE tb_tagihan MODIFY COLUMN status_tagihan ENUM('belum_bayar', 'lunas', 'terlambat', 'piutang') NOT NULL DEFAULT 'belum_bayar'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Turunkan 'piutang' -> 'belum_bayar' dulu sebelum enum lama diberlakukan lagi,
        // supaya rollback tidak gagal karena ada nilai yang tidak dikenal enum lama.
        DB::statement("UPDATE tb_tagihan SET status_tagihan = 'belum_bayar' WHERE status_tagihan = 'piutang'");
        DB::statement(
            "ALTER TABLE tb_tagihan MODIFY COLUMN status_tagihan ENUM('belum_bayar', 'lunas', 'terlambat') NOT NULL DEFAULT 'belum_bayar'"
        );
    }
};
