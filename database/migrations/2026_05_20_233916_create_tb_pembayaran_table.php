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
        Schema::create('tb_pembayaran', function (Blueprint $table) {
            $table->id('pembayaran_id');
            $table->foreignId('tagihan_id')->constrained('tb_tagihan', 'tagihan_id')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('metode_pembayaran', 50);
            $table->decimal('nominal_bayar', 10, 2);
            $table->timestamp('tanggal_bayar')->nullable();
            $table->enum('status_pembayaran', ['sukses', 'gagal', 'pending'])->default('pending');
            $table->string('snap_token', 255)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->timestamp('created_at');                // when snap token was created / record inserted
            $table->timestamp('updated_at')->nullable();    // when callback updated the status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_pembayaran');
    }
};
