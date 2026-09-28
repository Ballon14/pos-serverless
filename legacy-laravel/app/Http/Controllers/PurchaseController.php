<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\ActivityLogger;
use App\Services\PurchaseService;
use App\Services\SupplierService;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected SupplierService $supplierService,
    ) {}

    public function index()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $supplierId = request('supplier_id');
        $purchases = $this->purchaseService->getPurchases($startDate, $endDate, $supplierId);
        $suppliers = $this->supplierService->getActive();

        return view('purchases.index', compact('purchases', 'suppliers', 'startDate', 'endDate', 'supplierId'));
    }

    public function create()
    {
        return view('purchases.create');
    }

    public function store(PurchaseRequest $request)
    {
        $purchase = $this->purchaseService->createPurchase(
            $request->input('supplier_id'),
            $request->input('tanggal'),
            $request->input('items'),
            $request->input('keterangan'),
            $request->file('foto_nota')
        );

        app(ActivityLogger::class)->log('purchase.create', 'Pembelian '.$purchase->invoice_number.' dicatat (Total: Rp '.number_format($purchase->total, 0, ',', '.').').');

        return redirect()->route('purchases.index')->with('success', 'Pembelian berhasil dicatat.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }
}
