<?php

use App\Models\User;
use App\Models\Kamar;
use App\Models\Hunian;
use App\Models\JadwalTagihan;
use App\Events\PenghuniTerdaftar;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    // ── View State ─────────────────────────────────────────────────────────
    // 'table'        → tampilan tabel normal
    // 'skeleton'     → animasi transisi 700ms sebelum form muncul
    // 'form'         → form tambah/edit penghuni
    // 'form-reaktivasi' → form pilih kamar untuk reaktivasi penghuni nonaktif
    public string $activeView = 'table';

    // ── Filter ─────────────────────────────────────────────────────────────
    public string $search       = '';
    public string $filterStatus = 'aktif'; // default: hanya tampilkan aktif

    // ── Form Fields — Tambah / Edit ────────────────────────────────────────
    public bool   $isEditing     = false;
    public ?int   $editingId     = null;
    public string $name          = '';
    public string $email         = '';
    public string $no_wa         = '';
    public string $password      = '';
    public string $kamar_id      = '';
    public string $kamar_id_asal = '';

    // ── Deteksi Email Duplikat (Opsi C) ────────────────────────────────────
    // Menyimpan data penghuni nonaktif jika email yang diinput sudah terdaftar
    public ?array $penghuniNonaktifDitemukan = null;

    // ── Reaktivasi ─────────────────────────────────────────────────────────
    public ?int   $reaktivasiId   = null;   // id penghuni yang akan diaktifkan
    public string $reaktivasiNama = '';     // untuk ditampilkan di form
    public string $reaktivasiKamarId = '';  // kamar yang dipilih untuk hunian baru

    protected function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            'no_wa'    => 'required|string|max:20',
            'password' => $this->isEditing ? 'nullable|min:6' : 'required|min:6',
            'kamar_id' => 'required|exists:tb_kamar,kamar_id',
        ];
    }

    protected function messages(): array
    {
        return [
            // Validasi Nama
            'name.required' => 'Nama penghuni wajib diisi.',
            'name.string'   => 'Format nama tidak valid.',
            'name.max'      => 'Nama penghuni maksimal terdiri dari 100 karakter.',

            // Validasi Email
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid (contoh: nama@email.com).',
            'email.unique'   => 'Email ini sudah digunakan oleh penghuni lain.',

            // Validasi Nomor WhatsApp
            'no_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'no_wa.string'   => 'Format nomor WhatsApp tidak valid.',
            'no_wa.max'      => 'Nomor WhatsApp maksimal 20 karakter.',

            // Validasi Password
            'password.required' => 'Password wajib diisi saat menambahkan penghuni baru.',
            'password.min'      => 'Password minimal harus 6 karakter.',

            // Validasi Kamar
            'kamar_id.required' => 'Kamar wajib dipilih.',
            'kamar_id.exists'   => 'Data kamar yang dipilih tidak ditemukan atau tidak valid.',
        ];
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // ── Deteksi email duplikat secara realtime ─────────────────────────────
    // Dipanggil otomatis oleh Livewire setiap kali $email berubah (wire:model.live)
    public function updatedEmail(string $value): void
    {
        $this->penghuniNonaktifDitemukan = null;

        if ($this->isEditing || blank($value)) return;

        $existing = User::where('email', $value)
            ->where('role', 'penghuni')
            ->where('status_akun', 'nonaktif')
            ->first();

        if ($existing) {
            $hunianTerakhir = $existing->hunian()
                ->where('status_hunian', 'selesai')
                ->with('kamar')
                ->latest('tanggal_keluar')
                ->first();

            $this->penghuniNonaktifDitemukan = [
                'id'            => $existing->id,
                'nama'          => $existing->name,
                'kamar_terakhir'=> $hunianTerakhir?->kamar?->nomor_kamar ?? '-',
            ];
        }
    }

    // ── CRUD Penghuni ──────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->reset([
            'name', 'email', 'no_wa', 'password', 'kamar_id', 'kamar_id_asal',
            'editingId', 'penghuniNonaktifDitemukan',
        ]);
        $this->isEditing = false;
        $this->resetValidation();
        $this->activeView = 'skeleton';
    }

    public function openEdit(int $id): void
    {
        $this->reset([
            'name', 'email', 'no_wa', 'password', 'kamar_id', 'kamar_id_asal',
            'penghuniNonaktifDitemukan',
        ]);

        $user   = User::findOrFail($id);
        $hunian = $user->hunian()->where('status_hunian', 'aktif')->first();

        $this->editingId      = $user->id;
        $this->name           = $user->name;
        $this->email          = $user->email;
        $this->no_wa          = $user->no_wa ?? '';
        $this->password       = '';
        $this->kamar_id       = (string) ($hunian?->kamar_id ?? '');
        $this->kamar_id_asal  = $this->kamar_id;
        $this->isEditing      = true;
        $this->resetValidation();
        $this->activeView     = 'skeleton';
    }

    public function cancelForm(): void
    {
        $this->reset([
            'name', 'email', 'no_wa', 'password', 'kamar_id', 'kamar_id_asal',
            'editingId', 'isEditing', 'penghuniNonaktifDitemukan',
        ]);
        $this->resetValidation();
        $this->activeView = 'table';
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'name'  => $this->name,
                'email' => $this->email,
                'no_wa' => $this->no_wa,
                ...($this->password ? ['password' => Hash::make($this->password)] : []),
            ]);

            // Deteksi pindah kamar
            $pindahKamar = $this->kamar_id !== $this->kamar_id_asal;

            if ($pindahKamar) {
                $kamarBaru = Kamar::where('kamar_id', $this->kamar_id)
                    ->where('status_kamar', 'tersedia')
                    ->first();

                if (! $kamarBaru) {
                    $this->addError('kamar_id', 'Kamar yang dipilih sudah tidak tersedia.');
                    return;
                }

                $hunianLama = $user->hunian()
                    ->where('status_hunian', 'aktif')
                    ->with('jadwalTagihan')
                    ->first();

                $jadwalLama = $hunianLama?->jadwalTagihan;

                if ($hunianLama) {
                    Kamar::where('kamar_id', $hunianLama->kamar_id)
                         ->update(['status_kamar' => 'tersedia']);
                    $hunianLama->update([
                        'status_hunian'  => 'selesai',
                        'tanggal_keluar' => now(),
                    ]);
                    $jadwalLama?->update(['status_jadwal' => 'nonaktif']);
                }

                $hunianBaru = Hunian::create([
                    'user_id'        => $user->id,
                    'kamar_id'       => $kamarBaru->kamar_id,
                    'tanggal_masuk'  => now()->toDateString(),
                    'tanggal_keluar' => null,
                    'status_hunian'  => 'aktif',
                ]);

                $kamarBaru->update(['status_kamar' => 'terisi']);

                JadwalTagihan::create([
                    'hunian_id'           => $hunianBaru->hunian_id,
                    'tanggal_generate'    => $jadwalLama?->tanggal_generate
                                            ?? min((int) now()->format('d'), 28),
                    'tanggal_jatuh_tempo' => $jadwalLama?->tanggal_jatuh_tempo ?? 7,
                    'status_jadwal'       => 'aktif',
                ]);

                $pesan = 'Data penghuni diperbarui dan kamar berhasil dipindahkan.';
            } else {
                $pesan = 'Data penghuni berhasil diperbarui.';
            }
        } else {
            $penghuni = User::create([
                'name'        => $this->name,
                'email'       => $this->email,
                'no_wa'       => $this->no_wa,
                'password'    => Hash::make($this->password),
                'role'        => 'penghuni',
                'status_akun' => 'aktif',
            ]);

            $kamar = Kamar::where('kamar_id', $this->kamar_id)
                ->where('status_kamar', 'tersedia')
                ->first();

            if (! $kamar) {
                $this->addError('kamar_id', 'Kamar yang dipilih sudah tidak tersedia.');
                return;
            }

            event(new PenghuniTerdaftar($penghuni, $kamar));

            $pesan = 'Penghuni baru berhasil ditambahkan.';
        }

        $this->reset([
            'name', 'email', 'no_wa', 'password', 'kamar_id', 'kamar_id_asal',
            'editingId', 'isEditing', 'penghuniNonaktifDitemukan',
        ]);
        $this->activeView = 'table';
        $this->resetPage();
        $this->dispatch('toast', pesan: $pesan, tipe: 'sukses');
    }

    // Dipanggil HANYA setelah user konfirmasi di Alpine delete popup
    public function delete(int $id): void
    {
        $user   = User::findOrFail($id);
        
        // Ambil hunian beserta jadwal tagihannya
        $hunian = $user->hunian()
            ->where('status_hunian', 'aktif')
            ->with('jadwalTagihan') // Pastikan relasi ini ada di model Hunian
            ->first();

        if ($hunian) {
            // 1. Kosongkan kamar
            Kamar::where('kamar_id', $hunian->kamar_id)
                 ->update(['status_kamar' => 'tersedia']);
            
            // 2. Selesaikan masa hunian
            $hunian->update([
                'status_hunian'  => 'selesai',
                'tanggal_keluar' => now(),
            ]);

            // 3. Matikan generator jadwal tagihan agar bulan depan tidak otomatis terbuat
            if ($hunian->jadwalTagihan) {
                $hunian->jadwalTagihan->update(['status_jadwal' => 'nonaktif']);
            }
        }

        // 4. Nonaktifkan user (Riwayat tagihan tetap aman di database)
        $user->update(['status_akun' => 'nonaktif']);
        
        $this->resetPage();
        $this->dispatch('toast', pesan: 'Penghuni berhasil dinonaktifkan. Data tagihan tetap disimpan sebagai riwayat.', tipe: 'sukses');
    }

    // ── Reaktivasi ─────────────────────────────────────────────────────────

    // Dibuka dari: (1) tombol di tabel saat filter=nonaktif, (2) tombol di form tambah saat email duplikat
    public function openReaktivasi(int $id): void
    {
        $user = User::findOrFail($id);

        $this->reaktivasiId     = $user->id;
        $this->reaktivasiNama   = $user->name;
        $this->reaktivasiKamarId = '';
        $this->resetValidation();

        // Tutup form tambah jika sedang terbuka, lalu masuk ke skeleton
        $this->reset([
            'name', 'email', 'no_wa', 'password', 'kamar_id', 'kamar_id_asal',
            'editingId', 'isEditing', 'penghuniNonaktifDitemukan',
        ]);
        $this->activeView = 'skeleton';
    }

    public function cancelReaktivasi(): void
    {
        $this->reset(['reaktivasiId', 'reaktivasiNama', 'reaktivasiKamarId']);
        $this->resetValidation();
        $this->activeView = 'table';
    }

    public function simpanReaktivasi(): void
    {
        $this->validate([
            'reaktivasiKamarId' => 'required|exists:tb_kamar,kamar_id',
        ], [
            'reaktivasiKamarId.required' => 'Pilih kamar untuk penghuni ini.',
        ]);

        $kamar = Kamar::where('kamar_id', $this->reaktivasiKamarId)
            ->where('status_kamar', 'tersedia')
            ->first();

        if (! $kamar) {
            $this->addError('reaktivasiKamarId', 'Kamar yang dipilih sudah tidak tersedia.');
            return;
        }

        $user = User::findOrFail($this->reaktivasiId);

        // [KUNCI ANTI-DUPLIKASI]: Intip konfigurasi dari hunian & jadwal paling terakhir sebelum dinonaktifkan
        $hunianLama = Hunian::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $jadwalLama = null;
        if ($hunianLama) {
            $jadwalLama = JadwalTagihan::where('hunian_id', $hunianLama->hunian_id)->first();
        }

        // 1. Aktifkan kembali akun user
        $user->update(['status_akun' => 'aktif']);

        // 2. Buat catatan Hunian baru (menjaga kebersihan data check-in/check-out)
        $hunianBaru = Hunian::create([
            'user_id'        => $user->id,
            'kamar_id'       => $kamar->kamar_id,
            'tanggal_masuk'  => now()->toDateString(),
            'tanggal_keluar' => null,
            'status_hunian'  => 'aktif',
        ]);

        // 3. Tandai kamar baru sebagai terisi
        $kamar->update(['status_kamar' => 'terisi']);

        // 4. Buat jadwal baru mengikat ke hunian baru dengan meniru tanggal lama jika ada
        if ($jadwalLama) {
            // Menggunakan kembali tanggal generate & jatuh tempo dari komitmen lama
            JadwalTagihan::create([
                'hunian_id'           => $hunianBaru->hunian_id,
                'tanggal_generate'    => $jadwalLama->tanggal_generate, 
                'tanggal_jatuh_tempo' => $jadwalLama->tanggal_jatuh_tempo,
                'status_jadwal'       => 'aktif',
            ]);
        } else {
            // Fallback: Jika data jadwal lama tidak ditemukan, buat dengan tanggal hari ini (max tgl 28)
            JadwalTagihan::create([
                'hunian_id'           => $hunianBaru->hunian_id,
                'tanggal_generate'    => min((int) now()->format('d'), 28),
                'tanggal_jatuh_tempo' => 7,
                'status_jadwal'       => 'aktif',
            ]);
        }

        // 5. Bersihkan state form dan kembalikan ke view table
        $this->reset(['reaktivasiId', 'reaktivasiNama', 'reaktivasiKamarId']);
        $this->activeView = 'table';
        $this->resetPage();
        $this->dispatch('toast', pesan: 'Penghuni berhasil diaktifkan kembali dengan penyesuaian jadwal lama.', tipe: 'sukses');
    }

    public function render()
    {
        $penghuniList = User::where('role', 'penghuni')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('no_wa', 'like', "%{$this->search}%")
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_akun', $this->filterStatus)
            )
            ->with(['hunian' => fn($q) =>
                $q->where('status_hunian', 'aktif')->with('kamar')
            ])
            ->orderBy('name')
            ->paginate(10);

        $kamarTersedia = Kamar::where('status_kamar', 'tersedia')
            ->when($this->isEditing && $this->kamar_id_asal, fn($q) =>
                $q->orWhere('kamar_id', $this->kamar_id_asal)
            )
            ->orderBy('nomor_kamar')
            ->get();

        return view('components.admin.penghuni.table',
            compact('penghuniList', 'kamarTersedia'));
    }
};
?>

