<?php

use App\Models\Pembayaran;
use App\Models\Hunian;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    // ── Filter Waktu ────────────────────────────────────────────────────────
    public string $filterBulan = '';
    public string $filterTahun = '';

    public function mount()
    {
        // Set nilai bawaan ke bulan dan tahun saat ini ketika halaman dibuka
        $this->filterBulan = Carbon::now()->format('m');
        $this->filterTahun = Carbon::now()->format('Y');
    }

    // Reset halaman saat filter diubah
    public function updatingFilterBulan(): void { $this->resetPage(); }
    public function updatingFilterTahun(): void { $this->resetPage(); }

    public function render()
    {
        $hunian = Hunian::where('user_id', Auth::id())
            ->where('status_hunian', 'aktif')
            ->first();

        $pembayaran = Pembayaran::query()
            ->when($hunian, function($query) use ($hunian) {
                $query->whereHas('tagihan', fn($q) =>
                    $q->where('hunian_id', $hunian->hunian_id)
                );
            }, function($query) {
                $query->where('pembayaran_id', '<', 0); // Cegah error jika tak ada hunian
            })
            ->when($this->filterBulan !== 'semua', fn($q) => 
                $q->whereMonth('created_at', $this->filterBulan)
            )
            ->when($this->filterTahun !== 'semua', fn($q) => 
                $q->whereYear('created_at', $this->filterTahun)
            )
            ->with(['tagihan'])
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        // Cek apakah ada transaksi berstatus pending di halaman yang sedang dilihat
        $hasPending = $pembayaran->contains('status_pembayaran', 'pending');

        return view('components.penghuni.pembayaran.table', compact('pembayaran', 'hasPending'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 500)"
    {{-- Aktifkan auto-refresh setiap 5 detik HANYA jika ada transaksi pending --}}
    @if($hasPending) wire:poll.5s @endif
>
    {{-- ════════════════════════════════════════════════════════════════
         ALERT INFO PENDING TRANSAKSI
    ════════════════════════════════════════════════════════════════ --}}
    @if($hasPending)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert" style="background-color: #fffbeb; color: #b45309;">
            <div class="spinner-border spinner-border-sm me-3" role="status"></div>
            <div>
                <strong class="d-block" style="font-size: 15px;">Pembayaran sedang diproses...</strong>
                <span style="font-size: 14px;">Sistem sedang menunggu konfirmasi dari metode pembayaran. Halaman ini akan memuat ulang secara otomatis.</span>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════
         TOOLBAR: FILTER BULAN & TAHUN
    ════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <select wire:model.live="filterBulan" class="form-select custom-filter-select">
                    <option value="semua">Semua Bulan</option>
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
            </div>
            <div class="col-6 col-md-3">
                <select wire:model.live="filterTahun" class="form-select custom-filter-select">
                    <option value="semua">Semua Tahun</option>
                    @php $tahunSekarang = date('Y'); @endphp
                    @for($t = $tahunSekarang; $t >= $tahunSekarang - 3; $t--)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         GRID VIEW CARD (Desktop & Mobile)
    ════════════════════════════════════════════════════════════════ --}}
    <div class="card-grid-wrapper">
        {{-- Skeleton Loading (Hanya muncul saat inisialisasi awal Alpine) --}}
        <div x-show="!ready" x-cloak class="row g-3">
            @for($i = 0; $i < 6; $i++)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="item-card h-100">
                        <div class="item-card-header align-items-center">
                            <div class="skeleton skeleton-text" style="width:130px; height:18px"></div>
                            <div class="skeleton skeleton-badge"></div>
                        </div>
                        <div class="item-card-body mt-2">
                            <div class="d-flex w-100">
                                <div class="item-card-field mb-0">
                                    <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px"></div>
                                    <div class="skeleton skeleton-text" style="width:80px"></div>
                                </div>
                                <div class="item-card-field mb-0 ms-auto text-end">
                                    <div class="skeleton skeleton-text ms-auto" style="width:55px; margin-bottom:4px"></div>
                                    <div class="skeleton skeleton-text ms-auto" style="width:90px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- Real Data Cards --}}
        <div x-show="ready" x-cloak class="row g-3">
            {{-- Real Data Cards --}}
        <div class="row g-3">
            @forelse($pembayaran as $item)
                <div class="col-12 col-md-6 col-xl-4" wire:key="card-{{ $item->pembayaran_id }}">
                    {{-- PERBAIKAN DI SINI: Tambahkan atribut data-bs dan cursor: pointer --}}
                    <div class="item-card h-100 shadow-sm border-0 {{ strtolower(trim($item->status_pembayaran)) === 'pending' ? 'border border-warning' : '' }}" 
                         data-bs-toggle="modal" 
                         data-bs-target="#modalDetail-{{ $item->pembayaran_id }}"
                         style="cursor: pointer;">
                        
                        <div class="item-card-header align-items-center">
                            <span class="item-card-title mb-0" style="font-size:17px; font-weight:700;">
                                Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                            </span>
                            
                            @php $status = strtolower(trim($item->status_pembayaran)); @endphp
                            @if($status === 'sukses')
                                <span class="badge-sukses px-3 py-2 rounded-pill">Sukses</span>
                            @elseif($status === 'pending')
                                <span class="badge-pending px-3 py-2 rounded-pill d-inline-flex align-items-center">
                                    <span class="spinner-grow spinner-grow-sm me-2 opacity-75" style="width: 10px; height: 10px;" role="status"></span>
                                    Menunggu
                                </span>
                            @else
                                <span class="badge-nonaktif px-3 py-2 rounded-pill">Gagal</span>
                            @endif
                        </div>
                        
                        <div class="item-card-body mt-2">
                            <div class="d-flex w-100">
                                <div class="item-card-field mb-0 pe-3">
                                    <div class="field-label text-muted" style="font-size:12px;">Metode</div>
                                    <div class="field-value fw-medium" style="font-size:14px; text-transform:capitalize;">
                                        {{ $item->metode_pembayaran ? ucwords(str_replace('_', ' ', $item->metode_pembayaran)) : '-' }}
                                    </div>
                                </div>
                                
                                <div class="item-card-field mb-0 ms-auto text-end" style="white-space: nowrap;">
                                    <div class="field-label text-muted" style="font-size:12px;">Tgl Bayar</div>
                                    <div class="field-value fw-medium" style="font-size:14px;">
                                        {{ $item->tanggal_bayar ? \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 text-muted bg-white rounded-3 border">
                        <i class="bi bi-wallet2 fs-1 d-block mb-3 text-secondary"></i>
                        <h5 class="fw-semibold">Tidak ada transaksi</h5>
                        <p class="mb-0">Belum ada riwayat pembayaran pada bulan & tahun ini.</p>
                    </div>
                </div>
            @endforelse
        </div>
        
        {{-- PERBAIKAN DI SINI: Render semua modal di luar grid --}}
        @foreach($pembayaran as $item)
            @include('components.penghuni.pembayaran._modal_detail', ['item' => $item])
        @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4 d-flex justify-content-end" x-show="ready" x-cloak>
            @include('components.penghuni.pembayaran._pagination', ['data' => $pembayaran])
        </div>
    </div>
</div>

@push('styles')
<style>
/* Styling tambahan untuk Filter & Card Grid */
.custom-filter-select {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.9rem;
    padding: 0.6rem 1rem;
    background-color: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.2s ease-in-out;
}
.custom-filter-select:focus {
    border-color: var(--firabo-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    outline: none;
}
.item-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem;
    transition: transform 0.2s;
}
.item-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
</style>
@endpush