@extends('layouts.guest')
@section('title', '500 — Terjadi Kesalahan')

@section('content')
<div class="guest-card" style="max-width: 460px; text-align: center;">

    <div class="error-code">500</div>

    <div class="guest-logo" style="margin: 1rem auto;">
        <i class="bi bi-exclamation-triangle"></i>
    </div>

    <h1 class="guest-title">Terjadi Kesalahan</h1>
    <p class="guest-subtitle">
        Server mengalami masalah saat memproses permintaan ini.
        Tim kami akan segera menanganinya.
    </p>

    <div style="
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 13px;
        color: #991b1b;
        margin-bottom: 1.25rem;
        text-align: left;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    ">
        <i class="bi bi-info-circle" style="flex-shrink: 0; margin-top: 1px;"></i>
        <span>Jika masalah berlanjut, coba muat ulang halaman atau hubungi admin.</span>
    </div>

    <div class="d-flex flex-column gap-2">
        <button onclick="window.location.reload()" class="btn-firabo justify-content-center">
            <i class="bi bi-arrow-clockwise"></i> Coba Lagi
        </button>
        <a href="{{ auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('penghuni.dashboard')) : route('login') }}"
           class="btn-firabo-outline justify-content-center">
            <i class="bi bi-house"></i> Ke Dashboard
        </a>
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