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
                )
                ->orWhereHas('hunian.kamar', fn($q) =>
                    $q->where('nomor_kamar', 'like', "%{$this->search}%")
                )
            )
            ->orderBy('jadwal_id')
            ->paginate(10);

        return view('components.admin.jadwal-tagihan-table', compact('jadwalList'));
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

    <div class="search-bar mb-3">
        <i class="bi bi-search"></i>
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Cari nama / kamar...">
    </div>

    <div class="firabo-card p-0 overflow-hidden">
        <div style="position:relative">
            <div wire:loading
                 style="position:absolute; inset:0; background:rgba(255,255,255,0.75);
                        z-index:10; display:flex; align-items:center; justify-content:center;
                        border-radius:0 0 12px 12px">
                <div class="d-flex align-items-center gap-2" style="color:var(--firabo-primary)">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span style="font-size:13px">Memuat...</span>
                </div>
            </div>
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
                <tbody>
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

                        {{-- Inline edit tanggal_generate --}}
                        <td>
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_generate"
                                    class="firabo-input" style="width:80px; height:32px; padding:0 8px"
                                    min="1" max="28">
                                @error('tanggal_generate')
                                    <div class="text-danger" style="font-size:11px">{{ $message }}</div>
                                @enderror
                            @else
                                Tanggal {{ $jadwal->tanggal_generate }}
                            @endif
                        </td>

                        {{-- Inline edit tanggal_jatuh_tempo --}}
                        <td>
                            @if($editingId == $jadwal->jadwal_id)
                                <input type="number" wire:model.live="tanggal_jatuh_tempo"
                                    class="firabo-input" style="width:80px; height:32px; padding:0 8px"
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
        </div>
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
             style="font-size:13px; color:#6b7280">
            <span>
                Menampilkan {{ $jadwalList->firstItem() ?? 0 }}–{{ $jadwalList->lastItem() ?? 0 }}
                dari {{ $jadwalList->total() }} jadwal
            </span>
            <div class="firabo-pagination">
                <button class="page-btn" wire:click="previousPage"
                        {{ !$jadwalList->onFirstPage() ?: 'disabled' }}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                @foreach($jadwalList->getUrlRange(1, $jadwalList->lastPage()) as $page => $url)
                    <button class="page-btn {{ $page == $jadwalList->currentPage() ? 'active' : '' }}"
                            wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                @endforeach
                <button class="page-btn" wire:click="nextPage"
                        {{ $jadwalList->hasMorePages() ?: 'disabled' }}>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>