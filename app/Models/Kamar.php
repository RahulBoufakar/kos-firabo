<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    protected $table = 'tb_kamar';
    protected $primaryKey = 'kamar_id';

    protected $fillable = [
        'nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas', 'status_kamar',
    ];

    public function hunian()
    {
        return $this->hasMany(Hunian::class, 'kamar_id', 'kamar_id');
    }
}
