<?php

namespace App\Http\Controllers\Penghuni;

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

        return view('penghuni.profil', compact('user'));
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
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'no_wa' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($request->only('name', 'email', 'no_wa'));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    private function updatePassword(Request $request, $user)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
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
