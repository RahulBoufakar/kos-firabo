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
            'email'               => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
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
        $this->reset(['name','email','no_wa','password','kamar_id','tanggal_masuk','editingId']);
        $this->tanggal_generate    = '1';
        $this->tanggal_jatuh_tempo = '7';
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
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
            $hunian->update(['status_hunian' => 'selesai', 'tanggal_keluar' => now()]);
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

        $kamarTersedia = Kamar::whereIn('status_kamar', ['tersedia'])
            ->orderBy('nomor_kamar')
            ->get();

        return view('components.admin.penghuni-table',
            compact('penghuniList', 'kamarTersedia'));
    }
};
?>

<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

            <tbody wire:loading.class.remove="d-none" class="d-none">
                @for($i = 0; $i < 5; $i++)
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width:120px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:160px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:100px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:50px"></div></td>
                    <td><div class="skeleton skeleton-badge"></div></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>

            <tbody wire:loading.remove>
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

        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
             style="font-size:13px; color:#6b7280">
            <span>
                Menampilkan {{ $penghuniList->firstItem() ?? 0 }}–{{ $penghuniList->lastItem() ?? 0 }}
                dari {{ $penghuniList->total() }} penghuni
            </span>
            <div class="firabo-pagination">
                <button class="page-btn" wire:click="previousPage"
                        {{ !$penghuniList->onFirstPage() ?: 'disabled' }}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                @foreach($penghuniList->getUrlRange(1, $penghuniList->lastPage()) as $page => $url)
                    <button class="page-btn {{ $page == $penghuniList->currentPage() ? 'active' : '' }}"
                            wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                @endforeach
                <button class="page-btn" wire:click="nextPage"
                        {{ $penghuniList->hasMorePages() ?: 'disabled' }}>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    @if($showModal)
    <div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
        <div class="modal-box" style="max-width:580px">
            <div class="modal-box-header">
                <h5 class="mb-0 fw-600">{{ $isEditing ? 'Edit Penghuni' : 'Tambah Penghuni Baru' }}</h5>
                <button wire:click="$set('showModal', false)" class="btn-close"></button>
            </div>
            <div class="modal-box-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Nama Lengkap</label>
                        <input type="text" wire:model="name" class="firabo-input" placeholder="Budi Santoso">
                        @error('name') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Email</label>
                        <input type="email" wire:model="email" class="firabo-input" placeholder="budi@email.com">
                        @error('email') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">No. WhatsApp</label>
                        <input type="text" wire:model="no_wa" class="firabo-input" placeholder="08123456789">
                        @error('no_wa') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">
                            Password {{ $isEditing ? '(kosongkan jika tidak diubah)' : '' }}
                        </label>
                        <input type="password" wire:model="password" class="firabo-input" placeholder="••••••••">
                        @error('password') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Kamar</label>
                        <select wire:model="kamar_id" class="firabo-input">
                            <option value="">Pilih Kamar</option>
                            @foreach($kamarTersedia as $kamar)
                                <option value="{{ $kamar->kamar_id }}">
                                    {{ $kamar->nomor_kamar }} — {{ $kamar->tipe_kamar }}
                                </option>
                            @endforeach
                        </select>
                        @error('kamar_id') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label" style="font-size:14px; font-weight:500">Tanggal Masuk</label>
                        <input type="date" wire:model="tanggal_masuk" class="firabo-input">
                        @error('tanggal_masuk') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    {{-- <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Tanggal Generate (1–28)</label>
                        <input type="number" wire:model="tanggal_generate" class="firabo-input" min="1" max="28">
                        <small class="text-muted" style="font-size:11px">Tanggal dalam bulan tagihan di-generate</small>
                        @error('tanggal_generate') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Jatuh Tempo (hari)</label>
                        <input type="number" wire:model="tanggal_jatuh_tempo" class="firabo-input" min="1" max="30">
                        <small class="text-muted" style="font-size:11px">Jarak hari dari tanggal generate</small>
                        @error('tanggal_jatuh_tempo') <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div> @enderror
                    </div> --}}
                </div>
            </div>
            <div class="modal-box-footer">
                <button wire:click="$set('showModal', false)" class="btn-firabo-outline me-2">Batal</button>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-firabo">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>{{ $isEditing ? 'Simpan' : 'Tambah Penghuni' }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="bi bi-arrow-repeat me-1"></i> Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirm Delete --}}
    @if($showDeleteConfirm)
    <div class="modal-backdrop-custom">
        <div class="modal-box" style="max-width:420px">
            <div class="modal-box-header">
                <h5 class="mb-0 fw-600 text-danger">Nonaktifkan Penghuni</h5>
                <button wire:click="$set('showDeleteConfirm', false)" class="btn-close"></button>
            </div>
            <div class="modal-box-body">
                <p class="mb-0" style="font-size:14px">
                    Penghuni akan dinonaktifkan dan kamar akan dibebaskan kembali.
                </p>
            </div>
            <div class="modal-box-footer">
                <button wire:click="$set('showDeleteConfirm', false)" class="btn-firabo-outline me-2">Batal</button>
                <button wire:click="delete" class="btn btn-danger">
                    <i class="bi bi-person-x me-1"></i> Ya, Nonaktifkan
                </button>
            </div>
        </div>
    </div>
    @endif
</div>