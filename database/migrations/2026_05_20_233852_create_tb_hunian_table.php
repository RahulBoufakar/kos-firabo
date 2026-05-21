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
        Schema::create('tb_hunian', function (Blueprint $table) {
            $table->id('hunian_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kamar_id')->constrained('tb_kamar', 'kamar_id')->cascadeOnDelete();
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->enum('status_hunian', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_hunian');
    }
};
