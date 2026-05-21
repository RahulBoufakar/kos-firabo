<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalTagihan extends Model
{
    protected $table = 'tb_jadwal_tagihan';
    protected $primaryKey = 'jadwal_id';

    protected $fillable = [
        'hunian_id', 'tanggal_generate', 'tanggal_jatuh_tempo', 'status_jadwal',
    ];

    public function hunian()
    {
        return $this->belongsTo(Hunian::class, 'hunian_id', 'hunian_id');
    }

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'jadwal_id', 'jadwal_id');
    }
}
