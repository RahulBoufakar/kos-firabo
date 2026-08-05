<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
    .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #2d7a56; padding-bottom: 10px; }
    .header h1 { font-size: 16px; margin: 0 0 2px; color: #1A4731; }
    .header p { font-size: 11px; color: #6b7280; margin: 0; }
    .meta { font-size: 10px; color: #6b7280; margin-bottom: 14px; line-height: 1.5; }
    table.summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
    table.summary td { width: 50%; text-align: center; padding: 10px; border: 1px solid #d4e9dc; background: #eef7f2; }
    table.summary .value { display: block; font-size: 14px; font-weight: bold; color: #1A4731; }
    table.summary .label { display: block; font-size: 10px; color: #6b7280; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    table.data th { background: #eef7f2; color: #374151; padding: 6px 8px; border: 1px solid #d4e9dc; text-align: left; }
    table.data td { padding: 6px 8px; border: 1px solid #d4e9dc; vertical-align: top; }
    .text-right { text-align: right; }
    .footer { margin-top: 18px; font-size: 9px; color: #9ca3af; text-align: right; }
</style>
</head>
<body>

    <div class="header">
        <h1>Laporan Penghuni Kabur / Piutang Macet</h1>
        <p>Kos Firabo</p>
    </div>

    <div class="meta">
        Dicetak: {{ $tanggalCetak->translatedFormat('d F Y, H:i') }} WIT oleh {{ $dicetakOleh }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="value">Rp {{ number_format($ringkasan['total_piutang'], 0, ',', '.') }}</span>
                <span class="label">Total Piutang Macet</span>
            </td>
            <td>
                <span class="value">{{ $ringkasan['jumlah_penghuni'] }}</span>
                <span class="label">Jumlah Penghuni Kabur</span>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:28%;">Nama Penghuni</th>
                <th style="width:14%;">Kamar Terakhir</th>
                <th style="width:20%;">Tanggal Keluar</th>
                <th style="width:19%;" class="text-right">Total Piutang</th>
                <th style="width:15%;" class="text-right">Jml Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($penghuni as $i => $u)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->hunianTerakhir?->kamar?->nomor_kamar ?? '-' }}</td>
                    <td>
                        {{ $u->hunianTerakhir?->tanggal_keluar
                            ? \Carbon\Carbon::parse($u->hunianTerakhir->tanggal_keluar)->translatedFormat('d F Y')
                            : '-' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($u->totalPiutang(), 0, ',', '.') }}</td>
                    <td class="text-right">{{ $u->jumlahTagihanTertunggak() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:16px;">Tidak ada penghuni kabur saat ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Kos Firabo &mdash; Laporan ini dibuat otomatis oleh sistem.
    </div>

</body>
</html>