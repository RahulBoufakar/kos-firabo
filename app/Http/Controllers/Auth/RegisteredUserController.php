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
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan form registrasi publik.
     * Kirim daftar kamar berstatus 'tersedia' ke view.
     */
    public function create(): View
    {
        $kamarTersedia = Kamar::where('status_kamar', 'tersedia')
            ->orderBy('nomor_kamar')
            ->get(['kamar_id', 'nomor_kamar', 'tipe_kamar', 'harga_sewa']);

        return view('auth.register', compact('kamarTersedia'));
    }

    /**
     * Proses registrasi penghuni baru.
     *
     * Fix:
     * - 'name' (bukan 'nama_lengkap') — sesuai kolom tabel users
     * - $kamarTersedia (bukan $kamars) — konsisten dengan controller
     * - Hapus field tanggal_masuk / tanggal_generate / tanggal_jatuh_tempo
     *   dari validasi — field itu ada di view lama, tapi tidak dipakai.
     *   Jadwal dibuat oleh listener BuatHunianDanJadwalTagihan dengan default.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:100', 'unique:users,email'],
            'no_wa'    => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'kamar_id' => ['required', 'integer', 'exists:tb_kamar,kamar_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.unique'       => 'Email sudah digunakan, gunakan email lain.',
            'no_wa.required'     => 'Nomor WhatsApp wajib diisi.',
            'no_wa.regex'        => 'Format nomor WhatsApp tidak valid.',
            'kamar_id.required'  => 'Silakan pilih kamar.',
            'kamar_id.exists'    => 'Kamar yang dipilih tidak ditemukan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Guard race-condition: kamar harus masih tersedia saat submit
        $kamar = Kamar::where('kamar_id', $request->kamar_id)
            ->where('status_kamar', 'tersedia')
            ->first();

        if (! $kamar) {
            return back()
                ->withInput()
                ->withErrors(['kamar_id' => 'Kamar yang Anda pilih sudah tidak tersedia. Silakan pilih kamar lain.']);
        }

        $user = User::create([
            'name'        => $request->name,        // FIX: dulu 'nama_lengkap'
            'email'       => $request->email,
            'no_wa'       => $request->no_wa,
            'password'    => Hash::make($request->password),
            'role'        => 'penghuni',
            'status_akun' => 'aktif',
        ]);

        event(new Registered($user));
        event(new PenghuniTerdaftar($user, $kamar));

        Auth::login($user);

        return redirect()->route('penghuni.dashboard');
    }
}