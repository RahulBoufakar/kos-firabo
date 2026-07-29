<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekStatusAkunPenghuni
{
    /**
     * Blokir akses penghuni yang statusnya bukan 'aktif' (nonaktif/kabur)
     * ke halaman-halaman normal, arahkan ke halaman Pelunasan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::user()->isAktif()) {
            return redirect()->route('penghuni.akun-nonaktif');
        }

        return $next($request);
    }
}
