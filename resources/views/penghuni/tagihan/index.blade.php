@extends('layouts.penghuni')
@section('title', 'Tagihan Saya')
@section('content')

<div class="page-header">
    <h1 class="page-title">Tagihan Saya</h1>
    <p class="page-subtitle">Riwayat dan status tagihan kamu.</p>
</div>

@livewire('penghuni.tagihan.table')

@endsection