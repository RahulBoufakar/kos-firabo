<?php

use App\Models\JadwalTagihan;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // ── View State ─────────────────────────────────────────────────────────
    public string $activeView = 'table';

    // ── Filter ─────────────────────────────────────────────────────────────
    public string $search = '';
    public string $filterStatus = 'aktif'; // Default ke 'aktif' jadwal

    // ── Form Fields ────────────────────────────────────────────────────────
    public ?int   $editingId           = null;
    public string $tanggal_generate    = '';
    public string $tanggal_jatuh_tempo = '';
    public string $status_jadwal       = ''; 

    // Reset halaman ke nomor 1 jika filter berubah
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openEdit(int $id): void
    {
        $jadwal = JadwalTagihan::findOrFail($id);

        $this->editingId           = $jadwal->jadwal_id;
        $this->status_jadwal       = strtolower(trim($jadwal->status_jadwal));
        $this->tanggal_generate    = (string) $jadwal->tanggal_generate;
        $this->tanggal_jatuh_tempo = (string) $jadwal->tanggal_jatuh_tempo;
        $this->resetValidation();
        $this->activeView          = 'skeleton';
    }

    public function cancelForm(): void
    {
        $this->reset(['editingId', 'tanggal_generate', 'tanggal_jatuh_tempo', 'status_jadwal']);
        $this->resetValidation();
        $this->activeView = 'table';
    }

    public function save(): void
    {
        $this->validate([
            'tanggal_generate'    => 'required|integer|min:1|max:28',
            'tanggal_jatuh_tempo' => 'required|integer|min:1|max:30',
            'status_jadwal'       => 'required|in:aktif,nonaktif',
        ], [
            'tanggal_generate.min'    => 'Minimal tanggal 1.',
            'tanggal_generate.max'    => 'Maksimal tanggal 28 (aman untuk Februari).',
            'tanggal_jatuh_tempo.min' => 'Minimal 1 hari.',
            'tanggal_jatuh_tempo.max' => 'Maksimal 30 hari.',
            'status_jadwal.in'        => 'Status jadwal tidak valid.',
        ]);

        JadwalTagihan::findOrFail($this->editingId)->update([
            'tanggal_generate'    => $this->tanggal_generate,
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
            'status_jadwal'       => $this->status_jadwal,
        ]);

        $this->reset(['editingId', 'tanggal_generate', 'tanggal_jatuh_tempo', 'status_jadwal']);
        $this->activeView = 'table';
        $this->resetPage();

        $this->dispatch('toast', pesan: 'Jadwal tagihan berhasil diperbarui.', tipe: 'sukses');
    }

    public function render()
    {
        $jadwalList = JadwalTagihan::query()
            ->with(['hunian.user', 'hunian.kamar'])
            // Filter Pencarian Nama / Kamar
            ->when($this->search, fn($q) =>
                $q->whereHas('hunian.user', fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                )->orWhereHas('hunian.kamar', fn($q) =>
                    $q->where('nomor_kamar', 'like', "%{$this->search}%")
                )
            )
            // KUNCI FILTER STATUS JADWAL
            ->when($this->filterStatus !== 'semua', fn($q) =>
                $q->where('status_jadwal', $this->filterStatus)
            )
            ->orderBy('jadwal_id')
            ->paginate(10);

        return view('components.admin.jadwal.table', compact('jadwalList'));
    }
};
?>

