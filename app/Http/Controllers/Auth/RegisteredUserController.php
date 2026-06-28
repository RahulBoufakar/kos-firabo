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
     * Menangani proses registrasi penghuni baru.
     *
     * Penjelasan khusus:
     * - Melakukan validasi input (name, email, no_wa, kamar_id, password) dan
     *   mengembalikan pesan error kustom jika gagal.
     * - Memastikan kamar yang dipilih masih berstatus 'tersedia' untuk
     *   mencegah race condition sebelum membuat user.
     * - Jika valid, membuat record User dengan role 'penghuni' dan status_akun
     *   'aktif', lalu memicu event Registered dan event PenghuniTerdaftar
     *   (yang bertanggung jawab membuat hunian & jadwal tagihan secara terpisah).
     * - Melakukan login otomatis untuk user yang baru dibuat dan mengarahkan
     *   ke route penghuni.dashboard.
     *
     * Catatan: field terkait tanggal (tanggal_masuk / tanggal_generate /
     * tanggal_jatuh_tempo) tidak divalidasi di sini karena dijadwalkan oleh
     * listener terkait.
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
            // Bagian ini akan menggantikan {{ $message }} di file Blade Anda
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format email tidak valid (contoh: nama@email.com).',
            'email.unique'       => 'Email ini sudah terdaftar. Silakan gunakan email lain.',
            'no_wa.required'     => 'Nomor WhatsApp wajib diisi.',
            'no_wa.numeric'      => 'Nomor WhatsApp harus berupa angka.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password harus memiliki minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'kamar_id.required'  => 'Anda harus memilih kamar yang tersedia.',
            'kamar_id.exists'    => 'Kamar yang dipilih tidak valid atau tidak tersedia.',
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