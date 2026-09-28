<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f4f4f4; text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-box { margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .summary-grid { width: 100%; }
        .summary-grid td { border: none; padding: 5px 10px; }
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
            
            <div class="title" style="font-size: 16px; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 10px;">LAPORAN PENJUALAN</div>
            <div class="subtitle">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
            @if(!empty($cashierName))
            <div class="subtitle">Kasir: {{ $cashierName }}</div>
            @endif
        </div>
    </div>

    <div class="summary-box">
        <table class="summary-grid">
            <tr>
                <td><strong>Total Transaksi Selesai:</strong><br>{{ $data['summary']['total_transactions'] }}</td>
                <td><strong>Total Pendapatan:</strong><br>Rp {{ number_format($data['summary']['total_revenue'], 0, ',', '.') }}</td>
                <td><strong>Total Item Terjual:</strong><br>{{ $data['summary']['total_items_sold'] }}</td>
            </tr>
        </table>
    </div>

    <h3>Rincian Penjualan per Produk</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode/SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-center">Qty Terjual</th>
                <th class="text-right">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['items'] as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->category_name }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL KESELURUHAN</th>
                <th class="text-center">{{ $data['summary']['total_items_sold'] }}</th>
                <th class="text-right">Rp {{ number_format($data['summary']['total_revenue'], 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <p>Oleh: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
