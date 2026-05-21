@extends('layouts.admin')
@section('title', 'Manajemen Kamar')
@section('content')

<div class="page-header">
    <h1 class="page-title">Manajemen Kamar</h1>
    <p class="page-subtitle">Kelola data, status, dan fasilitas kamar kos.</p>
</div>

@livewire('admin.kamar.table')

@endsection