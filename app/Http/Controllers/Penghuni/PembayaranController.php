<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;

/**
 * PembayaranController (Penghuni)
 *
 * Bertanggung jawab atas dua hal dalam konteks pembayaran penghuni:
 *
 * 1. index()    — Menampilkan riwayat seluruh pembayaran milik penghuni
 * 2. callback() — Menerima dan memproses webhook dari Midtrans
 *
 * Catatan: Tidak ada method store() di sini karena proses pembayaran
 * dimulai dari client-side (Snap.js popup), bukan dari POST ke server kita.
 * snap_token sudah disiapkan oleh MidtransService saat halaman show tagihan
 * dimuat, sehingga tidak perlu route store terpisah.
 */
class PembayaranController extends Controller
{
    // =========================================================================
    //  INDEX — Riwayat Pembayaran Penghuni
    // =========================================================================

    /**
     * Tampilkan seluruh riwayat pembayaran milik penghuni yang sedang login.
     *
     * Query mengikuti relasi:
     *   tb_pembayaran → tb_tagihan → tb_hunian → user_id = auth()->id()
     *
     * Ini memastikan penghuni hanya bisa melihat pembayaran miliknya sendiri,
     * tanpa perlu Policy karena filter dilakukan langsung di query.
     */
    public function index(): \Illuminate\View\View
    {
        // Ambil hunian aktif penghuni yang login
        // Jika tidak punya hunian, $hunian = null dan view menampilkan empty state
        $hunian = Auth::user()->hunianAktif;

        $pembayaran = collect(); // default kosong jika belum punya hunian

        if ($hunian) {
            $pembayaran = Pembayaran::query()
                // Join ke tagihan untuk filter berdasarkan hunian
                ->whereHas('tagihan', function ($q) use ($hunian) {
                    $q->where('hunian_id', $hunian->hunian_id);
                })
                // Eager load relasi yang dibutuhkan view
                ->with([
                    'tagihan',        // untuk tampilkan periode & nominal tagihan
                    'pencatat',       // nama admin jika pembayaran manual
                ])
                // Terbaru di atas
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        return view('penghuni.pembayaran.index', compact('pembayaran', 'hunian'));
    }

    // =========================================================================
    //  CALLBACK — Webhook Midtrans
    // =========================================================================

    /**
     * Terima dan proses notifikasi transaksi dari server Midtrans.
     *
     * Endpoint ini dipanggil oleh Midtrans (bukan browser penghuni),
     * sehingga harus exempt dari CSRF — lihat bootstrap/app.php.
     *
     * Alur:
     * 1. Parse notifikasi via Midtrans\Notification (SDK otomatis verifikasi signature)
     * 2. Ekstrak tagihan_id dari order_id (format: FIRABO-{tagihan_id}-{timestamp})
     * 3. Tentukan status pembayaran & tagihan berdasarkan transaction_status
     * 4. Update record di tb_pembayaran dan tb_tagihan
     *
     * Selalu return HTTP 200 — jika kita return non-200, Midtrans akan
     * terus me-retry notifikasi yang sama hingga beberapa kali.
     */
    public function callback(Request $request): Response
    {
        // Konfigurasi Midtrans SDK untuk proses verifikasi signature key
        // yang dikirim Midtrans di setiap notifikasi
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);

        // Parse notifikasi — SDK akan throw Exception jika signature tidak valid
        // (misalnya request bukan dari Midtrans atau data dimanipulasi)
        try {
            $notif = new Notification();
        } catch (\Exception $e) {
            Log::error('[Midtrans Callback] Gagal parse notifikasi', [
                'error' => $e->getMessage(),
                'body'  => $request->all(),
            ]);
            // 400 hanya untuk request yang jelas-jelas tidak valid / bukan dari Midtrans
            return response('Bad Request', 400);
        }

        // Ambil field-field penting dari notifikasi Midtrans
        $orderId           = $notif->order_id;           // format: FIRABO-{tagihan_id}-{timestamp}
        $transactionStatus = $notif->transaction_status; // capture | settlement | pending | deny | cancel | expire
        $fraudStatus       = $notif->fraud_status ?? null; // accept | deny | challenge (khusus kartu kredit)
        $paymentType       = $notif->payment_type;       // gopay | qris | bank_transfer | credit_card | dll
        $grossAmount       = $notif->gross_amount;       // nominal transaksi dari Midtrans

        Log::info('[Midtrans Callback] Notifikasi diterima', [
            'order_id'           => $orderId,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
            'payment_type'       => $paymentType,
        ]);

        // Ekstrak tagihan_id dari order_id
        // order_id format: FIRABO-{tagihan_id}-{unix_timestamp}
        // contoh: FIRABO-42-1717200000 → parts[1] = '42'
        $parts     = explode('-', $orderId);
        $tagihanId = $parts[1] ?? null;

        if (! $tagihanId) {
            Log::error('[Midtrans Callback] Format order_id tidak dikenali', compact('orderId'));
            return response('OK', 200); // tetap 200 agar tidak di-retry
        }

        // Cari tagihan di database — jika tidak ketemu, log dan keluar
        $tagihan = Tagihan::find($tagihanId);

        if (! $tagihan) {
            Log::error('[Midtrans Callback] Tagihan tidak ditemukan', compact('tagihanId'));
            return response('OK', 200);
        }

        // Resolusi status: mapping dari status Midtrans ke status internal sistem
        [$statusPembayaran, $statusTagihan] = $this->resolveStatus(
            $transactionStatus,
            $fraudStatus
        );

        // Cari record pembayaran yang statusnya masih pending untuk tagihan ini
        // Record ini dibuat terlebih dahulu oleh MidtransService::getOrCreateSnapToken()
        // saat penghuni membuka halaman detail tagihan
        $pembayaran = Pembayaran::where('tagihan_id', $tagihanId)
            ->where('status_pembayaran', 'pending')
            ->latest()
            ->first();

        if ($pembayaran) {
            // Update record pending yang sudah ada
            $pembayaran->update([
                'metode_pembayaran' => $paymentType ?? 'online',
                'nominal_bayar'     => $grossAmount,
                // tanggal_bayar hanya diisi jika transaksi benar-benar sukses
                'tanggal_bayar'     => $statusPembayaran === 'sukses' ? Carbon::now() : null,
                'status_pembayaran' => $statusPembayaran,
                'transaction_id'    => $orderId,
            ]);
        } else {
            // Fallback: buat record baru
            // Terjadi jika: callback datang tanpa ada record pending sebelumnya
            // (misalnya token dibuat di luar sistem, atau record terhapus)
            Log::warning('[Midtrans Callback] Tidak ada record pending, membuat record baru', [
                'tagihan_id' => $tagihanId,
            ]);

            Pembayaran::create([
                'tagihan_id'        => $tagihanId,
                'user_id'           => null, // null = transaksi online, bukan pencatatan manual admin
                'metode_pembayaran' => $paymentType ?? 'online',
                'nominal_bayar'     => $grossAmount,
                'tanggal_bayar'     => $statusPembayaran === 'sukses' ? Carbon::now() : null,
                'status_pembayaran' => $statusPembayaran,
                'snap_token'        => null,
                'transaction_id'    => $orderId,
            ]);
        }

        // Update status tagihan — hanya dilakukan jika resolveStatus()
        // mengembalikan nilai (tidak null), yaitu hanya saat sukses
        if ($statusTagihan) {
            $tagihan->update(['status_tagihan' => $statusTagihan]);

            Log::info('[Midtrans Callback] Tagihan diperbarui ke: ' . $statusTagihan, [
                'tagihan_id' => $tagihanId,
            ]);
        }

        return response('OK', 200);
    }

