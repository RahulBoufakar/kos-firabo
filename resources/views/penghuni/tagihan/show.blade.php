@extends('layouts.penghuni')
@section('title', 'Detail Tagihan')

@section('content')

@php
    $isLunas     = $tagihan->status_tagihan === 'lunas';
    $isTerlambat = $tagihan->status_tagihan === 'terlambat';
    $sisaHari    = \Carbon\Carbon::today()->diffInDays($tagihan->tanggal_jatuh_tempo, false);
    $kamar       = $tagihan->hunian->kamar;
    $pembayaranSukses = $tagihan->pembayaran->where('status_pembayaran', 'sukses')->first();
@endphp

{{-- Back Link --}}
<div class="mb-3">
    <a href="{{ route('penghuni.tagihan.index') }}" class="back-link">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Tagihan
    </a>
</div>

{{-- ══════════ HERO CARD ══════════ --}}
<div class="tagihan-hero {{ $isLunas ? 'hero-lunas' : ($isTerlambat ? 'hero-terlambat' : 'hero-belum') }}">
    <div class="tagihan-hero-top">
        <div class="tagihan-hero-label">
            {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('F Y') }}
            — Kamar {{ $kamar->nomor_kamar }}
        </div>
        @if ($isLunas)
            <span class="badge-lunas">Lunas</span>
        @elseif ($isTerlambat)
            <span class="badge-terlambat">Terlambat</span>
        @else
            <span class="badge-belum">Belum Bayar</span>
        @endif
    </div>

    <div class="tagihan-nominal">
        Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
    </div>

    @if (! $isLunas)
        <div class="tagihan-countdown">
            <i class="bi {{ $isTerlambat ? 'bi-exclamation-circle' : 'bi-clock' }}"></i>
            @if ($isTerlambat)
                Terlambat {{ abs($sisaHari) }} hari
            @elseif ($sisaHari === 0)
                Jatuh tempo hari ini
            @else
                {{ $sisaHari }} hari lagi
            @endif
        </div>
    @endif
</div>

{{-- ══════════ DETAIL CARD ══════════ --}}
<div class="firabo-card mt-3">
    <div class="detail-row">
        <div class="detail-label"><i class="bi bi-door-open me-2"></i>Kamar</div>
        <div class="detail-value">
            {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label"><i class="bi bi-calendar3 me-2"></i>Tanggal Tagihan</div>
        <div class="detail-value">
            {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('d F Y') }}
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label"><i class="bi bi-calendar-x me-2"></i>Jatuh Tempo</div>
        <div class="detail-value {{ $isTerlambat ? 'text-danger fw-semibold' : '' }}">
            {{ \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label"><i class="bi bi-cash-stack me-2"></i>Nominal</div>
        <div class="detail-value fw-bold" style="color:var(--firabo-primary-dark);">
            Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
        </div>
    </div>
    @if ($isLunas && $pembayaranSukses)
        <div class="detail-row">
            <div class="detail-label"><i class="bi bi-credit-card me-2"></i>Metode Bayar</div>
            <div class="detail-value text-capitalize">
                {{ str_replace('_', ' ', $pembayaranSukses->metode_pembayaran) }}
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="bi bi-check2-circle me-2"></i>Dibayar Pada</div>
            <div class="detail-value">
                {{ \Carbon\Carbon::parse($pembayaranSukses->tanggal_bayar)->translatedFormat('d F Y, H:i') }}
            </div>
        </div>
    @endif
</div>

{{-- ══════════ TOMBOL BAYAR ══════════ --}}
@if (! $isLunas && $snapToken)
    <div class="mt-3">
        <button
            id="pay-button"
            class="btn-firabo w-100"
            style="font-size:1rem; padding:.875rem;"
            onclick="bayarSekarang()"
        >
            <i class="bi bi-credit-card me-2"></i>
            Bayar Sekarang
        </button>
        <p class="text-center text-muted mt-2" style="font-size:.8rem;">
            <i class="bi bi-shield-check me-1"></i>
            Pembayaran aman & terenkripsi via Midtrans
        </p>
    </div>
@elseif ($isLunas)
    <div class="firabo-card mt-3 text-center py-3" style="border-color:#bbf7d0;">
        <i class="bi bi-check-circle-fill" style="font-size:1.75rem;color:#16a34a;"></i>
        <p class="mt-2 mb-0 fw-semibold" style="color:#166534;">Tagihan ini sudah lunas</p>
    </div>
@endif

@endsection

{{-- ══════════════════════════════════════════════
     MIDTRANS SNAP.JS
     Diload di bagian bawah — hanya pada halaman ini.
══════════════════════════════════════════════ --}}
@push('scripts')
@if (! $isLunas && $snapToken)
<script
    src="{{ config('services.midtrans.is_production')
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ $clientKey }}"
></script>
<script>
function bayarSekarang() {
    const btn = document.getElementById('pay-button');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...';

    window.snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) {
            // Midtrans callback webhook akan update status di backend.
            // Di sini kita cukup redirect ke daftar tagihan dengan pesan sukses.
            window.location.href = '{{ route('penghuni.tagihan.index') }}?paid=1';
        },
        onPending: function(result) {
            window.location.href = '{{ route('penghuni.tagihan.index') }}?pending=1';
        },
        onError: function(result) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
            alert('Pembayaran gagal. Silakan coba lagi.');
            console.error('Midtrans error:', result);
        },
        onClose: function() {
            // Popup ditutup tanpa transaksi — reset tombol
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
        }
    });
}
</script>
@endif

{{-- Tampilkan flash setelah kembali dari Midtrans --}}
@if (request('paid'))
<script>
    // Tampilkan notifikasi sukses ringan — status akan diupdate via webhook
    document.addEventListener('DOMContentLoaded', () => {
        const toast = document.createElement('div');
        toast.innerHTML = `
            <div style="position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
                background:#dcfce7;border:1px solid #86efac;color:#166534;
                padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;
                display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 12px rgba(0,0,0,.1);">
                <i class="bi bi-check-circle-fill"></i>
                Pembayaran diterima! Status akan diperbarui otomatis.
            </div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    });
</script>
@endif
@endpush

@push('styles')
<style>
/* ── Hero Card ── */
.tagihan-hero {
    border-radius: 16px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
}

.hero-belum {
    background: linear-gradient(135deg, var(--firabo-primary-dark) 0%, var(--firabo-primary) 100%);
    color: #fff;
}

.hero-terlambat {
    background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);
    color: #fff;
}

.hero-lunas {
    background: linear-gradient(135deg, #14532d 0%, #16a34a 100%);
    color: #fff;
}

.tagihan-hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .75rem;
}

.tagihan-hero-label {
    font-size: .875rem;
    opacity: .85;
    font-weight: 500;
}

.tagihan-nominal {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -.02em;
    line-height: 1.1;
    margin-bottom: .5rem;
}

.tagihan-countdown {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .875rem;
    opacity: .9;
    background: rgba(255,255,255,.15);
    padding: .3rem .75rem;
    border-radius: 999px;
}

/* ── Detail Row ── */
.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: .75rem 0;
    border-bottom: 1px solid var(--firabo-border);
    font-size: .9rem;
}

.detail-row:last-child { border-bottom: none; }

.detail-label {
    color: #6b7280;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.detail-value { font-weight: 500; text-align: right; }

/* ── Back Link ── */
.back-link {
    display: inline-flex;
    align-items: center;
    color: var(--firabo-primary);
    font-size: .875rem;
    font-weight: 500;
    text-decoration: none;
    transition: opacity .15s;
}

.back-link:hover { opacity: .75; color: var(--firabo-primary); }
</style>
@endpush