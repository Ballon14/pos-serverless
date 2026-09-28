<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function recordMovement(
        Product $product,
        string $type,
        int $qty,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $keterangan = null
    ): StockMovement {
        if ($qty === 0) {
            throw new \InvalidArgumentException('Jumlah mutasi stok tidak boleh 0.');
        }
        if ($type !== 'adjustment' && $qty < 0) {
            throw new \InvalidArgumentException('Jumlah mutasi stok harus lebih dari 0.');
        }

        return DB::transaction(function () use ($product, $type, $qty, $referenceType, $referenceId, $keterangan) {
            $stokSebelum = $product->stok;

            if ($type === 'in' || $type === 'return' || $type === 'adjustment') {
                if ($type === 'adjustment') {
                    $product->update(['stok' => max(0, $stokSebelum + $qty)]);
                } else {
                    $product->increment('stok', $qty);
                }
            } elseif ($type === 'out') {
                if ($product->stok < $qty) {
                    throw new \RuntimeException(
                        "Stok {$product->name} tidak mencukupi untuk pengurangan {$qty} (tersisa {$product->stok})."
                    );
                }

                $product->decrement('stok', $qty);
            } else {
                throw new \InvalidArgumentException("Tipe mutasi stok tidak dikenal: {$type}");
            }

            $product->refresh();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'qty' => $qty,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $product->stok,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'keterangan' => $keterangan,
                'user_id' => Auth::id(),
            ]);
        });
    }

    public function getMovements(int $productId, $startDate = null, $endDate = null)
    {
        $query = StockMovement::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<', Carbon::parse($endDate)->addDay());
        }

        return $query->paginate(20);
    }
}
