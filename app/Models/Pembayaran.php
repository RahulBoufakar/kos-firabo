<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'tb_pembayaran';
    protected $primaryKey = 'pembayaran_id';
    public $updatedAt = false; // tb_pembayaran hanya punya created_at

    protected $fillable = [
        'tagihan_id', 'user_id', 'metode_pembayaran', 'nominal_bayar',
        'tanggal_bayar', 'status_pembayaran', 'snap_token', 'transaction_id',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id', 'tagihan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
