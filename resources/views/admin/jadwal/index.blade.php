@extends('layouts.admin')
@section('title', 'Pengaturan Jadwal')
@section('content')

<div class="page-header">
    <h1 class="page-title">Pengaturan Jadwal Tagihan</h1>
    <p class="page-subtitle">Atur tanggal generate dan jatuh tempo tagihan per hunian.</p>
</div>

@livewire('admin.jadwal-tagihan-table')

@endsection