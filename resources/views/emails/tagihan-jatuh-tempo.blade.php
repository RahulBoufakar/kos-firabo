{{--
    resources/views/emails/tagihan-jatuh-tempo.blade.php

    Template email reminder H-1 sebelum jatuh tempo.
    Nada lebih urgen dari tagihan-baru — ada kata "besok" yang jelas.
--}}
@component('mail::message')

{{-- Salam --}}
Halo **{{ $tagihan->hunian->user->name }}**,

Ini adalah pengingat bahwa tagihan sewa Anda akan **jatuh tempo besok**.
Segera lakukan pembayaran agar tidak terkena status terlambat.

---

{{-- Rincian Tagihan --}}
@component('mail::table')
| Keterangan | Detail |
|:---|:---|
| Periode | {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('F Y') }} |
| Kamar | {{ $tagihan->hunian->kamar->nomor_kamar }} — {{ $tagihan->hunian->kamar->tipe_kamar }} |
| Nominal | **Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}** |
| Jatuh Tempo | ⚠️ **{{ \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}** |
| Status | Belum Dibayar |
@endcomponent

{{-- CTA Button --}}
@component('mail::button', ['url' => route('penghuni.tagihan.show', $tagihan->tagihan_id)])
Bayar Sekarang
@endcomponent

Jika Anda sudah melakukan pembayaran, abaikan email ini — status akan
diperbarui otomatis oleh sistem.

Terima kasih,
**Tim Kos Firabo**

@endcomponent