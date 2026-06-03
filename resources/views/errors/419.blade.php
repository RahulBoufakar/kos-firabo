@extends('layouts.guest')
@section('title', '419 — Sesi Kedaluwarsa')

@section('content')
<div class="guest-card" style="max-width: 460px; text-align: center;">

    <div class="error-code">419</div>

    <div class="guest-logo" style="margin: 1rem auto;">
        <i class="bi bi-clock-history"></i>
    </div>

    <h1 class="guest-title">Sesi Kedaluwarsa</h1>
    <p class="guest-subtitle">
        Halaman ini sudah terlalu lama dibuka. Muat ulang untuk melanjutkan.
    </p>

    <div style="
        background: var(--firabo-primary-light);
        border: 1px solid var(--firabo-border);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 13px;
        color: #374151;
        margin-bottom: 1.25rem;
        text-align: left;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    ">
        <i class="bi bi-lightbulb" style="color: var(--firabo-primary); flex-shrink: 0; margin-top: 1px;"></i>
        <span>Ini bisa terjadi jika kamu meninggalkan halaman terlalu lama atau membuka banyak tab sekaligus.</span>
    </div>

    <div class="d-flex flex-column gap-2">
        <button onclick="window.location.reload()" class="btn-firabo justify-content-center">
            <i class="bi bi-arrow-clockwise"></i> Muat Ulang Halaman
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
    color: var(--firabo-warning);
    line-height: 1;
    letter-spacing: -4px;
    opacity: 0.2;
    margin-bottom: -1rem;
}
</style>
@endpush