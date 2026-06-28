<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// #[Fillable(['name', 'email', 'password'])]
// #[Hidden(['password', 'remember_token'])]

/**
 * Model User — representasi pengguna aplikasi Kos Firabo.
 *
 * Mendukung dua role:
 *  - 'admin'    : pengelola kos yang mengelola kamar, penghuni, dan tagihan.
 *  - 'penghuni' : penyewa kamar yang bisa melihat tagihan & melakukan pembayaran.
 *
 * Relasi utama:
 *  - hunian()      → HasMany ke Hunian (riwayat semua hunian user).
 *  - hunianAktif() → HasOne  ke Hunian (hunian yang sedang aktif).
 *  - pembayaran()  → HasMany ke Pembayaran (semua pembayaran user).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // protected $primaryKey = 'user_id';

    /**
     * Kolom yang boleh di-mass assign.
     *
     * - name        : nama lengkap pengguna
     * - email       : alamat email (digunakan untuk login)
     * - no_wa       : nomor WhatsApp untuk notifikasi
     * - role        : role pengguna ('admin' | 'penghuni')
     * - status_akun : status akun ('aktif' | 'nonaktif')
     * - password    : kata sandi (otomatis di-hash via casts)
     */
    protected $fillable = [
        'name', 'email', 'no_wa', 'role', 'status_akun', 'password',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi ke JSON/array.
     * Password dan remember_token tidak boleh terekspos ke response API.
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Definisi casting atribut.
     *
     * - email_verified_at → otomatis di-cast ke instance Carbon (datetime).
     * - password           → otomatis di-hash saat di-set (fitur Laravel 10+).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relasi ──────────────────────────────────────────────

    /**
     * Semua riwayat hunian milik user ini (termasuk yang sudah selesai).
     * Satu user bisa punya banyak hunian jika pernah pindah kamar.
     */
    public function hunian()
    {
        return $this->hasMany(Hunian::class, 'user_id');
    }

    /**
     * Hunian aktif saat ini.
     * Digunakan di tabel admin untuk menampilkan nomor kamar.
     */
    public function hunianAktif(): HasOne
    {
        return $this->hasOne(Hunian::class, 'user_id')
            ->where('status_hunian', 'aktif')
            ->latest('tanggal_masuk');
    }

    /**
     * Semua pembayaran yang dilakukan oleh user ini.
     * Mencakup pembayaran manual maupun online (Midtrans).
     */
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'user_id');
    }

    // ── Helper ──────────────────────────────────────────────

    /**
     * Cek apakah user memiliki role admin.
     *
     * @return bool true jika role === 'admin'
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user memiliki role penghuni.
     *
     * @return bool true jika role === 'penghuni'
     */
    public function isPenghuni(): bool
    {
        return $this->role === 'penghuni';
    }
}
