<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\StockService;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index()
    {
        $productId = request('product_id');
        $startDate = request('start_date');
        $endDate = request('end_date');

        $movements = null;
        $product = null;

        if ($productId) {
            $product = Product::with('category')->findOrFail($productId);
            $movements = $this->stockService->getMovements($productId, $startDate, $endDate);
        }

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock.index', compact('movements', 'product', 'products', 'productId', 'startDate', 'endDate'));
    }

    public function lowStock()
    {
        $products = Product::whereColumn('stok', '<=', 'min_stok')
            ->where('is_active', true)
            ->with('category')
            ->paginate(20);

        return view('stock.low-stock', compact('products'));
    }

    public function adjust()
    {
        $data = request()->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = (int) $data['qty'];

        if ($qty === 0) {
            return back()->with('error', 'Jumlah penyesuaian tidak boleh 0.');
        }

        if ($qty < 0 && $product->stok + $qty < 0) {
            return back()->with('error', "Stok {$product->name} tidak mencukupi untuk pengurangan {$qty} (tersisa {$product->stok}).");
        }

        $this->stockService->recordMovement(
            $product,
            'adjustment',
            $qty,
            keterangan: $data['keterangan'] ?? null
        );

        app(ActivityLogger::class)->log(
            'stock.adjust',
            'Stok "'.$product->name.'" disesuaikan '.($qty >= 0 ? '+' : '').$qty.' (menjadi '.($product->fresh()->stok).').'
        );

        return back()->with('success', 'Stok "'.$product->name.'" berhasil disesuaikan.');
    }
}
