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
    table.summary td { width: 33.33%; text-align: center; padding: 10px; border: 1px solid #d4e9dc; background: #eef7f2; }
    table.summary .value { display: block; font-size: 14px; font-weight: bold; color: #1A4731; }
    table.summary .label { display: block; font-size: 10px; color: #6b7280; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    table.data th { background: #eef7f2; color: #374151; padding: 6px 8px; border: 1px solid #d4e9dc; text-align: left; }
    table.data td { padding: 6px 8px; border: 1px solid #d4e9dc; vertical-align: top; }
    .text-right { text-align: right; }
    .status-belum { color: #92400e; font-weight: bold; }
    .status-terlambat { color: #991b1b; font-weight: bold; }
    .footer { margin-top: 18px; font-size: 9px; color: #9ca3af; text-align: right; }
</style>
</head>
<body>

    <div class="header">
        <h1>Laporan Tagihan Belum Dibayar</h1>
        <p>Kos Firabo</p>
    </div>

    <div class="meta">
        Dicetak: {{ $tanggalCetak->translatedFormat('d F Y, H:i') }} WIB oleh {{ $dicetakOleh }}<br>
        Filter status: {{ $filterStatus === 'belum_bayar' ? 'Belum Bayar' : ($filterStatus === 'terlambat' ? 'Terlambat' : 'Semua') }}
        &mdash; hanya penghuni aktif (tidak termasuk penghuni berstatus kabur)
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="value">Rp {{ number_format($ringkasan['total_nominal'], 0, ',', '.') }}</span>
                <span class="label">Total Belum Tertagih</span>
            </td>
            <td>
                <span class="value">{{ $ringkasan['jumlah_belum'] }}</span>
                <span class="label">Belum Bayar</span>
            </td>
            <td>
                <span class="value">{{ $ringkasan['jumlah_telat'] }}</span>
                <span class="label">Terlambat</span>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:26%;">Penghuni</th>
                <th style="width:12%;">Kamar</th>
                <th style="width:18%;" class="text-right">Nominal</th>
                <th style="width:20%;">Jatuh Tempo</th>
                <th style="width:20%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tagihan as $i => $t)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->hunian?->user?->name ?? '-' }}</td>
                    <td>{{ $t->hunian?->kamar?->nomor_kamar ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}</td>
                    <td class="{{ $t->status_tagihan === 'terlambat' ? 'status-terlambat' : 'status-belum' }}">
                        {{ $t->status_tagihan === 'terlambat' ? 'Terlambat' : 'Belum Bayar' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:16px;">Semua tagihan sudah lunas</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Kos Firabo &mdash; Laporan ini dibuat otomatis oleh sistem.
    </div>

</body>
</html>