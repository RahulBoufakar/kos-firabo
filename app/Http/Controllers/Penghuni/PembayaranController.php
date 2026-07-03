<?php

namespace App\Http\Controllers\Penghuni;

use App\Events\PembayaranBerhasil;
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

    /**
     * Invalidasi snap token yang expired/gagal.
     *
     * Dipanggil dari Snap.js onError handler via fetch() ketika Midtrans
     * melaporkan token sudah expired atau transaksi gagal di sisi client.
     *
     * Yang dilakukan:
     * 1. Cari record pending milik tagihan ini
     * 2. Update statusnya ke 'gagal'
     * 3. Return JSON sukses → client redirect ke halaman show (token baru akan digenerate)
     *
     * Kenapa return JSON bukan redirect?
     * Karena dipanggil via fetch() dari JavaScript, bukan dari form submit biasa.
     */
    public function invalidateToken(Tagihan $tagihan): \Illuminate\Http\JsonResponse
    {
        // Pastikan tagihan ini milik penghuni yang sedang login
        // Cek manual via hunian — tidak pakai Policy karena ini endpoint JSON
        if ($tagihan->hunian->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Tandai semua record pending tagihan ini sebagai gagal
        // Sehingga getOrCreateSnapToken() tidak akan mengembalikan token lama ini
        Pembayaran::where('tagihan_id', $tagihan->tagihan_id)
            ->where('status_pembayaran', 'pending')
            ->update([
                'status_pembayaran' => 'gagal',
                'tanggal_bayar'     => null,
            ]);
    
        return response()->json(['message' => 'Token invalidated']);
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
        // Konfigurasi Midtrans SDK
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);

        try {
            $notif = new Notification();
        } catch (\Exception $e) {
            Log::error('[Midtrans Callback] Gagal parse notifikasi', [
                'error' => $e->getMessage(),
                'body'  => $request->all(),
            ]);
            return response('Bad Request', 400);
        }

        // 1. FALLBACK KE $request UNTUK MENGHINDARI ANOMALI REDIRECT
        // Ambil data langsung dari $request JSON jika SDK memberikan data yang tertukar
        $orderId           = $request->order_id ?? $notif->order_id;
        $transactionId     = $request->transaction_id ?? $notif->transaction_id;
        $transactionStatus = $request->transaction_status ?? $notif->transaction_status;
        $fraudStatus       = $request->fraud_status ?? $notif->fraud_status ?? null;
        $paymentType       = $request->payment_type ?? $notif->payment_type;
        $grossAmount       = $request->gross_amount ?? $notif->gross_amount;

        // 2. CEK ANOMALI TRANSAKSI
        // Jika order_id malah berisi karakter acak tanpa tanda '-', kembalikan respons OK 
        // agar Midtrans tidak terus melakukan retry pada request redirect yang cacat ini.
        // Webhook asli (POST) dari Midtrans nantinya akan memiliki format yang benar.
        if (!str_contains($orderId, '-')) {
            Log::warning('[Midtrans Callback] Anomali Payload: order_id terdeteksi sebagai transaction_id. Mengabaikan request (kemungkinan dari GET Redirect).', compact('orderId', 'transactionId'));
            return response('OK', 200); 
        }

        Log::info('[Midtrans Callback] Notifikasi diterima', [
            'order_id'           => $orderId,
            'transaction_id'     => $transactionId,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
            'payment_type'       => $paymentType,
        ]);

        // Ekstrak tagihan_id dari order_id
        $parts     = explode('-', $orderId);
        $tagihanId = $parts[1] ?? null;

        if (! $tagihanId) {
            Log::error('[Midtrans Callback] Format order_id tidak dikenali', compact('orderId'));
            return response('OK', 200);
        }

        $tagihan = Tagihan::find($tagihanId);

        if (! $tagihan) {
            Log::error('[Midtrans Callback] Tagihan tidak ditemukan', compact('tagihanId'));
            return response('OK', 200);
        }

        // Resolusi status
        [$statusPembayaran, $statusTagihan] = $this->resolveStatus(
            $transactionStatus,
            $fraudStatus
        );

        $pembayaran = Pembayaran::where('tagihan_id', $tagihanId)
            ->where('status_pembayaran', 'pending')
            ->latest()
            ->first();

        if ($pembayaran) {
            $pembayaran->update([
                'metode_pembayaran' => $paymentType ?? 'online',
                'nominal_bayar'     => $grossAmount,
                'tanggal_bayar'     => $statusPembayaran === 'sukses' ? Carbon::now() : null,
                'status_pembayaran' => $statusPembayaran,
                'transaction_id'    => $transactionId, // PERBAIKAN BUG MAPPING: Gunakan $transactionId
            ]);
        } else {
            Log::warning('[Midtrans Callback] Tidak ada record pending, membuat record baru', [
                'tagihan_id' => $tagihanId,
            ]);

            Pembayaran::create([
                'tagihan_id'        => $tagihanId,
                'user_id'           => null,
                'metode_pembayaran' => $paymentType ?? 'online',
                'nominal_bayar'     => $grossAmount,
                'tanggal_bayar'     => $statusPembayaran === 'sukses' ? Carbon::now() : null,
                'status_pembayaran' => $statusPembayaran,
                'snap_token'        => null,
                'transaction_id'    => $transactionId, // PERBAIKAN BUG MAPPING: Gunakan $transactionId
            ]);
        }

        if ($statusTagihan) {
            $tagihan->update(['status_tagihan' => $statusTagihan]);

            Log::info('[Midtrans Callback] Tagihan diperbarui ke: ' . $statusTagihan, [
                'tagihan_id' => $tagihanId,
            ]);
        }

        if ($statusPembayaran === 'sukses') {
            $pembayaran->refresh();
            event(new PembayaranBerhasil($pembayaran));
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