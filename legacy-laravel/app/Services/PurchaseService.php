<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService,
        protected PriceChangeService $priceChangeService,
    ) {}

    public function createPurchase(int $supplierId, string $tanggal, array $items, ?string $keterangan = null, $fotoNota = null): Purchase
    {
        return DB::transaction(function () use ($supplierId, $tanggal, $items, $keterangan, $fotoNota) {
            $total = 0;

            foreach ($items as $key => $item) {
                $items[$key]['subtotal'] = $item['harga'] * $item['qty'];
                $total += $items[$key]['subtotal'];
            }

            // Store foto nota if uploaded
            $fotoNotaPath = null;
            if ($fotoNota) {
                $fotoNotaPath = $fotoNota->store('purchases/nota', 'public');
            }

            $purchase = Purchase::create([
                'invoice_number' => Purchase::generateInvoiceNumber(),
                'supplier_id' => $supplierId,
                'user_id' => Auth::id(),
                'tanggal' => $tanggal,
                'total' => $total,
                'status' => 'received',
                'keterangan' => $keterangan,
                'foto_nota' => $fotoNotaPath,
            ]);

            foreach ($items as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Tambah stok
                $product = Product::query()->lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    throw new \RuntimeException(
                        "Produk tidak ditemukan (ID: {$item['product_id']})."
                    );
                }

                if (! empty($item['update_harga_beli'])) {
                    $oldPrice = (float) $product->harga_beli;
                    $newPrice = (float) $item['harga'];
                    $product->update(['harga_beli' => $newPrice]);
                    $this->priceChangeService->record($product, $oldPrice, $newPrice, 'purchase', $purchase);
                }
                $this->stockService->recordMovement(
                    $product,
                    'in',
                    $item['qty'],
                    Purchase::class,
                    $purchase->id,
                    'Pembelian '.$purchase->invoice_number
                );
            }

            return $purchase;
        });
    }

    public function getPurchases($startDate = null, $endDate = null, $supplierId = null)
    {
        $query = Purchase::with(['supplier', 'user', 'items.product'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->paginate(15);
    }
}
