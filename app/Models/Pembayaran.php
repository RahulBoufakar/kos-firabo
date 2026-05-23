<?php

namespace App\Models;

use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Admin yang mencatat pembayaran manual.
     * Null untuk pembayaran online via Midtrans.
     */
    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
