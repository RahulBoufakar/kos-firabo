{{-- resources/views/components/penghuni/pembayaran/_modal_detail.blade.php --}}

<div wire:ignore.self wire:key="modal-{{ $item->pembayaran_id }}" class="modal fade" id="modalDetail-{{ $item->pembayaran_id }}" tabindex="-1" aria-labelledby="modalDetailLabel-{{ $item->pembayaran_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            
            {{-- HEADER MODAL --}}
            <div class="modal-header border-bottom-0 pb-0 justify-content-between align-items-center">
                <h5 class="modal-title fw-bold" id="modalDetailLabel-{{ $item->pembayaran_id }}">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- BODY MODAL --}}
            <div class="modal-body pt-4">
                {{-- Nominal & Status (Highlight) --}}
                <div class="text-center mb-4">
                    <p class="text-muted mb-1" style="font-size: 14px;">Total Pembayaran</p>
                    <h2 class="fw-bolder mb-2 text-dark">Rp {{ number_format($item->nominal_bayar, 0, ',', '.') }}</h2>
                    
                    @php $status = strtolower(trim($item->status_pembayaran)); @endphp
                    @if($status === 'sukses')
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill border border-success border-opacity-25">Sukses</span>
                    @elseif($status === 'pending')
                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill border border-warning border-opacity-25">
                            <span class="spinner-grow spinner-grow-sm me-1 opacity-75" style="width: 10px; height: 10px;" role="status"></span>
                            Menunggu Pembayaran
                        </span>
                    @else
                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill border border-danger border-opacity-25">Gagal / Dibatalkan</span>
                    @endif
                </div>

                {{-- ── BAGIAN 1: RINCIAN TAGIHAN ── --}}
                <div class="bg-light rounded-3 p-3 mb-3 border">
                    <h6 class="fw-bold mb-3" style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Rincian Tagihan</h6>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted" style="font-size: 14px;">Nomor Tagihan</span>
                        <span class="fw-medium text-end" style="font-size: 14px;">
                            #INV-{{ $item->tagihan_id }}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted" style="font-size: 14px;">Periode</span>
                        <span class="fw-medium text-end" style="font-size: 14px;">
                            {{-- Sesuaikan dengan nama kolom bulan/tahun di tabel Tagihan Anda --}}
                            {{ $item->tagihan->tanggal_tagihan->translatedFormat('F') }} {{ $item->tagihan->tanggal_tagihan->translatedFormat('Y')}}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span class="text-muted" style="font-size: 14px;">Tipe Tagihan</span>
                        <span class="fw-medium text-end" style="font-size: 14px;">
                            {{-- Contoh pemanggilan, sesuaikan dengan kolom Anda --}}
                            Sewa Kos
                        </span>
                    </div>
                </div>

                {{-- ── BAGIAN 2: INFORMASI TRANSAKSI ── --}}
                <div class="px-2">
                    <h6 class="fw-bold mb-3 mt-4" style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Sistem Pembayaran</h6>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted" style="font-size: 14px;">Metode Bayar</span>
                        <span class="fw-medium" style="font-size: 14px; text-transform: uppercase;">
                            {{ $item->metode_pembayaran ? str_replace('_', ' ', $item->metode_pembayaran) : '-' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted" style="font-size: 14px;">ID Transaksi</span>
                        <span class="fw-medium text-end" style="font-size: 14px; word-break: break-all; max-width: 60%;">
                            {{ $item->transaction_id ?? '-' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted" style="font-size: 14px;">Waktu Dibuat</span>
                        <span class="fw-medium" style="font-size: 14px;">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size: 14px;">Waktu Lunas</span>
                        <span class="fw-medium" style="font-size: 14px;">
                            {{ $item->tanggal_bayar ? \Carbon\Carbon::parse($item->tanggal_bayar)->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- FOOTER MODAL --}}
            <div class="modal-footer border-top-0 pt-3 pb-4 justify-content-center px-4">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-medium py-2" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>