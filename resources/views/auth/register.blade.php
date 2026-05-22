@extends('layouts.guest')

@section('title', 'Daftar Akun — Firabo Kos')

@section('content')
<div class="guest-card" style="max-width: 560px;">
    {{-- Header --}}
    <div class="text-center mb-4">
        <div class="guest-logo mb-3">
            <i class="bi bi-house-heart-fill"></i>
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--firabo-green-dark);">Daftar Akun Penghuni</h4>
        <p class="text-muted small mb-0">Lengkapi data diri dan informasi hunian Anda</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- Section: Data Diri --}}
        <p class="text-uppercase fw-semibold small mb-2" style="color: var(--firabo-green); letter-spacing: .06em;">
            <i class="bi bi-person-fill me-1"></i> Data Diri
        </p>

        {{-- Nama Lengkap --}}
        <div class="mb-3">
            <label for="nama_lengkap" class="form-label fw-medium">Nama Lengkap</label>
            <input
                type="text"
                id="nama_lengkap"
                name="nama_lengkap"
                class="form-control @error('nama_lengkap') is-invalid @enderror"
                value="{{ old('nama_lengkap') }}"
                placeholder="Nama sesuai KTP"
                required
                autofocus
            >
            @error('nama_lengkap')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label fw-medium">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                placeholder="email@contoh.com"
                required
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- No WhatsApp --}}
        <div class="mb-3">
            <label for="no_wa" class="form-label fw-medium">Nomor WhatsApp</label>
            <div class="input-group">
                <span class="input-group-text text-muted small">
                    <i class="bi bi-whatsapp me-1" style="color: #25d366;"></i> +62
                </span>
                <input
                    type="tel"
                    id="no_wa"
                    name="no_wa"
                    class="form-control @error('no_wa') is-invalid @enderror"
                    value="{{ old('no_wa') }}"
                    placeholder="08xxxxxxxxxx"
                    required
                >
                @error('no_wa')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-text">Digunakan untuk notifikasi tagihan via WhatsApp.</div>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Minimal 8 karakter"
                required
                autocomplete="new-password"
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-medium">Konfirmasi Password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                placeholder="Ulangi password"
                required
                autocomplete="new-password"
            >
        </div>

        <hr class="my-4">

        {{-- Section: Data Hunian --}}
        <p class="text-uppercase fw-semibold small mb-2" style="color: var(--firabo-green); letter-spacing: .06em;">
            <i class="bi bi-door-open-fill me-1"></i> Data Hunian
        </p>

        {{-- Pilih Kamar --}}
        <div class="mb-3">
            <label for="kamar_id" class="form-label fw-medium">Pilih Kamar</label>
            <select
                id="kamar_id"
                name="kamar_id"
                class="form-select @error('kamar_id') is-invalid @enderror"
                required
            >
                <option value="" disabled selected>— Pilih kamar tersedia —</option>
                @forelse($kamars as $kamar)
                    <option value="{{ $kamar->kamar_id }}" {{ old('kamar_id') == $kamar->kamar_id ? 'selected' : '' }}>
                        Kamar {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
                        (Rp {{ number_format($kamar->harga_sewa, 0, ',', '.') }}/bln)
                    </option>
                @empty
                    <option value="" disabled>Tidak ada kamar tersedia saat ini</option>
                @endforelse
            </select>
            @error('kamar_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Tanggal Masuk --}}
        <div class="mb-3">
            <label for="tanggal_masuk" class="form-label fw-medium">Tanggal Masuk</label>
            <input
                type="date"
                id="tanggal_masuk"
                name="tanggal_masuk"
                class="form-control @error('tanggal_masuk') is-invalid @enderror"
                value="{{ old('tanggal_masuk') }}"
                max="{{ date('Y-m-d') }}"
                required
            >
            @error('tanggal_masuk')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Jadwal Tagihan (2 kolom) --}}
        <div class="row g-3 mb-4">
            <div class="col-6">
                <label for="tanggal_generate" class="form-label fw-medium">
                    Tanggal Generate Tagihan
                    <i class="bi bi-info-circle text-muted ms-1"
                       data-bs-toggle="tooltip"
                       title="Tanggal dalam sebulan saat tagihan otomatis dibuat. Maks. 28 agar berlaku di semua bulan.">
                    </i>
                </label>
                <div class="input-group">
                    <input
                        type="number"
                        id="tanggal_generate"
                        name="tanggal_generate"
                        class="form-control @error('tanggal_generate') is-invalid @enderror"
                        value="{{ old('tanggal_generate', 1) }}"
                        min="1"
                        max="28"
                        required
                    >
                    <span class="input-group-text text-muted small">tgl/bln</span>
                    @error('tanggal_generate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-6">
                <label for="tanggal_jatuh_tempo" class="form-label fw-medium">
                    Jatuh Tempo (hari)
                    <i class="bi bi-info-circle text-muted ms-1"
                       data-bs-toggle="tooltip"
                       title="Jarak hari dari tanggal generate hingga batas pembayaran. Contoh: 7 berarti 7 hari setelah tagihan terbit.">
                    </i>
                </label>
                <div class="input-group">
                    <input
                        type="number"
                        id="tanggal_jatuh_tempo"
                        name="tanggal_jatuh_tempo"
                        class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror"
                        value="{{ old('tanggal_jatuh_tempo', 7) }}"
                        min="1"
                        max="30"
                        required
                    >
                    <span class="input-group-text text-muted small">hari</span>
                    @error('tanggal_jatuh_tempo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Info box --}}
        <div class="alert alert-info d-flex gap-2 align-items-start py-2 px-3 mb-4 small">
            <i class="bi bi-lightbulb-fill mt-1 flex-shrink-0" style="color: var(--firabo-green);"></i>
            <div>
                <strong>Contoh:</strong> Generate tgl <strong>1</strong>, jatuh tempo <strong>7 hari</strong>
                → tagihan terbit tiap tgl 1, batas bayar tgl 8 bulan yang sama.
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-firabo w-100 py-2 fw-semibold">
            <i class="bi bi-person-check-fill me-2"></i>Daftar Sekarang
        </button>
    </form>

    {{-- Footer link --}}
    <p class="text-center text-muted small mt-4 mb-0">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="fw-semibold" style="color: var(--firabo-green);">Masuk di sini</a>
    </p>
</div>

@push('scripts')
<script>
    // Inisialisasi Bootstrap Tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipEls.forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    });
</script>
@endpush
@endsection