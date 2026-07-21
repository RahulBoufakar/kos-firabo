<?php

use App\Models\Pembayaran;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $mode = 'bulanan'; // bulanan | tahunan
    public string $search = '';
    public string $filterBulan = '';
    public string $filterTahun = '';

    public array $listTahun = [];

    public function mount(): void
    {
        $this->filterBulan = now()->format('m');
        $this->filterTahun = now()->format('Y');

        // 1. Ambil tahun unik dari data pembayaran yang sukses
        $tahunDb = Pembayaran::where('status_pembayaran', 'sukses')
            ->selectRaw('YEAR(tanggal_bayar) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->toArray();

        $tahunSekarang = (int) now()->format('Y');

        if (empty($tahunDb)) {
            // 2. Jika tidak ada data sama sekali, tampilkan tahun ini dan 2 tahun ke belakang
            $this->listTahun = [$tahunSekarang, $tahunSekarang - 1, $tahunSekarang - 2];
        } else {
            // Pastikan tahun berjalan (saat ini) selalu ada di daftar meskipun belum ada transaksi
            if (!in_array($tahunSekarang, $tahunDb)) {
                $tahunDb[] = $tahunSekarang;
                rsort($tahunDb); // Urutkan kembali dari tahun terbaru (descending)
            }
            $this->listTahun = $tahunDb;
        }
    }

    public function updatedMode(): void       { $this->resetPage(); }
    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
    public function updatedFilterTahun(): void { $this->resetPage(); }

    protected function baseQuery()
    {
        return Pembayaran::where('status_pembayaran', 'sukses')
            ->when($this->mode === 'bulanan', fn($q) =>
                $q->whereMonth('tanggal_bayar', $this->filterBulan)
                  ->whereYear('tanggal_bayar', $this->filterTahun)
            )
            ->when($this->mode === 'tahunan', fn($q) =>
                $q->whereYear('tanggal_bayar', $this->filterTahun)
            )
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereHas('tagihan.hunian.user', fn($q3) =>
                            $q3->where('name', 'like', "%{$this->search}%")
                        )
                        ->orWhereHas('tagihan.hunian.kamar', fn($q3) =>
                            $q3->where('nomor_kamar', 'like', "%{$this->search}%")
                        );
                });
            });
    }

    public function render()
    {
        $pembayaran = $this->baseQuery()
            ->with(['tagihan.hunian.user', 'tagihan.hunian.kamar', 'user'])
            ->orderByDesc('tanggal_bayar')
            ->paginate(10);

        $ringkasan = [
            'total'     => $this->baseQuery()->sum('nominal_bayar'),
            'jumlah'    => $this->baseQuery()->count(),
            'rata_rata' => $this->baseQuery()->avg('nominal_bayar') ?? 0,
        ];

        // Rekap per bulan hanya dihitung saat mode tahunan, dan sengaja TIDAK
        // terpengaruh pencarian — supaya breakdown-nya tetap gambaran 1 tahun penuh.
        $breakdownBulanan = null;
        if ($this->mode === 'tahunan') {
            $breakdownBulanan = Pembayaran::where('status_pembayaran', 'sukses')
                ->whereYear('tanggal_bayar', $this->filterTahun)
                ->selectRaw('MONTH(tanggal_bayar) as bulan, SUM(nominal_bayar) as total, COUNT(*) as jumlah')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get()
                ->keyBy('bulan');
        }

        return view('components.admin.laporan.pemasukan', compact('pembayaran', 'ringkasan', 'breakdownBulanan'));
    }
};
?>


