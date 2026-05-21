<?php

use App\Models\User;
use App\Models\Kamar;
use App\Models\Hunian;
use App\Models\JadwalTagihan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $editingId = null;

    public bool $showDeleteConfirm = false;
    public ?int $deletingId = null;

    public string $name = '';
    public string $email = '';
    public string $no_wa = '';
    public string $password = '';
    public string $kamar_id = '';
    public string $tanggal_masuk = '';
    public string $tanggal_generate = '1';
    public string $tanggal_jatuh_tempo = '7';

    protected function rules(): array
    {
        return [
            'name'                => 'required|string|max:100',
            'email'               => 'required|email|unique:users,email,'
                                     . ($this->editingId ?? 'NULL'),
            'no_wa'               => 'required|string|max:20',
            'password'            => $this->isEditing ? 'nullable|min:6' : 'required|min:6',
            'kamar_id'            => 'required|exists:tb_kamar,kamar_id',
            'tanggal_masuk'       => 'required|date',
            'tanggal_generate'    => 'required|integer|min:1|max:28',
            'tanggal_jatuh_tempo' => 'required|integer|min:1|max:30',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'no_wa', 'password',
                      'kamar_id', 'tanggal_masuk', 'editingId']);
        $this->tanggal_generate    = '1';
        $this->tanggal_jatuh_tempo = '7';
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->reset(['name', 'email', 'no_wa', 'password', 'kamar_id', 'tanggal_masuk']);

        $user   = User::findOrFail($id);
        $hunian = $user->hunian()->where('status_hunian', 'aktif')
                       ->with('jadwalTagihan')->first();

        $this->editingId           = $user->id;
        $this->name                = $user->name;
        $this->email               = $user->email;
        $this->no_wa               = $user->no_wa ?? '';
        $this->password            = '';
        $this->kamar_id            = (string) ($hunian?->kamar_id ?? '');
        $this->tanggal_masuk       = $hunian?->tanggal_masuk?->format('Y-m-d') ?? '';
        $this->tanggal_generate    = (string) ($hunian?->jadwalTagihan?->tanggal_generate ?? '1');
        $this->tanggal_jatuh_tempo = (string) ($hunian?->jadwalTagihan?->tanggal_jatuh_tempo ?? '7');
        $this->isEditing           = true;
        $this->showModal           = true;
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

            $hunian = $user->hunian()->where('status_hunian', 'aktif')->first();
            if ($hunian) {
                $hunian->update([
                    'kamar_id'      => $this->kamar_id,
                    'tanggal_masuk' => $this->tanggal_masuk,
                ]);
                $hunian->jadwalTagihan?->update([
                    'tanggal_generate'    => $this->tanggal_generate,
                    'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
                ]);
            }
            session()->flash('success', 'Data penghuni berhasil diperbarui.');
        } else {
            $user = User::create([
                'name'        => $this->name,
                'email'       => $this->email,
                'no_wa'       => $this->no_wa,
                'password'    => Hash::make($this->password),
                'role'        => 'penghuni',
                'status_akun' => 'aktif',
            ]);

            Kamar::where('kamar_id', $this->kamar_id)
                 ->update(['status_kamar' => 'terisi']);

            $hunian = Hunian::create([
                'user_id'       => $user->id,
                'kamar_id'      => $this->kamar_id,
                'tanggal_masuk' => $this->tanggal_masuk,
                'status_hunian' => 'aktif',
            ]);

            JadwalTagihan::create([
                'hunian_id'           => $hunian->hunian_id,
                'tanggal_generate'    => $this->tanggal_generate,
                'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
                'status_jadwal'       => 'aktif',
            ]);

            session()->flash('success', 'Penghuni baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId        = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $user   = User::findOrFail($this->deletingId);
        $hunian = $user->hunian()->where('status_hunian', 'aktif')->first();

        if ($hunian) {
            Kamar::where('kamar_id', $hunian->kamar_id)
                 ->update(['status_kamar' => 'tersedia']);
            $hunian->update([
                'status_hunian'  => 'selesai',
                'tanggal_keluar' => now(),
            ]);
        }

        $user->update(['status_akun' => 'nonaktif']);
        $this->showDeleteConfirm = false;
        session()->flash('success', 'Penghuni berhasil dinonaktifkan.');
        $this->resetPage();
    }

    public function render()
    {
        $penghuniList = User::where('role', 'penghuni')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('no_wa', 'like', "%{$this->search}%")
            )
            ->with(['hunian' => fn($q) =>
                $q->where('status_hunian', 'aktif')->with('kamar')
            ])
            ->orderBy('name')
            ->paginate(10);

        $kamarTersedia = Kamar::where('status_kamar', 'tersedia')
            ->orderBy('nomor_kamar')
            ->get();

        return view('components.admin.penghuni.table',
            compact('penghuniList', 'kamarTersedia'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 600)"
    class="table-card-wrapper"
>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Cari penghuni...">
        </div>
        <button wire:click="openCreate" class="btn-firabo">
            <i class="bi bi-plus-lg"></i> Tambah Penghuni
        </button>
    </div>

    {{-- Desktop Table --}}
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
                            <button wire:click="openEdit({{ $user->id }})"
                                    class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $user->id }})"
                                    class="btn btn-sm btn-outline-danger"
                                    {{ $user->status_akun === 'nonaktif' ? 'disabled' : '' }}>
                                <i class="bi bi-person-x"></i>
                            </button>
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

    {{-- Mobile Card --}}
    <div class="card-view">
        <div x-show="!ready" x-cloak>
            @for($i = 0; $i < 4; $i++)
            <div class="item-card">
                <div class="item-card-header">
                    <div class="skeleton skeleton-text" style="width:130px; height:16px"></div>
                    <div class="skeleton" style="width:70px; height:28px; border-radius:6px"></div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:35px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:55px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-badge"></div>
                    </div>
                    <div class="item-card-field full-width">
                        <div class="skeleton skeleton-text" style="width:35px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:160px"></div>
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
                        <button wire:click="openEdit({{ $user->id }})"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button wire:click="confirmDelete({{ $user->id }})"
                                class="btn btn-sm btn-outline-danger"
                                {{ $user->status_akun === 'nonaktif' ? 'disabled' : '' }}>
                            <i class="bi bi-person-x"></i>
                        </button>
                    </div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="field-label">Kamar</div>
                        <div class="field-value" style="color:var(--firabo-primary)">
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
                        <div class="field-value" style="font-size:12px">{{ $user->email }}</div>
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

    @include('components.admin.penghuni._modal-form')
    @include('components.admin.penghuni._modal-delete')
</div>