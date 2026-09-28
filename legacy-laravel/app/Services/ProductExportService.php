<?php

namespace App\Services;

use App\Models\Product;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use Illuminate\Support\Collection;

class ProductExportService
{
    public function exportToBrowser(string $fileName = 'data_produk.xlsx')
    {
        $options = new Options();
        $writer = new Writer($options);
        
        $writer->openToBrowser($fileName);

        // Header Row
        $headerRow = Row::fromValues([
            'Kategori',
            'SKU',
            'Nama Produk',
            'Harga Beli',
            'Harga Jual',
            'Grosir Tiers (JSON)',
            'Stok',
            'Min Stok',
            'Satuan',
            'Deskripsi',
            'Status Aktif (1=Aktif, 0=Tidak)'
        ]);
        $writer->addRow($headerRow);

        // Data Rows
        Product::with('category')->chunk(100, function (Collection $products) use ($writer) {
            foreach ($products as $product) {
                $row = Row::fromValues([
                    $product->category ? $product->category->name : '',
                    $product->sku,
                    $product->name,
                    $product->harga_beli,
                    $product->harga_jual,
                    json_encode($product->grosir_tiers ?: []),
                    $product->stok,
                    $product->min_stok,
                    $product->satuan,
                    $product->deskripsi,
                    $product->is_active ? 1 : 0,
                ]);
                $writer->addRow($row);
            }
        });

        $writer->close();
        exit; // Terminate execution after streaming
    }
}
