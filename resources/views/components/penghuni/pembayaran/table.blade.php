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
            // Logika Filter (Menggunakan created_at agar pembayaran 'Pending' tetap muncul)
            ->when($this->filterBulan !== 'semua', fn($q) => 
                $q->whereMonth('created_at', $this->filterBulan)
            )
            ->when($this->filterTahun !== 'semua', fn($q) => 
                $q->whereYear('created_at', $this->filterTahun)
            )
            ->with(['tagihan'])
            ->orderBy('created_at', 'desc')
            ->paginate(9); // Angka 9 bagus untuk grid 3 kolom

        return view('components.penghuni.pembayaran.table', compact('pembayaran'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 500)"
>
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
                    {{-- Loop mundur 3 tahun terakhir --}}
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
        {{-- Skeleton Loading --}}
        <div x-show="!ready" x-cloak class="row g-3">
            @for($i = 0; $i < 6; $i++)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="item-card h-100">
                        <div class="item-card-header align-items-center">
                            <div class="skeleton skeleton-text" style="width:130px; height:18px"></div>
                            <div class="skeleton skeleton-badge"></div>
                        </div>
                        <div class="item-card-body mt-2">
                            {{-- Ditambahkan w-100 dan ms-auto --}}
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
            @forelse($pembayaran as $item)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="item-card h-100 shadow-sm border-0">
                        <div class="item-card-header align-items-center">
                            <span class="item-card-title mb-0" style="font-size:17px; font-weight:700;">
                                Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                            </span>
                            
                            @php $status = strtolower(trim($item->status_pembayaran)); @endphp
                            @if($status === 'sukses')
                                <span class="badge-sukses px-3 py-2 rounded-pill">Sukses</span>
                            @elseif($status === 'pending')
                                <span class="badge-pending px-3 py-2 rounded-pill">Pending</span>
                            @else
                                <span class="badge-nonaktif px-3 py-2 rounded-pill">Gagal</span>
                            @endif
                        </div>
                        <div class="item-card-body mt-2">
                            {{-- Menggunakan d-flex w-100 --}}
                            <div class="d-flex w-100">
                                {{-- Kiri: Metode Pembayaran (ditambahkan pe-3 agar tidak menabrak jika nama metode panjang) --}}
                                <div class="item-card-field mb-0 pe-3">
                                    <div class="field-label text-muted" style="font-size:12px;">Metode</div>
                                    <div class="field-value fw-medium" style="font-size:14px; text-transform:capitalize;">
                                        {{ $item->metode_pembayaran ? ucwords(str_replace('_', ' ', $item->metode_pembayaran)) : '-' }}
                                    </div>
                                </div>
                                
                                {{-- Kanan: Tanggal Bayar (ms-auto memaksa elemen ini merapat ke ujung kanan) --}}
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