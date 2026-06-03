@extends('layouts.guest')
@section('title', '403 — Akses Ditolak')

@section('content')
<div class="guest-card" style="max-width: 460px; text-align: center;">

    <div class="error-code">403</div>

    <div class="guest-logo" style="margin: 1rem auto;">
        <i class="bi bi-shield-lock"></i>
    </div>

    <h1 class="guest-title">Akses Ditolak</h1>
    <p class="guest-subtitle">
        Kamu tidak memiliki izin untuk mengakses halaman ini.
    </p>

    @if($exception->getMessage())
        <div style="
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 1.25rem;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        ">
            <i class="bi bi-info-circle" style="flex-shrink: 0; margin-top: 1px;"></i>
            <span>{{ $exception->getMessage() }}</span>
        </div>
    @endif

    <div class="d-flex flex-column gap-2 mt-3">
        <a href="{{ auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('penghuni.dashboard')) : route('login') }}"
           class="btn-firabo justify-content-center">
            <i class="bi bi-house"></i> Ke Dashboard
        </a>
        @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-firabo-outline w-100 justify-content-center">
                <i class="bi bi-box-arrow-right"></i> Login dengan Akun Lain
            </button>
        </form>
        @endauth
    </div>

</div>
@endsection

@push('styles')
<style>
.error-code {
    font-size: 5rem;
    font-weight: 800;
    color: var(--firabo-danger);
    line-height: 1;
    letter-spacing: -4px;
    opacity: 0.12;
    margin-bottom: -1rem;
}
</style>
@endpush