<div
    x-data="{
        showSkeleton: false,
        deletePopup: { show: false, id: null, nama: '' },
        toast: { show: false, pesan: '', tipe: 'sukses' },

        mulaiSkeleton() {
            this.showSkeleton = true;
            setTimeout(() => {
                this.showSkeleton = false;
                // Tentukan view tujuan berdasarkan state Livewire
                if ($wire.reaktivasiId) {
                    $wire.activeView = 'form-reaktivasi';
                } else {
                    $wire.activeView = 'form';
                }
            }, 700);
        },
        bukaPopupHapus(id, nama) {
            this.deletePopup = { show: true, id, nama };
        },
        tutupPopupHapus() {
            this.deletePopup = { show: false, id: null, nama: '' };
        },
        konfirmasiHapus() {
            $wire.delete(this.deletePopup.id);
            this.tutupPopupHapus();
        },
        tampilToast(pesan, tipe = 'sukses') {
            this.toast = { show: true, pesan, tipe };
            setTimeout(() => this.toast.show = false, 3000);
        }
    }"
    x-init="
        $watch('$wire.activeView', val => {
            if (val === 'skeleton') mulaiSkeleton();
        });
        $wire.on('toast', ({ pesan, tipe }) => tampilToast(pesan, tipe));
    "
>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: TABEL
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'table'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0"
    >
        {{-- Toolbar --}}
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
            <div class="d-flex gap-2 flex-wrap">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari penghuni..."
                    >
                </div>
                <select
                    wire:model.live="filterStatus"
                    class="firabo-input"
                    style="width:auto; height:38px; padding:0 .875rem;"
                >
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <button wire:click="openCreate" class="btn-firabo">
                <i class="bi bi-plus-lg"></i> Tambah Penghuni
            </button>
        </div>

        {{-- Table + Card wrapper --}}
        <div
            class="table-card-wrapper"
            x-data="{ ready: false }"
            x-init="setTimeout(() => ready = true, 600)"
        >
            {{-- ── Desktop ── --}}
            <div class="table-view">
                <div class="firabo-card p-0 overflow-hidden">
                    <table class="firabo-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. WA</th>
                                <th>Kamar</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody x-show="!ready" x-cloak>
                            @include('components.admin.penghuni._skeleton')
                        </tbody>
                        <tbody x-show="ready" x-cloak>
                            @forelse($penghuniList as $user)
                                <tr>
                                    <td style="font-weight:500">{{ $user->name }}</td>
                                    <td style="font-size:13px; color:#6b7280">{{ $user->email }}</td>
                                    <td style="font-size:13px">{{ $user->no_wa ?? '-' }}</td>
                                    <td>
                                        @php $hunian = $user->hunian->first(); @endphp
                                        @if($hunian)
                                            <span style="color:var(--firabo-primary); font-weight:500">
                                                {{ $hunian->kamar->nomor_kamar ?? '-' }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->status_akun === 'aktif')
                                            <span class="badge-tersedia">Aktif</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($user->status_akun === 'aktif')
                                            <button
                                                wire:click="openEdit({{ $user->id }})"
                                                class="btn btn-sm btn-outline-secondary me-1"
                                                title="Edit"
                                            ><i class="bi bi-pencil"></i></button>
                                            <button
                                                @click="bukaPopupHapus({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Nonaktifkan"
                                            ><i class="bi bi-person-x"></i></button>
                                        @else
                                            <button
                                                wire:click="openReaktivasi({{ $user->id }})"
                                                class="btn btn-sm btn-outline-success"
                                                title="Aktifkan Kembali"
                                            ><i class="bi bi-person-check"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        Tidak ada penghuni ditemukan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @include('components.admin.penghuni._pagination', ['data' => $penghuniList])
                </div>
            </div>

            {{-- ── Mobile: Card View ── --}}
            <div class="card-view">
                <div x-show="!ready" x-cloak>
                    @for($i = 0; $i < 4; $i++)
                        <div class="item-card">
                            <div class="item-card-header">
                                <div class="skeleton skeleton-text" style="width:130px; height:16px;"></div>
                                <div class="skeleton" style="width:70px; height:28px; border-radius:6px;"></div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:35px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:55px;"></div>
                                </div>
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-badge"></div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="skeleton skeleton-text" style="width:35px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:160px;"></div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div x-show="ready" x-cloak>
                    @forelse($penghuniList as $user)
                        @php $hunian = $user->hunian->first(); @endphp
                        <div class="item-card">
                            <div class="item-card-header">
                                <span class="item-card-title">{{ $user->name }}</span>
                                <div class="item-card-actions">
                                    @if($user->status_akun === 'aktif')
                                        <button
                                            wire:click="openEdit({{ $user->id }})"
                                            class="btn btn-sm btn-outline-secondary"
                                        ><i class="bi bi-pencil"></i></button>
                                        <button
                                            @click="bukaPopupHapus({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                            class="btn btn-sm btn-outline-danger"
                                        ><i class="bi bi-person-x"></i></button>
                                    @else
                                        <button
                                            wire:click="openReaktivasi({{ $user->id }})"
                                            class="btn btn-sm btn-outline-success"
                                        ><i class="bi bi-person-check"></i></button>
                                    @endif
                                </div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="field-label">Kamar</div>
                                    <div class="field-value" style="color:var(--firabo-primary); font-weight:500">
                                        {{ $hunian?->kamar->nomor_kamar ?? '-' }}
                                    </div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Status</div>
                                    <div class="field-value">
                                        @if($user->status_akun === 'aktif')
                                            <span class="badge-tersedia">Aktif</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="field-label">Email</div>
                                    <div class="field-value" style="font-size:12px;">{{ $user->email }}</div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="field-label">No. WA</div>
                                    <div class="field-value">{{ $user->no_wa ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Tidak ada penghuni ditemukan
                        </div>
                    @endforelse
                    @include('components.admin.penghuni._pagination', ['data' => $penghuniList])
                </div>
            </div>

        </div>{{-- /table-card-wrapper --}}
    </div>{{-- /view:table --}}

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: SKELETON TRANSISI
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="showSkeleton"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0"
        x-cloak
    >
        @include('components.admin.penghuni._skeleton-form')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: FORM TAMBAH / EDIT
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'form'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
    >
        @include('components.admin.penghuni._modal-form')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: FORM REAKTIVASI
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'form-reaktivasi'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
    >
        @include('components.admin.penghuni._modal-reaktivasi')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         POPUP NONAKTIFKAN
    ════════════════════════════════════════════════════════════════ --}}
    @include('components.admin.penghuni._modal-delete')

    {{-- ════════════════════════════════════════════════════════════════
         TOAST NOTIFICATION
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-end="opacity-0 translate-y-3"
        x-cloak
        class="firabo-toast"
        :class="toast.tipe === 'sukses' ? 'firabo-toast--sukses' : 'firabo-toast--gagal'"
    >
        <i class="bi" :class="toast.tipe === 'sukses' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
        <span x-text="toast.pesan"></span>
    </div>

</div>{{-- /root --}}
