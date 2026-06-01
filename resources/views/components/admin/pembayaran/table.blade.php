<?php

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Events\PembayaranBerhasil;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    // Mengontrol transisi antara tabel dan form
    public bool $isFormOpen = false; 
    
    public string $tagihan_id = '';
    public string $nominal_bayar = '';
    public string $tanggal_bayar = '';
    public string $metode_pembayaran = 'manual';
    public string $status_pembayaran = 'sukses';

    protected $rules = [
        'tagihan_id'        => 'required|exists:tb_tagihan,tagihan_id',
        'nominal_bayar'     => 'required|numeric|min:1',
        'tanggal_bayar'     => 'required|date',
        'metode_pembayaran' => 'required|string',
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // Fitur Auto-fill: Menarik nominal tagihan saat tagihan_id dipilih
    public function updatedTagihanId($value)
    {
        if ($value) {
            $tagihan = Tagihan::find($value);
            if ($tagihan) {
                $this->nominal_bayar = $tagihan->nominal;
            }
        } else {
            $this->nominal_bayar = '';
        }
    }

    public function openCreate(): void
    {
        $this->reset(['tagihan_id', 'nominal_bayar', 'metode_pembayaran', 'status_pembayaran']);
        $this->metode_pembayaran = 'manual';
        $this->status_pembayaran = 'sukses';
        $this->tanggal_bayar = now()->format('Y-m-d');
        $this->isFormOpen    = true; // Buka form
    }

    public function closeForm(): void
    {
        $this->isFormOpen = false; // Kembali ke tabel
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        $tagihan = Tagihan::findOrFail($this->tagihan_id);
        $status = $this->status_pembayaran;

        $pembayaran = Pembayaran::create([
            'tagihan_id'        => $this->tagihan_id,
            'user_id'           => Auth::id(),
            'metode_pembayaran' => $this->metode_pembayaran,
            'nominal_bayar'     => $this->nominal_bayar,
            'tanggal_bayar'     => $this->tanggal_bayar,
            'status_pembayaran' => $status,
        ]);

        if ($status === 'sukses') {
            $tagihan->update(['status_tagihan' => 'lunas']);

            // ── NEW: Fire event → listener kirim email konfirmasi ke penghuni ──
            // Ini memberi transparansi: penghuni dapat notifikasi
            // bahwa admin telah mencatat pembayaran atas nama mereka.
            $pembayaran->refresh();
            event(new PembayaranBerhasil($pembayaran));
        }

        $this->isFormOpen = false;
        $this->resetPage();

        // Mengirim event ke frontend untuk toast
        if ($status === 'sukses') {
            $this->dispatch('success', message: 'Pembayaran manual berhasil dicatat.');
        } elseif ($status === 'gagal') {
            $this->dispatch('error', message: 'Pembayaran manual gagal dicatat.');
        } else {
            $this->dispatch('success', message: 'Pembayaran manual dicatat dengan status pending.');
        }
    }

    public function render()
    {
        $pembayaran = Pembayaran::query()
            ->with(['tagihan.hunian.user', 'tagihan.hunian.kamar', 'user'])
            ->when($this->search, fn($q) =>
                $q->whereHas('tagihan.hunian.user', fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_pembayaran', $this->filterStatus)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $tagihanBelumLunas = Tagihan::where('status_tagihan', '!=', 'lunas')
            ->with(['hunian.user', 'hunian.kamar'])
            ->get();

        return view('components.admin.pembayaran.table',
            compact('pembayaran', 'tagihanBelumLunas'));
    }
};
?>

<div class="table-card-wrapper"x-data="{
        ready: false,
        toast: { show: false, tipe: '', pesan: '' }
     }"
     x-init="
        setTimeout(() => ready = true, 600);

        $wire.on('success', (event) => {
            toast.show = true;
            toast.tipe = 'sukses';
            toast.pesan = event.message;
            setTimeout(() => toast.show = false, 3000);
        });

        $wire.on('error', (event) => {
            toast.show = true;
            toast.tipe = 'gagal';
            toast.pesan = event.message;
            setTimeout(() => toast.show = false, 3000);
        });
     ">
    
     {{-- Sidebar --}}
    @if(!$isFormOpen)
        {{-- VIEW TABEL (Muncul jika isFormOpen false) --}}
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
            <div class="d-flex gap-2 flex-wrap">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama penghuni...">
                </div>
                <select wire:model.live="filterStatus" class="firabo-input" style="width:auto; height:38px; padding:0 0.875rem">
                    <option value="">Semua Status</option>
                    <option value="sukses">Sukses</option>
                    <option value="pending">Pending</option>
                    <option value="gagal">Gagal</option>
                </select>
            </div>
            <button wire:click="openCreate" class="btn-firabo">
                <i class="bi bi-plus-lg"></i> Catat Manual
            </button>
        </div>

        {{-- Desktop Table --}}
        <div class="table-view">
            <div class="firabo-card p-0 overflow-hidden">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Tanggal Bayar</th>
                            <th>Status</th>
                            <th>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody x-show="!ready" x-cloak>
                        @include('components.admin.pembayaran._skeleton')
                    </tbody>
                    <tbody x-show="ready" x-cloak>
                        @forelse($pembayaran as $item)
                        <tr>
                            <td style="font-weight:500">{{ $item->tagihan->hunian->user->name ?? '-' }}</td>
                            <td>
                                <span style="color:var(--firabo-primary); font-weight:500">
                                    {{ $item->tagihan->hunian->kamar->nomor_kamar ?? '-' }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}</td>
                            <td style="font-size:13px; text-transform:capitalize">{{ str_replace('_', ' ', $item->metode_pembayaran) }}</td>
                            <td style="font-size:13px">{{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}</td>
                            <td>
                                @if($item->status_pembayaran === 'sukses')
                                    <span class="badge-sukses">Sukses</span>
                                @elseif($item->status_pembayaran === 'pending')
                                    <span class="badge-pending">Pending</span>
                                @else
                                    <span class="badge-nonaktif">Gagal</span>
                                @endif
                            </td>
                            <td style="font-size:13px; color:#6b7280">{{ $item->user->name ?? 'Online' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i> Belum ada riwayat pembayaran
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @include('components.admin.pembayaran._pagination', ['data' => $pembayaran])
            </div>
        </div>

        {{-- Mobile Card --}}
        <div class="card-view">
            <div x-show="!ready" x-cloak>
                @for($i = 0; $i < 4; $i++)
                    {{-- Struktur Skeleton Mobile Anda --}}
                    <div class="item-card">
                        <div class="item-card-header align-items-center">
                            <div class="skeleton skeleton-text" style="width:130px; height:18px"></div>
                            <div class="skeleton skeleton-badge"></div>
                        </div>
                        <div class="item-card-body mt-2">
                            <div class="d-flex w-100">
                                <div class="item-card-field mb-0 pe-3">
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
                @endfor
            </div>

            <div x-show="ready" x-cloak>
                @forelse($pembayaran as $item)
                <div class="item-card shadow-sm border-0">
                    <div class="item-card-header align-items-center">
                        <span class="item-card-title mb-0" style="font-size:17px; font-weight:700;">
                            Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                        </span>
                        @if($item->status_pembayaran === 'sukses')
                            <span class="badge-sukses px-3 py-2 rounded-pill">Sukses</span>
                        @elseif($item->status_pembayaran === 'pending')
                            <span class="badge-pending px-3 py-2 rounded-pill">Pending</span>
                        @else
                            <span class="badge-nonaktif px-3 py-2 rounded-pill">Gagal</span>
                        @endif
                    </div>
                    <div class="item-card-body mt-2">
                        <div class="d-flex w-100">
                            <div class="item-card-field mb-0 pe-3">
                                <div class="field-label text-muted" style="font-size:12px;">Penghuni / Kamar</div>
                                <div class="field-value fw-medium" style="font-size:14px; text-transform:capitalize;">
                                    {{ $item->tagihan->hunian->user->name ?? '-' }} ({{ $item->tagihan->hunian->kamar->nomor_kamar ?? '-' }})
                                </div>
                            </div>
                            <div class="item-card-field mb-0 ms-auto text-end" style="white-space: nowrap;">
                                <div class="field-label text-muted" style="font-size:12px;">Tgl Bayar</div>
                                <div class="field-value fw-medium" style="font-size:14px;">
                                    {{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-4 d-block mb-2"></i> Belum ada riwayat pembayaran
                </div>
                @endforelse
                @include('components.admin.pembayaran._pagination', ['data' => $pembayaran])
            </div>
        </div>

    @else
        {{-- VIEW FORM (Sembunyikan tabel, Include file form) --}}
        @include('components.admin.pembayaran._modal-form')
    @endif

   {{-- ════════════════════════════════════════════════════════════════
         TOAST NOTIFICATION — pojok kanan bawah
         Dipicu via $wire.on('toast') dari Livewire dispatch().

         Fix: static style dan dynamic :style tidak bisa digabung —
         Alpine `:style` akan override/replace `style` statis sepenuhnya.
         Solusi: semua style statis masuk ke CSS class `.firabo-toast`,
         dynamic hanya untuk warna (via x-bind:class).
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
</div>

@push('styles')
<style>
.field-error { font-size: .8rem; color: #dc2626; }

    
/* ── Toast ── */
.firabo-toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 2000;
    display: inline-flex;        /* inline-flex: lebar fit content */
    align-items: center;
    gap: .5rem;
    padding: .625rem 1rem;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    white-space: nowrap;         /* pastikan tidak wrap */
    pointer-events: none;        /* tidak mengganggu klik di bawahnya */
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
</style>
@endpush