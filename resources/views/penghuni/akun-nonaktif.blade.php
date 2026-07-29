@extends('layouts.guest')
@section('title', 'Akun Nonaktif')

@section('content')
<div class="guest-card" style="max-width: 460px; text-align: center;">

    <div class="guest-logo" style="background:#fee2e2; color:#991b1b; margin: 0 auto 1rem;">
        <i class="bi bi-lock-fill"></i>
    </div>

    <h1 class="guest-title">Akun Anda Nonaktif</h1>
    <p class="guest-subtitle">
        Akun ini dinonaktifkan karena masih memiliki tagihan yang belum
        diselesaikan. Silakan hubungi pengelola kos untuk informasi lebih
        lanjut dan proses pelunasan.
    </p>

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
        <span>Akun akan diaktifkan kembali oleh admin setelah tagihan yang tertunggak diselesaikan.</span>
    </div>

    <div class="d-flex flex-column gap-2">
        <a href="{{ route('contact') }}" class="btn-firabo justify-content-center">
            <i class="bi bi-headset"></i> Hubungi Kami
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-firabo-outline w-100 justify-content-center">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

</div>
@endsection