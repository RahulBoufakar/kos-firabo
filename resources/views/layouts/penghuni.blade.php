<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Penghuni') — Kos Firabo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body>

<aside class="sidebar">
    <a href="{{ route('penghuni.dashboard') }}" class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-house-fill"></i>
        </div>
        <div class="brand-text">
            <div class="brand-name">Kos Firabo</div>
            <div class="brand-sub">Panel Penghuni</div>
        </div>
    </a>
    <div class="sidebar-divider"></div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="{{ route('penghuni.dashboard') }}"
               class="nav-link {{ request()->routeIs('penghuni.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> Home
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('penghuni.tagihan.index') }}"
               class="nav-link {{ request()->routeIs('penghuni.tagihan.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Tagihan
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('penghuni.pembayaran.index') }}"
               class="nav-link {{ request()->routeIs('penghuni.pembayaran.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-back"></i> Pembayaran
            </a>
        </li>
    </ul>

    <div class="sidebar-bottom">
        <a href="{{ route('penghuni.profil.edit') }}"
           class="nav-link {{ request()->routeIs('penghuni.profil.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i> Profile
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
</aside>

<div class="main-wrapper">
    <div class="topbar">
        <a href="{{ route('penghuni.dashboard') }}" class="topbar-brand">
            <i class="bi bi-house-fill"></i> Kos Firabo
        </a>
        <div class="topbar-user">
            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
        </div>
    </div>

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <nav class="bottom-nav">
        <a href="{{ route('penghuni.dashboard') }}"
           class="bottom-nav-item {{ request()->routeIs('penghuni.dashboard') ? 'active' : '' }}">
            <i class="bi bi-house"></i><span>Home</span>
        </a>
        <a href="{{ route('penghuni.tagihan.index') }}"
           class="bottom-nav-item {{ request()->routeIs('penghuni.tagihan.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i><span>Tagihan</span>
        </a>
        <a href="{{ route('penghuni.pembayaran.index') }}"
           class="bottom-nav-item {{ request()->routeIs('penghuni.pembayaran.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card-2-back"></i><span>Bayar</span>
        </a>
        <a href="{{ route('penghuni.profil.edit') }}"
           class="bottom-nav-item {{ request()->routeIs('penghuni.profil.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i><span>Profil</span>
        </a>
    </nav>
</div>

@stack('scripts')
@livewireScripts
</body>
</html>