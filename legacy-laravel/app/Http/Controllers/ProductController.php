<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StockService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected CategoryService $categoryService,
        protected StockService $stockService,
    ) {}

    public function index()
    {
        $search = request('search');
        $categoryId = request('category_id');
        $products = $this->productService->getAll($search, $categoryId);
        $categories = $this->categoryService->getActive();

        return view('products.index', compact('products', 'categories', 'search', 'categoryId'));
    }

    public function create()
    {
        $categories = $this->categoryService->getActive();

        return view('products.create', compact('categories'));
    }

    public function store(ProductRequest $request)
    {
        $product = $this->productService->store(
            $request->validated(),
            $request->file('foto')
        );
        app(ActivityLogger::class)->log('product.create', 'Produk "'.$product->name.'" (SKU: '.$product->sku.') ditambahkan.');

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load('category');
        $stockMovements = $this->stockService->getMovements($product->id);

        return view('products.show', compact('product', 'stockMovements'));
    }

    public function edit(Product $product)
    {
        $categories = $this->categoryService->getActive();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->productService->update(
            $product,
            $request->validated(),
            $request->file('foto')
        );
        app(ActivityLogger::class)->log('product.update', 'Produk "'.$product->name.'" (SKU: '.$product->sku.') diperbarui.');

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        try {
            $this->productService->delete($product);
        } catch (\RuntimeException $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
        app(ActivityLogger::class)->log('product.delete', 'Produk "'.$product->name.'" (SKU: '.$product->sku.') dihapus.');

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function generateSku(Category $category)
    {
        $sku = $this->productService->generateSku($category->id);

        return response()->json(['sku' => $sku]);
    }

    public function export(\App\Services\ProductExportService $exportService)
    {
        app(ActivityLogger::class)->log('product.export', 'Mengekspor data produk ke Excel.');
        $exportService->exportToBrowser('data_produk_' . date('Ymd_His') . '.xlsx');
    }

    public function import(\App\Http\Requests\ImportProductRequest $request, \App\Services\ProductImportService $importService)
    {
        try {
            $result = $importService->import($request->file('file'));
            
            app(ActivityLogger::class)->log('product.import', "Mengimpor data produk (Baru: {$result['imported']}, Update: {$result['updated']}, Gagal: {$result['failed']}).");
            
            return redirect()->route('products.index')->with('success', "Berhasil import produk. Baru: {$result['imported']}, Diupdate: {$result['updated']}, Gagal: {$result['failed']}.");
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }

    public function downloadTemplate(\Illuminate\Http\Request $request)
    {
        $headers = [
            'Kategori (Wajib)',
            'SKU (Kosongkan untuk otomatis)',
            'Nama Produk',
            'Harga Beli',
            'Harga Jual',
            'Grosir Tiers (JSON)',
            'Stok',
            'Min Stok',
            'Satuan',
            'Deskripsi',
            'Aktif (1/0)'
        ];

        $categoryName = 'Makanan';
        if ($request->has('category_id') && $request->category_id != '') {
            $cat = \App\Models\Category::find($request->category_id);
            if ($cat) {
                $categoryName = $cat->name;
            }
        }

        $options = new \OpenSpout\Writer\CSV\Options();
        $writer = new \OpenSpout\Writer\CSV\Writer($options);
        $fileName = 'template_import_produk_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $categoryName)) . '.csv';
        
        $writer->openToBrowser($fileName);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));
        
        // Provide 1 sample row with formatting
        $exampleRow = [
            $categoryName,
            '',
            'Contoh Produk 1',
            '2500',
            '3000',
            '[{"minimal_grosir":10,"harga_grosir":2900},{"minimal_grosir":40,"harga_grosir":2800}]',
            '100',
            '10',
            'pcs',
            'Deskripsi singkat',
            '1'
        ];
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($exampleRow));
        
        // Provide 5 empty rows pre-filled with the category
        for ($i = 0; $i < 5; $i++) {
            $emptyRow = [
                $categoryName,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '1'
            ];
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($emptyRow));
        }
        
        $writer->close();
        exit;
    }
}
