@extends('layouts.admin')
@section('title', 'Laporan Pemasukan')
@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title">Laporan Pemasukan</h1>
        <p class="page-subtitle">Rekap pemasukan bulanan & tahunan.</p>
    </div>
    <a href="{{ route('admin.laporan.index') }}" class="btn-firabo-outline">
        <i class="bi bi-arrow-left"></i> Kembali ke Laporan
    </a>
</div>

@livewire('admin.laporan.pemasukan')

@endsection