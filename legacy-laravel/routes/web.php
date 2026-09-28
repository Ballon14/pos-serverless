<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master Data - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::post('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');
        Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::get('/products/import-template', [ProductController::class, 'downloadTemplate'])->name('products.import-template');
        Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
        Route::resource('products', ProductController::class);
        Route::get('/products/generate-sku/{category}', [ProductController::class, 'generateSku'])->name('products.generate-sku');
        Route::resource('suppliers', SupplierController::class)->except('show');

        // Purchases
        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

        // Stock
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/low', [StockController::class, 'lowStock'])->name('stock.low');
        Route::post('/stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');

        // Sale Returns
        Route::get('/sale-returns', [SaleReturnController::class, 'index'])->name('sale-returns.index');
        Route::get('/sale-returns/create/{sale}', [SaleReturnController::class, 'create'])->name('sale-returns.create');
        Route::post('/sale-returns/{sale}', [SaleReturnController::class, 'store'])->name('sale-returns.store');
        Route::get('/sale-returns/{saleReturn}', [SaleReturnController::class, 'show'])->name('sale-returns.show');

        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        // Settings (Backup, Restore & Config)
        Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/update', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/backup', [\App\Http\Controllers\SettingController::class, 'backup'])->name('settings.backup');
        Route::get('/settings/backup-csv', [\App\Http\Controllers\SettingController::class, 'backupCsv'])->name('settings.backup-csv');
        Route::post('/settings/restore', [\App\Http\Controllers\SettingController::class, 'restore'])->name('settings.restore');
        Route::post('/settings/restore-csv', [\App\Http\Controllers\SettingController::class, 'restoreCsv'])->name('settings.restore-csv');
    });

    // POS - Admin & Kasir
    Route::middleware('role:admin|kasir')->group(function () {
        Route::get('/pos', [SaleController::class, 'pos'])->name('pos');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    });

    // Reports - Admin & Manager & Kasir (Kasir only sees their own sales)
    Route::middleware('role:admin|manager|kasir')->prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('reports.sales');
    });

    Route::middleware('role:admin|manager')->prefix('reports')->group(function () {
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/price-change', [ReportController::class, 'priceChange'])->name('reports.price-change');
        Route::post('/price-change/sync-master', [ReportController::class, 'syncMasterPrice'])->name('reports.sync-master-price');
    });
});

require __DIR__.'/auth.php';
