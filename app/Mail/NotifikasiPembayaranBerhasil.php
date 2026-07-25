<?php

namespace App\Mail;

use App\Models\Pembayaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

/**
 * NotifikasiPembayaranBerhasil
 *
 * Konfirmasi pembayaran yang dikirim ke penghuni setelah:
 * - Pembayaran online via Midtrans berhasil (settlement/capture)
 * - Admin mencatat pembayaran manual dengan status 'sukses'
 *
 * Untuk pembayaran manual oleh admin, email ini berfungsi sebagai bukti
 * dan transparansi — penghuni tahu ada pencatatan di sistem atas nama mereka.
 */
class NotifikasiPembayaranBerhasil extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pembayaran $pembayaran,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('email-tagihan')];
    }

    public function envelope(): Envelope
    {
        $penghuni = $this->pembayaran->tagihan->hunian->user;
        $nominal  = 'Rp ' . number_format($this->pembayaran->nominal_bayar, 0, ',', '.');

        return new Envelope(
            to:      [$penghuni->email],
            subject: "✅ Pembayaran Berhasil — {$nominal} | Kos Firabo",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pembayaran-berhasil',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}