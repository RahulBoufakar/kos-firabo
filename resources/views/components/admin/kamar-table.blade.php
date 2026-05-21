<?php

use App\Models\Kamar;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $editingId = null;

    public bool $showDeleteConfirm = false;
    public ?int $deletingId = null;

    public string $nomor_kamar = '';
    public string $tipe_kamar = '';
    public string $harga_sewa = '';
    public string $fasilitas = '';
    public string $status_kamar = 'tersedia';

    protected function rules(): array
    {
        return [
            'nomor_kamar'  => 'required|string|max:10|unique:tb_kamar,nomor_kamar,' . ($this->editingId ?? 'NULL') . ',kamar_id',
            'tipe_kamar'   => 'required|string|max:50',
            'harga_sewa'   => 'required|numeric|min:0',
            'fasilitas'    => 'nullable|string',
            'status_kamar' => 'required|in:tersedia,terisi,nonaktif',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['nomor_kamar','tipe_kamar','harga_sewa','fasilitas','editingId']);
        $this->status_kamar = 'tersedia';
        $this->isEditing    = false;
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $kamar = Kamar::findOrFail($id);
        $this->editingId    = $kamar->kamar_id;
        $this->nomor_kamar  = $kamar->nomor_kamar;
        $this->tipe_kamar   = $kamar->tipe_kamar;
        $this->harga_sewa   = (string) $kamar->harga_sewa;
        $this->fasilitas    = $kamar->fasilitas ?? '';
        $this->status_kamar = $kamar->status_kamar;
        $this->isEditing    = true;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            Kamar::findOrFail($this->editingId)->update([
                'nomor_kamar'  => $this->nomor_kamar,
                'tipe_kamar'   => $this->tipe_kamar,
                'harga_sewa'   => $this->harga_sewa,
                'fasilitas'    => $this->fasilitas,
                'status_kamar' => $this->status_kamar,
            ]);
            session()->flash('success', 'Data kamar berhasil diperbarui.');
        } else {
            Kamar::create([
                'nomor_kamar'  => $this->nomor_kamar,
                'tipe_kamar'   => $this->tipe_kamar,
                'harga_sewa'   => $this->harga_sewa,
                'fasilitas'    => $this->fasilitas,
                'status_kamar' => $this->status_kamar,
            ]);
            session()->flash('success', 'Kamar baru berhasil ditambahkan.');
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
        Kamar::findOrFail($this->deletingId)->delete();
        $this->showDeleteConfirm = false;
        $this->deletingId        = null;
        session()->flash('success', 'Kamar berhasil dihapus.');
        $this->resetPage();
    }

    public function render()
    {
        $kamar = Kamar::query()
            ->when($this->search, fn($q) =>
                $q->where('nomor_kamar', 'like', "%{$this->search}%")
                  ->orWhere('tipe_kamar', 'like', "%{$this->search}%")
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_kamar', $this->filterStatus)
            )
            ->orderBy('nomor_kamar')
            ->paginate(10);

        return view('components.admin.kamar-table', compact('kamar'));
    }
};
?>

