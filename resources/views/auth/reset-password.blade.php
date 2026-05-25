@extends('layouts.guest')
@section('title', 'Reset Password')

@section('content')
<div class="guest-card">

    <div class="guest-logo">
        <i class="bi bi-shield-lock"></i>
    </div>
    <h1 class="guest-title">Buat Password Baru</h1>
    <p class="guest-subtitle">Pastikan minimal 8 karakter</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label class="form-label" style="font-size:14px; font-weight:500">Alamat Email</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email"
                       name="email"
                       value="{{ old('email', $request->email) }}"
                       class="firabo-input"
                       autocomplete="username"
                       autofocus
                       required>
            </div>
            @error('email')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" style="font-size:14px; font-weight:500">Password Baru</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input type="password"
                       name="password"
                       class="firabo-input"
                       placeholder="Minimal 8 karakter"
                       autocomplete="new-password"
                       required>
            </div>
            @error('password')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label" style="font-size:14px; font-weight:500">Konfirmasi Password Baru</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock-fill"></i>
                <input type="password"
                       name="password_confirmation"
                       class="firabo-input"
                       placeholder="Ulangi password baru"
                       autocomplete="new-password"
                       required>
            </div>
            @error('password_confirmation')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-firabo w-100 justify-content-center py-2">
            Simpan Password Baru <i class="bi bi-check-lg"></i>
        </button>
    </form>

    <p class="text-center mt-3 mb-0" style="font-size:12px; color:#9ca3af">
        <i class="bi bi-shield-check me-1"></i>Sistem Manajemen Aman & Terenkripsi
    </p>
</div>
@endsection