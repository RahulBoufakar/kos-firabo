@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <div class="alert alert-info">
        Lupa password? Tidak masalah. Masukkan alamat email Anda dan kami akan
        mengirimkan link reset password untuk membuat password baru.
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="form-label">
                Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="form-control @error('email') is-invalid @enderror"
            >

            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                Email Password Reset Link
            </button>
        </div>
    </form>
@endsection