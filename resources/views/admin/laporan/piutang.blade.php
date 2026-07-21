@extends('layouts.admin')
@section('title', 'Laporan Piutang Macet')
@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title">Laporan Penghuni Kabur / Piutang Macet</h1>
        <p class="page-subtitle">Daftar penghuni berstatus kabur beserta total piutang yang belum tertagih.</p>
    </div>
    <a href="{{ route('admin.laporan.index') }}" class="btn-firabo-outline">
        <i class="bi bi-arrow-left"></i> Kembali ke Laporan
    </a>
</div>

@livewire('admin.laporan.piutang')

@endsection