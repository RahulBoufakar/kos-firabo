<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    protected function baseQuery()
    {
        return User::where('status_akun', 'kabur')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['hunianTerakhir.kamar']);
    }

    public function render()
    {
        $penghuni = $this->baseQuery()->orderBy('name')->paginate(10);

        // Ringkasan dihitung dari SEMUA penghuni kabur yang cocok pencarian, bukan
        // cuma yang tampil di halaman saat ini — supaya angka totalnya tetap akurat
        // walau tabelnya dipaginasi.
        $semuaKabur = $this->baseQuery()->get();
        $ringkasan = [
            'total_piutang'   => $semuaKabur->sum(fn($u) => $u->totalPiutang()),
            'jumlah_penghuni' => $semuaKabur->count(),
        ];

        return view('components.admin.laporan.piutang', compact('penghuni', 'ringkasan'));
    }
};
?>


<div x-data="pdfPreviewModal(() => '{{ route('admin.laporan.piutang.pdf') }}?' + new URLSearchParams({ search: $wire.search }).toString())">

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-card">
                <div class="stat-value" style="font-size:1.5rem;">
                    Rp {{ number_format($ringkasan['total_piutang'], 0, ',', '.') }}
                </div>
                <div class="stat-label">Total Piutang Macet</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card stat-card-light">
                <div class="stat-value">{{ $ringkasan['jumlah_penghuni'] }}</div>
                <div class="stat-label">Jumlah Penghuni Kabur</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="search-bar flex-grow-1" style="max-width:360px;">
            <i class="bi bi-search"></i>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama penghuni..."
            >
        </div>
        <button type="button" class="btn-firabo" @click="bukaPreviewPdf()">
            <i class="bi bi-eye"></i> Pratinjau Download
        </button>
    </div>

    {{-- Table + Card wrapper --}}
    <div class="table-card-wrapper">

        {{-- Desktop --}}
        <div class="table-view">
            <div class="firabo-card p-0 overflow-hidden">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Nama Penghuni</th>
                            <th>Kamar Terakhir</th>
                            <th>Tanggal Keluar</th>
                            <th class="text-end">Jml Tagihan</th>
                            <th class="text-end">Total Piutang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penghuni as $u)
                            <tr>
                                <td class="fw-semibold">{{ $u->name }}</td>
                                <td>
                                    <span style="color:var(--firabo-primary); font-weight:500">
                                        {{ $u->hunianTerakhir?->kamar?->nomor_kamar ?? '-' }}
                                    </span>
                                </td>
                                <td style="font-size:.85rem;">
                                    {{ $u->hunianTerakhir?->tanggal_keluar
                                        ? \Carbon\Carbon::parse($u->hunianTerakhir->tanggal_keluar)->translatedFormat('d M Y')
                                        : '-' }}
                                </td>
                                <td class="text-end">{{ $u->jumlahTagihanTertunggak() }}</td>
                                <td class="text-end fw-semibold" style="color:#991b1b;">
                                    Rp {{ number_format($u->totalPiutang(), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                                    Tidak ada penghuni kabur saat ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($penghuni->hasPages())
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                         style="border-top:1px solid var(--firabo-border); background:#fafafa;">
                        <span style="font-size:.8rem; color:#6b7280;">
                            Menampilkan {{ $penghuni->firstItem() }}-{{ $penghuni->lastItem() }}
                            dari {{ $penghuni->total() }} penghuni
                        </span>
                        <div class="firabo-pagination">
                            <button class="page-btn" wire:click="previousPage"
                                {{ $penghuni->onFirstPage() ? 'disabled' : '' }}>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            @foreach ($penghuni->getUrlRange(
                                max(1, $penghuni->currentPage() - 2),
                                min($penghuni->lastPage(), $penghuni->currentPage() + 2)
                            ) as $page => $url)
                                <button
                                    class="page-btn {{ $page == $penghuni->currentPage() ? 'active' : '' }}"
                                    wire:click="gotoPage({{ $page }})"
                                >{{ $page }}</button>
                            @endforeach
                            <button class="page-btn" wire:click="nextPage"
                                {{ $penghuni->hasMorePages() ? '' : 'disabled' }}>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mobile --}}
        <div class="card-view">
            @forelse($penghuni as $u)
                <div class="item-card">
                    <div class="item-card-header">
                        <span class="item-card-title">{{ $u->name }}</span>
                        <span class="badge-kabur">
                            Rp {{ number_format($u->totalPiutang(), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="item-card-body">
                        <div class="item-card-field">
                            <div class="field-label">Kamar Terakhir</div>
                            <div class="field-value" style="color:var(--firabo-primary); font-weight:600;">
                                {{ $u->hunianTerakhir?->kamar?->nomor_kamar ?? '-' }}
                            </div>
                        </div>
                        <div class="item-card-field">
                            <div class="field-label">Jml Tagihan</div>
                            <div class="field-value">{{ $u->jumlahTagihanTertunggak() }}</div>
                        </div>
                        <div class="item-card-field full-width">
                            <div class="field-label">Tanggal Keluar</div>
                            <div class="field-value">
                                {{ $u->hunianTerakhir?->tanggal_keluar
                                    ? \Carbon\Carbon::parse($u->hunianTerakhir->tanggal_keluar)->translatedFormat('d F Y')
                                    : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                    Tidak ada penghuni kabur saat ini
                </div>
            @endforelse

            @if($penghuni->hasPages())
                <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
                     style="font-size:13px; color:#6b7280">
                    <span>
                        Menampilkan {{ $penghuni->firstItem() ?? 0 }}-{{ $penghuni->lastItem() ?? 0 }}
                        dari {{ $penghuni->total() }} penghuni
                    </span>
                    <div class="firabo-pagination">
                        <button class="page-btn" wire:click="previousPage"
                            {{ !$penghuni->onFirstPage() ?: 'disabled' }}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        @foreach($penghuni->getUrlRange(1, $penghuni->lastPage()) as $page => $url)
                            <button class="page-btn {{ $page == $penghuni->currentPage() ? 'active' : '' }}"
                                wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endforeach
                        <button class="page-btn" wire:click="nextPage"
                            {{ $penghuni->hasMorePages() ?: 'disabled' }}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

    </div>

    @include('components.admin.laporan._modal-preview-pdf', ['namaFile' => 'laporan-piutang-macet-' . now()->format('Y-m-d') . '.pdf'])

</div>