<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Hub — menu pilihan jenis laporan.
     */
    public function index()
    {
        return view('admin.laporan.index');
    }

    /**
     * Laporan Tagihan Belum Dibayar.
     */
    public function tagihanBelumBayar()
    {
        return view('admin.laporan.tagihan-belum-bayar');
    }

    /**
     * Dipakai tombol "Preview" — PDF ditampilkan inline di iframe (modal),
     * bukan didownload. Dipanggil lewat fetch() dari JS.
     */
    public function tagihanBelumBayarPdf(Request $request)
    {
        $pdf = $this->buildTagihanBelumBayarPdf($request);
        // Dibungkus JSON + base64 sengaja — supaya di level response HTTP,
        // ini tidak terlihat seperti "file yang bisa didownload" sama sekali
        // bagi download manager/ekstensi browser yang mengintip fetch().
        return response()->json([
            'pdf_base64' => base64_encode($pdf->output()),
        ]);
    }

    /**
     * Dipakai tombol "Unduh PDF" — navigasi <a> biasa, memaksa browser
     * menyimpan file (Content-Disposition: attachment).
     */
    public function tagihanBelumBayarPdfUnduh(Request $request)
    {
        return $this->buildTagihanBelumBayarPdf($request)
            ->download($this->namaFilePdf());
    }

    /**
     * Satu-satunya tempat query & data laporan dibangun — dipakai kedua
     * endpoint di atas supaya tidak ada logic yang terduplikasi/bisa beda hasil.
     */
    private function buildTagihanBelumBayarPdf(Request $request)
    {
        $status = $request->query('status', '');
        $search = $request->query('search', '');

        $tagihan = Tagihan::belumLunas()
            ->milikPenghuniAktif()
            ->when($status, fn($q) => $q->where('status_tagihan', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('hunian.user', fn($q3) => $q3->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('hunian.kamar', fn($q3) => $q3->where('nomor_kamar', 'like', "%{$search}%"));
                });
            })
            ->with(['hunian.user', 'hunian.kamar'])
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->get();

        $ringkasan = [
            'total_nominal' => $tagihan->sum('nominal'),
            'jumlah_belum'  => $tagihan->where('status_tagihan', 'belum_bayar')->count(),
            'jumlah_telat'  => $tagihan->where('status_tagihan', 'terlambat')->count(),
        ];

        return Pdf::loadView('admin.laporan.pdf.tagihan-belum-bayar', [
            'tagihan'      => $tagihan,
            'ringkasan'    => $ringkasan,
            'filterStatus' => $status,
            'dicetakOleh'  => Auth::user()->name,
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'portrait');
    }

    private function namaFilePdf(): string
    {
        return 'laporan-tagihan-belum-bayar-' . now()->format('Y-m-d') . '.pdf';
    }

    public function piutang()
    {
        return view('admin.laporan.piutang');
    }

    public function piutangPdf(Request $request)
    {
        $pdf = $this->buildPiutangPdf($request);

        return response()->json([
            'pdf_base64' => base64_encode($pdf->output()),
        ]);
    }

    private function buildPiutangPdf(Request $request)
    {
        $search = $request->query('search', '');

        $penghuni = User::where('status_akun', 'kabur')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->with(['hunianTerakhir.kamar'])
            ->orderBy('name')
            ->get();

        $ringkasan = [
            'total_piutang'   => $penghuni->sum(fn($u) => $u->totalPiutang()),
            'jumlah_penghuni' => $penghuni->count(),
        ];

        return Pdf::loadView('admin.laporan.pdf.piutang', [
            'penghuni'     => $penghuni,
            'ringkasan'    => $ringkasan,
            'dicetakOleh'  => Auth::user()->name,
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'portrait');
    }

    public function pemasukan()
    {
        return view('admin.laporan.pemasukan');
    }

    public function pemasukanPdf(Request $request)
    {
        $pdf = $this->buildPemasukanPdf($request);

        return response()->json([
            'pdf_base64' => base64_encode($pdf->output()),
        ]);
    }

    private function buildPemasukanPdf(Request $request)
    {
        $mode   = $request->query('mode', 'bulanan');
        $bulan  = $request->query('bulan', now()->format('m'));
        $tahun  = $request->query('tahun', now()->format('Y'));
        $search = $request->query('search', '');

        $pembayaran = Pembayaran::where('status_pembayaran', 'sukses')
            ->when($mode === 'bulanan', fn($q) =>
                $q->whereMonth('tanggal_bayar', $bulan)->whereYear('tanggal_bayar', $tahun)
            )
            ->when($mode === 'tahunan', fn($q) =>
                $q->whereYear('tanggal_bayar', $tahun)
            )
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('tagihan.hunian.user', fn($q3) => $q3->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('tagihan.hunian.kamar', fn($q3) => $q3->where('nomor_kamar', 'like', "%{$search}%"));
                });
            })
            ->with(['tagihan.hunian.user', 'tagihan.hunian.kamar', 'user'])
            ->orderBy('tanggal_bayar')
            ->get();

        $ringkasan = [
            'total'     => $pembayaran->sum('nominal_bayar'),
            'jumlah'    => $pembayaran->count(),
            'rata_rata' => $pembayaran->count() ? $pembayaran->avg('nominal_bayar') : 0,
        ];

        $breakdownBulanan = null;
        if ($mode === 'tahunan') {
            $breakdownBulanan = Pembayaran::where('status_pembayaran', 'sukses')
                ->whereYear('tanggal_bayar', $tahun)
                ->selectRaw('MONTH(tanggal_bayar) as bulan, SUM(nominal_bayar) as total, COUNT(*) as jumlah')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get()
                ->keyBy('bulan');
        }

        $labelPeriode = $mode === 'bulanan'
            ? \Carbon\Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->translatedFormat('F Y')
            : 'Tahun ' . $tahun;

        return Pdf::loadView('admin.laporan.pdf.pemasukan', [
            'pembayaran'       => $pembayaran,
            'ringkasan'        => $ringkasan,
            'breakdownBulanan' => $breakdownBulanan,
            'mode'             => $mode,
            'labelPeriode'     => $labelPeriode,
            'dicetakOleh'      => Auth::user()->name,
            'tanggalCetak'     => now(),
        ])->setPaper('a4', 'portrait');
    }
}
