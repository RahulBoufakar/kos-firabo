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
        Schema::create('tb_jadwal_tagihan', function (Blueprint $table) {
             $table->id('jadwal_id');
            $table->foreignId('hunian_id')->constrained('tb_hunian', 'hunian_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('tanggal_generate'); // 1–31
            $table->unsignedTinyInteger('tanggal_jatuh_tempo'); // jarak hari
            $table->enum('status_jadwal', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_jadwal_tagihan');
    }
};
