<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_low_stock_count_not_capped_by_list_limit(): void
    {
        $category = Category::create(['name' => 'Kategori Dashboard', 'slug' => 'kategori-dashboard']);

        for ($i = 0; $i < 15; $i++) {
            Product::create([
                'category_id' => $category->id,
                'name' => 'Produk Menipis '.$i,
                'sku' => 'LOW-'.$i,
                'harga_beli' => 1000,
                'harga_jual' => 2000,
                'stok' => 1,
                'min_stok' => 5,
                'satuan' => 'pcs',
                'is_active' => true,
            ]);
        }

        $data = app(ReportService::class)->getDashboardData();

        $this->assertCount(10, $data['low_stock']);
        $this->assertSame(15, $data['low_stock_count']);
    }

    public function test_dashboard_returns_sales_today_data(): void
    {
        $category = Category::create(['name' => 'Kategori Sales', 'slug' => 'kategori-sales']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Sales',
            'sku' => 'SLS-1',
            'harga_beli' => 1000,
            'harga_jual' => 5000,
            'stok' => 10,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);
        $user = User::create(['name' => 'Kasir', 'email' => 'kasir-dash@stockku.com', 'password' => 'password']);
        $this->actingAs($user);

        app(SaleService::class)->createSale(
            [['product_id' => $product->id, 'qty' => 2, 'diskon' => 0]],
            0,
            10000
        );

        $data = app(ReportService::class)->getDashboardData();

        $this->assertSame(10000.0, $data['sales_today']);
        $this->assertSame(1, $data['sales_count_today']);
    }

    public function test_dashboard_sales_chart_has_three_periods_with_monthly_aggregation(): void
    {
        $category = Category::create(['name' => 'Kategori Grafik', 'slug' => 'kategori-grafik']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Grafik',
            'sku' => 'GRF-1',
            'harga_beli' => 1000,
            'harga_jual' => 5000,
            'stok' => 100,
            'min_stok' => 2,
            'satuan' => 'pcs',
            'is_active' => true,
        ]);
        $user = User::create(['name' => 'Kasir', 'email' => 'kasir-grf@stockku.com', 'password' => 'password']);
        $this->actingAs($user);

        $this->travelTo(Carbon::parse('2026-01-05 10:00:00'));
        app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 5000);

        $this->travelTo(Carbon::parse('2026-02-10 10:00:00'));
        app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 2, 'diskon' => 0]], 0, 10000);
        app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 5000);

        $this->travelTo(Carbon::parse('2026-03-15 10:00:00'));
        app(SaleService::class)->createSale([['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]], 0, 7000);

        $data = app(ReportService::class)->getDashboardData();

        $chart = $data['sales_chart'];
        $this->assertCount(7, $chart['7d']['labels']);
        $this->assertCount(30, $chart['30d']['labels']);
        $this->assertCount(12, $chart['12m']['labels']);

        $this->assertContains('Jan', $chart['12m']['labels']);
        $this->assertContains('Feb', $chart['12m']['labels']);

        $janIndex = array_search('Jan', $chart['12m']['labels'], true);
        $febIndex = array_search('Feb', $chart['12m']['labels'], true);
        $this->assertSame(5000.0, $chart['12m']['data'][$janIndex]);
        $this->assertSame(15000.0, $chart['12m']['data'][$febIndex]);
        $this->assertSame(1, $chart['12m']['counts'][$janIndex]);
        $this->assertSame(2, $chart['12m']['counts'][$febIndex]);

        $this->assertSame(5000.0, $chart['12m']['data'][11]);
        $this->assertSame(1, $chart['12m']['counts'][11]);

        $this->assertSame(25000.0, $chart['12m']['total']);
        $this->assertSame(5000.0, $chart['7d']['total']);
        $this->assertSame(1, $chart['7d']['counts'][array_key_last($chart['7d']['counts'])]);
    }

    public function test_sales_report_items_are_paginated(): void
    {
        $user = User::create(['name' => 'Kasir', 'email' => 'kasir-rpt@stockku.com', 'password' => 'password']);
        $this->actingAs($user);
        $category = Category::create(['name' => 'Kategori Rpt', 'slug' => 'kategori-rpt']);

        for ($i = 1; $i <= 30; $i++) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Produk Rpt '.$i,
                'sku' => 'RPT-'.$i,
                'harga_beli' => 1000,
                'harga_jual' => 2000,
                'stok' => 100,
                'min_stok' => 2,
                'satuan' => 'pcs',
                'is_active' => true,
            ]);

            app(SaleService::class)->createSale(
                [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
                0,
                10000
            );
        }

        $data = app(ReportService::class)->getSalesReport(now()->toDateString(), now()->toDateString());

        $this->assertInstanceOf(LengthAwarePaginator::class, $data['items']);
        $this->assertSame(30, $data['items']->total());
        $this->assertSame(25, $data['items']->perPage());
        $this->assertSame(2, $data['items']->lastPage());
        $this->assertSame(30, $data['summary']['total_transactions']);
    }

    public function test_sales_report_can_return_all_items_for_pdf(): void
    {
        $user = User::create(['name' => 'Kasir', 'email' => 'kasir-rpt2@stockku.com', 'password' => 'password']);
        $this->actingAs($user);
        $category = Category::create(['name' => 'Kategori Rpt2', 'slug' => 'kategori-rpt2']);

        for ($i = 1; $i <= 30; $i++) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Produk Rpt2 '.$i,
                'sku' => 'RPT2-'.$i,
                'harga_beli' => 1000,
                'harga_jual' => 2000,
                'stok' => 100,
                'min_stok' => 2,
                'satuan' => 'pcs',
                'is_active' => true,
            ]);

            app(SaleService::class)->createSale(
                [['product_id' => $product->id, 'qty' => 1, 'diskon' => 0]],
                0,
                10000
            );
        }

        $data = app(ReportService::class)->getSalesReport(now()->toDateString(), now()->toDateString(), null, null, false);

        $this->assertInstanceOf(Collection::class, $data['items']);
        $this->assertCount(30, $data['items']);
    }
}
