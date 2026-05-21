<?php

use App\Models\Pembayaran;
use App\Models\Hunian;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public function render()
    {
        $hunian = Hunian::where('user_id', Auth::id())
            ->where('status_hunian', 'aktif')
            ->first();

        $pembayaran = Pembayaran::query()
            ->whereHas('tagihan', fn($q) =>
                $q->where('hunian_id', $hunian?->hunian_id)
            )
            ->with(['tagihan'])
            ->orderBy('created_at', 'desc')
            ->paginate(8);

        return view('components.penghuni.pembayaran.table',
            compact('pembayaran'));
    }
};
?>

<div
    x-data="{ ready: false }"
    x-init="setTimeout(() => ready = true, 600)"
    class="table-card-wrapper"
>
    {{-- Desktop Table --}}
    <div class="table-view">
        <div class="firabo-card p-0 overflow-hidden">
            <table class="firabo-table">
                <thead>
                    <tr>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody x-show="!ready" x-cloak>
                    @include('components.penghuni.pembayaran._skeleton')
                </tbody>
                <tbody x-show="ready" x-cloak>
                    @forelse($pembayaran as $item)
                    <tr>
                        <td style="font-weight:600">
                            Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
                        </td>
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Belum ada riwayat pembayaran
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @include('components.penghuni.pembayaran._pagination', ['data' => $pembayaran])
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
                        <div class="skeleton skeleton-text" style="width:45px; margin-bottom:4px"></div>
                        <div class="skeleton skeleton-text" style="width:80px"></div>
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
                    <span class="item-card-title" style="font-size:16px">
                        Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}
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
                        <div class="field-label">Metode</div>
                        <div class="field-value" style="text-transform:capitalize; font-size:13px">
                            {{ str_replace('_', ' ', $item->metode_pembayaran) }}
                        </div>
                    </div>
                    <div class="item-card-field">
                        <div class="field-label">Tgl Bayar</div>
                        <div class="field-value" style="font-size:12px">
                            {{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y') }}
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
            @include('components.penghuni.pembayaran._pagination', ['data' => $pembayaran])
        </div>
    </div>
</div>