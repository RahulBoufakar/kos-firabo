<?php

namespace App\Mail;

use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

/**
 * NotifikasiTagihanBaru
 *
 * Email yang dikirim ke penghuni saat tagihan bulanan baru di-generate.
 * Berisi: nominal, periode, tanggal jatuh tempo, dan link bayar.
 */
class NotifikasiTagihanBaru extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tagihan $tagihan,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('email-tagihan')];
    }

    public function envelope(): Envelope
    {
        $penghuni = $this->tagihan->hunian->user;
        $periode  = \Carbon\Carbon::parse($this->tagihan->tanggal_tagihan)
                        ->translatedFormat('F Y');

        return new Envelope(
            to:      [$penghuni->email],
            subject: "Tagihan Sewa Baru — {$periode} | Kos Firabo",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tagihan-baru',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}