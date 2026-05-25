<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Kos Firabo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body>

<aside class="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-house-fill"></i>
        </div>
        <div class="brand-text">
            <div class="brand-name">Kos Firabo</div>
            <div class="brand-sub">Admin Panel</div>
        </div>
    </a>
    <div class="sidebar-divider"></div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.penghuni.index') }}"
               class="nav-link {{ request()->routeIs('admin.penghuni.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Penghuni
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.kamar.index') }}"
               class="nav-link {{ request()->routeIs('admin.kamar.*') ? 'active' : '' }}">
                <i class="bi bi-door-closed"></i> Kamar
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.tagihan.index') }}"
               class="nav-link {{ request()->routeIs('admin.tagihan.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Tagihan
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.pembayaran.index') }}"
               class="nav-link {{ request()->routeIs('admin.pembayaran.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-back"></i> Pembayaran
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.jadwal.index') }}"
               class="nav-link {{ request()->routeIs('admin.jadwal.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> Pengaturan Jadwal
            </a>
        </li>
    </ul>

    <div class="sidebar-bottom">
        <a href="{{ route('admin.profil.edit') }}"
           class="nav-link {{ request()->routeIs('admin.profil.*') ? 'active' : '' }}">
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
   {{-- Mobile Topbar (Hanya Tampil di Mobile) --}}
    <div class="d-flex d-md-none justify-content-end p-3 bg-white shadow-sm mb-3">
        <!-- Inisialisasi state Alpine.js di container utama -->
        <div x-data="{ open: false }" @click.away="open = false" style="position: relative; display: inline-block;">

            <!-- Tombol Utama (Pemicu) - Tanpa border, bg transparan -->
            <button @click="open = !open" 
                    type="button" 
                    class="d-flex align-items-center gap-2 border-0 bg-transparent text-dark p-1"
                    style="cursor: pointer; outline: none;">
                
                <!-- Icon Profil -->
                <i class="bi bi-person-circle fs-5 text-secondary"></i>
                
                <!-- Nama Pengguna Dinamis Laravel -->
                <span class="fw-medium" style="font-size: 10px;">{{ auth()->user()->name ?? 'Admin' }}</span>
                
                <!-- Icon Panah (Berputar saat dropdown terbuka) -->
                <i class="bi bi-chevron-down text-muted ms-1" style="transition: transform 0.2s; font-size: 0.85rem;" :style="open ? 'transform: rotate(180deg);' : ''"></i>
            </button>

            <!-- Menu Dropdown -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="dropdown-menu show shadow-sm border-0"
                 style="position: absolute; right: 0; left: auto; top: 100%; margin-top: 0.5rem; display: none; min-width: 160px; z-index: 1050; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                
                <!-- Section 1: Profil -->
                <a href="{{ route('admin.profil.edit') }}" class="dropdown-item py-2">
                    <i class="bi bi-person me-2"></i> Profil Saya
                </a>

                <hr class="dropdown-divider my-1">

                <!-- Section 2: Logout -->
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger py-2">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </div>
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

    {{-- Mobile Bottom Nav --}}
    <nav class="bottom-nav">
        <a href="{{ route('admin.dashboard') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('admin.penghuni.index') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.penghuni.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i><span>Penghuni</span>
        </a>
        <a href="{{ route('admin.kamar.index') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.kamar.*') ? 'active' : '' }}">
            <i class="bi bi-door-closed"></i><span>Kamar</span>
        </a>
        <a href="{{ route('admin.tagihan.index') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.tagihan.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i><span>Tagihan</span>
        </a>
        <a href="{{ route('admin.pembayaran.index') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.pembayaran.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card-2-back"></i><span>Bayar</span>
        </a>
        <a href="{{ route('admin.jadwal.index') }}"
           class="bottom-nav-item {{ request()->routeIs('admin.jadwal.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i><span>Jadwal</span>
        </a>
    </nav>
</div>

@livewireScripts
</body>
</html>