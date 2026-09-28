<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(int $stok = 10, float $hargaJual = 5000, string $name = 'Produk Tes'): Product
    {
        $category = Category::create(['name' => 'Kategori Tes', 'slug' => 'kategori-'.uniqid()]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'sku' => 'TES-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => $hargaJual,
            'stok' => $stok,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);
    }

    private function actingAsUser(): User
    {
        $user = User::create([
            'name' => 'Kasir Tes',
            'email' => 'kasir-tes@stockku.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_create_sale_decrements_stock_and_creates_movement(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(stok: 10, hargaJual: 5000);

        $sale = app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 3, 'diskon' => 0]],
            1000,
            20000,
            'catatan tes'
        );

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'subtotal' => 15000,
            'diskon' => 1000,
            'grand_total' => 14000,
            'bayar' => 20000,
            'kembalian' => 6000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 3,
            'returned_qty' => 0,
        ]);
        $this->assertSame(7, $product->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'qty' => 3,
            'stok_sebelum' => 10,
            'stok_sesudah' => 7,
        ]);
        $this->assertCount(1, StockMovement::all());
    }

    public function test_oversell_is_rejected_and_stock_unchanged(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(stok: 2);

        try {
            app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 5, 'diskon' => 0]], 0, 100000);
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(2, $product->fresh()->stok);
    }

    public function test_sale_rejected_when_inactive_product(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(stok: 5);
        $product->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 100000);
    }

    public function test_diskon_is_clamped_to_subtotal(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(hargaJual: 5000);

        $sale = app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 2, 'diskon' => 0]],
            50000,
            0
        );

        $this->assertSame(0.0, (float) $sale->grand_total);
        $this->assertSame(0.0, (float) $sale->kembalian);
        $this->assertSame(10000.0, (float) $sale->diskon);
    }

    public function test_payment_less_than_total_is_rejected(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(hargaJual: 5000);

        try {
            app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 2, 'diskon' => 0]], 0, 5000);
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bayar', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $product->fresh()->stok);
    }

    public function test_empty_cart_is_rejected(): void
    {
        $this->actingAsUser();
        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([], 0, 100000);
    }

    public function test_invoice_number_has_unique_format(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(stok: 100);

        $sale1 = app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 100000);
        $sale2 = app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 100000);

        $this->assertNotSame($sale1->invoice_number, $sale2->invoice_number);
        $this->assertStringStartsWith('INV-'.now()->format('Ymd'), $sale1->invoice_number);
        $this->assertStringStartsWith('INV-'.now()->format('Ymd'), $sale2->invoice_number);
    }

    public function test_item_diskon_cannot_make_item_subtotal_negative(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(hargaJual: 5000);

        $sale = app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 1, 'diskon' => 999999]],
            0,
            100000
        );

        $this->assertSame(0.0, (float) $sale->grand_total);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'subtotal' => 0]);
    }

    public function test_sale_item_snapshots_harga_beli_at_time_of_sale(): void
    {
        $this->actingAsUser();
        $product = $this->makeProduct(stok: 10, hargaJual: 5000);

        $sale = app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 2, 'diskon' => 0]],
            0,
            20000
        );

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'harga_beli' => 3000,
        ]);

        $product->update(['harga_beli' => 9999]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'harga_beli' => 3000,
        ]);
    }
}
