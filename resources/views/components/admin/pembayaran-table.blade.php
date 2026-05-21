<?php

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    // Form pembayaran manual
    public bool $showModal = false;
    public string $tagihan_id = '';
    public string $nominal_bayar = '';
    public string $tanggal_bayar = '';
    public string $metode_pembayaran = 'manual';

    protected $rules = [
        'tagihan_id'        => 'required|exists:tb_tagihan,tagihan_id',
        'nominal_bayar'     => 'required|numeric|min:1',
        'tanggal_bayar'     => 'required|date',
        'metode_pembayaran' => 'required|string',
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['tagihan_id', 'nominal_bayar', 'metode_pembayaran']);
        $this->tanggal_bayar = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $tagihan = Tagihan::findOrFail($this->tagihan_id);

        Pembayaran::create([
            'tagihan_id'        => $this->tagihan_id,
            'user_id'           => Auth::id(),
            'metode_pembayaran' => $this->metode_pembayaran,
            'nominal_bayar'     => $this->nominal_bayar,
            'tanggal_bayar'     => $this->tanggal_bayar,
            'status_pembayaran' => 'sukses',
        ]);

        $tagihan->update(['status_tagihan' => 'lunas']);

        $this->showModal = false;
        session()->flash('success', 'Pembayaran manual berhasil dicatat.');
        $this->resetPage();
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

        return view('components.admin.pembayaran-table',
            compact('pembayaran', 'tagihanBelumLunas'));
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

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <div class="d-flex gap-2 flex-wrap">
            <div class="search-bar">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Cari nama penghuni...">
            </div>
            <select wire:model.live="filterStatus"
                    class="firabo-input" style="width:auto; height:38px; padding:0 0.875rem">
                <option value="">Semua Status</option>
                <option value="sukses">Sukses</option>
                <option value="pending">Pending</option>
                <option value="gagal">Gagal</option>
            </select>
        </div>
        <button wire:click="openCreate" class="btn-firabo">
            <i class="bi bi-plus-lg"></i> Catat Pembayaran Manual
        </button>
    </div>

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

            <tbody wire:loading.class.remove="d-none" class="d-none">
                @for($i = 0; $i < 5; $i++)
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width:120px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:55px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:110px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:70px"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:90px"></div></td>
                    <td><div class="skeleton skeleton-badge"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:80px"></div></td>
                </tr>
                @endfor
            </tbody>

            <tbody wire:loading.remove>
                @forelse($pembayaran as $item)
                <tr>
                    <td style="font-weight:500">
                        {{ $item->tagihan->hunian->user->name ?? '-' }}
                    </td>
                    <td>
                        <span style="color:var(--firabo-primary); font-weight:500">
                            {{ $item->tagihan->hunian->kamar->nomor_kamar ?? '-' }}
                        </span>
                    </td>
                    <td>Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}</td>
                    <td style="font-size:13px; text-transform:capitalize">
                        {{ str_replace('_', ' ', $item->metode_pembayaran) }}
                    </td>
                    <td style="font-size:13px">
                        {{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}
                    </td>
                    <td>
                        @if($item->status_pembayaran === 'sukses')
                            <span class="badge-sukses">Sukses</span>
                        @elseif($item->status_pembayaran === 'pending')
                            <span class="badge-pending">Pending</span>
                        @else
                            <span class="badge-nonaktif">Gagal</span>
                        @endif
                    </td>
                    <td style="font-size:13px; color:#6b7280">
                        {{ $item->user->name ?? 'Online' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        Belum ada riwayat pembayaran
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
             style="font-size:13px; color:#6b7280">
            <span>
                Menampilkan {{ $pembayaran->firstItem() ?? 0 }}–{{ $pembayaran->lastItem() ?? 0 }}
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
    </div>

    {{-- Modal Pembayaran Manual --}}
    @if($showModal)
    <div class="modal-backdrop-custom" wire:click.self="$set('showModal', false)">
        <div class="modal-box" style="max-width:480px">
            <div class="modal-box-header">
                <h5 class="mb-0 fw-600">Catat Pembayaran Manual</h5>
                <button wire:click="$set('showModal', false)" class="btn-close"></button>
            </div>
            <div class="modal-box-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" style="font-size:14px; font-weight:500">Tagihan</label>
                        <select wire:model="tagihan_id" class="firabo-input">
                            <option value="">Pilih Tagihan</option>
                            @foreach($tagihanBelumLunas as $t)
                                <option value="{{ $t->tagihan_id }}">
                                    {{ $t->hunian->user->name ?? '-' }} —
                                    {{ $t->hunian->kamar->nomor_kamar ?? '-' }} —
                                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('tagihan_id')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Nominal Bayar</label>
                        <input type="number" wire:model="nominal_bayar"
                               class="firabo-input" placeholder="1500000">
                        @error('nominal_bayar')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:14px; font-weight:500">Tanggal Bayar</label>
                        <input type="date" wire:model="tanggal_bayar" class="firabo-input">
                        @error('tanggal_bayar')
                            <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-size:14px; font-weight:500">Metode</label>
                        <select wire:model="metode_pembayaran" class="firabo-input">
                            <option value="manual">Manual (Cash/Transfer)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-box-footer">
                <button wire:click="$set('showModal', false)" class="btn-firabo-outline me-2">Batal</button>
                <button wire:click="save" wire:loading.attr="disabled" class="btn-firabo">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="bi bi-arrow-repeat me-1"></i> Menyimpan...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>