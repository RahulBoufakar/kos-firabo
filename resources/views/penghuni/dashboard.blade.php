@extends('layouts.penghuni')
@section('title', 'Dashboard Penghuni')
@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.5rem; font-weight:700; margin:0">
            Selamat Datang, {{ auth()->user()->name }}
        </h2>
        <p class="text-muted mb-0" style="font-size:14px">Overview Dashboard</p>
    </div>
    @if($hunian)
        <div class="firabo-card mb-0 d-flex align-items-center gap-2"
             style="padding:0.6rem 1rem">
            <i class="bi bi-door-closed" style="color:var(--firabo-primary); font-size:18px"></i>
            <span style="font-weight:600; font-size:14px">
                Kamar {{ $hunian->kamar->nomor_kamar }}
            </span>
        </div>
    @endif
</div>

<div class="row g-3">
    {{-- Tagihan Hero --}}
    <div class="col-12 col-lg-7">
        @if($tagihanAktif)
        <div class="tagihan-hero">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge-belum" style="background:rgba(255,255,255,0.2);
                      color:#fff; border-radius:20px; padding:4px 12px; font-size:12px">
                    <i class="bi bi-exclamation-triangle me-1"></i>Belum Bayar
                </span>
                <span style="font-size:13px; color:rgba(255,255,255,0.7)">
                    Jatuh Tempo
                    {{ $tagihanAktif->tanggal_jatuh_tempo->format('d M Y') }}
                </span>
            </div>
            <p style="font-size:13px; color:rgba(255,255,255,0.7); margin:0">
                Total Tagihan Bulan Ini
            </p>
            <div class="tagihan-nominal">
                Rp {{ number_format($tagihanAktif->nominal, 0, ',', '.') }}
            </div>
            <div class="tagihan-countdown">
                <i class="bi bi-clock"></i>
                @php
                    $sisaHari = now()->diffInRealDays($tagihanAktif->tanggal_jatuh_tempo, false);
                    // Jika sisaHari positif, berarti masih ada waktu sebelum jatuh tempo
                    // Jika sisaHari negatif, berarti sudah melewati jatuh tempo
                    $sisaHari = $sisaHari >= 0
                        ? (int) ceil($sisaHari)
                        : -(int) ceil(abs($sisaHari));
                @endphp
                @if($sisaHari > 0)
                    {{ $sisaHari }} Hari Lagi
                @elseif($sisaHari == 0)
                    Jatuh tempo hari ini!
                @else
                    Terlambat {{ abs($sisaHari) }} hari
                @endif
            </div>
            <div class="d-flex gap-3 mt-3">
                <a href="{{ route('penghuni.tagihan.show', $tagihanAktif->tagihan_id) }}"
                   class="btn-firabo-outline flex-fill justify-content-center"
                   style="border-color:rgba(255,255,255,0.5); color:#fff;
                          background:rgba(255,255,255,0.1)">
                    Bayar Sekarang
                </a>
                <a href="{{ route('penghuni.tagihan.index') }}"
                   class="btn-firabo flex-fill justify-content-center"
                   style="background:rgba(255,255,255,0.2); border:1.5px solid rgba(255,255,255,0.4)">
                    Lihat Riwayat
                </a>
            </div>
        </div>
        @else
        <div class="tagihan-hero d-flex flex-column align-items-center justify-content-center"
             style="min-height:180px">
            <i class="bi bi-check-circle" style="font-size:2.5rem; color:rgba(255,255,255,0.8)"></i>
            <p class="mt-2 mb-0" style="color:rgba(255,255,255,0.85); font-weight:500">
                Semua tagihan sudah lunas!
            </p>
            <a href="{{ route('penghuni.tagihan.index') }}"
               class="btn-firabo-outline mt-3"
               style="border-color:rgba(255,255,255,0.5); color:#fff;
                      background:rgba(255,255,255,0.1)">
                Lihat Riwayat
            </a>
        </div>
        @endif
    </div>

    {{-- Info Cards --}}
    <div class="col-12 col-lg-5">
        <div class="row g-3 h-100">
            {{-- Tagihan Air --}}
            <div class="col-12">
                <div class="firabo-card d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; background:var(--firabo-primary-light);
                                border-radius:10px; display:flex; align-items:center;
                                justify-content:center; flex-shrink:0">
                        <i class="bi bi-droplet" style="color:var(--firabo-primary); font-size:20px"></i>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#9ca3af">Tagihan Air</div>
                        <div style="font-weight:600; font-size:15px">Termasuk</div>
                    </div>
                </div>
            </div>
            {{-- Status Kontrak --}}
            <div class="col-12">
                <div class="firabo-card d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; background:var(--firabo-primary-light);
                                border-radius:10px; display:flex; align-items:center;
                                justify-content:center; flex-shrink:0">
                        <i class="bi bi-file-text" style="color:var(--firabo-primary); font-size:20px"></i>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#9ca3af">Status Kontrak</div>
                        <div style="font-weight:600; font-size:15px">
                            @if($hunian)
                                <span style="color:var(--firabo-primary)">Aktif</span>
                                @if($hunian->tanggal_keluar)
                                    <span style="font-size:12px; color:#6b7280; font-weight:400">
                                        — Berakhir: {{ $hunian->tanggal_keluar->format('d M Y') }}
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">Tidak ada hunian aktif</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection