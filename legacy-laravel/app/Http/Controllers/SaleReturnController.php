<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index()
    {
        $returns = SaleReturn::with(['sale', 'processedBy'])
            ->latest()
            ->paginate(15);

        return view('sale-returns.index', compact('returns'));
    }

    public function create(Sale $sale)
    {
        $sale->load('items.product');

        return view('sale-returns.create', compact('sale'));
    }

    public function store(Request $request, Sale $sale)
    {
        $request->validate([
            'alasan' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $this->saleService->processReturn($sale, $request->input('items'), $request->input('alasan'));

        return redirect()->route('sale-returns.index')->with('success', 'Retur berhasil diproses.');
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.items.product', 'items.product', 'processedBy']);

        return view('sale-returns.show', compact('saleReturn'));
    }
}
