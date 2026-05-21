<?php

use App\Models\Tagihan;
use App\Models\Hunian;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterBulan = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterBulan(): void { $this->resetPage(); }

    public function render()
    {
        $tagihan = Tagihan::query()
            ->with(['hunian.user', 'hunian.kamar'])
            ->when($this->search, fn($q) =>
                $q->whereHas('hunian.user', fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                )
                ->orWhereHas('hunian.kamar', fn($q) =>
                    $q->where('nomor_kamar', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_tagihan', $this->filterStatus)
            )
            ->when($this->filterBulan, fn($q) =>
                $q->whereMonth('tanggal_tagihan', date('m', strtotime($this->filterBulan)))
                  ->whereYear('tanggal_tagihan', date('Y', strtotime($this->filterBulan)))
            )
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->paginate(10);

        return view('components.admin.tagihan-table', compact('tagihan'));
    }
};
?>

<div>
    {{-- Toolbar --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Cari nama / kamar...">
        </div>
        <select wire:model.live="filterStatus"
                class="firabo-input" style="width:auto; height:38px; padding:0 0.875rem">
            <option value="">Semua Status</option>
            <option value="belum_bayar">Belum Bayar</option>
            <option value="lunas">Lunas</option>
            <option value="terlambat">Terlambat</option>
        </select>
        <input type="month" wire:model.live="filterBulan"
               class="firabo-input" style="width:auto; height:38px">
    </div>

    <div class="firabo-card p-0 overflow-hidden">
        <table class="firabo-table">
            <thead>
                <tr>
                    <th>Penghuni</th>
                    <th>Kamar</th>
                    <th>Nominal</th>
                    <th>Tanggal Tagihan</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody wire:loading.class.remove="d-none" class="d-none">
                @for($i = 0; $i < 5; $i++)
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width:120px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:60px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:100px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:90px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:90px"></div></td>
                    <td><div class="skeleton skeleton-badge"></div></td>
                </tr>
                @endfor
            </tbody>

            <tbody wire:loading.remove>
                @forelse($tagihan as $item)
                <tr>
                    <td style="font-weight:500">
                        {{ $item->hunian->user->name ?? '-' }}
                    </td>
                    <td>
                        <span style="color:var(--firabo-primary); font-weight:500">
                            {{ $item->hunian->kamar->nomor_kamar ?? '-' }}
                        </span>
                    </td>
                    <td>Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                    <td style="font-size:13px">
                        {{ $item->tanggal_tagihan->format('d M Y') }}
                    </td>
                    <td style="font-size:13px">
                        @php
                            $isLate = $item->tanggal_jatuh_tempo < now() && $item->status_tagihan !== 'lunas';
                        @endphp
                        <span style="{{ $isLate ? 'color:#dc3545; font-weight:500' : '' }}">
                            {{ $item->tanggal_jatuh_tempo->format('d M Y') }}
                        </span>
                    </td>
                    <td>
                        @if($item->status_tagihan === 'lunas')
                            <span class="badge-lunas">Lunas</span>
                        @elseif($item->status_tagihan === 'terlambat')
                            <span class="badge-terlambat">Terlambat</span>
                        @else
                            <span class="badge-belum">Belum Bayar</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        Tidak ada tagihan ditemukan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
             style="font-size:13px; color:#6b7280">
            <span>
                Menampilkan {{ $tagihan->firstItem() ?? 0 }}–{{ $tagihan->lastItem() ?? 0 }}
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
    </div>
</div>