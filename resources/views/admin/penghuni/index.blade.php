@extends('layouts.admin')
@section('title', 'Manajemen Penghuni')
@section('content')

<div class="page-header">
    <h1 class="page-title">Manajemen Penghuni</h1>
    <p class="page-subtitle">Kelola data penghuni dan hunian kamar.</p>
</div>

@livewire('admin.penghuni.table')

@endsection