<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('admin.profil', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($request->input('_form_type') === 'password') {
            return $this->updatePassword($request, $user);
        }

        return $this->updateProfil($request, $user);
    }

    // -------------------------------------------------------

    private function updateProfil(Request $request, $user)
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'no_wa' => ['nullable', 'string', 'max:20'],
        ], [
            // Validasi Nama
            'name.required' => 'Nama admin wajib diisi.',
            'name.string'   => 'Format nama tidak valid.',
            'name.max'      => 'Nama maksimal terdiri dari 100 karakter.',

            // Validasi Nomor WhatsApp
            'no_wa.string'  => 'Format nomor WhatsApp tidak valid.',
            'no_wa.max'     => 'Nomor WhatsApp maksimal 20 karakter.',
        ]);

        $user->update($request->only('name', 'no_wa'));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    private function updatePassword(Request $request, $user)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password tidak sesuai.',
            'password.min'              => 'Password baru minimal harus 8 karakter.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
