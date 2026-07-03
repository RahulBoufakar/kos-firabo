@extends('layouts.penghuni')
@section('title', 'Tagihan Saya')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tagihan</h1>
    <p class="page-subtitle">Semua Tagihan kamu.</p>
</div>

@livewire('penghuni.tagihan.table')
@endsection