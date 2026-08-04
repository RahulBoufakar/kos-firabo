<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2d7a56; padding-bottom: 12px; }
    .header h1 { font-size: 16px; margin: 0 0 2px; color: #1A4731; }
    .header p { font-size: 11px; color: #6b7280; margin: 0; }
    .status-badge {
        display: inline-block; margin-top: 10px; padding: 5px 14px;
        border-radius: 20px; font-size: 11px; font-weight: bold;
        background: #dcfce7; color: #166534;
    }
    .nominal { font-size: 20px; font-weight: bold; color: #1A4731; text-align: center; margin: 20px 0; }
    table.data { width: 100%; border-collapse: collapse; font-size: 11.5px; margin-top: 10px; }
    table.data td { padding: 8px 10px; border: 1px solid #d4e9dc; }
    table.data td.label { width: 35%; color: #6b7280; background: #eef7f2; }
    .footer { margin-top: 28px; font-size: 9px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>

    <div class="header">
        <h1>Bukti Pembayaran</h1>
        <p>Kos Firabo</p>
        <div class="status-badge">LUNAS</div>
    </div>

    <div class="nominal">
        Rp {{ number_format($pembayaran->nominal_bayar, 0, ',', '.') }}
    </div>

    <table class="data">
        <tr>
            <td class="label">Nomor Tagihan</td>
            <td>#INV-{{ $pembayaran->tagihan_id }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penghuni</td>
            <td>{{ $pembayaran->tagihan->hunian->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kamar</td>
            <td>{{ $pembayaran->tagihan->hunian->kamar->nomor_kamar ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td>{{ \Carbon\Carbon::parse($pembayaran->tagihan->tanggal_tagihan)->translatedFormat('F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td style="text-transform:capitalize;">
                {{ str_replace('_', ' ', $pembayaran->metode_pembayaran) }}
            </td>
        </tr>
        <tr>
            <td class="label">ID Transaksi</td>
            <td>{{ $pembayaran->transaction_id ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Waktu Lunas</td>
            <td>
                {{ $pembayaran->tanggal_bayar
                    ? \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->translatedFormat('d F Y, H:i')
                    : '-' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &mdash; Kos Firabo.
        Dokumen ini dibuat otomatis oleh sistem.
    </div>

</body>
</html>