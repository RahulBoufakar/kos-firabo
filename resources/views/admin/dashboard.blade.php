@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')

<div class="page-header">
    <h1 class="page-title">Dashboard Overview</h1>
    <p class="page-subtitle">Welcome back to Kos Firabo management.</p>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_kamar'] }}</div>
            <div class="stat-label">Total Kamar</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['penghuni_aktif'] }}</div>
            <div class="stat-label">Penghuni Aktif</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['tagihan_belum'] }}</div>
            <div class="stat-label" style="font-size:0.785rem;">Tagihan Belum Lunas</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card">
            <div class="stat-value" style="font-size: {{ $stats['pemasukan'] >= 1000000000 ? '1.5rem' : ($stats['pemasukan'] >= 1000000 ? '1.75rem' : '2rem') }}">
                {{ $stats['pemasukan'] >= 1000000000
                    ? 'Rp ' . number_format($stats['pemasukan'] / 1000000000, 2, ',', '.') . ' M'
                    : ($stats['pemasukan'] >= 1000000
                        ? 'Rp ' . number_format($stats['pemasukan'] / 1000000, 1, ',', '.') . ' Jt'
                        : 'Rp ' . number_format($stats['pemasukan'], 0, ',', '.')) }}
            </div>
            <div class="stat-label">Pemasukan (Rp)</div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['kamar_tersedia'] }}</div>
            <div class="stat-label">Kamar Tersedia</div>
        </div>
    </div>
</div>

{{-- Two column tables --}}
<div class="row g-3">
    {{-- Tagihan Jatuh Tempo --}}
    <div class="col-12 col-lg-6">
        <div class="firabo-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-title mb-0">Tagihan Jatuh Tempo Terdekat</h6>
                <a href="{{ route('admin.tagihan.index', ['view' => 'jatuh_tempo']) }}"
                   style="font-size:13px; color:var(--firabo-primary); text-decoration:none">
                    Lihat Semua
                </a>
            </div>
            <table class="firabo-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kamar</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tagihanDekat as $item)
                    <tr>
                        <td style="font-size:13px; font-weight:500">
                            {{ $item->hunian->user->name ?? '-' }}
                        </td>
                        <td style="font-size:13px">
                            {{ $item->hunian->kamar->nomor_kamar ?? '-' }}
                        </td>
                        <td style="font-size:13px">
                            @php $isLate = $item->tanggal_jatuh_tempo < now(); @endphp
                            <span style="{{ $isLate ? 'color:#dc3545; font-weight:600' : '' }}">
                                {{ $item->tanggal_jatuh_tempo->format('d M Y') }}
                            </span>
                        </td>
                        <td style="font-size:13px">
                            Rp {{ number_format($item->nominal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted" style="font-size:13px">
                            Semua tagihan sudah lunas 🎉
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Transaksi Terbaru --}}
    <div class="col-12 col-lg-6">
        <div class="firabo-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-title mb-0">Transaksi Terbaru</h6>
                <a href="{{ route('admin.pembayaran.index') }}"
                   style="font-size:13px; color:var(--firabo-primary); text-decoration:none">
                    Lihat Riwayat
                </a>
            </div>
            <table class="firabo-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiTerbaru as $item)
                    <tr>
                        <td style="font-size:13px; font-weight:500">
                            {{ $item->tagihan->hunian->user->name ?? '-' }}
                        </td>
                        <td style="font-size:13px">
                            {{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}
                        </td>
                        <td style="font-size:13px">
                            Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($item->status_pembayaran === 'sukses')
                                <span class="badge-sukses">Lunas</span>
                            @elseif($item->status_pembayaran === 'pending')
                                <span class="badge-pending">Pending</span>
                            @else
                                <span class="badge-nonaktif">Gagal</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted" style="font-size:13px">
                            Belum ada transaksi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection