<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tb_tagihan', function (Blueprint $table) {
            $table->id('tagihan_id');
            $table->foreignId('hunian_id')->constrained('tb_hunian', 'hunian_id')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->constrained('tb_jadwal_tagihan', 'jadwal_id')->cascadeOnDelete();
            $table->decimal('nominal', 10, 2);
            $table->date('tanggal_tagihan');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status_tagihan', ['belum_bayar', 'lunas', 'terlambat'])->default('belum_bayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_tagihan');
    }
};
