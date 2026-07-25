@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="guest-card">
    <div class="guest-logo">
        <i class="bi bi-house-fill"></i>
    </div>
    <h1 class="guest-title">Kos Firabo</h1>
    <p class="guest-subtitle">Manajemen Properti Pintar</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" style="font-size:14px; font-weight:500">Email</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="firabo-input" placeholder="admin@kosfirabo.com" required autofocus>
            </div>
            @error('email')
                <div class="text-danger mt-1" style="font-size:12px">
                    {{ $message === 'The email field is required.' ? 'Email harus diisi.' : ($message === 'The email must be a valid email address.' ? 'Email harus berupa alamat email yang valid.' : ($message === 'These credentials do not match our records.' ? 'Email atau kata sandi salah.' : $message)) }}
                </div>
            @enderror
        </div>

        <div class="mb-2">
            <label class="form-label" style="font-size:14px; font-weight:500">Password</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" name="password"
                       class="firabo-input" placeholder="••••••••" required>
            </div>
            @error('password')
                <div class="text-danger mt-1" style="font-size:12px">
                    {{ $message === 'The password field is required.' ? 'Kata sandi harus diisi.' : ($message === 'The password must be at least 8 characters.' ? 'Kata sandi minimal 8 karakter.' : $message) }}
                </div>
            @enderror
        </div>

        <div class="text-end mb-3">
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="font-size:13px; color:var(--firabo-primary); text-decoration:none">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-firabo w-100 justify-content-center py-2">
            Login <i class="bi bi-arrow-right"></i>
        </button>

        <hr class="my-3">
        {{-- <p class="text-center mb-0" style="font-size:13px; color:#6b7280">
            Belum punya akun?
            <a href="{{ route('register') }}"
               style="color:var(--firabo-primary); font-weight:500; text-decoration:none">
                Daftar di sini
            </a>
        </p> --}}
    </form>

    {{-- Link Hubungi Kami --}}
    <div class="text-center mt-3">
        <a href="{{ route('contact') }}"
           style="font-size:12px; color:#9ca3af; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;"
           onmouseover="this.style.color='var(--firabo-primary)'"
           onmouseout="this.style.color='#9ca3af'">
            <i class="bi bi-headset"></i> Butuh bantuan? Hubungi Kami
        </a>
    </div>

    <p class="text-center mt-3 mb-0" style="font-size:12px; color:#9ca3af">
        <i class="bi bi-shield-check me-1"></i>Sistem Manajemen Aman & Terenkripsi
    </p>
</div>
@endsection