<div x-data="pdfPreviewModal(() => '{{ route('admin.laporan.pemasukan.pdf') }}?' + new URLSearchParams({ mode: $wire.mode, bulan: $wire.filterBulan, tahun: $wire.filterTahun, search: $wire.search }).toString())">

    {{-- Toggle Mode --}}
    <div class="d-flex gap-2 mb-3">
        <button type="button" wire:click="$set('mode', 'bulanan')"
            class="{{ $mode === 'bulanan' ? 'btn-firabo' : 'btn-firabo-outline' }}"
            style="font-size:.85rem; padding:.45rem 1rem;">
            <i class="bi bi-calendar3"></i> Bulanan
        </button>
        <button type="button" wire:click="$set('mode', 'tahunan')"
            class="{{ $mode === 'tahunan' ? 'btn-firabo' : 'btn-firabo-outline' }}"
            style="font-size:.85rem; padding:.45rem 1rem;">
            <i class="bi bi-calendar-range"></i> Tahunan
        </button>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-value" style="font-size:1.4rem;">
                    Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}
                </div>
                <div class="stat-label">Total Pemasukan</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-card stat-card-light">
                <div class="stat-value">{{ $ringkasan['jumlah'] }}</div>
                <div class="stat-label">Jumlah Transaksi</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-card stat-card-light">
                <div class="stat-value" style="font-size:1.2rem;">
                    Rp {{ number_format($ringkasan['rata_rata'], 0, ',', '.') }}
                </div>
                <div class="stat-label">Rata-rata / Transaksi</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="d-flex gap-2 flex-wrap flex-grow-1">
            <div class="search-bar" style="max-width:280px;">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama penghuni / kamar...">
            </div>

            @if($mode === 'bulanan')
                <select wire:model.live="filterBulan" class="firabo-input" style="width:auto; height:38px; padding:0 .875rem;">
                    <option value="01">Januari</option>
                    <option value="02">Februari</option>
                    <option value="03">Maret</option>
                    <option value="04">April</option>
                    <option value="05">Mei</option>
                    <option value="06">Juni</option>
                    <option value="07">Juli</option>
                    <option value="08">Agustus</option>
                    <option value="09">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            @endif

            <select wire:model.live="filterTahun" class="firabo-input" style="width:auto; height:38px; padding:0 .875rem;">
                @foreach($listTahun as $tahun)
                    <option value="{{ $tahun }}">{{ $tahun }}</option>
                @endforeach
            </select>
        </div>

        <button type="button" class="btn-firabo" @click="bukaPreviewPdf()">
            <i class="bi bi-eye"></i> Pratinjau Download
        </button>
    </div>

    {{-- Rekap per Bulan — hanya muncul saat mode Tahunan --}}
    @if($mode === 'tahunan')
        <div class="firabo-card mb-3">
            <h6 class="card-title mb-3">Rekap per Bulan — {{ $filterTahun }}</h6>
            <div class="table-responsive">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Jumlah Transaksi</th>
                            <th class="text-end">Total Pemasukan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($b = 1; $b <= 12; $b++)
                            @php $row = $breakdownBulanan->get($b); @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</td>
                                <td class="text-end">{{ $row->jumlah ?? 0 }}</td>
                                <td class="text-end fw-semibold">
                                    Rp {{ number_format($row->total ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Rincian Transaksi --}}
    <div class="table-card-wrapper">

        <div class="table-view">
            <div class="firabo-card p-0 overflow-hidden">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th class="text-end">Nominal</th>
                            <th>Metode</th>
                            <th>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pembayaran as $p)
                            <tr>
                                <td style="font-size:.85rem;">
                                    {{ \Carbon\Carbon::parse($p->tanggal_bayar)->translatedFormat('d M Y') }}
                                </td>
                                <td class="fw-semibold">{{ $p->tagihan?->hunian?->user?->name ?? '-' }}</td>
                                <td>
                                    <span style="color:var(--firabo-primary); font-weight:500">
                                        {{ $p->tagihan?->hunian?->kamar?->nomor_kamar ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp {{ number_format($p->nominal_bayar, 0, ',', '.') }}
                                </td>
                                <td style="font-size:.85rem; text-transform:capitalize;">
                                    {{ str_replace('_', ' ', $p->metode_pembayaran) }}
                                </td>
                                <td style="font-size:.85rem; color:#6b7280;">
                                    {{ $p->user?->name ?? 'Online' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    Tidak ada transaksi pada periode ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($pembayaran->hasPages())
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                         style="border-top:1px solid var(--firabo-border); background:#fafafa;">
                        <span style="font-size:.8rem; color:#6b7280;">
                            Menampilkan {{ $pembayaran->firstItem() }}-{{ $pembayaran->lastItem() }}
                            dari {{ $pembayaran->total() }} transaksi
                        </span>
                        <div class="firabo-pagination">
                            <button class="page-btn" wire:click="previousPage"
                                {{ $pembayaran->onFirstPage() ? 'disabled' : '' }}>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            @foreach ($pembayaran->getUrlRange(
                                max(1, $pembayaran->currentPage() - 2),
                                min($pembayaran->lastPage(), $pembayaran->currentPage() + 2)
                            ) as $page => $url)
                                <button class="page-btn {{ $page == $pembayaran->currentPage() ? 'active' : '' }}"
                                    wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                            @endforeach
                            <button class="page-btn" wire:click="nextPage"
                                {{ $pembayaran->hasMorePages() ? '' : 'disabled' }}>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mobile --}}
        <div class="card-view">
            @forelse($pembayaran as $p)
                <div class="item-card">
                    <div class="item-card-header">
                        <span class="item-card-title">{{ $p->tagihan?->hunian?->user?->name ?? '-' }}</span>
                        <span class="fw-semibold" style="color:var(--firabo-primary);">
                            Rp {{ number_format($p->nominal_bayar, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="item-card-body">
                        <div class="item-card-field">
                            <div class="field-label">Kamar</div>
                            <div class="field-value" style="color:var(--firabo-primary); font-weight:600;">
                                {{ $p->tagihan?->hunian?->kamar?->nomor_kamar ?? '-' }}
                            </div>
                        </div>
                        <div class="item-card-field">
                            <div class="field-label">Tanggal Bayar</div>
                            <div class="field-value">
                                {{ \Carbon\Carbon::parse($p->tanggal_bayar)->translatedFormat('d M Y') }}
                            </div>
                        </div>
                        <div class="item-card-field">
                            <div class="field-label">Metode</div>
                            <div class="field-value text-capitalize">
                                {{ str_replace('_', ' ', $p->metode_pembayaran) }}
                            </div>
                        </div>
                        <div class="item-card-field">
                            <div class="field-label">Dicatat Oleh</div>
                            <div class="field-value">{{ $p->user?->name ?? 'Online' }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                    Tidak ada transaksi pada periode ini
                </div>
            @endforelse

            @if($pembayaran->hasPages())
                <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
                     style="font-size:13px; color:#6b7280">
                    <span>
                        Menampilkan {{ $pembayaran->firstItem() ?? 0 }}-{{ $pembayaran->lastItem() ?? 0 }}
                        dari {{ $pembayaran->total() }} transaksi
                    </span>
                    <div class="firabo-pagination">
                        <button class="page-btn" wire:click="previousPage"
                            {{ !$pembayaran->onFirstPage() ?: 'disabled' }}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        @foreach($pembayaran->getUrlRange(1, $pembayaran->lastPage()) as $page => $url)
                            <button class="page-btn {{ $page == $pembayaran->currentPage() ? 'active' : '' }}"
                                wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endforeach
                        <button class="page-btn" wire:click="nextPage"
                            {{ $pembayaran->hasMorePages() ?: 'disabled' }}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

    </div>

    @include('components.admin.laporan._modal-preview-pdf', ['namaFile' => 'laporan-pemasukan-' . now()->format('Y-m-d') . '.pdf'])

</div>