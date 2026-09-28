<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { color: #555; }
        .section { margin-bottom: 30px; }
        .section-title { font-weight: bold; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; }
        .row { display: table; width: 100%; margin-bottom: 10px; }
        .col-label { display: table-cell; width: 70%; }
        .col-value { display: table-cell; width: 30%; text-align: right; }
        .subtotal { font-weight: bold; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 5px; }
        .text-red { color: #d32f2f; }
        .grand-total { margin-top: 30px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .grand-total-row { display: table; width: 100%; font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header" style="position: relative; padding-top: 10px; min-height: 80px;">
        @if(file_exists(public_path('logo.png')))
            <img src="{{ public_path('logo.png') }}" alt="Logo" style="position: absolute; left: 0; top: 10px; height: 70px; width: 70px; object-fit: contain; filter: grayscale(100%);">
        @endif
        
        <div style="text-align: center;">
            <div class="title" style="font-size: 22px; font-weight: bold; margin-bottom: 3px;">TOKO MBA EMI</div>
            <div class="subtitle" style="font-size: 11px; margin-bottom: 2px;">Jrakah, Kecamatan Bayan, Purworejo</div>
            <div class="subtitle" style="font-size: 11px; margin-bottom: 15px;">Telp/WA: +62 852-2824-8031</div>
            
            <div class="title" style="font-size: 16px; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 10px;">LAPORAN LABA RUGI</div>
            <div class="subtitle">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Pendapatan</div>
        <div class="row">
            <div class="col-label">Penjualan Kotor</div>
            <div class="col-value">Rp {{ number_format($data['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="row">
            <div class="col-label">Diskon Penjualan</div>
            <div class="col-value text-red">(Rp {{ number_format($data['discounts'], 0, ',', '.') }})</div>
        </div>
        <div class="row subtotal">
            <div class="col-label">Penjualan Bersih</div>
            <div class="col-value">Rp {{ number_format($data['net_revenue'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Harga Pokok Penjualan (HPP)</div>
        <div class="row">
            <div class="col-label">Total HPP Barang Terjual</div>
            <div class="col-value">Rp {{ number_format($data['cogs'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grand-total">
        <div class="grand-total-row">
            <div class="col-label">LABA KOTOR</div>
            <div class="col-value">Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}</div>
        </div>
        <div class="row" style="margin-top: 10px; font-size: 12px; font-weight: normal;">
            <div class="col-label">Margin Laba Kotor:</div>
            <div class="col-value">{{ $data['net_revenue'] > 0 ? round(($data['gross_profit'] / $data['net_revenue']) * 100, 2) : 0 }}%</div>
        </div>
    </div>

    <div style="margin-top: 50px; text-align: right; font-size: 12px;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <p>Oleh: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
