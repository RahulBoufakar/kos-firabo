@extends('layouts.admin')
@section('title', 'Riwayat Pembayaran')
@section('content')

<div class="page-header">
    <h1 class="page-title">Riwayat Pembayaran</h1>
    <p class="page-subtitle">Rekap seluruh transaksi pembayaran.</p>
</div>

@livewire('admin.pembayaran.table')

@endsection