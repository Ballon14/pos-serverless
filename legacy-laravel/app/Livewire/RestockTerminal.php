<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\PurchaseService;

use Livewire\Component;
use Livewire\WithFileUploads;

class RestockTerminal extends Component
{
    use WithFileUploads;

    public string $search = '';

    public array $cart = [];



    public $supplierId = '';

    public string $tanggal = '';

    public string $keterangan = '';

    public $fotoNota;

    public bool $updateHargaMaster = false;

    public ?int $lastPurchaseId = null;

    public function mount(): void
    {
        $this->tanggal = date('Y-m-d');
        $this->restoreCartFromSession();
    }

    public function updated($name): void
    {
        if (in_array($name, ['cart', 'supplierId', 'tanggal', 'keterangan', 'updateHargaMaster'], true)) {
            $this->saveCartToSession();
        }
    }


    public function addBySearchEnter(): void
    {
        $code = trim((string) $this->search);

        if ($code === '') {
            return;
        }

        $product = $this->findByCode($code);

        if (! $product) {
            return;
        }

        $this->search = '';
        $this->addToCart($product->id);
    }

    private function findByCode(string $code): ?Product
    {
        return Product::where('is_active', true)
            ->whereRaw('LOWER(sku) = ?', [mb_strtolower($code)])
            ->first();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product || ! $product->is_active) {
            return;
        }

        $key = 'p_'.$productId;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
            $this->cart[$key]['subtotal'] = $this->cart[$key]['qty'] * $this->cart[$key]['harga'];
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'harga' => (float) $product->harga_beli,
                'qty' => 1,
                'subtotal' => (float) $product->harga_beli,
            ];
        }

        $this->search = '';
        $this->saveCartToSession();
    }

    public function updateQty(string $key, int $qty): void
    {
        if (isset($this->cart[$key])) {
            if ($qty <= 0) {
                unset($this->cart[$key]);
            } else {
                $this->cart[$key]['qty'] = $qty;
                $this->cart[$key]['subtotal'] = $qty * $this->cart[$key]['harga'];
            }
        }
        $this->saveCartToSession();
    }

    public function updateHarga(string $key, $harga): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['harga'] = max(0, (float) $harga);
            $this->cart[$key]['subtotal'] = $this->cart[$key]['qty'] * $this->cart[$key]['harga'];
        }
        $this->saveCartToSession();
    }

    public function removeItem(string $key): void
    {
        unset($this->cart[$key]);
        $this->saveCartToSession();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->supplierId = '';
        $this->tanggal = date('Y-m-d');
        $this->keterangan = '';
        $this->fotoNota = null;
        $this->updateHargaMaster = false;
        $this->saveCartToSession();
    }

    public function getTotalProperty(): float
    {
        return array_sum(array_column($this->cart, 'subtotal'));
    }

    public function processRestock(): void
    {
        if (empty($this->cart)) {
            session()->flash('restock-error', 'Keranjang masih kosong.');

            return;
        }

        if (empty($this->supplierId)) {
            session()->flash('restock-error', 'Supplier wajib dipilih.');

            return;
        }

        if (empty($this->tanggal)) {
            session()->flash('restock-error', 'Tanggal pembelian wajib diisi.');

            return;
        }

        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'update_harga_beli' => $this->updateHargaMaster,
            ];
        }

        try {
            $fotoNotaFile = null;
            if ($this->fotoNota) {
                $fotoNotaFile = $this->fotoNota;
            }

            $purchaseService = app(PurchaseService::class);
            $purchase = $purchaseService->createPurchase(
                (int) $this->supplierId,
                $this->tanggal,
                $items,
                $this->keterangan ?: null,
                $fotoNotaFile
            );

            $this->lastPurchaseId = $purchase->id;

            app(ActivityLogger::class)->log(
                'purchase.create',
                'Pembelian '.$purchase->invoice_number.' dicatat (Total: Rp '.number_format($purchase->total, 0, ',', '.').').'
            );

            $this->clearCart();
            session()->flash('restock-success', 'Pembelian berhasil dicatat! Invoice: '.$purchase->invoice_number);
        } catch (\Exception $e) {
            session()->flash('restock-error', 'Gagal mencatat pembelian: '.$e->getMessage());
        }
    }

    private function saveCartToSession(): void
    {
        if (! auth()->check()) {
            return;
        }

        session()->put('restock_cart_'.auth()->id(), [
            'cart' => $this->cart,
            'supplierId' => $this->supplierId,
            'tanggal' => $this->tanggal,
            'keterangan' => $this->keterangan,
            'updateHargaMaster' => $this->updateHargaMaster,
        ]);
    }

    private function restoreCartFromSession(): void
    {
        if (! auth()->check()) {
            return;
        }

        $data = session()->get('restock_cart_'.auth()->id());

        if (! is_array($data) || empty($data['cart'])) {
            return;
        }

        $validated = [];

        foreach ($data['cart'] as $key => $item) {
            $product = Product::find($item['product_id'] ?? null);

            if (! $product || ! $product->is_active) {
                continue;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $harga = max(0, (float) ($item['harga'] ?? $product->harga_beli));

            $validated[$key] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'harga' => $harga,
                'qty' => $qty,
                'subtotal' => $harga * $qty,
            ];
        }

        $this->cart = $validated;
        $this->supplierId = $data['supplierId'] ?? '';
        $this->tanggal = $data['tanggal'] ?? date('Y-m-d');
        $this->keterangan = (string) ($data['keterangan'] ?? '');
        $this->updateHargaMaster = (bool) ($data['updateHargaMaster'] ?? false);

        if (! empty($this->cart)) {
            $this->saveCartToSession();
        }
    }

    public function render()
    {
        $query = Product::where('is_active', true);

        if (strlen($this->search) > 0) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%')
                    ->orWhereHas('category', function ($q2) {
                        $q2->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $products = $query->orderBy('name')->limit(50)->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('livewire.restock-terminal', [
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }
}
