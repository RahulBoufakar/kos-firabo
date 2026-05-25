@extends('layouts.guest')
@section('title', 'Lupa Password')

@section('content')
<div class="guest-card">

    <div class="guest-logo">
        <i class="bi bi-envelope-paper"></i>
    </div>
    <h1 class="guest-title">Lupa Password?</h1>
    <p class="guest-subtitle">Kami akan kirimkan link reset ke emailmu</p>

    @if(session('status'))
        <div style="display:flex; align-items:center; gap:.5rem;
                    background:#dcfce7; border:1px solid #86efac; border-radius:10px;
                    padding:.75rem 1rem; margin-bottom:1.25rem; font-size:13.5px; color:#166534;">
            <i class="bi bi-check-circle-fill" style="flex-shrink:0;"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label class="form-label" style="font-size:14px; font-weight:500">Alamat Email</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="firabo-input"
                       placeholder="email@kamu.com"
                       autofocus
                       required>
            </div>
            @error('email')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-firabo w-100 justify-content-center py-2">
            Kirim Link Reset <i class="bi bi-send"></i>
        </button>
    </form>

    <hr class="my-3">
    <p class="text-center mb-0" style="font-size:13px; color:#6b7280">
        Ingat passwordnya?
        <a href="{{ route('login') }}"
           style="color:var(--firabo-primary); font-weight:500; text-decoration:none">
            Kembali login
        </a>
    </p>

    <p class="text-center mt-3 mb-0" style="font-size:12px; color:#9ca3af">
        <i class="bi bi-shield-check me-1"></i>Sistem Manajemen Aman & Terenkripsi
    </p>
</div>
@endsection