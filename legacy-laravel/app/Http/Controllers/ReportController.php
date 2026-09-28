<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PriceChangeService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected PriceChangeService $priceChangeService,
    ) {}

    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $userId = $request->input('user_id');
        $productId = $request->input('product_id');

        if (! auth()->user()->hasRole(['admin'])) {
            $userId = auth()->id();
        }

        $data = $this->reportService->getSalesReport($startDate, $endDate, $userId, $productId);
        $cashiers = User::role(['admin', 'kasir'])->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        if ($request->input('export') === 'pdf') {
            $data = $this->reportService->getSalesReport($startDate, $endDate, $userId, $productId, false);

            // Determine cashier name for filename & display
            $cashierName = null;
            if ($userId) {
                $cashier = $cashiers->firstWhere('id', $userId);
                $cashierName = $cashier ? $cashier->name : null;
            }

            $pdf = Pdf::loadView('reports.sales-pdf', compact('data', 'startDate', 'endDate', 'cashierName'));

            // Build descriptive filename: laporan-penjualan_YYYY-MM-DD_sd_YYYY-MM-DD[_NamaKasir].pdf
            $filename = 'laporan-penjualan_' . $startDate . '_sd_' . $endDate;
            if ($cashierName) {
                $filename .= '_' . \Illuminate\Support\Str::slug($cashierName);
            }
            $filename .= '.pdf';

            return $pdf->download($filename);
        }

        return view('reports.sales', compact('data', 'startDate', 'endDate', 'userId', 'productId', 'cashiers', 'products'));
    }

    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $this->reportService->getProfitLossReport($startDate, $endDate);

        if ($request->input('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.profit-loss-pdf', compact('data', 'startDate', 'endDate'));

            return $pdf->download('laporan-laba-rugi.pdf');
        }

        return view('reports.profit-loss', compact('data', 'startDate', 'endDate'));
    }

    public function stock(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $productId = $request->input('product_id');

        $movements = $this->reportService->getStockReport($startDate, $endDate, $productId);
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('reports.stock', compact('movements', 'startDate', 'endDate', 'productId', 'products'));
    }

    public function attendance(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $employeeId = $request->input('employee_id');

        $data = $this->reportService->getAttendanceReport($startDate, $endDate, $employeeId, $request->input('export') !== 'pdf');
        $employees = Employee::where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('roles', function ($r) {
                    $r->where('name', 'admin');
                });
            })
            ->orderBy('nama')
            ->get();

        if ($request->input('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.attendance-pdf', compact('data', 'startDate', 'endDate'));

            return $pdf->download('laporan-absensi.pdf');
        }

        return view('reports.attendance', compact('data', 'startDate', 'endDate', 'employeeId', 'employees'));
    }

    public function priceChange(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $productId = $request->input('product_id');

        $products = Product::where('is_active', true)->orderBy('name')->get();

        if ($request->input('export') === 'pdf') {
            $data = $this->reportService->getPriceChangeReport($startDate, $endDate, $productId, false);

            $pdf = Pdf::loadView('reports.price-change-pdf', [
                'data' => $data,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])->setPaper('a4', 'landscape');

            $filename = 'rekap-perubahan-harga-beli_' . $startDate . '_sd_' . $endDate . '.pdf';
            return $pdf->download($filename);
        }

        $data = $this->reportService->getPriceChangeReport($startDate, $endDate, $productId, true);

        return view('reports.price-change', compact('data', 'startDate', 'endDate', 'productId', 'products'));
    }

    public function syncMasterPrice(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        $productIds = $request->input('product_ids');
        $updated = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($productIds, &$updated) {
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get();

            foreach ($products as $product) {
                // Get last purchase price for this product
                $lastPurchaseItem = PurchaseItem::select('purchase_items.*')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchases.status', '!=', 'cancelled')
                    ->where('purchase_items.product_id', $product->id)
                    ->orderByDesc('purchases.tanggal')
                    ->orderByDesc('purchases.created_at')
                    ->first();

                if (! $lastPurchaseItem) {
                    continue;
                }

                $lastBoughtPrice = (float) $lastPurchaseItem->harga;
                $oldPrice = (float) $product->harga_beli;

                if ($oldPrice === $lastBoughtPrice) {
                    continue;
                }

                $purchase = \App\Models\Purchase::find($lastPurchaseItem->purchase_id);

                $product->update(['harga_beli' => $lastBoughtPrice]);
                $this->priceChangeService->record($product, $oldPrice, $lastBoughtPrice, 'sync_master', $purchase);

                app(ActivityLogger::class)->log(
                    'product.sync_price',
                    'Harga beli "'.$product->name.'" disinkronkan dari Rp '.number_format($oldPrice, 0, ',', '.').' → Rp '.number_format($lastBoughtPrice, 0, ',', '.').'.'
                );

                $updated++;
            }
        });

        $message = $updated > 0
            ? $updated.' produk berhasil disinkronkan ke harga restock terakhir.'
            : 'Tidak ada produk yang perlu disinkronkan.';

        return redirect()->back()->with('success', $message);
    }
}
