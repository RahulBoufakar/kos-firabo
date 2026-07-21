<?php

use App\Models\Tagihan;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // ── Filter ────────────────────────────────────────────────────────────
    public string $search = '';
    public string $status = '';  // belum_bayar | lunas | terlambat

    // Mode khusus dari tombol "Jatuh Tempo Terdekat" di dashboard (?view=jatuh_tempo).
    // Saat aktif: filter status manual diabaikan, tampilkan semua yang belum lunas,
    // urut dari tanggal_jatuh_tempo paling dekat.
    public bool $modeJatuhTempo = false;

    public function mount(): void
    {
        $this->modeJatuhTempo = request()->query('view') === 'jatuh_tempo';
    }

    // Reset pagination saat filter berubah
    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    // Dipanggil dari tombol "Tampilkan Semua Tagihan" saat modeJatuhTempo aktif
    public function nonaktifkanModeJatuhTempo(): void
    {
        $this->modeJatuhTempo = false;
        $this->resetPage();
    }

    public function render()
    {
        $tagihan = Tagihan::query()
            ->with([
                'hunian.user',
                'hunian.kamar',
                'pembayaran' => fn($q) => $q->where('status_pembayaran', 'sukses')->latest()->limit(1),
            ])
            ->when($this->search, fn($q) =>
                $q->whereHas('hunian.user', fn($q2) =>
                    $q2->where('name', 'like', "%{$this->search}%")
                )->orWhereHas('hunian.kamar', fn($q2) =>
                    $q2->where('nomor_kamar', 'like', "%{$this->search}%")
                )
            )
            ->when(
                $this->modeJatuhTempo,
                fn($q) => $q->whereIn('status_tagihan', ['belum_bayar', 'terlambat']),
                fn($q) => $q->when($this->status, fn($q2) => $q2->where('status_tagihan', $this->status))
            )
            ->when(
                $this->modeJatuhTempo,
                fn($q) => $q->orderBy('tanggal_jatuh_tempo', 'desc'),
                fn($q) => $q->orderByDesc('tanggal_tagihan')
            )
            ->paginate(10);

        return view('components.admin.tagihan.table', compact('tagihan'));
    }
};
?>

