<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hunian extends Model
{
    protected $table = 'tb_hunian';
    protected $primaryKey = 'hunian_id';

    protected $fillable = [
        'user_id', 'kamar_id', 'tanggal_masuk', 'tanggal_keluar', 'status_hunian',
    ];

    protected $casts = [
        'tanggal_masuk'  => 'date',
        'tanggal_keluar' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'kamar_id', 'kamar_id');
    }

    /**
     * Jadwal tagihan (One-to-One per hunian, dibuat saat registrasi).
     */
    public function jadwalTagihan(): HasOne
    {
        return $this->hasOne(JadwalTagihan::class, 'hunian_id', 'hunian_id');
    }
 
    /**
     * Semua tagihan yang dihasilkan dari hunian ini.
     */

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'hunian_id', 'hunian_id');
    }

    /**
     * Total nominal tagihan yang belum lunas (belum_bayar + terlambat) dari hunian ini.
     * Dipakai untuk deteksi "piutang macet" saat penghuni dinonaktifkan/kabur.
     */
    public function totalBelumLunas(): float
    {
        return (float) $this->tagihan()
            ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
            ->sum('nominal');
    }

    /**
     * Tutup hunian ini secara aman: tandai selesai, matikan jadwal tagihan
     * terkait, DAN konversi tagihan yang masih belum lunas jadi 'piutang'
     * kalau ada — mengikuti pola yang sudah dipakai di delete() penghuni.
     * Dipanggil saat kamar diubah ke kosong/nonaktif dari sisi Kamar,
     * bukan dari sisi Penghuni.
     *
     * @return float total piutang yang terbentuk dari penutupan ini (0 kalau lunas semua)
     */
    public function tutupAman(): float
    {
        $piutang = $this->totalBelumLunas();

        if ($piutang > 0) {
            $this->tagihan()
                ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
                ->update(['status_tagihan' => 'piutang']);
        }

        $this->jadwalTagihan?->update(['status_jadwal' => 'nonaktif']);

        $this->update([
            'status_hunian'  => 'selesai',
            'tanggal_keluar' => $this->tanggal_keluar ?? now(),
        ]);

        return $piutang;
    }
}
