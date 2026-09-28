<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected PriceChangeService $priceChangeService,
    ) {}
    public function getAll($search = null, $categoryId = null)
    {
        $query = Product::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->latest()->paginate(15);
    }

    public function store(array $data, ?UploadedFile $foto = null): Product
    {
        if ($foto) {
            $data['foto'] = $foto->store('products', 'public');
        }

        // Auto-generate SKU if empty
        if (empty($data['sku']) && !empty($data['category_id'])) {
            $data['sku'] = $this->generateSku($data['category_id']);
        }

        return Product::create($data);
    }

    public function update(Product $product, array $data, ?UploadedFile $foto = null): Product
    {
        if ($foto) {
            // Delete old foto
            if ($product->foto) {
                Storage::disk('public')->delete($product->foto);
            }
            $data['foto'] = $foto->store('products', 'public');
        }

        // Detect harga_beli change before updating
        $oldHargaBeli = (float) $product->harga_beli;

        $product->update($data);

        // Log price change if harga_beli actually changed
        if (isset($data['harga_beli'])) {
            $newHargaBeli = (float) $data['harga_beli'];
            $this->priceChangeService->record($product, $oldHargaBeli, $newHargaBeli, 'manual_edit');
        }

        return $product;
    }

    public function delete(Product $product): bool
    {
        if ($product->saleItems()->exists() || $product->purchaseItems()->exists() || $product->stockMovements()->exists()) {
            throw new \RuntimeException('Produk tidak dapat dihapus karena memiliki riwayat transaksi.');
        }

        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        return $product->delete();
    }

    public function getLowStock()
    {
        return Product::whereColumn('stok', '<=', 'min_stok')
            ->where('is_active', true)
            ->with('category')
            ->get();
    }

    public function searchForPos(string $search)
    {
        return Product::where('is_active', true)
            ->where('stok', '>', 0)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();
    }

    /**
     * Generate SKU otomatis dari kategori.
     * Format: PREFIX-0001 (3 huruf dari nama kategori + nomor urut)
     */
    public function generateSku(int $categoryId): string
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return 'PRD-0001';
        }

        $prefix = $this->getCategoryPrefix($category->name);

        // Cari nomor urut tertinggi yang sudah ada dengan prefix ini
        $lastSku = Product::where('sku', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(sku, '-', -1) AS UNSIGNED) DESC")
            ->value('sku');

        if ($lastSku) {
            $lastNumber = (int) substr($lastSku, strrpos($lastSku, '-') + 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat prefix 3 huruf dari nama kategori.
     */
    private function getCategoryPrefix(string $name): string
    {
        // Hapus karakter non-huruf, ambil 3 huruf pertama, uppercase
        $clean = preg_replace('/[^a-zA-Z]/', '', $name);
        $prefix = strtoupper(substr($clean, 0, 3));

        // Fallback jika kurang dari 3 huruf
        return str_pad($prefix, 3, 'X');
    }
}
