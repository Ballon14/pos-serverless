<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Common\Exception\SpoutException;
use RuntimeException;

class ProductImportService
{
    public function __construct(protected ProductService $productService)
    {
    }

    public function import(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        if ($extension === 'csv') {
            $reader = new CsvReader();
        } elseif ($extension === 'xlsx') {
            $reader = new XlsxReader();
        } else {
            throw new RuntimeException("Format file tidak didukung. Gunakan .xlsx atau .csv");
        }

        try {
            $reader->open($filePath);
        } catch (SpoutException $e) {
            throw new RuntimeException("Gagal membaca file: " . $e->getMessage());
        }

        $imported = 0;
        $updated = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    // Skip header (row 1)
                    if ($rowIndex === 1) {
                        continue;
                    }

                    $cells = $row->toArray();

                    // Minimum required columns: Kategori(0), Nama Produk(2), Harga Jual(4)
                    if (empty($cells[2])) {
                        $failed++;
                        continue;
                    }

                    $categoryName = trim($cells[0] ?? '');
                    $sku = trim($cells[1] ?? '');
                    $name = trim($cells[2] ?? '');
                    $hargaBeli = floatval(str_replace(',', '', $cells[3] ?? 0));
                    $hargaJual = floatval(str_replace(',', '', $cells[4] ?? 0));
                    $grosirTiersStr = trim($cells[5] ?? '');
                    $grosirTiers = json_decode($grosirTiersStr, true) ?? [];
                    $stok = (int) ($cells[6] ?? 0);
                    $minStok = (int) ($cells[7] ?? 5);
                    $satuan = trim($cells[8] ?? 'pcs');
                    $deskripsi = trim($cells[9] ?? '');
                    $isActive = isset($cells[10]) ? (bool) $cells[10] : true;

                    // 1. Resolve Category
                    $category = null;
                    if ($categoryName) {
                        $category = Category::firstOrCreate(
                            ['name' => $categoryName],
                            ['is_active' => true]
                        );
                    }
                    $categoryId = $category ? $category->id : null;

                    // 2. Resolve SKU
                    if (empty($sku)) {
                        // Generate SKU if empty and we have a category
                        if ($categoryId) {
                            $sku = $this->productService->generateSku($categoryId);
                        } else {
                            $sku = 'PRD-' . strtoupper(substr(uniqid(), -6));
                        }
                    }

                    // 3. Insert or Update Product
                    $product = Product::where('sku', $sku)->first();

                    if ($product) {
                        // Update
                        $product->update([
                            'category_id' => $categoryId,
                            'name' => $name,
                            'harga_beli' => $hargaBeli,
                            'harga_jual' => $hargaJual,
                            'grosir_tiers' => $grosirTiers,
                            'stok' => $stok,
                            'min_stok' => $minStok,
                            'satuan' => $satuan,
                            'deskripsi' => $deskripsi,
                            'is_active' => $isActive,
                        ]);
                        $updated++;
                    } else {
                        // Insert
                        Product::create([
                            'category_id' => $categoryId,
                            'sku' => $sku,
                            'name' => $name,
                            'harga_beli' => $hargaBeli,
                            'harga_jual' => $hargaJual,
                            'grosir_tiers' => $grosirTiers,
                            'stok' => $stok,
                            'min_stok' => $minStok,
                            'satuan' => $satuan,
                            'deskripsi' => $deskripsi,
                            'is_active' => $isActive,
                        ]);
                        $imported++;
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $reader->close();
            throw new RuntimeException("Gagal import data pada baris {$rowIndex}: " . $e->getMessage());
        }

        $reader->close();

        return [
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
        ];
    }
}
