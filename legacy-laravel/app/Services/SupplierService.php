<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function getAll($search = null)
    {
        $query = Supplier::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(15);
    }

    public function store(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    public function delete(Supplier $supplier): bool
    {
        if ($supplier->purchases()->exists()) {
            throw new \RuntimeException('Supplier tidak dapat dihapus karena memiliki riwayat pembelian.');
        }

        return $supplier->delete();
    }

    public function getActive()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }
}
