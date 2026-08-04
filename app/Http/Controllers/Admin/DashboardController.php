<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
// use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_kamar'       => Kamar::count(),
            'penghuni_aktif'    => User::where('role', 'penghuni')
                                       ->where('status_akun', 'aktif')->count(),
            'tagihan_belum'     => Tagihan::where('status_tagihan', 'belum_bayar')
                                          ->orWhere('status_tagihan', 'terlambat')->count(),
            'pemasukan'         => Pembayaran::where('status_pembayaran', 'sukses')
                                             ->whereMonth('created_at', now()->month)
                                             ->sum('nominal_bayar'),
            'kamar_tersedia'    => Kamar::where('status_kamar', 'tersedia')->count(),
        ];

        $tagihanDekat = Tagihan::with(['hunian.user', 'hunian.kamar'])
            ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
            ->orderBy('tanggal_jatuh_tempo', 'desc')
            ->limit(5)
            ->get();

        $transaksiTerbaru = Pembayaran::with(['tagihan.hunian.user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'tagihanDekat', 'transaksiTerbaru'));
    }
}
