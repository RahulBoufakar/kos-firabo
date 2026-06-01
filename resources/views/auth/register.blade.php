@extends('layouts.guest')
@section('title', 'Daftar Akun — Firabo Kos')

@section('content')
<div class="guest-card" style="max-width: 520px;">

    {{-- Header --}}
    <div class="text-center mb-4">
        <div class="guest-logo mb-3">
            <i class="bi bi-house-heart-fill"></i>
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--firabo-primary-dark);">Daftar Akun Penghuni</h4>
        <p class="text-muted small mb-0">Lengkapi data diri dan pilih kamar yang tersedia</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- Section: Data Diri --}}
        <p class="text-uppercase fw-semibold small mb-2"
           style="color: var(--firabo-primary); letter-spacing: .06em;">
            <i class="bi bi-person-fill me-1"></i> Data Diri
        </p>

        {{-- Nama Lengkap — FIX: name="name" (bukan nama_lengkap) --}}
        <div class="mb-3">
            <label for="name" class="form-label fw-medium">Nama Lengkap</label>
            <div class="input-icon-wrap">
                <i class="bi bi-person"></i>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="firabo-input @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    placeholder="Nama sesuai KTP"
                    required
                    autofocus
                >
            </div>
            @error('name')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label fw-medium">Email</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="firabo-input @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="email@contoh.com"
                    required
                >
            </div>
            @error('email')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        {{-- No WhatsApp --}}
        <div class="mb-3">
            <label for="no_wa" class="form-label fw-medium">Nomor WhatsApp</label>
            <div class="input-icon-wrap">
                <i class="bi bi-whatsapp" style="color:#25d366;"></i>
                <input
                    type="tel"
                    id="no_wa"
                    name="no_wa"
                    class="firabo-input @error('no_wa') is-invalid @enderror"
                    value="{{ old('no_wa') }}"
                    placeholder="081234567890"
                    required
                >
            </div>
            @error('no_wa')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
            <div class="form-text" style="font-size:11px; color:#9ca3af; margin-top:4px;">
                Digunakan untuk notifikasi tagihan via email.
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Password</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="firabo-input @error('password') is-invalid @enderror"
                    placeholder="Minimal 8 karakter"
                    required
                    autocomplete="new-password"
                >
            </div>
            @error('password')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-medium">Konfirmasi Password</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock-fill"></i>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="firabo-input"
                    placeholder="Ulangi password"
                    required
                    autocomplete="new-password"
                >
            </div>
        </div>

        <hr class="my-4">

        {{-- Section: Data Hunian --}}
        <p class="text-uppercase fw-semibold small mb-2"
           style="color: var(--firabo-primary); letter-spacing: .06em;">
            <i class="bi bi-door-open-fill me-1"></i> Pilih Kamar
        </p>

        {{--
            Pilih Kamar
            FIX: variabel $kamarTersedia (sesuai controller — bukan $kamars)
        --}}
        <div class="mb-4">
            <label for="kamar_id" class="form-label fw-medium">Kamar yang Tersedia</label>
            <select
                id="kamar_id"
                name="kamar_id"
                class="firabo-input @error('kamar_id') is-invalid @enderror"
                style="height:44px;"
                required
            >
                <option value="" disabled selected>— Pilih kamar —</option>
                @forelse($kamarTersedia as $kamar)
                    <option value="{{ $kamar->kamar_id }}"
                            {{ old('kamar_id') == $kamar->kamar_id ? 'selected' : '' }}>
                        Kamar {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
                        (Rp {{ number_format($kamar->harga_sewa, 0, ',', '.') }}/bln)
                    </option>
                @empty
                    <option value="" disabled>Tidak ada kamar tersedia saat ini</option>
                @endforelse
            </select>
            @error('kamar_id')
                <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
            @enderror
            <div class="form-text" style="font-size:11px; color:#9ca3af; margin-top:4px;">
                Jadwal tagihan akan diatur otomatis oleh admin setelah pendaftaran.
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-firabo w-100 justify-content-center py-2">
            <i class="bi bi-person-check-fill me-2"></i>Daftar Sekarang
        </button>
    </form>

    <hr class="my-3">
    <p class="text-center mb-0" style="font-size:13px; color:#6b7280">
        Sudah punya akun?
        <a href="{{ route('login') }}"
           style="color:var(--firabo-primary); font-weight:500; text-decoration:none">
            Masuk di sini
        </a>
    </p>

    <p class="text-center mt-3 mb-0" style="font-size:12px; color:#9ca3af">
        <i class="bi bi-shield-check me-1"></i>Sistem Manajemen Aman & Terenkripsi
    </p>
</div>
@endsection