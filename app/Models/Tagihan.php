<?php

namespace App\Models;

use App\Models\Hunian;
use App\Models\JadwalTagihan;
use App\Models\Pembayaran;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    protected $table = 'tb_tagihan';
    protected $primaryKey = 'tagihan_id';

    protected $fillable = [
        'hunian_id', 'jadwal_id', 'nominal',
        'tanggal_tagihan', 'tanggal_jatuh_tempo', 'status_tagihan',
    ];

    protected $casts = [
        'tanggal_tagihan'      => 'date',
        'tanggal_jatuh_tempo'  => 'date',
    ];

    public function hunian()
    {
        return $this->belongsTo(Hunian::class, 'hunian_id', 'hunian_id');
    }

    public function jadwalTagihan()
    {
        return $this->belongsTo(JadwalTagihan::class, 'jadwal_id', 'jadwal_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'tagihan_id', 'tagihan_id');
    }

    // ── Helper ──────────────────────────────────────────────
 
    /**
     * Ambil pembayaran dengan status sukses (jika sudah lunas).
     */
    public function pembayaranLunas(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'tagihan_id', 'tagihan_id')
            ->where('status_pembayaran', 'sukses');
    }
 
    /**
     * Apakah tagihan ini sudah melewati jatuh tempo?
     */
    public function isTerlambat(): bool
    {
        return $this->status_tagihan !== 'lunas'
            && Carbon::today()->gt($this->tanggal_jatuh_tempo);
    }
 
    /**
     * Hitung sisa hari menuju jatuh tempo (negatif = sudah lewat).
     */
    public function sisaHari(): int
    {
        return Carbon::today()->diffInDays($this->tanggal_jatuh_tempo, false);
    }

    /**
     * Scope: hanya tagihan yang belum lunas (belum_bayar + terlambat).
     * Dipakai di dashboard, mode "jatuh tempo terdekat", dan Laporan Tagihan Belum Dibayar.
     */
    public function scopeBelumLunas($query)
    {
        return $query->whereIn('status_tagihan', ['belum_bayar', 'terlambat']);
    }

    /**
     * Scope: hanya tagihan yang hunian-nya terhubung ke penghuni berstatus AKTIF.
     * Dipakai untuk memisahkan "tagihan yang masih realistis ditagih" dari piutang
     * penghuni yang sudah kabur (itu masuk laporan Piutang Macet terpisah).
     */
    public function scopeMilikPenghuniAktif($query)
    {
        return $query->whereHas('hunian.user', fn($q) => $q->where('status_akun', 'aktif'));
    }

    /**
     * Scope: tagihan yang statusnya sudah dikonversi jadi piutang macet
     * (penghuninya kabur sebelum lunas). Terpisah dari belumLunas() secara sengaja.
     */
    public function scopePiutang($query)
    {
        return $query->where('status_tagihan', 'piutang');
    }
}
