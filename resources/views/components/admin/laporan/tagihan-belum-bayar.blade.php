<?php

use App\Models\Tagihan;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = ''; // '' = semua, belum_bayar, terlambat

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterStatus(): void  { $this->resetPage(); }

    /**
     * Query dasar TANPA filter status — sengaja dipisah dari filter status supaya
     * 3 kartu ringkasan selalu menampilkan breakdown lengkap (belum_bayar vs terlambat),
     * walau tabel di bawahnya sedang difilter ke salah satu jenis saja.
     */
    protected function baseQuery()
    {
        return Tagihan::belumLunas()
            ->milikPenghuniAktif()
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereHas('hunian.user', fn($q3) =>
                            $q3->where('name', 'like', "%{$this->search}%")
                        )
                        ->orWhereHas('hunian.kamar', fn($q3) =>
                            $q3->where('nomor_kamar', 'like', "%{$this->search}%")
                        );
                });
            });
    }

    public function render()
    {
        $ringkasan = [
            'total_nominal' => $this->baseQuery()->sum('nominal'),
            'jumlah_belum'  => $this->baseQuery()->where('status_tagihan', 'belum_bayar')->count(),
            'jumlah_telat'  => $this->baseQuery()->where('status_tagihan', 'terlambat')->count(),
        ];

        $tagihan = $this->baseQuery()
            ->when($this->filterStatus, fn($q) => $q->where('status_tagihan', $this->filterStatus))
            ->with(['hunian.user', 'hunian.kamar'])
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->paginate(10);

        return view('components.admin.laporan.tagihan-belum-bayar', compact('tagihan', 'ringkasan'));
    }
};
?>

