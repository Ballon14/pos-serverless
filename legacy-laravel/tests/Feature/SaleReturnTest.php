<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(int $stok = 20): Product
    {
        $category = Category::create(['name' => 'Kategori Tes', 'slug' => 'kategori-'.uniqid()]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Tes',
            'sku' => 'TES-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'stok' => $stok,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);
    }

    private function makeSale(array $qtys): Sale
    {
        $user = User::create([
            'name' => 'Kasir Tes',
            'email' => 'kasir-tes-'.uniqid().'@stockku.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $items = [];
        foreach ($qtys as $qty) {
            $product = $this->makeProduct();
            $items[] = ['product_id' => $product->id, 'qty' => $qty, 'diskon' => 0];
        }

        return app(SaleService::class)->createSale($items, 0, 1000000);
    }

    public function test_partial_return_updates_status_and_restores_stock(): void
    {
        $sale = $this->makeSale([2]);
        $product = $sale->items->first()->product;
        $this->assertSame(18, $product->fresh()->stok);
        $this->assertSame('completed', $sale->fresh()->status);

        $saleReturn = app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 1]],
            'Rusak'
        );

        $this->assertDatabaseHas('sale_returns', [
            'id' => $saleReturn->id,
            'sale_id' => $sale->id,
            'total_refund' => 5000,
        ]);
        $this->assertSame(1, $sale->items->first()->fresh()->returned_qty);
        $this->assertSame('partial_return', $sale->fresh()->status);
        $this->assertSame(19, $product->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'return',
            'qty' => 1,
        ]);
    }

    public function test_full_return_sets_status_returned(): void
    {
        $sale = $this->makeSale([2]);
        $product = $sale->items->first()->product;

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 2]],
            'Ganti barang'
        );

        $this->assertSame('returned', $sale->fresh()->status);
        $this->assertSame(2, $sale->items->first()->fresh()->returned_qty);
        $this->assertSame(20, $product->fresh()->stok);
    }

    public function test_over_return_is_rejected(): void
    {
        $sale = $this->makeSale([2]);
        $product = $sale->items->first()->product;

        try {
            app(SaleService::class)->processReturn(
                $sale,
                [['product_id' => $product->id, 'qty' => 3]],
                'Retur berlebihan'
            );
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $this->assertDatabaseCount('sale_returns', 0);
        $this->assertSame(0, $sale->items->first()->fresh()->returned_qty);
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame(18, $product->fresh()->stok);
    }

    public function test_returning_more_than_remaining_after_partial_return_is_rejected(): void
    {
        $sale = $this->makeSale([3]);
        $product = $sale->items->first()->product;

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 2]],
            'Retur pertama'
        );

        $this->expectException(ValidationException::class);

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 2]],
            'Retur kedua melebihi sisa'
        );
    }

    public function test_returning_fully_returned_sale_is_rejected(): void
    {
        $sale = $this->makeSale([1]);
        $product = $sale->items->first()->product;

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 1]],
            'Retur penuh'
        );

        $this->assertSame('returned', $sale->fresh()->status);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $product->id, 'qty' => 1]],
            'Retur dobel'
        );
    }

    public function test_returning_product_not_in_sale_is_rejected(): void
    {
        $sale = $this->makeSale([1]);
        $foreignProduct = $this->makeProduct();

        $this->expectException(ValidationException::class);

        app(SaleService::class)->processReturn(
            $sale,
            [['product_id' => $foreignProduct->id, 'qty' => 1]],
            'Produk asing'
        );
    }
}
