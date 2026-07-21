{{-- Partial reusable untuk semua halaman Laporan.
     Wajib di-include di dalam root yang x-data-nya pakai pdfPreviewModal(...).
     Pemakaian: @include('components.admin.laporan._modal-preview-pdf', ['namaFile' => 'nama-file.pdf']) --}}
<div
    x-show="pdfModal.show"
    x-cloak
    x-transition.opacity
    style="position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1055;"
    @keydown.escape.window="tutupPreviewPdf()"
    @click="tutupPreviewPdf()"
>
    <div style="display:flex; align-items:center; justify-content:center; height:100%; padding:1rem;">
        <div
            x-show="pdfModal.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            style="
                background:#fff; border-radius:14px;
                width:100%; max-width:900px; height:90vh;
                display:flex; flex-direction:column; overflow:hidden;
                box-shadow:0 20px 60px rgba(0,0,0,.25);
            "
            @click.stop
        >
            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                 style="border-bottom:1px solid var(--firabo-border); flex-shrink:0;">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-file-earmark-pdf me-2" style="color:var(--firabo-primary);"></i>
                    Pratinjau Laporan
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn-firabo"
                        style="padding:.4rem .9rem; font-size:.82rem;"
                        :disabled="pdfModal.loading"
                        @click="downloadPdfDariPreview('{{ $namaFile }}')"
                    >
                        <i class="bi bi-download"></i> Download
                    </button>
                    <button type="button" class="btn-close" @click="tutupPreviewPdf()"></button>
                </div>
            </div>

            {{-- Area PDF — Dibuat flex:1 1 auto & height:100% agar benar-benar penuh ke bawah --}}
            <div style="flex:1 1 auto; height:100%; position:relative; background:#525659; overflow:hidden;">
                <div
                    x-show="pdfModal.loading"
                    class="text-center text-white"
                    style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:10;"
                >
                    <div class="spinner-border mb-2" role="status"></div>
                    <div style="font-size:.85rem;">Memuat pratinjau...</div>
                </div>
                
                {{-- Iframe diberi absolute & inset:0 agar mutlak menempel pada tepi container-nya --}}
                <iframe
                    x-show="!pdfModal.loading"
                    x-ref="pdfFrame"
                    :src="pdfModal.url ?? 'about:blank'"
                    style="position:absolute; inset:0; width:100%; height:100%; border:none; display:block;"
                ></iframe>
            </div>
        </div>
    </div>
</div>