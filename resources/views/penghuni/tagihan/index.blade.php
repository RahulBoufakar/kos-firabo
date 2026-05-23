@extends('layouts.penghuni')
@section('title', 'Tagihan Saya')

@section('content')

<div class="page-header">
    <h1 class="page-title">Tagihan Saya</h1>
    <p class="page-subtitle">Pantau dan bayar tagihan sewa kamar Anda.</p>
</div>

{{-- Flash dari redirect Midtrans --}}
@if (request('paid'))
    <div class="alert-firabo alert-success mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>
        Pembayaran diterima! Status tagihan akan diperbarui otomatis dalam beberapa saat.
    </div>
@elseif (request('pending'))
    <div class="alert-firabo alert-warning mb-3">
        <i class="bi bi-clock-fill me-2"></i>
        Pembayaran Anda sedang diproses. Kami akan memberi tahu Anda setelah selesai.
    </div>
@endif

@if (! $hunian)
    <div class="firabo-card text-center py-5">
        <i class="bi bi-house-slash" style="font-size:2.5rem;color:#9ca3af;"></i>
        <p class="mt-3 mb-0 text-muted">Anda belum memiliki hunian aktif.</p>
        <small class="text-muted">Hubungi admin jika ini tidak seharusnya terjadi.</small>
    </div>
@else
    @forelse ($tagihan as $t)
        @php
            $sisaHari    = \Carbon\Carbon::today()->diffInDays($t->tanggal_jatuh_tempo, false);
            $isLunas     = $t->status_tagihan === 'lunas';
            $isTerlambat = $t->status_tagihan === 'terlambat';
        @endphp

        <a href="{{ route('penghuni.tagihan.show', $t->tagihan_id) }}" class="tagihan-list-item">
            <div class="tagihan-list-left">
                <div class="tagihan-list-icon {{ $isLunas ? 'icon-lunas' : ($isTerlambat ? 'icon-terlambat' : 'icon-belum') }}">
                    <i class="bi {{ $isLunas ? 'bi-check-circle-fill' : ($isTerlambat ? 'bi-exclamation-circle-fill' : 'bi-clock-fill') }}"></i>
                </div>
                <div>
                    <div class="tagihan-list-periode">
                        {{ \Carbon\Carbon::parse($t->tanggal_tagihan)->translatedFormat('F Y') }}
                    </div>
                    <div class="tagihan-list-meta">
                        @if ($isLunas)
                            <span class="badge-lunas">Lunas</span>
                        @elseif ($isTerlambat)
                            <span class="badge-terlambat">Terlambat {{ abs($sisaHari) }} hari</span>
                        @else
                            <span class="badge-belum">
                                Jatuh tempo {{ $sisaHari > 0 ? $sisaHari.' hari lagi' : 'hari ini' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="tagihan-list-right">
                <div class="tagihan-list-nominal">
                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>

    @empty
        <div class="firabo-card text-center py-5">
            <i class="bi bi-receipt" style="font-size:2.5rem;color:#9ca3af;"></i>
            <p class="mt-3 mb-0 text-muted">Belum ada tagihan.</p>
        </div>
    @endforelse

    @if ($tagihan instanceof \Illuminate\Pagination\LengthAwarePaginator && $tagihan->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $tagihan->links() }}
        </div>
    @endif
@endif

@endsection

@push('styles')
<style>
.tagihan-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #fff;
    border: 1.5px solid var(--firabo-border);
    border-radius: 12px;
    margin-bottom: .75rem;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s, transform .1s;
}

.tagihan-list-item:hover {
    border-color: var(--firabo-primary);
    box-shadow: 0 2px 10px rgba(45,122,86,.1);
    transform: translateY(-1px);
    color: inherit;
}

.tagihan-list-left  { display: flex; align-items: center; gap: .875rem; }
.tagihan-list-right { display: flex; align-items: center; gap: .75rem; flex-shrink: 0; }

.tagihan-list-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.icon-lunas     { background: #dcfce7; color: #166534; }
.icon-terlambat { background: #fee2e2; color: #991b1b; }
.icon-belum     { background: #fef3c7; color: #92400e; }

.tagihan-list-periode {
    font-weight: 600; font-size: .9rem;
    color: var(--firabo-primary-dark);
}

.tagihan-list-meta { margin-top: .2rem; }

.tagihan-list-nominal {
    font-weight: 700; font-size: 1rem;
    color: var(--firabo-primary-dark);
}

.alert-firabo {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    padding: .75rem 1rem;
    border-radius: 10px;
    font-size: .875rem;
}

.alert-success {
    background: #dcfce7;
    border: 1px solid #86efac;
    color: #166534;
}

.alert-warning {
    background: #fef3c7;
    border: 1px solid #fde68a;
    color: #92400e;
}
</style>
@endpush