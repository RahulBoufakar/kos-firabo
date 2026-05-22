<?php

namespace App\Http\Controllers\Auth;

use App\Events\PenghuniTerdaftar;
use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
     /**
     * Tampilkan form registrasi publik.
     * Kirim daftar kamar yang berstatus 'tersedia' ke view.
     */
    public function create(): View
    {
        $kamarTersedia = Kamar::where('status_kamar', 'tersedia')
            ->orderBy('nomor_kamar')
            ->get(['kamar_id', 'nomor_kamar', 'tipe_kamar', 'harga_sewa']);
 
        return view('auth.register', compact('kamarTersedia'));
    }
 
    /**
     * Proses registrasi penghuni baru dari form publik.
     *
     * Validasi mencakup:
     * - Field standar Breeze (nama, email, password)
     * - Field tambahan: no_wa, kamar_id
     * - Kamar wajib berstatus 'tersedia' saat submit (race-condition guard)
     *
     * Setelah user dibuat, fire dua event:
     * 1. Registered (Laravel built-in) — untuk email verification jika dipakai
     * 2. PenghuniTerdaftar (custom) — untuk auto-create hunian + jadwal tagihan
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:100', 'unique:users,email'],
            'no_wa'        => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'kamar_id'     => ['required', 'integer', 'exists:tb_kamar,kamar_id'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.unique'          => 'Email sudah digunakan, gunakan email lain.',
            'no_wa.required'        => 'Nomor WhatsApp wajib diisi.',
            'no_wa.regex'           => 'Format nomor WhatsApp tidak valid.',
            'kamar_id.required'     => 'Silakan pilih kamar.',
            'kamar_id.exists'       => 'Kamar yang dipilih tidak ditemukan.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ]);
 
        // Guard: pastikan kamar masih tersedia saat submit
        // (mencegah race condition jika dua orang mendaftar bersamaan)
        $kamar = Kamar::where('kamar_id', $request->kamar_id)
            ->where('status_kamar', 'tersedia')
            ->first();
 
        if (! $kamar) {
            return back()
                ->withInput()
                ->withErrors(['kamar_id' => 'Kamar yang Anda pilih sudah tidak tersedia. Silakan pilih kamar lain.']);
        }
 
        // Buat user baru dengan role penghuni
        $user = User::create([
            'nama_lengkap' => $request->nama_lengkap,
            'email'        => $request->email,
            'no_wa'        => $request->no_wa,
            'password'     => Hash::make($request->password),
            'role'         => 'penghuni',
            'status_akun'  => 'aktif',
        ]);
 
        // Fire event Registered bawaan Laravel (untuk email verification)
        event(new Registered($user));
 
        // Fire event custom → listener akan buat hunian + jadwal tagihan
        event(new PenghuniTerdaftar($user, $kamar));
 
        // Login otomatis setelah register
        Auth::login($user);
 
        return redirect()->route('penghuni.dashboard');
    }
}
