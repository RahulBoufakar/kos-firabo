<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TagihanController extends Controller
{
    /**
     * Daftar semua tagihan milik penghuni yang sedang login.
     */
    public function index(): View
    {
        $hunian = Auth::user()->hunianAktif;
 
        $tagihan = $hunian
            ? Tagihan::where('hunian_id', $hunian->hunian_id)
                ->orderByDesc('tanggal_tagihan')
                ->paginate(10)
            : collect();
 
        return view('penghuni.tagihan.index', compact('tagihan', 'hunian'));
    }
 
    /**
     * Detail satu tagihan + tombol bayar Midtrans.
     *
     * Mengembalikan snap_token ke view agar Snap.js bisa
     * membuka popup pembayaran langsung dari halaman ini.
     */
    public function show(Tagihan $tagihan, MidtransService $midtrans): View
    {
        // Pastikan tagihan ini milik penghuni yang login
        Gate::authorize('view', $tagihan);
 
        // Muat relasi yang dibutuhkan view
        $tagihan->load([
            'hunian.kamar',
            'hunian.user',
            'pembayaran' => fn($q) => $q->latest(),
        ]);
 
        $snapToken  = null;
        $clientKey  = config('services.midtrans.client_key');
 
        // Sediakan snap token hanya untuk tagihan yang belum lunas
        if ($tagihan->status_tagihan !== 'lunas') {
            $snapToken = $midtrans->getOrCreateSnapToken($tagihan);
        }
 
        return view('penghuni.tagihan.show', compact('tagihan', 'snapToken', 'clientKey'));
    }
}
