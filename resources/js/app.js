import '../scss/app.scss';
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Alpine.data reusable: modal pratinjau + download PDF, dipakai semua halaman Laporan.
// Cara pakai di root Livewire component:
//   x-data="pdfPreviewModal(() => 'URL_PDF_DENGAN_QUERY_PARAM')"
document.addEventListener('alpine:init', () => {
    Alpine.data('pdfPreviewModal', (buildUrl) => ({
        pdfModal: { show: false, url: null, base64: null, loading: false },

        bukaPreviewPdf() {
            this.pdfModal = { show: true, url: null, base64: null, loading: true };

            fetch(buildUrl(), { credentials: 'same-origin' })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (!data.pdf_base64) throw new Error('Data kosong');
                    this.pdfModal.base64 = data.pdf_base64;
                    this.pdfModal.url = 'data:application/pdf;base64,' + data.pdf_base64;
                    this.pdfModal.loading = false;
                })
                .catch(err => {
                    this.tutupPreviewPdf();
                    alert('Gagal memuat pratinjau (' + err.message + '). Silakan coba lagi.');
                });
        },

        tutupPreviewPdf() {
            this.pdfModal = { show: false, url: null, base64: null, loading: false };
        },

        downloadPdfDariPreview(namaFile = 'laporan.pdf') {
            if (!this.pdfModal.base64) return;

            const byteChars = atob(this.pdfModal.base64);
            const byteNumbers = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            const blob = new Blob([new Uint8Array(byteNumbers)], { type: 'application/pdf' });

            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = namaFile;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(a.href);
        }
    }));
});