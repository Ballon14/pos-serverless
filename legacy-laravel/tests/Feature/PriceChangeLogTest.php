<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PriceChangeLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PriceChangeService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceChangeLogTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('admin');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-'.uniqid()]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Produk Test',
            'sku' => 'TST-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'stok' => 50,
            'min_stok' => 5,
            'satuan' => 'pcs',
            'is_active' => true,
        ], $overrides));
    }

    public function test_price_change_service_records_change(): void
    {
        $this->adminUser();
        $product = $this->createProduct();
        $service = app(PriceChangeService::class);

        $log = $service->record($product, 3000, 3500, 'manual_edit');

        $this->assertNotNull($log);
        $this->assertDatabaseHas('price_change_logs', [
            'product_id' => $product->id,
            'harga_lama' => 3000,
            'harga_baru' => 3500,
            'sumber' => 'manual_edit',
        ]);
    }

    public function test_price_change_service_skips_when_no_change(): void
    {
        $this->adminUser();
        $product = $this->createProduct();
        $service = app(PriceChangeService::class);

        $log = $service->record($product, 3000, 3000, 'manual_edit');

        $this->assertNull($log);
        $this->assertDatabaseMissing('price_change_logs', [
            'product_id' => $product->id,
        ]);
    }

    public function test_purchase_with_update_harga_beli_creates_log(): void
    {
        $user = $this->adminUser();
        $product = $this->createProduct(['harga_beli' => 3000]);
        $supplier = Supplier::create(['name' => 'Supplier Test', 'code' => 'SUP-'.uniqid(), 'phone' => '081234567890']);

        $purchaseService = app(PurchaseService::class);
        $purchase = $purchaseService->createPurchase(
            $supplier->id,
            now()->toDateString(),
            [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'harga' => 3500,
                    'update_harga_beli' => true,
                ],
            ],
        );

        $this->assertDatabaseHas('price_change_logs', [
            'product_id' => $product->id,
            'harga_lama' => 3000,
            'harga_baru' => 3500,
            'sumber' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'user_id' => $user->id,
        ]);

        // Master price should be updated
        $this->assertSame(3500, (int) $product->fresh()->harga_beli);
    }

    public function test_purchase_without_update_harga_beli_does_not_create_log(): void
    {
        $this->adminUser();
        $product = $this->createProduct(['harga_beli' => 3000]);
        $supplier = Supplier::create(['name' => 'Supplier Test', 'code' => 'SUP-'.uniqid(), 'phone' => '081234567890']);

        app(PurchaseService::class)->createPurchase(
            $supplier->id,
            now()->toDateString(),
            [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'harga' => 3500,
                    'update_harga_beli' => false,
                ],
            ],
        );

        $this->assertDatabaseMissing('price_change_logs', [
            'product_id' => $product->id,
        ]);

        // Master price should NOT be updated
        $this->assertSame(3000, (int) $product->fresh()->harga_beli);
    }

    public function test_product_edit_creates_price_change_log(): void
    {
        $user = $this->adminUser();
        $category = Category::create(['name' => 'KatEdit', 'slug' => 'kat-edit-'.uniqid()]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Edit Test',
            'sku' => 'EDT-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'stok' => 10,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Produk Edit Test',
            'sku' => $product->sku,
            'harga_beli' => 4500,
            'harga_jual' => 6000,
            'min_stok' => 2,
            'satuan' => 'pcs',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('price_change_logs', [
            'product_id' => $product->id,
            'harga_lama' => 3000,
            'harga_baru' => 4500,
            'sumber' => 'manual_edit',
            'user_id' => $user->id,
        ]);
    }

    public function test_product_edit_same_price_does_not_create_log(): void
    {
        $this->adminUser();
        $category = Category::create(['name' => 'KatSame', 'slug' => 'kat-same-'.uniqid()]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Same Price',
            'sku' => 'SMP-'.uniqid(),
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'stok' => 10,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Produk Same Price Updated Name',
            'sku' => $product->sku,
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'min_stok' => 2,
            'satuan' => 'pcs',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('price_change_logs', [
            'product_id' => $product->id,
        ]);
    }

    public function test_report_service_reads_from_price_change_logs(): void
    {
        $this->adminUser();
        $product = $this->createProduct();

        // Create some price change logs
        PriceChangeLog::create([
            'product_id' => $product->id,
            'harga_lama' => 3000,
            'harga_baru' => 3500,
            'sumber' => 'purchase',
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        PriceChangeLog::create([
            'product_id' => $product->id,
            'harga_lama' => 3500,
            'harga_baru' => 4000,
            'sumber' => 'manual_edit',
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        $reportService = app(ReportService::class);
        $data = $reportService->getPriceChangeReport(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            null,
            false,
        );

        $this->assertSame(2, $data['summary']['total_changes']);
        $this->assertSame(2, $data['summary']['total_naik']);
        $this->assertSame(0, $data['summary']['total_turun']);
        $this->assertSame(1, $data['summary']['products_affected']);

        $changes = $data['changes'];
        $this->assertCount(2, $changes);

        // Verify sumber labels
        $sumbers = $changes->pluck('sumber')->toArray();
        $this->assertContains('Restock', $sumbers);
        $this->assertContains('Edit Produk', $sumbers);
    }

    public function test_model_computed_attributes(): void
    {
        $this->adminUser();
        $product = $this->createProduct();

        $log = PriceChangeLog::create([
            'product_id' => $product->id,
            'harga_lama' => 1000,
            'harga_baru' => 1500,
            'sumber' => 'manual_edit',
            'user_id' => auth()->id(),
        ]);

        $this->assertSame(500.0, $log->selisih);
        $this->assertSame(50.0, $log->persen);
        $this->assertSame('naik', $log->tipe);

        $logDown = PriceChangeLog::create([
            'product_id' => $product->id,
            'harga_lama' => 2000,
            'harga_baru' => 1500,
            'sumber' => 'manual_edit',
            'user_id' => auth()->id(),
        ]);

        $this->assertSame(-500.0, $logDown->selisih);
        $this->assertSame(-25.0, $logDown->persen);
        $this->assertSame('turun', $logDown->tipe);
    }

    public function test_sync_master_price_updates_product_and_creates_log(): void
    {
        $user = $this->adminUser();
        $product = $this->createProduct(['harga_beli' => 3000]);
        $supplier = Supplier::create(['name' => 'Supplier Sync', 'code' => 'SUP-'.uniqid(), 'phone' => '081234567890']);

        // Create a purchase with a different price (no update_harga_beli)
        $purchase = app(PurchaseService::class)->createPurchase(
            $supplier->id,
            now()->toDateString(),
            [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'harga' => 4000,
                    'update_harga_beli' => false,
                ],
            ],
        );

        // Master price should still be 3000
        $this->assertSame(3000, (int) $product->fresh()->harga_beli);

        // Now sync via controller
        $response = $this->post(route('reports.sync-master-price'), [
            'product_ids' => [$product->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Master price should now be 4000
        $this->assertSame(4000, (int) $product->fresh()->harga_beli);

        // A price change log with sumber 'sync_master' should exist with purchase reference
        $this->assertDatabaseHas('price_change_logs', [
            'product_id' => $product->id,
            'harga_lama' => 3000,
            'harga_baru' => 4000,
            'sumber' => 'sync_master',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_sync_master_price_skips_when_already_synced(): void
    {
        $this->adminUser();
        $product = $this->createProduct(['harga_beli' => 4000]);
        $supplier = Supplier::create(['name' => 'Supplier Skip', 'code' => 'SUP-'.uniqid(), 'phone' => '081234567890']);

        // Create a purchase with same price as master
        app(PurchaseService::class)->createPurchase(
            $supplier->id,
            now()->toDateString(),
            [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'harga' => 4000,
                    'update_harga_beli' => false,
                ],
            ],
        );

        $response = $this->post(route('reports.sync-master-price'), [
            'product_ids' => [$product->id],
        ]);

        $response->assertRedirect();

        // No sync_master log should be created since prices match
        $this->assertDatabaseMissing('price_change_logs', [
            'product_id' => $product->id,
            'sumber' => 'sync_master',
        ]);
    }

    public function test_kasir_cannot_access_price_change_report(): void
    {
        Role::findOrCreate('kasir');
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        $this->actingAs($kasir);

        $response = $this->get(route('reports.price-change'));

        $response->assertForbidden();
    }

    public function test_kasir_cannot_sync_master_price(): void
    {
        Role::findOrCreate('kasir');
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');
        $this->actingAs($kasir);

        $product = $this->createProduct();

        $response = $this->post(route('reports.sync-master-price'), [
            'product_ids' => [$product->id],
        ]);

        $response->assertForbidden();
    }
}
