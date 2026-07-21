@extends('layouts.admin')
@section('title', 'Laporan')
@section('content')

<div class="page-header">
    <h1 class="page-title">Laporan</h1>
    <p class="page-subtitle">Pilih jenis laporan yang ingin ditampilkan.</p>
</div>

<div class="row g-3">

    <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.laporan.tagihan-belum-bayar') }}" class="text-decoration-none">
            <div class="firabo-card h-100" style="transition:transform .15s, box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(45,122,86,0.12)';"
                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div style="width:44px; height:44px; border-radius:10px; background:var(--firabo-primary-light);
                            display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
                    <i class="bi bi-receipt" style="color:var(--firabo-primary); font-size:1.25rem;"></i>
                </div>
                <h6 class="card-title mb-1">Tagihan Belum Dibayar</h6>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Semua tagihan belum lunas, diurut dari jatuh tempo paling dekat.
                </p>
                <span style="font-size:13px; color:var(--firabo-primary); font-weight:500;">
                    Buka Laporan <i class="bi bi-arrow-right"></i>
                </span>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.laporan.pemasukan') }}" class="text-decoration-none">
            <div class="firabo-card h-100" style="transition:transform .15s, box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(45,122,86,0.12)';"
                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div style="width:44px; height:44px; border-radius:10px; background:var(--firabo-primary-light);
                            display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
                    <i class="bi bi-cash-coin" style="color:var(--firabo-primary); font-size:1.25rem;"></i>
                </div>
                <h6 class="card-title mb-1">Pemasukan</h6>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Rekap pemasukan bulanan & tahunan.
                </p>
                <span style="font-size:13px; color:var(--firabo-primary); font-weight:500;">
                    Buka Laporan <i class="bi bi-arrow-right"></i>
                </span>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.laporan.piutang') }}" class="text-decoration-none">
            <div class="firabo-card h-100" style="transition:transform .15s, box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(45,122,86,0.12)';"
                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <div style="width:44px; height:44px; border-radius:10px; background:var(--firabo-primary-light);
                            display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
                    <i class="bi bi-person-x" style="color:var(--firabo-primary); font-size:1.25rem;"></i>
                </div>
                <h6 class="card-title mb-1">Penghuni Kabur / Piutang Macet</h6>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Daftar penghuni kabur beserta total piutang yang belum tertagih.
                </p>
                <span style="font-size:13px; color:var(--firabo-primary); font-weight:500;">
                    Buka Laporan <i class="bi bi-arrow-right"></i>
                </span>
            </div>
        </a>
    </div>

</div>

@endsection