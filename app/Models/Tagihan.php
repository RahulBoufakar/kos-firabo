<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