    // =========================================================================
    //  PRIVATE HELPER
    // =========================================================================

    /**
     * Mapping status dari Midtrans ke status internal sistem.
     *
     * Midtrans memiliki banyak status transaksi. Kita sederhanakan
     * menjadi tiga status internal: sukses | pending | gagal.
     *
     * Status tagihan hanya diubah ke 'lunas' saat pembayaran benar-benar
     * sukses (capture+accept atau settlement). Untuk status lain,
     * tagihan tidak disentuh ($statusTagihan = null).
     *
     * Referensi status Midtrans:
     * https://docs.midtrans.com/reference/get-transaction-status
     *
     * @return array{0: string, 1: string|null}
     *         [status_pembayaran, status_tagihan|null]
     */
    private function resolveStatus(string $transactionStatus, ?string $fraudStatus): array
    {
        return match (true) {
            // Kartu kredit: capture + tidak fraud → sukses
            $transactionStatus === 'capture' && $fraudStatus === 'accept',
            // Transfer bank, e-wallet, QRIS: settlement = uang sudah masuk
            $transactionStatus === 'settlement'
                => ['sukses', 'lunas'],

            // Menunggu pembayaran dari pengguna (misalnya VA belum dibayar)
            // Tagihan tidak diubah — masih belum_bayar atau terlambat
            $transactionStatus === 'pending'
                => ['pending', null],

            // Transaksi gagal karena berbagai sebab
            // Tagihan tidak diubah — penghuni masih bisa mencoba lagi
            in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])
                => ['gagal', null],

            // Status tidak dikenali — anggap pending untuk keamanan
            default => ['pending', null],
        };
    }
}