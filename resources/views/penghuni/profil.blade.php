@extends('layouts.penghuni')
@section('title', 'Profil')

@section('content')

<div x-data="{
        openModal: {{ $errors->has('current_password') || $errors->has('password') ? 'true' : 'false' }},

        toast: { show: false, pesan: '', tipe: 'sukses' },
        tampilToast(pesan, tipe = 'sukses') {
            this.toast = { show: true, pesan, tipe };
            setTimeout(() => this.toast.show = false, 3000);
        }
    }"
    x-init="
        @if(session('success'))
            tampilToast('{{ session('success') }}', 'sukses');
        @endif
    ">

    <div class="page-header">
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-subtitle">Kelola informasi akun dan data penghuni kamu.</p>
    </div>

    <div class="row g-3">

        {{-- KIRI: Informasi Akun --}}
        <div class="col-12 col-lg-6">
            <div class="firabo-card h-100">
                <h6 class="card-title mb-3">Informasi Akun</h6>

                @if($errors->has('current_password') || $errors->has('password'))
                    <div class="alert alert-danger alert-dismissible fade show mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>Gagal memperbarui password. Cek kembali form ubah password.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('penghuni.profil.update') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form_type" value="profil">
                    <div class="mb-3">
                        <label class="form-label-firabo">Nama Lengkap</label>
                        <input type="text" name="name"
                               value="{{ old('name', $user->name) }}"
                               class="firabo-input">
                        @error('name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-firabo">Email</label>
                        <input type="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               class="firabo-input">
                        @error('email') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label-firabo">No. WhatsApp</label>
                        <input type="text" name="no_wa"
                               value="{{ old('no_wa', $user->no_wa) }}"
                               class="firabo-input">
                        @error('no_wa') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn-firabo">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                        <button type="button" class="btn-firabo-outline" @click="openModal = true">
                            <i class="bi bi-key me-1"></i> Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- KANAN: Detail Penghuni --}}
        <div class="col-12 col-lg-6">
            <div class="firabo-card h-100">
                <h6 class="card-title mb-4">Detail Penghuni</h6>

                @php $hunian = $user->hunianAktif; @endphp

                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                        <i class="bi bi-door-open fs-4"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted" style="font-size:13px;">Kamar yang Dihuni</p>
                        <h5 class="mb-0 fw-bold" style="color:#1f2937;">
                            {{ $hunian?->kamar?->nomor_kamar ?? 'Belum ada kamar' }}
                        </h5>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted" style="font-size:13px;">Tanggal Masuk</p>
                        <h5 class="mb-0 fw-bold" style="color:#1f2937;">
                            {{ $hunian?->tanggal_masuk
                                ? \Carbon\Carbon::parse($hunian->tanggal_masuk)->translatedFormat('d F Y')
                                : '-' }}
                        </h5>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                        <i class="bi bi-person-badge fs-4"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted" style="font-size:13px;">Status Penghuni</p>
                        @if($user->status_akun === 'aktif')
                            <span class="badge-tersedia px-3 py-2" style="border-radius:6px;">Aktif / Menetap</span>
                        @else
                            <span class="badge-nonaktif px-3 py-2" style="border-radius:6px;">Tidak Aktif</span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 p-3 rounded"
                     style="background:var(--firabo-primary-light); border:1px dashed var(--firabo-border);">
                    <p class="mb-0 text-muted text-center" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Data detail penghuni dikelola oleh Admin. Jika ada ketidaksesuaian, silakan hubungi pengelola kos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UBAH PASSWORD — 3-layer Firabo pattern --}}
    <div x-show="openModal"
         x-cloak
         x-transition.opacity
         style="position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1055;">

        <div style="display:flex; align-items:center; justify-content:center; height:100%;">

            <div x-show="openModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 style="background:#fff; border-radius:12px; width:100%; max-width:460px; margin:1rem; box-shadow:0 8px 32px rgba(0,0,0,0.18);"
                 @click.outside="openModal = false">

                <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3"
                     style="border-bottom:1px solid var(--firabo-border);">
                    <h6 class="mb-0" style="font-size:15px; font-weight:600;">
                        <i class="bi bi-shield-lock me-2" style="color:var(--firabo-primary)"></i>Ubah Password
                    </h6>
                    <button type="button" class="btn-close" @click="openModal = false"></button>
                </div>

                <form method="POST" action="{{ route('penghuni.profil.update') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form_type" value="password">
                    <div class="px-4 py-3">
                        <div class="mb-3">
                            <label class="form-label-firabo">Password Saat Ini</label>
                            <input type="password" name="current_password"
                                   class="firabo-input" autocomplete="current-password">
                            @error('current_password')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label-firabo">Password Baru</label>
                            <input type="password" name="password"
                                   class="firabo-input" autocomplete="new-password">
                            @error('password')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label-firabo">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation"
                                   class="firabo-input" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 px-4 pb-4">
                        <button type="button" class="btn-firabo-outline" @click="openModal = false">
                            Batal
                        </button>
                        <button type="submit" class="btn-firabo">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Password
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- TOAST NOTIFICATION --}}
    <div x-show="toast.show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="firabo-toast"
         :class="toast.tipe === 'sukses' ? 'firabo-toast--sukses' : 'firabo-toast--gagal'">
        <i class="bi"
           :class="toast.tipe === 'sukses' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
        <span x-text="toast.pesan"></span>
    </div>

</div>

@endsection