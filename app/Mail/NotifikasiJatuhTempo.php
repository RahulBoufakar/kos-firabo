<?php

namespace App\Mail;

use App\Models\Tagihan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * NotifikasiJatuhTempo
 *
 * Reminder H-1 yang dikirim ke penghuni sehari sebelum jatuh tempo.
 * Nada lebih mendesak dari TagihanBaru — ada kata "besok" dan CTA besar.
 */
class NotifikasiJatuhTempo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tagihan $tagihan,
    ) {}

    public function envelope(): Envelope
    {
        $penghuni = $this->tagihan->hunian->user;
        $jt       = \Carbon\Carbon::parse($this->tagihan->tanggal_jatuh_tempo)
                        ->translatedFormat('d F Y');

        return new Envelope(
            to:      [$penghuni->email],
            subject: "⏰ Pengingat — Tagihan Jatuh Tempo Besok ({$jt}) | Kos Firabo",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tagihan-jatuh-tempo',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}