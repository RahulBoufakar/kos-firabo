<?php

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

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
        $this->showModal     = true;
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

        return view('components.admin.pembayaran.table',
            compact('pembayaran', 'tagihanBelumLunas'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 600)"
    class="table-card-wrapper"
>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Toolbar --}}
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
            @include('components.admin.pembayaran._pagination', ['data' => $pembayaran])
        </div>
    </div>

    {{-- Mobile Card --}}
    <div class="card-view">
        <div x-show="!ready" x-cloak>
            @for($i = 0; $i < 4; $i++)
            <div class="item-card">
                <div class="item-card-header">
                    <div class="skeleton skeleton-text" style="width:130px; height:16px"></div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:55px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:50px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:100px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:70px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:55px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:90px"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        <div x-show="ready" x-cloak>
            @forelse($pembayaran as $item)
            <div class="item-card">
                <div class="item-card-header">
                    <span class="item-card-title">
                        {{ $item->tagihan->hunian->user->name ?? '-' }}
                    </span>
                    @if($item->status_pembayaran === 'sukses')
                        <span class="badge-sukses">Sukses</span>
                    @elseif($item->status_pembayaran === 'pending')
                        <span class="badge-pending">Pending</span>
                    @else
                        <span class="badge-nonaktif">Gagal</span>
                    @endif
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="field-label">Kamar</div>
                        <div class="field-value" style="color:var(--firabo-primary)">
                            {{ $item->tagihan->hunian->kamar->nomor_kamar ?? '-' }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Nominal</div>
                        <div class="field-value">
                            Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Metode</div>
                        <div class="field-value" style="font-size:12px; text-transform:capitalize">
                            {{ str_replace('_', ' ', $item->metode_pembayaran) }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Tgl Bayar</div>
                        <div class="field-value" style="font-size:12px">
                            {{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}
                        </div>
                    </div>
                    <div class="item-card-field full-width">
                        <div class="field-label">Dicatat Oleh</div>
                        <div class="field-value" style="font-size:12px; color:#6b7280">
                            {{ $item->user->name ?? 'Online' }}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                Belum ada riwayat pembayaran
            </div>
            @endforelse
            @include('components.admin.pembayaran._pagination', ['data' => $pembayaran])
        </div>
    </div>

    @include('components.admin.pembayaran._modal-form')
</div>