<div
    x-data="{
        showSkeleton: false,
        toast: { show: false, pesan: '', tipe: 'sukses' },

        mulaiSkeleton() {
            this.showSkeleton = true;
            setTimeout(() => {
                this.showSkeleton = false;
                $wire.activeView = 'form';
            }, 700);
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
        {{-- Toolbar dengan Tambahan Dropdown Filter Status --}}
        <div class="mb-3">
            <div class="row g-2 align-items-center">
                {{-- Kolom Input Cari --}}
                <div class="col">
                    <div class="search-bar w-100">
                        <i class="bi bi-search"></i>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama penghuni / kamar..."
                        >
                    </div>
                </div>
                {{-- Kolom Dropdown Filter Status --}}
                <div class="col-auto">
                    <select 
                        wire:model.live="filterStatus" 
                        class="form-select filter-select"
                        style="height: 42px; min-width: 160px; border-radius: 10px; font-size: 0.875rem;"
                    >
                        <option value="semua">Semua Jadwal</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
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
                                <th>Penghuni</th>
                                <th>Kamar</th>
                                <th>Tanggal Generate</th>
                                <th>Jatuh Tempo</th>
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
                                    <td>Tanggal {{ $jadwal->tanggal_generate }}</td>
                                    <td>{{ $jadwal->tanggal_jatuh_tempo }} hari</td>
                                    <td>
                                        @if(strtolower(trim($jadwal->status_jadwal)) === 'aktif')
                                            <span class="badge-tersedia">Aktif</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button
                                            wire:click="openEdit({{ $jadwal->jadwal_id }})"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Edit Jadwal"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
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

            {{-- ── Mobile: Card View ── --}}
            <div class="card-view">
                <div x-show="!ready" x-cloak>
                    @for($i = 0; $i < 4; $i++)
                        <div class="item-card">
                            <div class="item-card-header">
                                <div class="skeleton skeleton-text" style="width:120px; height:16px;"></div>
                                <div class="skeleton" style="width:36px; height:28px; border-radius:6px;"></div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:60px;"></div>
                                </div>
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-badge"></div>
                                </div>
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:80px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:70px;"></div>
                                </div>
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:70px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:50px;"></div>
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
                                    <button
                                        wire:click="openEdit({{ $jadwal->jadwal_id }})"
                                        class="btn btn-sm btn-outline-secondary"
                                    ><i class="bi bi-pencil"></i></button>
                                </div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="field-label">Kamar</div>
                                    <div class="field-value" style="color:var(--firabo-primary); font-weight:500">
                                        {{ $jadwal->hunian->kamar->nomor_kamar ?? '-' }}
                                    </div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Status</div>
                                    <div class="field-value">
                                        @if(strtolower(trim($jadwal->status_jadwal)) === 'aktif')
                                            <span class="badge-tersedia">Aktif</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Tgl Generate</div>
                                    <div class="field-value">Tanggal {{ $jadwal->tanggal_generate }}</div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Jatuh Tempo</div>
                                    <div class="field-value">{{ $jadwal->tanggal_jatuh_tempo }} hari</div>
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
        @include('components.admin.jadwal._skeleton-form')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: FORM EDIT
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'form'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
    >
        @include('components.admin.jadwal._modal-form')
    </div>

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
        <i
            class="bi"
            :class="toast.tipe === 'sukses' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"
        ></i>
        <span x-text="toast.pesan"></span>
    </div>

</div>{{-- /root --}}

<style>
/* ── Toast ── */
.firabo-toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 2000;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .625rem 1rem;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    white-space: nowrap;
    pointer-events: none;
}
.firabo-toast--sukses {
    background: #fff;
    color: #166534;
    border: 1px solid #86efac;
}
.firabo-toast--sukses .bi { color: #16a34a; font-size: 1rem; }
.firabo-toast--gagal {
    background: #fff;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
.firabo-toast--gagal .bi { color: #dc2626; font-size: 1rem; }

/* Tambahan Style untuk Dropdown Filter */
.filter-select {
    border: 1px solid #e2e8f0;
    color: #475569;
    background-color: #fff;
    padding-left: 1rem;
    padding-right: 2.5rem;
    transition: all 0.2s;
}
.filter-select:focus {
    border-color: var(--firabo-primary);
    box-shadow: 0 0 0 3px rgba(var(--firabo-primary-rgb), 0.1);
    outline: none;
}
</style>