<div>
    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="d-flex gap-2 flex-wrap">
            <div class="search-bar">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Cari kamar...">
            </div>
            <select wire:model.live="filterStatus"
                    class="firabo-input" style="width:auto; height:38px; padding:0 0.875rem">
                <option value="">Semua Status</option>
                <option value="tersedia">Tersedia</option>
                <option value="terisi">Terisi</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>
        <button wire:click="openCreate" class="btn-firabo">
            <i class="bi bi-plus-lg"></i> Tambah Kamar
        </button>
    </div>

    {{-- Table --}}
    <div class="firabo-card p-0 overflow-hidden">
        <table class="firabo-table">
            <thead>
                <tr>
                    <th>Nomor Kamar</th>
                    <th>Tipe Kamar</th>
                    <th>Harga Sewa</th>
                    <th>Fasilitas</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>

            {{-- Skeleton saat loading --}}
            <tbody wire:loading.class.remove="d-none" class="d-none">
                @for($i = 0; $i < 5; $i++)
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width:55px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:110px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:130px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:90px"></div></td>
                    <td><div class="skeleton skeleton-badge"></div></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>

            <tbody wire:loading.remove>
                @forelse($kamar as $item)
                <tr>
                    <td>
                        <span style="color:var(--firabo-primary); font-weight:500">
                            {{ $item->nomor_kamar }}
                        </span>
                    </td>
                    <td>{{ $item->tipe_kamar }}</td>
                    <td>Rp {{ number_format($item->harga_sewa, 0, ',', '.') }}/bln</td>
                    <td style="max-width:180px">
                        <span class="text-truncate d-block" title="{{ $item->fasilitas }}">
                            {{ $item->fasilitas ?? '-' }}
                        </span>
                    </td>
                    <td>
                        @if($item->status_kamar === 'tersedia')
                            <span class="badge-tersedia">Tersedia</span>
                        @elseif($item->status_kamar === 'terisi')
                            <span class="badge-terisi">Terisi</span>
                        @else
                            <span class="badge-nonaktif">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button wire:click="openEdit({{ $item->kamar_id }})"
                                class="btn btn-sm btn-outline-secondary me-1">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button wire:click="confirmDelete({{ $item->kamar_id }})"
                                class="btn btn-sm btn-outline-danger"
                                {{ $item->status_kamar === 'terisi' ? 'disabled' : '' }}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        Tidak ada data kamar ditemukan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
             style="font-size:13px; color:#6b7280">
            <span>
                Menampilkan {{ $kamar->firstItem() ?? 0 }}–{{ $kamar->lastItem() ?? 0 }}
                dari {{ $kamar->total() }} kamar
            </span>
            <div class="firabo-pagination">
                <button class="page-btn" wire:click="previousPage"
                        {{ !$kamar->onFirstPage() ?: 'disabled' }}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                @foreach($kamar->getUrlRange(1, $kamar->lastPage()) as $page => $url)
                    <button class="page-btn {{ $page == $kamar->currentPage() ? 'active' : '' }}"
                            wire:click="gotoPage({{ $page }})">
                        {{ $page }}
                    </button>
                @endforeach
                <button class="page-btn" wire:click="nextPage"
                        {{ $kamar->hasMorePages() ?: 'disabled' }}>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Create / Edit --}}
    @if($showModal)
    <div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
        <div class="modal-box mt-3">
            <div class="modal-box-header">
                <h5 class="mb-0 fw-600">
                    {{ $isEditing ? 'Edit Kamar' : 'Tambah Kamar Baru' }}
                </h5>
                <button wire:click="$set('showModal', false)" class="btn-close"></button>
            </div>
            <div class="modal-box-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Nomor Kamar</label>
                        <input type="text" wire:model="nomor_kamar"
                               class="firabo-input" placeholder="A-101">
                        @error('nomor_kamar')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Tipe Kamar</label>
                        <input type="text" wire:model="tipe_kamar"
                               class="firabo-input" placeholder="Standard AC">
                        @error('tipe_kamar')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Harga Sewa/Bulan</label>
                        <input type="number" wire:model="harga_sewa"
                               class="firabo-input" placeholder="1500000">
                        @error('harga_sewa')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Status</label>
                        <select wire:model="status_kamar" class="firabo-input">
                            <option value="tersedia">Tersedia</option>
                            <option value="terisi">Terisi</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        @error('status_kamar')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-size:14px; font-weight:500">Fasilitas</label>
                        <textarea wire:model="fasilitas" class="firabo-input"
                                  rows="3" placeholder="AC, WiFi, Kamar Mandi Dalam..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-box-footer">
                <button wire:click="$set('showModal', false)" class="btn-firabo-outline me-2">
                    Batal
                </button>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-firabo">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambah Kamar' }}
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
                <h5 class="mb-0 fw-600 text-danger">Hapus Kamar</h5>
                <button wire:click="$set('showDeleteConfirm', false)" class="btn-close"></button>
            </div>
            <div class="modal-box-body">
                <p class="mb-0" style="font-size:14px">
                    Yakin ingin menghapus kamar ini? Data yang sudah dihapus tidak dapat dikembalikan.
                </p>
            </div>
            <div class="modal-box-footer">
                <button wire:click="$set('showDeleteConfirm', false)" class="btn-firabo-outline me-2">
                    Batal
                </button>
                <button wire:click="delete" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    @endif
</div>