<div>

    {{-- ══════════════════════════════════════════════════════════════
         BANNER MODE JATUH TEMPO — hanya muncul saat datang dari dashboard
    ══════════════════════════════════════════════════════════════ --}}
    @if($modeJatuhTempo)
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap"
             style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:.75rem 1rem;">
            <div class="d-flex align-items-center gap-2" style="font-size:.85rem; color:#92400e;">
                <i class="bi bi-clock-history"></i>
                Menampilkan semua tagihan <strong>belum lunas</strong>, diurutkan dari jatuh tempo paling dekat.
            </div>
            <button
                wire:click="nonaktifkanModeJatuhTempo"
                class="btn-firabo-outline"
                style="font-size:.8rem; padding:.35rem .875rem;"
            >
                <i class="bi bi-x-lg me-1"></i> Tampilkan Semua Tagihan
            </button>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         TOOLBAR
    ══════════════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">

        <div class="search-bar flex-grow-1" style="max-width: 360px;">
            <i class="bi bi-search"></i>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama penghuni atau nomor kamar..."
            >
        </div>

        @unless($modeJatuhTempo)
            <select class="firabo-input" wire:model.live="status" style="max-width: 180px;">
                <option value="">Semua Status</option>
                <option value="belum_bayar">Belum Bayar</option>
                <option value="lunas">Lunas</option>
                <option value="terlambat">Terlambat</option>
                <option value="piutang">Piutang</option>
            </select>
        @endunless

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABLE + CARD WRAPPER (responsive via container query)
    ══════════════════════════════════════════════════════════════ --}}
    <div
        class="table-card-wrapper"
        x-data="{ ready: false }"
        x-init="setTimeout(() => ready = true, 600)"
    >

        {{-- ── Desktop: Table ── --}}
        <div class="table-view">
            <div class="firabo-card p-0 overflow-hidden">
                <table class="firabo-table">
                    <thead>
                        <tr>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th>Periode</th>
                            <th>Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    {{-- Skeleton --}}
                    <tbody x-show="!ready" x-cloak>
                        @include('components.admin.tagihan._skeleton')
                    </tbody>

                    {{-- Data --}}
                    <tbody x-show="ready" x-cloak>
                        @forelse ($tagihan as $t)
                            @php
                                $penghuni    = $t->hunian?->user;
                                $kamar       = $t->hunian?->kamar;
                                $sisaHari    = \Carbon\Carbon::today()->diffInDays($t->tanggal_jatuh_tempo, false);
                                $isTerlambat = $t->status_tagihan === 'terlambat';
                                $isLunas     = $t->status_tagihan === 'lunas';
                                $isPiutang   = $t->status_tagihan === 'piutang';
                            @endphp
                            <tr>

                                {{-- Penghuni --}}
                                <td>
                                    <div class="fw-semibold" style="color: var(--firabo-primary-dark); font-size: .9rem;">
                                        {{ $penghuni?->name ?? '—' }}
                                    </div>
                                    <div style="font-size: .75rem; color: #9ca3af; margin-top: 1px;">
                                        {{ $penghuni?->email ?? '' }}
                                    </div>
                                </td>

                                {{-- Kamar --}}
                                <td>
                                    <span class="fw-medium" style="color: var(--firabo-primary);">
                                        {{ $kamar?->nomor_kamar ?? '—' }}
                                    </span>
                                    <div style="font-size: .75rem; color: #9ca3af;">
                                        {{ $kamar?->tipe_kamar ?? '' }}
                                    </div>
                                </td>

                                {{-- Periode --}}
                                <td style="font-size: .875rem; color: #374151;">
                                    {{ \Carbon\Carbon::parse($t->tanggal_tagihan)->translatedFormat('F Y') }}
                                </td>

                                {{-- Nominal --}}
                                <td class="fw-semibold" style="color: var(--firabo-primary-dark);">
                                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                                </td>

                                {{-- Jatuh Tempo --}}
                                <td>
                                    <div style="font-size: .85rem; {{ $isTerlambat ? 'color: #991b1b; font-weight: 600;' : 'color: #4b5563;' }}">
                                        {{ \Carbon\Carbon::parse($t->tanggal_jatuh_tempo)->translatedFormat('d M Y') }}
                                    </div>
                                    @if (! $isLunas && ! $isPiutang)
                                        <div style="font-size: .72rem; margin-top: 1px; color: {{ $isTerlambat ? '#991b1b' : '#9ca3af' }};">
                                            @if ($isTerlambat)
                                                {{ abs($sisaHari) }} hari terlambat
                                            @elseif ($sisaHari === 0)
                                                Hari ini
                                            @else
                                                {{ $sisaHari }} hari lagi
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if ($isLunas)
                                        <span class="badge-lunas">Lunas</span>
                                    @elseif ($isPiutang)
                                        <span class="badge-piutang">Piutang</span>
                                    @elseif ($isTerlambat)
                                        <span class="badge-terlambat">Terlambat</span>
                                    @else
                                        <span class="badge-belum">Belum Bayar</span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt d-block mb-2" style="font-size: 1.75rem; opacity: .4;"></i>
                                    Tidak ada tagihan ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination footer --}}
                @if ($tagihan->hasPages())
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                         style="border-top: 1px solid var(--firabo-border); background: #fafafa;">
                        <span style="font-size: .8rem; color: #6b7280;">
                            Menampilkan {{ $tagihan->firstItem() }}–{{ $tagihan->lastItem() }}
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

        {{-- ── Mobile: Card View ── --}}
        <div class="card-view">

            {{-- Skeleton mobile --}}
            <div x-show="!ready" x-cloak>
                @for ($i = 0; $i < 4; $i++)
                    <div class="item-card">
                        <div class="item-card-header">
                            <div class="skel" style="width:140px;height:14px;border-radius:4px;"></div>
                            <div class="skel" style="width:72px;height:22px;border-radius:20px;"></div>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-field">
                                <div class="skel" style="width:35px;height:11px;border-radius:4px;margin-bottom:4px;"></div>
                                <div class="skel" style="width:55px;height:13px;border-radius:4px;"></div>
                            </div>
                            <div class="item-card-field">
                                <div class="skel" style="width:40px;height:11px;border-radius:4px;margin-bottom:4px;"></div>
                                <div class="skel" style="width:80px;height:13px;border-radius:4px;"></div>
                            </div>
                            <div class="item-card-field full-width">
                                <div class="skel" style="width:50px;height:11px;border-radius:4px;margin-bottom:4px;"></div>
                                <div class="skel" style="width:90px;height:13px;border-radius:4px;"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Data mobile --}}
            <div x-show="ready" x-cloak>
                @forelse ($tagihan as $t)
                    @php
                        $penghuni    = $t->hunian?->user;
                        $kamar       = $t->hunian?->kamar;
                        $sisaHari    = \Carbon\Carbon::today()->diffInDays($t->tanggal_jatuh_tempo, false);
                        $isTerlambat = $t->status_tagihan === 'terlambat';
                        $isLunas     = $t->status_tagihan === 'lunas';
                        $isPiutang   = $t->status_tagihan === 'piutang';
                    @endphp
                    <div class="item-card">
                        <div class="item-card-header">
                            <span class="item-card-title">{{ $penghuni?->name ?? '—' }}</span>
                            @if ($isLunas)
                                <span class="badge-lunas">Lunas</span>
                            @elseif ($isPiutang)
                                <span class="badge-piutang">Piutang</span>
                            @elseif ($isTerlambat)
                                <span class="badge-terlambat">Terlambat</span>
                            @else
                                <span class="badge-belum">Belum Bayar</span>
                            @endif
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-field">
                                <div class="field-label">Kamar</div>
                                <div class="field-value" style="color: var(--firabo-primary); font-weight: 600;">
                                    {{ $kamar?->nomor_kamar ?? '—' }}
                                </div>
                            </div>
                            <div class="item-card-field">
                                <div class="field-label">Periode</div>
                                <div class="field-value">
                                    {{ \Carbon\Carbon::parse($t->tanggal_tagihan)->translatedFormat('M Y') }}
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
                                    @if (! $isLunas && ! $isPiutang)
                                        <span style="font-size: .75rem; font-weight: 400; color: {{ $isTerlambat ? '#991b1b' : '#9ca3af' }};">
                                            ({{ $isTerlambat ? abs($sisaHari).' hari terlambat' : ($sisaHari === 0 ? 'hari ini' : $sisaHari.' hari lagi') }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-receipt d-block mb-2" style="font-size: 1.75rem; opacity: .4;"></i>
                        Tidak ada tagihan ditemukan.
                    </div>
                @endforelse

                {{-- Pagination mobile --}}
                @include('components.admin.tagihan._pagination', ['data' => $tagihan])
            </div>

        </div>

    </div>{{-- /table-card-wrapper --}}

</div>

<style>
/* Skeleton shimmer — scope lokal agar tidak konflik */
.skel {
    display: block;
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200% 100%;
    animation: skel-shimmer 1.4s infinite;
}
@keyframes skel-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>