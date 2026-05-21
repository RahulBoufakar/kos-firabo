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
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('no_wa', 20)->nullable()->after('email');
                $table->enum('role', ['admin', 'penghuni'])->default('penghuni')->after('no_wa');
                $table->enum('status_akun', ['aktif', 'nonaktif'])->default('aktif')->after('role');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_wa', 'role', 'status_akun']);
        });
    }
};
