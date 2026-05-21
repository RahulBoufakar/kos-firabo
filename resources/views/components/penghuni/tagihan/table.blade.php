<?php

use App\Models\Tagihan;
use App\Models\Hunian;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $filterStatus = '';

    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function render()
    {
        $hunian = Hunian::where('user_id', Auth::id())
            ->where('status_hunian', 'aktif')
            ->first();

        $tagihan = Tagihan::query()
            ->where('hunian_id', $hunian?->hunian_id)
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_tagihan', $this->filterStatus)
            )
            ->orderBy('tanggal_tagihan', 'desc')
            ->paginate(8);

        return view('components.penghuni.tagihan.table',
            compact('tagihan', 'hunian'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 600)"
    class="table-card-wrapper"
>
    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <select wire:model.live="filterStatus"
                class="firabo-input" style="width:auto; height:38px; padding:0 0.875rem">
            <option value="">Semua Status</option>
            <option value="belum_bayar">Belum Bayar</option>
            <option value="lunas">Lunas</option>
            <option value="terlambat">Terlambat</option>
        </select>
    </div>

    {{-- Desktop Table --}}
    <div class="table-view">
        <div class="firabo-card p-0 overflow-hidden">
            <table class="firabo-table">
                <thead>
                    <tr>
                        <th>Nominal</th>
                        <th>Tanggal Tagihan</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody x-show="!ready" x-cloak>
                    @include('components.penghuni.tagihan._skeleton')
                </tbody>
                <tbody x-show="ready" x-cloak>
                    @forelse($tagihan as $item)
                    <tr>
                        <td style="font-weight:600">
                            Rp {{ number_format($item->nominal, 0, ',', '.') }}
                        </td>
                        <td style="font-size:13px">
                            {{ $item->tanggal_tagihan->format('d M Y') }}
                        </td>
                        <td style="font-size:13px">
                            @php $isLate = $item->tanggal_jatuh_tempo < now()
                                           && $item->status_tagihan !== 'lunas'; @endphp
                            <span style="{{ $isLate ? 'color:#dc3545; font-weight:600' : '' }}">
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
                        <td class="text-end">
                            @if($item->status_tagihan !== 'lunas')
                                <a href="{{ route('penghuni.tagihan.show', $item->tagihan_id) }}"
                                   class="btn-firabo" style="font-size:13px; padding:6px 14px">
                                    <i class="bi bi-credit-card me-1"></i> Bayar
                                </a>
                            @else
                                <a href="{{ route('penghuni.tagihan.show', $item->tagihan_id) }}"
                                   class="btn-firabo-outline" style="font-size:13px; padding:6px 14px">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Belum ada tagihan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @include('components.penghuni.tagihan._pagination', ['data' => $tagihan])
        </div>
    </div>

    {{-- Mobile Card --}}
    <div class="card-view">
        <div x-show="!ready" x-cloak>
            @for($i = 0; $i < 3; $i++)
            <div class="item-card">
                <div class="item-card-header">
                    <div class="skeleton skeleton-text" style="width:130px; height:18px"></div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:60px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:90px"></div>
                    </div>
                    <div class="item-card-field">
                        <div class="skeleton skeleton-text" style="width:70px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:90px"></div>
                    </div>
                    <div class="item-card-field full-width">
                        <div class="skeleton" style="width:100%; height:34px; border-radius:8px"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        <div x-show="ready" x-cloak>
            @forelse($tagihan as $item)
            @php $isLate = $item->tanggal_jatuh_tempo < now()
                           && $item->status_tagihan !== 'lunas'; @endphp
            <div class="item-card">
                <div class="item-card-header">
                    <span class="item-card-title" style="font-size:16px">
                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                    </span>
                    @if($item->status_tagihan === 'lunas')
                        <span class="badge-lunas">Lunas</span>
                    @elseif($item->status_tagihan === 'terlambat')
                        <span class="badge-terlambat">Terlambat</span>
                    @else
                        <span class="badge-belum">Belum Bayar</span>
                    @endif
                </div>
                <div class="item-card-body">
                    <div class="item-card-field">
                        <div class="field-label">Tgl Tagihan</div>
                        <div class="field-value" style="font-size:12px">
                            {{ $item->tanggal_tagihan->format('d M Y') }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Jatuh Tempo</div>
                        <div class="field-value" style="font-size:12px;
                             {{ $isLate ? 'color:#dc3545; font-weight:600' : '' }}">
                            {{ $item->tanggal_jatuh_tempo->format('d M Y') }}
                        </div>
                    </div>
                    <div class="item-card-field full-width" style="margin-top:4px">
                        @if($item->status_tagihan !== 'lunas')
                            <a href="{{ route('penghuni.tagihan.show', $item->tagihan_id) }}"
                               class="btn-firabo w-100 justify-content-center">
                                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                            </a>
                        @else
                            <a href="{{ route('penghuni.tagihan.show', $item->tagihan_id) }}"
                               class="btn-firabo-outline w-100 justify-content-center">
                                <i class="bi bi-eye me-1"></i> Lihat Detail
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                Belum ada tagihan
            </div>
            @endforelse
            @include('components.penghuni.tagihan._pagination', ['data' => $tagihan])
        </div>
    </div>
</div>