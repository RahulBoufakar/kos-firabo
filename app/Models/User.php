<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// #[Fillable(['name', 'email', 'password'])]
// #[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // protected $primaryKey = 'user_id';

    protected $fillable = [
        'name', 'email', 'no_wa', 'role', 'status_akun', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
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
    
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'user_id');
    }

    /**
     * Pembayaran yang dicatat oleh user ini (Admin pencatat).
     */
    public function pembayaranDicatat(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPenghuni(): bool
    {
        return $this->role === 'penghuni';
    }
}
