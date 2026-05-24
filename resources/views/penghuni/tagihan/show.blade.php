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
     - Script ini hanya dimuat jika tagihan belum lunas dan snapToken tersedia.
     - Fungsi bayarSekarang() memanggil window.snap.pay() dengan snapToken.
        - Callback onSuccess/onPending/onError/onClose menangani hasil pembayaran.
        - Fungsi invalidateTokenDanRefresh() dipanggil saat onError untuk invalidasi token lama
            dan refresh halaman agar dapat token baru.
        - Script tambahan untuk menampilkan toast notifikasi berdasarkan query param transaction_status
          yang dikirim dari redirect Midtrans setelah pembayaran selesai.
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
// URL endpoint invalidate token — dibaca dari blade agar tidak hardcode di JS
const invalidateUrl = '{{ route('penghuni.pembayaran.invalidate-token', $tagihan->tagihan_id) }}';
 
// CSRF token diambil dari meta tag yang sudah ada di layout
// Pastikan layout memiliki: <meta name="csrf-token" content="{{ csrf_token() }}">
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
 
async function invalidateTokenDanRefresh() {
    // Panggil backend untuk tandai token lama sebagai 'gagal'
    // sehingga getOrCreateSnapToken() akan generate token baru
    try {
        await fetch(invalidateUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        // Tetap redirect meski fetch gagal — token baru tetap bisa digenerate
        // selama record pending sebelumnya sudah ditandai expired oleh callback
        console.warn('Invalidate token fetch failed:', e);
    }
 
    // Redirect ke halaman show yang sama — TagihanController@show
    // akan memanggil getOrCreateSnapToken() dan generate token baru
    window.location.href = '{{ route('penghuni.tagihan.show', $tagihan->tagihan_id) }}';
}
 
function bayarSekarang() {
    const btn = document.getElementById('pay-button');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memuat...';
 
    window.snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) {
            // Webhook Midtrans sudah/akan update status di backend.
            // Redirect ke halaman yang sama dengan status param.
            window.location.href = '{{ route('penghuni.tagihan.show', $tagihan->tagihan_id) }}?transaction_status=settlement';
        },
        onPending: function(result) {
            window.location.href = '{{ route('penghuni.tagihan.show', $tagihan->tagihan_id) }}?transaction_status=pending';
        },
        onError: function(result) {
            // Token kemungkinan expired atau transaksi gagal.
            // Invalidasi dulu, lalu refresh halaman untuk dapat token baru.
            invalidateTokenDanRefresh();
        },
        onClose: function() {
            // User tutup popup tanpa bayar — cukup reset tombol.
            // Tidak perlu invalidasi karena token masih bisa dipakai.
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
        }
    });
}
</script>
@endif
 
{{-- ── Baca query param dari redirect Midtrans (finish URL) atau dari JS handler ── --}}
@php
    $txStatus = request('transaction_status');
@endphp
 
@if ($txStatus === 'settlement')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Tampilkan toast sukses — status tagihan akan diupdate oleh webhook
        const el = document.createElement('div');
        el.innerHTML = `<div style="position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
            background:#dcfce7;border:1px solid #86efac;color:#166534;
            padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;
            display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 12px rgba(0,0,0,.1);">
            <i class="bi bi-check-circle-fill"></i>
            Pembayaran berhasil! Status akan diperbarui otomatis.
        </div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    });
</script>
 
@elseif ($txStatus === 'pending')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.createElement('div');
        el.innerHTML = `<div style="position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
            background:#fef3c7;border:1px solid #fde68a;color:#92400e;
            padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;
            display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 12px rgba(0,0,0,.1);">
            <i class="bi bi-clock-fill"></i>
            Pembayaran sedang diproses. Kami akan memberi tahu Anda setelah selesai.
        </div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 6000);
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