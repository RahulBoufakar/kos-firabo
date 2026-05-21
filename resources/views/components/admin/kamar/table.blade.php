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
            'nomor_kamar'  => 'required|string|max:10|unique:tb_kamar,nomor_kamar,'
                              . ($this->editingId ?? 'NULL') . ',kamar_id',
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
        $this->reset(['nomor_kamar', 'tipe_kamar', 'harga_sewa',
                      'fasilitas', 'editingId']);
        $this->status_kamar = 'tersedia';
        $this->isEditing    = false;
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $this->reset(['nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas']);

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

        return view('components.admin.kamar.table', compact('kamar'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 600)"
    class="table-card-wrapper"
>   
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
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari kamar...">
            </div>
            <select wire:model.live="filterStatus"
                    class="firabo-input"
                    style="width:auto; height:38px; padding:0 0.875rem">
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

    {{-- ── DESKTOP — Table view ── --}}
    <div class="table-view">
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

                {{-- Skeleton --}}
                <tbody x-show="!ready" x-cloak>
                    @include('components.admin.kamar._skeleton')
                </tbody>

                {{-- Data --}}
                <tbody x-show="ready" x-cloak>
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

            {{-- Pagination desktop --}}
            @include('components.admin.kamar._pagination', ['data' => $kamar])
        </div>
    </div>

    {{-- ── MOBILE — Card view ── --}}
    <div class="card-view">

        {{-- Skeleton mobile --}}
        <div x-show="!ready" x-cloak>
            @for($i = 0; $i < 4; $i++)
            <div class="item-card">
                <div class="item-card-header">
                    <div class="skeleton skeleton-text" style="width:70px; height:16px"></div>
                    <div class="skeleton" style="width:70px; height:28px; border-radius:6px"></div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:90px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-badge"></div>
                    </div>
                    <div class="item-card-field full-width">
                        <div class="skeleton skeleton-text" style="width:50px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:140px"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        {{-- Data cards mobile --}}
        <div x-show="ready" x-cloak>
            @forelse($kamar as $item)
            <div class="item-card">
                <div class="item-card-header">
                    <span class="item-card-title">{{ $item->nomor_kamar }}</span>
                    <div class="item-card-actions">
                        <button wire:click="openEdit({{ $item->kamar_id }})"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button wire:click="confirmDelete({{ $item->kamar_id }})"
                                class="btn btn-sm btn-outline-danger"
                                {{ $item->status_kamar === 'terisi' ? 'disabled' : '' }}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="field-label">Tipe</div>
                        <div class="field-value">{{ $item->tipe_kamar }}</div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Status</div>
                        <div class="field-value">
                            @if($item->status_kamar === 'tersedia')
                                <span class="badge-tersedia">Tersedia</span>
                            @elseif($item->status_kamar === 'terisi')
                                <span class="badge-terisi">Terisi</span>
                            @else
                                <span class="badge-nonaktif">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Harga Sewa</div>
                        <div class="field-value">
                            Rp {{ number_format($item->harga_sewa, 0, ',', '.') }}/bln
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Fasilitas</div>
                        <div class="field-value" style="font-size:12px">
                            {{ $item->fasilitas ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                Tidak ada data kamar ditemukan
            </div>
            @endforelse

            {{-- Pagination mobile --}}
            @include('components.admin.kamar._pagination', ['data' => $kamar])
        </div>
    </div>

    {{-- Modals --}}
    @include('components.admin.kamar._modal-form')
    @include('components.admin.kamar._modal-delete')
</div>