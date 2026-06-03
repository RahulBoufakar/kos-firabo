@extends('layouts.guest')
@section('title', '404 — Halaman Tidak Ditemukan')

@section('content')
<div class="guest-card" style="max-width: 460px; text-align: center;">

    <div class="error-code">404</div>

    <div class="guest-logo" style="margin: 1rem auto;">
        <i class="bi bi-map"></i>
    </div>

    <h1 class="guest-title">Halaman Tidak Ditemukan</h1>
    <p class="guest-subtitle">
        Halaman yang kamu cari tidak ada atau sudah dipindahkan.
    </p>

    <div class="d-flex flex-column gap-2 mt-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}"
           class="btn-firabo justify-content-center">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
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
    color: var(--firabo-primary);
    line-height: 1;
    letter-spacing: -4px;
    opacity: 0.15;
    margin-bottom: -1rem;
}
</style>
@endpush