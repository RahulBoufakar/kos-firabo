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
    .section-title { font-size: 12px; font-weight: bold; color: #1A4731; margin: 18px 0 8px; }
    table.summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
    table.summary td { width: 33.33%; text-align: center; padding: 10px; border: 1px solid #d4e9dc; background: #eef7f2; }
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
        <h1>Laporan Pemasukan</h1>
        <p>Kos Firabo &mdash; {{ $labelPeriode }}</p>
    </div>

    <div class="meta">
        Dicetak: {{ $tanggalCetak->translatedFormat('d F Y, H:i') }} WIT oleh {{ $dicetakOleh }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="value">Rp {{ number_format($ringkasan['total'], 0, ',', '.') }}</span>
                <span class="label">Total Pemasukan</span>
            </td>
            <td>
                <span class="value">{{ $ringkasan['jumlah'] }}</span>
                <span class="label">Jumlah Transaksi</span>
            </td>
            <td>
                <span class="value">Rp {{ number_format($ringkasan['rata_rata'], 0, ',', '.') }}</span>
                <span class="label">Rata-rata / Transaksi</span>
            </td>
        </tr>
    </table>

    @if($mode === 'tahunan' && $breakdownBulanan)
        <div class="section-title">Rekap per Bulan</div>
        <table class="data" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th style="width:40%;">Bulan</th>
                    <th style="width:25%;" class="text-right">Jumlah Transaksi</th>
                    <th style="width:35%;" class="text-right">Total Pemasukan</th>
                </tr>
            </thead>
            <tbody>
                @for($b = 1; $b <= 12; $b++)
                    @php $row = $breakdownBulanan->get($b); @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</td>
                        <td class="text-right">{{ $row->jumlah ?? 0 }}</td>
                        <td class="text-right">Rp {{ number_format($row->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>
    @endif

    <div class="section-title">Rincian Transaksi</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:16%;">Tanggal Bayar</th>
                <th style="width:22%;">Penghuni</th>
                <th style="width:12%;">Kamar</th>
                <th style="width:16%;" class="text-right">Nominal</th>
                <th style="width:15%;">Metode</th>
                <th style="width:15%;">Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembayaran as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->tanggal_bayar)->translatedFormat('d M Y') }}</td>
                    <td>{{ $p->tagihan?->hunian?->user?->name ?? '-' }}</td>
                    <td>{{ $p->tagihan?->hunian?->kamar?->nomor_kamar ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($p->nominal_bayar, 0, ',', '.') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $p->metode_pembayaran)) }}</td>
                    <td>{{ $p->user?->name ?? 'Online' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:16px;">Tidak ada transaksi pada periode ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Kos Firabo &mdash; Laporan ini dibuat otomatis oleh sistem.
    </div>

</body>
</html>