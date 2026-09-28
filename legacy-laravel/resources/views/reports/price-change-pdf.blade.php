<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Perubahan Harga Beli</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; }
        th { background-color: #f4f4f4; text-align: left; font-size: 11px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-box { margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .summary-grid { width: 100%; }
        .summary-grid td { border: none; padding: 5px 10px; }
        .badge-naik { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-turun { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .text-naik { color: #059669; font-weight: bold; }
        .text-turun { color: #dc2626; font-weight: bold; }
        .line-through { text-decoration: line-through; color: #999; }
        .alert-box { margin-bottom: 20px; background: #fffbeb; padding: 12px 15px; border-radius: 5px; border: 1px solid #fbbf24; }
        .alert-title { font-weight: bold; color: #92400e; margin-bottom: 8px; font-size: 13px; }
        .alert-text { color: #78350f; font-size: 11px; margin-bottom: 8px; }
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
            
            <div class="title" style="font-size: 16px; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 10px;">REKAP PERUBAHAN HARGA BELI</div>
            <div class="subtitle">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="summary-box">
        <table class="summary-grid">
            <tr>
                <td><strong>Total Perubahan Harga:</strong><br>{{ $data['summary']['total_changes'] }}</td>
                <td><strong>Harga Naik:</strong><br><span class="text-naik">{{ $data['summary']['total_naik'] }}</span></td>
                <td><strong>Harga Turun:</strong><br><span class="text-turun">{{ $data['summary']['total_turun'] }}</span></td>
                <td><strong>Produk Terpengaruh:</strong><br>{{ $data['summary']['products_affected'] }}</td>
            </tr>
        </table>
    </div>

    @if($data['current_vs_last_bought']->isNotEmpty())
    <div class="alert-box">
        <div class="alert-title">⚠ Harga Aktual (Restock) Berbeda dengan Master Data</div>
        <div class="alert-text">Produk berikut baru saja dibeli dengan harga yang berbeda dari harga standar (Master Data) di sistem.</div>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kode/SKU</th>
                    <th class="text-right">Harga Master (Sistem)</th>
                    <th class="text-right">Harga Aktual (Restock)</th>
                    <th class="text-right">Selisih</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['current_vs_last_bought'] as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_beli_sekarang, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($item->harga_terakhir_dibeli, 0, ',', '.') }}</td>
                    <td class="text-right {{ $item->tipe === 'naik' ? 'text-naik' : 'text-turun' }}">
                        {{ $item->tipe === 'naik' ? '+' : '' }}Rp {{ number_format($item->selisih, 0, ',', '.') }} ({{ $item->tipe === 'naik' ? '+' : '' }}{{ $item->persen }}%)
                    </td>
                    <td class="text-center">
                        <span class="{{ $item->tipe === 'naik' ? 'badge-naik' : 'badge-turun' }}">
                            {{ $item->tipe === 'naik' ? '↑ Naik' : '↓ Turun' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <h3>Riwayat Perubahan Harga Beli</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Kategori</th>
                <th class="text-right">Harga Beli Lama</th>
                <th class="text-right">Harga Beli Baru</th>
                <th class="text-right">Selisih</th>
                <th class="text-center">Status</th>
                <th class="text-center">Sumber</th>
                <th>Invoice/Ref</th>
                <th>Pencatat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['changes'] as $i => $change)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td style="white-space: nowrap;">{{ $change->tanggal->format('d/m/Y') }}</td>
                <td>
                    {{ $change->product_name }}
                    <br><span style="font-size: 10px; color: #999;">{{ $change->product_sku }}</span>
                </td>
                <td>{{ $change->category_name }}</td>
                <td class="text-right line-through">Rp {{ number_format($change->harga_lama, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold;">Rp {{ number_format($change->harga_baru, 0, ',', '.') }}</td>
                <td class="text-right {{ $change->tipe === 'naik' ? 'text-naik' : 'text-turun' }}">
                    {{ $change->tipe === 'naik' ? '+' : '' }}Rp {{ number_format($change->selisih, 0, ',', '.') }}
                    <br><span style="font-size: 10px;">({{ $change->tipe === 'naik' ? '+' : '' }}{{ $change->persen }}%)</span>
                </td>
                <td class="text-center">
                    <span class="{{ $change->tipe === 'naik' ? 'badge-naik' : 'badge-turun' }}">
                        {{ $change->tipe === 'naik' ? '↑ Naik' : '↓ Turun' }}
                    </span>
                </td>
                <td class="text-center" style="font-size: 10px;">{{ $change->sumber }}</td>
                <td style="font-size: 10px; white-space: nowrap;">{{ $change->invoice_perubahan }}</td>
                <td>{{ $change->pencatat }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 20px; color: #999;">Tidak ada perubahan harga pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <p>Oleh: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
