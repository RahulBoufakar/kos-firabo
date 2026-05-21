@extends('layouts.admin')
@section('title', 'Manajemen Tagihan')
@section('content')

<div class="page-header">
    <h1 class="page-title">Manajemen Tagihan</h1>
    <p class="page-subtitle">Pantau status tagihan seluruh penghuni.</p>
</div>

@livewire('admin.tagihan-table')

@endsection