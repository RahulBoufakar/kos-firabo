<?php

namespace App\Models;

use App\Models\Hunian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kamar extends Model
{
    protected $table = 'tb_kamar';
    protected $primaryKey = 'kamar_id';

    protected $fillable = [
        'nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas', 'status_kamar',
    ];


    // ── Relasi ──────────────────────────────────────────────
 
    /**
     * Semua hunian yang pernah/sedang menggunakan kamar ini.
     */
    public function hunian(): HasMany
    {
        return $this->hasMany(Hunian::class, 'kamar_id', 'kamar_id');
    }
 
    /**
     * Hunian yang sedang aktif di kamar ini.
     */
    public function hunianAktif(): HasMany
    {
        return $this->hasMany(Hunian::class, 'kamar_id', 'kamar_id')
            ->where('status_hunian', 'aktif');
    }
 
    // ── Helper ──────────────────────────────────────────────
 
    public function isTersedia(): bool
    {
        return $this->status_kamar === 'tersedia';
    }

    /**
     * Sumber kebenaran tunggal: kamar ini sedang dihuni penghuni aktif?
     * Dicek dari RELASI, bukan cuma kolom status_kamar — supaya anti-anomali
     * kalau status_kamar dan data hunian pernah tidak sinkron.
     */
    public function sedangDihuni(): bool
    {
        return $this->status_kamar === 'terisi'
            || $this->hunianAktif()->exists();
    }

    /**
     * Boleh dihapus permanen? Kamar hanya boleh dihapus kalau tidak sedang
     * dihuni DAN tidak punya histori hunian sama sekali (kamar baru, salah
     * input). Kalau sudah pernah dihuni tapi sekarang kosong, sebaiknya
     * dinonaktifkan (status 'nonaktif'), bukan dihapus — supaya histori
     * tagihan/pembayaran lama tidak kehilangan induk data kamarnya.
     */
    public function bolehDihapusPermanen(): bool
    {
        return ! $this->sedangDihuni() && ! $this->hunian()->exists();
    }
}
