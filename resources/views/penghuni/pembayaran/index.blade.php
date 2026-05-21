@extends('layouts.penghuni')
@section('title', 'Riwayat Pembayaran')
@section('content')

<div class="page-header">
    <h1 class="page-title">Riwayat Pembayaran</h1>
    <p class="page-subtitle">Semua catatan transaksi pembayaran kamu.</p>
</div>

@livewire('penghuni.pembayaran.table')

@endsection