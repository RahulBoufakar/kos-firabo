<div class="d-flex justify-content-center" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="firabo-card w-100" style="max-width: 500px;">
        <div class="firabo-card-header pb-0 border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Reaktivasi Penghuni</h5>
            <p class="text-muted" style="font-size: 14px;">
                Aktifkan kembali akun penghuni dan pilihkan kamar baru.
            </p>
        </div>
        
        <div class="firabo-card-body p-4 pt-3">
            <form wire:submit.prevent="simpanReaktivasi">
                
                {{-- Alert Informasi --}}
                <div class="alert alert-info d-flex align-items-center mb-4" style="font-size: 13px; border-radius: 10px; padding: 12px 16px;">
                    <i class="bi bi-info-circle-fill me-3 fs-5"></i>
                    <div>
                        Sistem akan mengaktifkan kembali akun <strong>{{ $reaktivasiNama }}</strong>, membuat jadwal tagihan baru, dan menandai kamar yang dipilih menjadi terisi.
                    </div>
                </div>

                {{-- Field: Nama Penghuni (Read-only) --}}
                <div class="mb-3">
                    <label class="firabo-label">Nama Penghuni</label>
                    <input 
                        type="text" 
                        class="firabo-input" 
                        value="{{ $reaktivasiNama }}" 
                        readonly 
                        style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed;"
                    >
                </div>

                {{-- Field: Pilih Kamar --}}
                <div class="mb-4">
                    <label for="reaktivasiKamarId" class="firabo-label">
                        Pilih Kamar Baru <span class="text-danger">*</span>
                    </label>
                    <select 
                        id="reaktivasiKamarId"
                        wire:model="reaktivasiKamarId" 
                        class="firabo-input @error('reaktivasiKamarId') is-invalid @enderror"
                    >
                        <option value="">-- Pilih Kamar Tersedia --</option>
                        @forelse($kamarTersedia as $kamar)
                            <option value="{{ $kamar->kamar_id }}">
                                {{ $kamar->nomor_kamar }} 
                                {{ $kamar->tipe_kamar ? ' - ' . $kamar->tipe_kamar : '' }}
                            </option>
                        @empty
                            <option value="" disabled>Tidak ada kamar tersedia</option>
                        @endforelse
                    </select>
                    @error('reaktivasiKamarId')
                        <div class="invalid-feedback" style="font-size: 12px; margin-top: 4px;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-2 border-top pt-4 mt-2">
                    <button 
                        type="button" 
                        wire:click="cancelReaktivasi" 
                        class="btn btn-outline-secondary" 
                        style="border-radius: 8px; font-weight: 500; padding: 8px 16px;"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="btn-firabo" 
                        style="padding: 8px 20px;"
                    >
                        <i class="bi bi-person-check-fill me-1"></i> Simpan & Aktifkan
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>