<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Penghuni\PembayaranController;

// API route for Midtrans callback
Route::post('/penghuni/pembayaran/callback', [PembayaranController::class, 'callback'])
    ->name('api.pembayaran.callback');