<?php

use App\Models\JadwalTagihan;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public string $tanggal_generate = '';
    public string $tanggal_jatuh_tempo = '';

    protected $rules = [
        'tanggal_generate'    => 'required|integer|min:1|max:28',
        'tanggal_jatuh_tempo' => 'required|integer|min:1|max:30',
    ];

    public function updatingSearch(): void { $this->resetPage(); }

    public function startEdit(int $id): void
    {
        $this->reset(['tanggal_generate', 'tanggal_jatuh_tempo']);
        $jadwal = JadwalTagihan::findOrFail($id);
        $this->editingId           = $jadwal->jadwal_id;
        $this->tanggal_generate    = (string) $jadwal->tanggal_generate;
        $this->tanggal_jatuh_tempo = (string) $jadwal->tanggal_jatuh_tempo;
    }

    public function cancelEdit(): void
    {
        $this->editingId           = null;
        $this->tanggal_generate    = '';
        $this->tanggal_jatuh_tempo = '';
    }

    public function save(int $id): void
    {
        $this->validate();
        JadwalTagihan::findOrFail($id)->update([
            'tanggal_generate'    => $this->tanggal_generate,
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
        ]);
        $this->cancelEdit();
        session()->flash('success', 'Jadwal tagihan berhasil diperbarui.');
        $this->resetPage();
    }

    public function render()
    {
        $jadwalList = JadwalTagihan::query()
            ->with(['hunian.user', 'hunian.kamar'])
            ->when($this->search, fn($q) =>
                $q->whereHas('hunian.user', fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                )->orWhereHas('hunian.kamar', fn($q) =>
                    $q->where('nomor_kamar', 'like', "%{$this->search}%")
                )
            )
            ->orderBy('jadwal_id')
            ->paginate(10);

        return view('components.admin.jadwal.table', compact('jadwalList'));
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

    <div class="search-bar mb-3">
        <i class="bi bi-search"></i>
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Cari nama / kamar...">
    </div>

    {{-- Desktop Table --}}
    <div class="table-view">
        <div class="firabo-card p-0 overflow-hidden">
            <table class="firabo-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>Kamar</th>
                        <th>Tanggal Generate</th>
                        <th>Jatuh Tempo (hari)</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody x-show="!ready" x-cloak>
                    @include('components.admin.jadwal._skeleton')
                </tbody>
                <tbody x-show="ready" x-cloak>
                    @forelse($jadwalList as $jadwal)
                    <tr>
                        <td style="font-weight:500">
                            {{ $jadwal->hunian->user->name ?? '-' }}
                        </td>
                        <td>
                            <span style="color:var(--firabo-primary); font-weight:500">
                                {{ $jadwal->hunian->kamar->nomor_kamar ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_generate"
                                       class="firabo-input"
                                       style="width:80px; height:32px; padding:0 8px"
                                       min="1" max="28">
                                @error('tanggal_generate')
                                    <div class="text-danger" style="font-size:11px">{{ $message }}</div>
                                @enderror
                            @else
                                Tanggal {{ $jadwal->tanggal_generate }}
                            @endif
                        </td>
                        <td>
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_jatuh_tempo"
                                       class="firabo-input"
                                       style="width:80px; height:32px; padding:0 8px"
                                       min="1" max="30">
                                @error('tanggal_jatuh_tempo')
                                    <div class="text-danger" style="font-size:11px">{{ $message }}</div>
                                @enderror
                            @else
                                {{ $jadwal->tanggal_jatuh_tempo }} hari
                            @endif
                        </td>
                        <td>
                            @if($jadwal->status_jadwal === 'aktif')
                                <span class="badge-tersedia">Aktif</span>
                            @else
                                <span class="badge-nonaktif">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($editingId == $jadwal->jadwal_id)
                                <button wire:click="save({{ $jadwal->jadwal_id }})"
                                        class="btn btn-sm btn-success me-1">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button wire:click="cancelEdit"
                                        class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @else
                                <button wire:click="startEdit({{ $jadwal->jadwal_id }})"
                                        class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Tidak ada jadwal tagihan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @include('components.admin.jadwal._pagination', ['data' => $jadwalList])
        </div>
    </div>

    {{-- Mobile Card --}}
    <div class="card-view">
        <div x-show="!ready" x-cloak>
            @for($i = 0; $i < 4; $i++)
            <div class="item-card">
                <div class="item-card-header">
                    <div class="skeleton skeleton-text" style="width:130px; height:16px"></div>
                    <div class="skeleton" style="width:36px; height:28px; border-radius:6px"></div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:55px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-badge"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:80px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:80px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:90px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:60px"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        <div x-show="ready" x-cloak>
            @forelse($jadwalList as $jadwal)
            <div class="item-card">
                <div class="item-card-header">
                    <span class="item-card-title">
                        {{ $jadwal->hunian->user->name ?? '-' }}
                    </span>
                    <div class="item-card-actions">
                        @if($editingId == $jadwal->jadwal_id)
                            <button wire:click="save({{ $jadwal->jadwal_id }})"
                                    class="btn btn-sm btn-success">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button wire:click="cancelEdit"
                                    class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @else
                            <button wire:click="startEdit({{ $jadwal->jadwal_id }})"
                                    class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="field-label">Kamar</div>
                        <div class="field-value" style="color:var(--firabo-primary)">
                            {{ $jadwal->hunian->kamar->nomor_kamar ?? '-' }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Status</div>
                        <div class="field-value">
                            @if($jadwal->status_jadwal === 'aktif')
                                <span class="badge-tersedia">Aktif</span>
                            @else
                                <span class="badge-nonaktif">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Tgl Generate</div>
                        <div class="field-value">
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_generate"
                                       class="firabo-input"
                                       style="width:75px; height:32px; padding:0 8px"
                                       min="1" max="28">
                            @else
                                Tanggal {{ $jadwal->tanggal_generate }}
                            @endif
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Jatuh Tempo</div>
                        <div class="field-value">
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_jatuh_tempo"
                                       class="firabo-input"
                                       style="width:75px; height:32px; padding:0 8px"
                                       min="1" max="30">
                            @else
                                {{ $jadwal->tanggal_jatuh_tempo }} hari
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                Tidak ada jadwal tagihan
            </div>
            @endforelse
            @include('components.admin.jadwal._pagination', ['data' => $jadwalList])
        </div>
    </div>
</div>