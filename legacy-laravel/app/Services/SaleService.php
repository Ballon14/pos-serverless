<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function createSale(array $items, float $diskon, float $bayar, ?string $catatan = null, string $paymentMethod = 'cash'): Sale
    {
        if (! in_array($paymentMethod, ['cash', 'qris'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak valid.']);
        }

        $sale = DB::transaction(function () use ($items, $diskon, $bayar, $catatan, $paymentMethod) {
            if (empty($items)) {
                throw ValidationException::withMessages(['items' => 'Keranjang belanja kosong.']);
            }

            $subtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::with('category')->lockForUpdate()->find($item['product_id']);

                if (! $product || ! $product->is_active || ! $product->category?->is_active) {
                    throw ValidationException::withMessages(['items' => 'Produk tidak ditemukan atau tidak aktif.']);
                }

                $qty = max(1, (int) $item['qty']);

                if ($product->stok < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi (tersisa {$product->stok}).",
                    ]);
                }

                $itemDiskon = max(0, (float) ($item['diskon'] ?? 0));
                $itemSubtotal = max(0, ($product->harga_jual * $qty) - $itemDiskon);

                $preparedItems[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'diskon' => $itemDiskon,
                    'harga' => (float) $product->harga_jual,
                    'subtotal' => $itemSubtotal,
                ];

                $subtotal += $itemSubtotal;
            }

            $diskon = max(0, $diskon);
            $diskon = min($diskon, $subtotal);
            $grandTotal = $subtotal - $diskon;
            $bayar = max(0, (float) $bayar);

            if ($bayar < $grandTotal) {
                throw ValidationException::withMessages(['bayar' => 'Jumlah bayar kurang dari total belanja.']);
            }

            $kembalian = $bayar - $grandTotal;
            $sale = $this->createSaleRecord($subtotal, $diskon, $grandTotal, $bayar, $kembalian, $catatan, $paymentMethod);

            foreach ($preparedItems as $item) {
                $sale->items()->create([
                    'product_id' => $item['product']->id,
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'harga_beli' => (float) $item['product']->harga_beli,
                    'diskon' => $item['diskon'],
                    'subtotal' => $item['subtotal'],
                ]);

                $this->stockService->recordMovement(
                    $item['product'],
                    'out',
                    $item['qty'],
                    Sale::class,
                    $sale->id,
                    'Penjualan '.$sale->invoice_number
                );
            }

            return $sale;
        });

        app(ActivityLogger::class)->log(
            'sale.create',
            'Penjualan '.$sale->invoice_number.' dibuat (Total: Rp '.number_format($sale->grand_total, 0, ',', '.').')'
        );

        return $sale;
    }

    public function processReturn(Sale $sale, array $returnItems, string $alasan): SaleReturn
    {
        $saleReturn = DB::transaction(function () use ($sale, $returnItems, $alasan) {
            if (empty($returnItems)) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item untuk di-retur.']);
            }

            if ($sale->status === 'returned') {
                throw ValidationException::withMessages(['sale' => 'Penjualan ini sudah di-retur penuh.']);
            }

            $prepared = [];

            foreach ($returnItems as $item) {
                $saleItem = $sale->items()
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $saleItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk tersebut tidak ada pada transaksi ini.',
                    ]);
                }

                $qty = max(1, (int) $item['qty']);
                $sisa = $saleItem->qty - $saleItem->returned_qty;

                if ($qty > $sisa) {
                    throw ValidationException::withMessages([
                        'items' => "Qty retur melebihi sisa produk {$saleItem->product->name} (sisa {$sisa}).",
                    ]);
                }

                $prepared[] = ['saleItem' => $saleItem, 'qty' => $qty];
            }

            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'return_number' => SaleReturn::generateReturnNumber(),
                'alasan' => $alasan,
                'status' => 'approved',
                'processed_by' => Auth::id(),
            ]);

            $totalRefund = 0;

            foreach ($prepared as $entry) {
                $saleItem = $entry['saleItem'];
                $qty = $entry['qty'];

                $refund = (float) $saleItem->harga * $qty;
                $totalRefund += $refund;

                $saleReturn->items()->create([
                    'product_id' => $saleItem->product_id,
                    'qty' => $qty,
                    'harga' => $saleItem->harga,
                    'subtotal' => $refund,
                ]);

                $saleItem->increment('returned_qty', $qty);

                $product = $saleItem->product;
                $this->stockService->recordMovement(
                    $product,
                    'return',
                    $qty,
                    SaleReturn::class,
                    $saleReturn->id,
                    'Retur '.$saleReturn->return_number
                );
            }

            $saleReturn->update(['total_refund' => $totalRefund]);

            $isFullyReturned = $sale->items()->whereColumn('returned_qty', '<', 'qty')->doesntExist();
            $sale->update(['status' => $isFullyReturned ? 'returned' : 'partial_return']);

            return $saleReturn;
        });

        app(ActivityLogger::class)->log(
            'sale.return',
            'Retur '.$saleReturn->return_number.' untuk '.$sale->invoice_number.' (Refund: Rp '.number_format($saleReturn->total_refund, 0, ',', '.').')'
        );

        return $saleReturn;
    }

    public function getSales($startDate = null, $endDate = null, $userId = null)
    {
        $query = Sale::with(['user', 'items.product'])->latest('id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<', Carbon::parse($endDate)->addDay());
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->paginate(15);
    }

    private function createSaleRecord(
        float $subtotal,
        float $diskon,
        float $grandTotal,
        float $bayar,
        float $kembalian,
        ?string $catatan,
        string $paymentMethod = 'cash'
    ): Sale {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return Sale::create([
                    'invoice_number' => Sale::generateInvoiceNumber(),
                    'user_id' => Auth::id(),
                    'subtotal' => $subtotal,
                    'diskon' => $diskon,
                    'grand_total' => $grandTotal,
                    'bayar' => $bayar,
                    'kembalian' => $kembalian,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                    'catatan' => $catatan,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new QueryException(
            Sale::query()->getConnection(),
            'INSERT INTO sales (invoice_number) VALUES (...)',
            [],
            new \Exception('Gagal menghasilkan nomor invoice yang unik.')
        );
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $code = $exception->errorInfo[1] ?? null;

        return in_array($code, [1062, 19, 1555, 1561], true);
    }
}
