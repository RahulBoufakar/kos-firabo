@extends('layouts.admin')
@section('title', 'Laporan Tagihan Belum Dibayar')
@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title">Laporan Tagihan Belum Dibayar</h1>
        <p class="page-subtitle">Diurutkan dari jatuh tempo paling dekat.</p>
    </div>
    <a href="{{ route('admin.laporan.index') }}" class="btn-firabo-outline">
        <i class="bi bi-arrow-left"></i> Kembali ke Laporan
    </a>
</div>

@livewire('admin.laporan.tagihan-belum-bayar')

@endsection