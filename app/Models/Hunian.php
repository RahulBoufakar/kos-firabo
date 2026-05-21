<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function jadwalTagihan()
    {
        return $this->hasOne(JadwalTagihan::class, 'hunian_id', 'hunian_id');
    }

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'hunian_id', 'hunian_id');
    }
}
