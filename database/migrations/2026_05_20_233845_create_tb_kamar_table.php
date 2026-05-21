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
        Schema::create('tb_kamar', function (Blueprint $table) {
            $table->id('kamar_id');
            $table->string('nomor_kamar', 10)->unique();
            $table->string('tipe_kamar', 50);
            $table->decimal('harga_sewa', 10, 2);
            $table->text('fasilitas')->nullable();
            $table->enum('status_kamar', ['tersedia', 'terisi', 'nonaktif'])->default('tersedia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_kamar');
    }
};
