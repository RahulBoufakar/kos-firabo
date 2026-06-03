{{--
    resources/views/emails/pembayaran-berhasil.blade.php

    Template email konfirmasi pembayaran berhasil.
    Dikirim untuk dua skenario:
    1. Pembayaran online via Midtrans (user_id = null, metode = gopay/qris/dll)
    2. Pembayaran dicatat manual oleh admin (user_id = id admin, metode = manual/cash)

    Untuk pembayaran manual, ada catatan tambahan agar penghuni
    tahu bahwa admin yang mencatat transaksi ini.
--}}
@component('mail::message')

{{-- Salam --}}
Halo **{{ $pembayaran->tagihan->hunian->user->name }}**,

@if($pembayaran->user_id)
{{-- Pembayaran manual oleh admin --}}
Pembayaran tagihan sewa Anda telah **dicatat oleh admin** Kos Firabo.
Email ini dikirim sebagai bukti dan transparansi pencatatan.
@else
{{-- Pembayaran online via Midtrans --}}
Pembayaran tagihan sewa Anda telah **berhasil diproses**. Terima kasih!
@endif

---

{{-- Rincian Pembayaran --}}
@component('mail::table')
| Keterangan | Detail |
|:---|:---|
| Periode | {{ \Carbon\Carbon::parse($pembayaran->tagihan->tanggal_tagihan)->translatedFormat('F Y') }} |
| Kamar | {{ $pembayaran->tagihan->hunian->kamar->nomor_kamar }} — {{ $pembayaran->tagihan->hunian->kamar->tipe_kamar }} |
| Nominal Dibayar | **Rp {{ number_format($pembayaran->nominal_bayar, 0, ',', '.') }}** |
| Metode Pembayaran | {{ ucwords(str_replace('_', ' ', $pembayaran->metode_pembayaran)) }} |
| Tanggal Bayar | {{ $pembayaran->tanggal_bayar ? \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->translatedFormat('d F Y, H:i') : \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} |
| Status | ✅ Lunas |
@if($pembayaran->user_id && $pembayaran->pencatat)
| Dicatat Oleh | {{ $pembayaran->pencatat->name }} (Admin) |
@endif
@endcomponent

{{-- CTA Button --}}
@component('mail::button', ['url' => route('penghuni.tagihan.show', $pembayaran->tagihan->tagihan_id)])
Lihat Detail Tagihan
@endcomponent

@if($pembayaran->user_id)
Jika Anda merasa pencatatan ini tidak sesuai, segera hubungi pengelola kos.
@else
Simpan email ini sebagai bukti pembayaran Anda.
@endif

Terima kasih,
**Tim Kos Firabo**

@endcomponent