<div x-data="pdfPreviewModal(() => '{{ route('admin.laporan.tagihan-belum-bayar.pdf') }}?' + new URLSearchParams({ status: $wire.filterStatus, search: $wire.search }).toString())">

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-value" style="font-size:1.5rem;">
                    Rp {{ number_format($ringkasan['total_nominal'], 0, ',', '.') }}
                </div>
                <div class="stat-label">Total Belum Tertagih</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-card stat-card-light">
                <div class="stat-value">{{ $ringkasan['jumlah_belum'] }}</div>
                <div class="stat-label">Belum Bayar</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-card stat-card-light">
                <div class="stat-value" style="color:#dc3545;">{{ $ringkasan['jumlah_telat'] }}</div>
                <div class="stat-label">Terlambat</div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="d-flex gap-2 flex-wrap flex-grow-1">
            <div class="search-bar" style="max-width:320px;">
                <i class="bi bi-search"></i>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama penghuni atau nomor kamar..."
                >
            </div>
            <select wire:model.live="filterStatus" class="firabo-input" style="width:auto; height:38px; padding:0 .875rem;">
                <option value="">Semua Status</option>
                <option value="belum_bayar">Belum Bayar</option>
                <option value="terlambat">Terlambat</option>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn-firabo" @click="bukaPreviewPdf()">
                <i class="bi bi-eye"></i> Pratinjau Download
            </button>
        </div>
    </div>

    <div class="table-card-wrapper">

        <div class="table-view">
            <div class="firabo-card p-0 overflow-hidden">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th>Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50" wire:target="search">
                        @forelse($tagihan as $t)
                            @php
                                $penghuni    = $t->hunian?->user;
                                $kamar       = $t->hunian?->kamar;
                                $sisaHari    = \Carbon\Carbon::today()->diffInDays($t->tanggal_jatuh_tempo, false);
                                $isTerlambat = $t->status_tagihan === 'terlambat';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size:.9rem;">{{ $penghuni?->name ?? '-' }}</div>
                                    <div style="font-size:.75rem; color:#9ca3af;">{{ $penghuni?->email ?? '' }}</div>
                                </td>
                                <td>
                                    <span style="color:var(--firabo-primary); font-weight:500">
                                        {{ $kamar?->nomor_kamar ?? '-' }}
                                    </span>
                                </td>
                                <td class="fw-semibold">Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                                <td>
                                    <div style="{{ $isTerlambat ? 'color:#991b1b; font-weight:600;' : '' }}">
                                        {{ \Carbon\Carbon::parse($t->tanggal_jatuh_tempo)->translatedFormat('d M Y') }}
                                    </div>
                                    <div style="font-size:.72rem; color:{{ $isTerlambat ? '#991b1b' : '#9ca3af' }};">
                                        @if($isTerlambat)
                                            {{ abs($sisaHari) }} hari terlambat
                                        @elseif($sisaHari === 0)
                                            Hari ini
                                        @else
                                            {{ $sisaHari }} hari lagi
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($isTerlambat)
                                        <span class="badge-terlambat">Terlambat</span>
                                    @else
                                        <span class="badge-belum">Belum Bayar</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                                    Semua tagihan sudah lunas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($tagihan->hasPages())
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                         style="border-top:1px solid var(--firabo-border); background:#fafafa;">
                        <span style="font-size:.8rem; color:#6b7280;">
                            Menampilkan {{ $tagihan->firstItem() }}-{{ $tagihan->lastItem() }}
                            dari {{ $tagihan->total() }} tagihan
                        </span>
                        <div class="firabo-pagination">
                            <button class="page-btn" wire:click="previousPage"
                                {{ $tagihan->onFirstPage() ? 'disabled' : '' }}>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            @foreach ($tagihan->getUrlRange(
                                max(1, $tagihan->currentPage() - 2),
                                min($tagihan->lastPage(), $tagihan->currentPage() + 2)
                            ) as $page => $url)
                                <button
                                    class="page-btn {{ $page == $tagihan->currentPage() ? 'active' : '' }}"
                                    wire:click="gotoPage({{ $page }})"
                                >{{ $page }}</button>
                            @endforeach
                            <button class="page-btn" wire:click="nextPage"
                                {{ $tagihan->hasMorePages() ? '' : 'disabled' }}>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-view">
            @forelse($tagihan as $t)
                @php
                    $penghuni    = $t->hunian?->user;
                    $kamar       = $t->hunian?->kamar;
                    $sisaHari    = \Carbon\Carbon::today()->diffInDays($t->tanggal_jatuh_tempo, false);
                    $isTerlambat = $t->status_tagihan === 'terlambat';
                @endphp
                <div class="item-card">
                    <div class="item-card-header">
                        <span class="item-card-title">{{ $penghuni?->name ?? '-' }}</span>
                        @if($isTerlambat)
                            <span class="badge-terlambat">Terlambat</span>
                        @else
                            <span class="badge-belum">Belum Bayar</span>
                        @endif
                    </div>
                    <div class="item-card-body">
                        <div class="item-card-field">
                            <div class="field-label">Kamar</div>
                            <div class="field-value" style="color:var(--firabo-primary); font-weight:600;">
                                {{ $kamar?->nomor_kamar ?? '-' }}
                            </div>
                        </div>
                        <div class="item-card-field">
                            <div class="field-label">Nominal</div>
                            <div class="field-value fw-semibold">
                                Rp {{ number_format($t->nominal, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="item-card-field full-width">
                            <div class="field-label">Jatuh Tempo</div>
                            <div class="field-value {{ $isTerlambat ? 'text-danger fw-semibold' : '' }}">
                                {{ \Carbon\Carbon::parse($t->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}
                                <span style="font-size:.75rem; font-weight:400; color:{{ $isTerlambat ? '#991b1b' : '#9ca3af' }};">
                                    ({{ $isTerlambat ? abs($sisaHari).' hari terlambat' : ($sisaHari === 0 ? 'hari ini' : $sisaHari.' hari lagi') }})
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                    Semua tagihan sudah lunas
                </div>
            @endforelse

            @if($tagihan->hasPages())
                <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
                     style="font-size:13px; color:#6b7280">
                    <span>
                        Menampilkan {{ $tagihan->firstItem() ?? 0 }}-{{ $tagihan->lastItem() ?? 0 }}
                        dari {{ $tagihan->total() }} tagihan
                    </span>
                    <div class="firabo-pagination">
                        <button class="page-btn" wire:click="previousPage"
                            {{ !$tagihan->onFirstPage() ?: 'disabled' }}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        @foreach($tagihan->getUrlRange(1, $tagihan->lastPage()) as $page => $url)
                            <button class="page-btn {{ $page == $tagihan->currentPage() ? 'active' : '' }}"
                                wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endforeach
                        <button class="page-btn" wire:click="nextPage"
                            {{ $tagihan->hasMorePages() ?: 'disabled' }}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

    </div>{{-- /table-card-wrapper --}}
    {{-- ════════════════════════════════════════════════════════════════
         MODAL PRATINJAU PDF
    ════════════════════════════════════════════════════════════════ --}}
        @include('components.admin.laporan._modal-preview-pdf', ['namaFile' => 'laporan-tagihan-belum-bayar-' . now()->format('Y-m-d') . '.pdf'])

    </div>
</div>