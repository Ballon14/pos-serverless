<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\SupplierService;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index()
    {
        $search = request('search');
        $suppliers = $this->supplierService->getAll($search);

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(SupplierRequest $request)
    {
        $supplier = $this->supplierService->store($request->validated());
        app(ActivityLogger::class)->log('supplier.create', 'Supplier "'.$supplier->name.'" ('.$supplier->code.') ditambahkan.');

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $this->supplierService->update($supplier, $request->validated());
        app(ActivityLogger::class)->log('supplier.update', 'Supplier "'.$supplier->name.'" diperbarui.');

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $this->supplierService->delete($supplier);
        } catch (\RuntimeException $e) {
            return redirect()->route('suppliers.index')->with('error', $e->getMessage());
        }
        app(ActivityLogger::class)->log('supplier.delete', 'Supplier "'.$supplier->name.'" dihapus.');

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
