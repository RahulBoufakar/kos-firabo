@extends('layouts.penghuni')
@section('title', 'Profil')
@section('content')

<div class="page-header">
    <h1 class="page-title">Profil Saya</h1>
    <p class="page-subtitle">Kelola informasi akun kamu.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="firabo-card">
            <h6 class="card-title mb-3">Informasi Akun</h6>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('penghuni.profil.update') }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Nama Lengkap
                    </label>
                    <input type="text" value="{{ $user->name }}"
                           class="firabo-input" disabled
                           style="background:#f9fafb; color:#9ca3af">
                    <small class="text-muted" style="font-size:11px">
                        Nama hanya dapat diubah oleh admin
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:14px; font-weight:500">Email</label>
                    <input type="email" value="{{ $user->email }}"
                           class="firabo-input" disabled
                           style="background:#f9fafb; color:#9ca3af">
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        No. WhatsApp
                    </label>
                    <input type="text" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}"
                           class="firabo-input" placeholder="08123456789">
                    @error('no_wa')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-3">
                <p style="font-size:13px; font-weight:500; color:#374151">
                    Ubah Password
                    <span class="text-muted fw-normal">(kosongkan jika tidak ingin diubah)</span>
                </p>

                <div class="mb-3">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Password Saat Ini
                    </label>
                    <input type="password" name="current_password"
                           class="firabo-input" placeholder="••••••••">
                    @error('current_password')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Password Baru
                    </label>
                    <input type="password" name="password"
                           class="firabo-input" placeholder="••••••••">
                    @error('password')
                        <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:14px; font-weight:500">
                        Konfirmasi Password Baru
                    </label>
                    <input type="password" name="password_confirmation"
                           class="firabo-input" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-firabo">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

@endsection