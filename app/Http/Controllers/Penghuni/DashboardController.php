<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\Hunian;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $hunian = Hunian::where('user_id', Auth::id())
            ->where('status_hunian', 'aktif')
            ->with(['kamar'])
            ->first();

        $tagihanAktif = null;
        if ($hunian) {
            $tagihanAktif = Tagihan::where('hunian_id', $hunian->hunian_id)
                ->whereIn('status_tagihan', ['belum_bayar', 'terlambat'])
                ->orderBy('tanggal_jatuh_tempo', 'asc')
                ->first();
        }

        return view('penghuni.dashboard', compact('hunian', 'tagihanAktif'));
    }
}
