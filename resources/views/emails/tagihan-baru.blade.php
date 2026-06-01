{{--
    resources/views/emails/tagihan-baru.blade.php

    Template email notifikasi tagihan bulanan baru.
    Di-render oleh NotifikasiTagihanBaru Mailable.
    Menggunakan layout kustom vendor/mail/html/layout.blade.php
    (sudah dikustomisasi tema Firabo dari Tahap 10).
--}}
@component('mail::message')

{{-- Salam --}}
Halo **{{ $tagihan->hunian->user->name }}**,

Tagihan sewa bulan **{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('F Y') }}**
untuk kamar **{{ $tagihan->hunian->kamar->nomor_kamar }}** telah diterbitkan.

---

{{-- Rincian Tagihan --}}
@component('mail::table')
| Keterangan | Detail |
|:---|:---|
| Periode | {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('F Y') }} |
| Kamar | {{ $tagihan->hunian->kamar->nomor_kamar }} — {{ $tagihan->hunian->kamar->tipe_kamar }} |
| Nominal | **Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}** |
| Tanggal Tagihan | {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('d F Y') }} |
| Jatuh Tempo | **{{ \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}** |
| Status | Belum Dibayar |
@endcomponent

Harap selesaikan pembayaran sebelum tanggal jatuh tempo untuk menghindari status terlambat.

{{-- CTA Button --}}
@component('mail::button', ['url' => route('penghuni.tagihan.show', $tagihan->tagihan_id)])
Bayar Sekarang
@endcomponent

Jika Anda memiliki pertanyaan, silakan hubungi pengelola kos secara langsung.

Terima kasih,
**Tim Kos Firabo**

@endcomponent