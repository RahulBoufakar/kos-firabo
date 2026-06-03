<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Penghuni;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── Guest ───────────────────────────────────────────────
Route::get('/', function(){
    if (Auth::check()) {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('penghuni.dashboard');
    }
    return redirect()->route('login');
});

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Auth routes (Breeze)
require __DIR__.'/auth.php';

// ─── Admin ───────────────────────────────────────────────
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('kamar', Admin\KamarController::class);

        Route::resource('penghuni', Admin\PenghuniController::class);

        Route::resource('tagihan', Admin\TagihanController::class)
            ->only(['index', 'show']);

        Route::resource('pembayaran', Admin\PembayaranController::class)
            ->only(['index', 'store']);

        Route::resource('jadwal', Admin\JadwalTagihanController::class)
            ->only(['index', 'edit', 'update']);

        Route::get('/profil', [Admin\ProfilController::class, 'edit'])
            ->name('profil.edit');
        Route::patch('/profil', [Admin\ProfilController::class, 'update'])
            ->name('profil.update');
    });

// ─── Penghuni ─────────────────────────────────────────────
Route::prefix('penghuni')
    ->name('penghuni.')
    ->middleware(['auth', 'role:penghuni'])
    ->group(function () {

        Route::get('/dashboard', [Penghuni\DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/tagihan', [Penghuni\TagihanController::class, 'index'])
            ->name('tagihan.index');
        Route::get('/tagihan/{tagihan}', [Penghuni\TagihanController::class, 'show'])
            ->name('tagihan.show');

        Route::get('/pembayaran', [Penghuni\PembayaranController::class, 'index'])
            ->name('pembayaran.index');

        // Midtrans callback
        Route::post('/pembayaran/callback', [Penghuni\PembayaranController::class, 'callback'])
            ->name('pembayaran.callback')
            ->withoutMiddleware(['auth', 'role:penghuni']);

        // Invalidasi snap token expired — dipanggil via fetch() dari Snap.js onError
        Route::post('/pembayaran/{tagihan}/invalidate-token', [
                Penghuni\PembayaranController::class,
                'invalidateToken',])
            ->name('pembayaran.invalidate-token');

        Route::get('/profil', [Penghuni\ProfilController::class, 'edit'])
            ->name('profil.edit');
        Route::patch('/profil', [Penghuni\ProfilController::class, 'update'])
            ->name('profil.update');
    });