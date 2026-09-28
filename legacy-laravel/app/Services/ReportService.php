<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSalesReport($startDate, $endDate, $userId = null, $productId = null, $paginate = true)
    {
        $itemsQuery = SaleItem::select(
            'sale_items.product_id',
            DB::raw('SUM(sale_items.qty) as qty'),
            DB::raw('SUM(sale_items.subtotal) as subtotal')
        )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.created_at', '>=', $startDate)
            ->where('sales.created_at', '<', Carbon::parse($endDate)->addDay())
            ->where('sales.status', '!=', 'returned');

        if ($userId) {
            $itemsQuery->where('sales.user_id', $userId);
        }
        if ($productId) {
            $itemsQuery->where('sale_items.product_id', $productId);
        }

        $itemsQuery->groupBy('sale_items.product_id');

        $aggregated = $itemsQuery->get();

        // Load product data for the aggregated product IDs
        $productIds = $aggregated->pluck('product_id');
        $products = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        $items = $aggregated->map(function ($row) use ($products) {
            $product = $products->get($row->product_id);

            return (object) [
                'product_id' => $row->product_id,
                'sku' => $product->sku ?? '-',
                'name' => $product->name ?? '-',
                'category_name' => $product->category->name ?? '-',
                'qty' => (int) $row->qty,
                'subtotal' => (float) $row->subtotal,
            ];
        })->sortByDesc('qty')->values();

        $totalRevenue = $items->sum('subtotal');
        $totalItemsSold = $items->sum('qty');

        // Count distinct transactions via SQL
        $txQuery = Sale::where('created_at', '>=', $startDate)
            ->where('created_at', '<', Carbon::parse($endDate)->addDay())
            ->where('status', '!=', 'returned');

        if ($userId) {
            $txQuery->where('user_id', $userId);
        }
        if ($productId) {
            $txQuery->whereHas('items', fn ($q) => $q->where('product_id', $productId));
        }

        $totalTransactions = $txQuery->count();

        if (! $paginate) {
            return [
                'summary' => [
                    'total_transactions' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'total_items_sold' => $totalItemsSold,
                ],
                'items' => $items,
            ];
        }

        $perPage = 25;
        $page = (int) request('page', 1);

        $paginated = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_revenue' => $totalRevenue,
                'total_items_sold' => $totalItemsSold,
            ],
            'items' => $paginated,
        ];
    }

    public function getProfitLossReport($startDate, $endDate)
    {
        $sales = Sale::with('items.product')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', Carbon::parse($endDate)->addDay())
            ->where('status', '!=', 'returned')
            ->get();

        $revenue = $sales->sum('subtotal');
        $discounts = $sales->sum('diskon');
        $net_revenue = $sales->sum('grand_total');

        $cogs = $sales->sum(function ($sale) {
            return $sale->items->sum(function ($item) {
                $hargaBeli = $item->harga_beli ?? $item->product?->harga_beli ?? 0;

                return $hargaBeli * $item->qty;
            });
        });

        // Retur
        $totalRetur = SaleReturn::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('created_at', '>=', $startDate)
                ->where('created_at', '<', Carbon::parse($endDate)->addDay());
        })->where('status', 'approved')->sum('total_refund');

        $gross_profit = $net_revenue - $cogs - $totalRetur;

        return [
            'revenue' => $revenue,
            'discounts' => $discounts,
            'net_revenue' => $net_revenue,
            'cogs' => $cogs,
            'total_retur' => $totalRetur,
            'gross_profit' => $gross_profit,
        ];
    }

    public function getStockReport($startDate, $endDate, $productId = null)
    {
        $query = StockMovement::with(['product', 'user'])
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', Carbon::parse($endDate)->addDay());

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function getAttendanceReport($startDate, $endDate, $employeeId = null, $paginate = true)
    {
        $query = Employee::where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('roles', function ($r) {
                    $r->where('name', 'admin');
                });
            })
            ->orderBy('nama');

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $paginate ? $query->paginate(15) : $query->get();

        $attendances = Attendance::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        $rows = [];
        foreach ($employees as $employee) {
            $empAtt = $attendances->where('employee_id', $employee->id);
            $totalDays = $empAtt->count();
            $hadir = $empAtt->where('status', 'hadir')->count();

            $rows[] = [
                'employee_name' => $employee->nama,
                'employee_jabatan' => $employee->jabatan,
                'total_days' => $totalDays,
                'hadir' => $hadir,
                'sakit' => $empAtt->where('status', 'sakit')->count(),
                'izin' => $empAtt->where('status', 'izin')->count(),
                'cuti' => $empAtt->where('status', 'cuti')->count(),
                'alpha' => $empAtt->where('status', 'alpha')->count(),
                'attendance_percentage' => $totalDays > 0 ? round(($hadir / $totalDays) * 100) : 0,
            ];
        }

        usort($rows, fn ($a, $b) => $b['attendance_percentage'] <=> $a['attendance_percentage']);

        return [
            'rows' => $rows,
            'employees' => $employees,
        ];
    }

    public function getDashboardData()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Penjualan hari ini
        $salesToday = Sale::whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->where('status', '!=', 'returned')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total, COUNT(*) as jumlah')
            ->first();

        // Grafik penjualan 7 hari terakhir
        $dailySales = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(grand_total) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', '!=', 'returned')
            ->where('created_at', '>=', $today->copy()->subDays(6))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Grafik penjualan 30 hari terakhir
        $dailySales30 = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(grand_total) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', '!=', 'returned')
            ->where('created_at', '>=', $today->copy()->subDays(29))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Grafik penjualan 12 bulan terakhir (agregasi di PHP agar kompatibel semua driver)
        $monthlySales = Sale::where('status', '!=', 'returned')
            ->where('created_at', '>=', $today->copy()->subMonths(11)->startOfMonth())
            ->get(['created_at', 'grand_total'])
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m'))
            ->map(fn ($items) => ['total' => (float) $items->sum('grand_total'), 'count' => $items->count()]);

        $salesChart = [
            '7d' => $this->buildChartSeries($today->copy()->subDays(6), $today, 'day', $dailySales),
            '30d' => $this->buildChartSeries($today->copy()->subDays(29), $today, 'day', $dailySales30),
            '12m' => $this->buildChartSeries($today->copy()->subMonths(11)->startOfMonth(), $today, 'month', $monthlySales),
        ];

        // Produk terlaris bulan ini
        $topProducts = SaleItem::select(
            'product_id',
            DB::raw('SUM(qty) as total_qty'),
            DB::raw('SUM(subtotal) as total_sales')
        )
            ->whereHas('sale', function ($q) use ($startOfMonth) {
                $q->where('created_at', '>=', $startOfMonth)
                    ->where('status', '!=', 'returned');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product')
            ->get();

        // Produk stok menipis (daftar dibatasi 10, hitung penuh untuk kartu)
        $lowStock = Product::whereColumn('stok', '<=', 'min_stok')
            ->where('is_active', true)
            ->with('category')
            ->limit(10)
            ->get();

        $lowStockCount = Product::whereColumn('stok', '<=', 'min_stok')
            ->where('is_active', true)
            ->count();

        // Penjualan bulan ini
        $salesThisMonth = Sale::where('created_at', '>=', $startOfMonth)
            ->where('status', '!=', 'returned')
            ->sum('grand_total');

        return [
            'sales_today' => (float) $salesToday->total,
            'sales_count_today' => (int) $salesToday->jumlah,
            'sales_this_month' => $salesThisMonth,
            'sales_chart' => $salesChart,
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
            'low_stock_count' => $lowStockCount,
        ];
    }

    /**
     * Bangun deret grafik (labels + data + total + rata-rata) dari jendela waktu.
     * $raw: Collection berisi data agregat per hari (key 'date'/'total') atau
     * Collection berisi total per bulan (key 'Y-m' => total).
     */
    private function buildChartSeries(Carbon $start, Carbon $end, string $unit, $raw): array
    {
        $labels = [];
        $data = [];
        $counts = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($unit === 'month') {
                $key = $cursor->format('Y-m');
                $label = $cursor->translatedFormat('M');
                $cursor->addMonth();
            } else {
                $key = $cursor->toDateString();
                $label = $cursor->translatedFormat('d M');
                $cursor->addDay();
            }

            $bucket = $raw[$key] ?? null;
            $labels[] = $label;
            $data[] = (float) ($unit === 'month' ? ($bucket['total'] ?? 0) : ($bucket->total ?? 0));
            $counts[] = (int) ($unit === 'month' ? ($bucket['count'] ?? 0) : ($bucket->count ?? 0));
        }

        $total = array_sum($data);
        $count = count($data);

        return [
            'labels' => $labels,
            'data' => $data,
            'counts' => $counts,
            'total' => $total,
            'average' => $count > 0 ? $total / $count : 0,
        ];
    }

    /**
     * Rekap perubahan harga beli produk.
     * Reads from the dedicated price_change_logs audit trail table.
     */
    public function getPriceChangeReport($startDate, $endDate, $productId = null, $paginate = true)
    {
        // Eager load reference (morphTo) to avoid N+1 on invoice lookup
        $baseQuery = \App\Models\PriceChangeLog::with(['product.category', 'user', 'reference'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderByDesc('created_at');

        if ($productId) {
            $baseQuery->where('product_id', $productId);
        }

        // Summary always needs full count (unpaginated)
        $summaryQuery = (clone $baseQuery);
        $summaryRows = $summaryQuery->get(['id', 'product_id', 'harga_lama', 'harga_baru']);

        $totalNaik = $summaryRows->filter(fn ($r) => (float) $r->harga_baru >= (float) $r->harga_lama)->count();
        $totalTurun = $summaryRows->count() - $totalNaik;
        $totalChanges = $summaryRows->count();
        $productsAffected = $summaryRows->pluck('product_id')->unique()->count();

        $summary = [
            'total_changes' => $totalChanges,
            'total_naik' => $totalNaik,
            'total_turun' => $totalTurun,
            'products_affected' => $productsAffected,
        ];

        // Transform helper
        $mapLog = function ($log) {
            // Invoice number via eager-loaded reference — no extra query
            $invoiceNumber = null;
            $purchaseId = null;
            if (in_array($log->sumber, ['purchase', 'sync_master']) && $log->reference_type === \App\Models\Purchase::class && $log->reference) {
                $invoiceNumber = $log->reference->invoice_number;
                $purchaseId = $log->reference->id;
            }

            $sumberLabel = match ($log->sumber) {
                'purchase' => 'Restock',
                'manual_edit' => 'Edit Produk',
                'sync_master' => 'Sync Master',
                default => ucfirst($log->sumber),
            };

            return (object) [
                'product_id' => $log->product_id,
                'product_name' => $log->product->name ?? '-',
                'product_sku' => $log->product->sku ?? '-',
                'category_name' => $log->product->category->name ?? '-',
                'harga_lama' => (float) $log->harga_lama,
                'harga_baru' => (float) $log->harga_baru,
                'selisih' => $log->selisih,
                'persen' => $log->persen,
                'tipe' => $log->tipe,
                'tanggal' => Carbon::parse($log->created_at),
                'sumber' => $sumberLabel,
                'invoice_perubahan' => $invoiceNumber ?? '-',
                'purchase_id' => $purchaseId,
                'pencatat' => $log->user->name ?? '-',
            ];
        };

        if (! $paginate) {
            // PDF export — fetch all
            $changes = $baseQuery->get()->map($mapLog);

            return [
                'summary' => $summary,
                'changes' => $changes,
                'current_vs_last_bought' => $this->buildCurrentVsLastBought($startDate, $endDate, $productId),
            ];
        }

        // Paginated: use DB-level pagination, then map only the current page
        $perPage = 20;
        $paginatedLogs = $baseQuery->paginate($perPage);

        $paginatedLogs->getCollection()->transform($mapLog);

        return [
            'summary' => $summary,
            'changes' => $paginatedLogs,
            'current_vs_last_bought' => $this->buildCurrentVsLastBought($startDate, $endDate, $productId),
        ];
    }

    /**
     * Build the "current vs last bought" comparison data.
     * Uses a single batch query to get the latest purchase price per product,
     * avoiding N+1 queries.
     */
    private function buildCurrentVsLastBought($startDate, $endDate, $productId = null)
    {
        $productIdsInRange = \App\Models\PurchaseItem::select('purchase_items.product_id')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', '!=', 'cancelled')
            ->whereDate('purchases.tanggal', '>=', $startDate)
            ->whereDate('purchases.tanggal', '<=', $endDate)
            ->distinct()
            ->pluck('product_id');

        if ($productId) {
            $productIdsInRange = $productIdsInRange->intersect([$productId]);
        }

        $currentVsLastBought = collect();

        if ($productIdsInRange->isEmpty()) {
            return $currentVsLastBought;
        }

        $products = Product::whereIn('id', $productIdsInRange)
            ->where('is_active', true)
            ->with('category')
            ->get();

        if ($products->isEmpty()) {
            return $currentVsLastBought;
        }

        // Batch query: get latest purchase price per product in one query
        // Using a subquery to find the max purchase date per product, then joining back
        $latestPrices = DB::table('purchase_items')
            ->select('purchase_items.product_id', 'purchase_items.harga')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', '!=', 'cancelled')
            ->whereIn('purchase_items.product_id', $products->pluck('id'))
            ->joinSub(
                DB::table('purchase_items as pi2')
                    ->select('pi2.product_id', DB::raw('MAX(CONCAT(p2.tanggal, " ", p2.created_at)) as max_key'))
                    ->join('purchases as p2', 'p2.id', '=', 'pi2.purchase_id')
                    ->where('p2.status', '!=', 'cancelled')
                    ->whereIn('pi2.product_id', $products->pluck('id'))
                    ->groupBy('pi2.product_id'),
                'latest',
                function ($join) {
                    $join->on('purchase_items.product_id', '=', 'latest.product_id')
                        ->whereRaw('CONCAT(purchases.tanggal, " ", purchases.created_at) = latest.max_key');
                }
            )
            ->get()
            ->keyBy('product_id');

        foreach ($products as $product) {
            $latest = $latestPrices->get($product->id);
            if (! $latest) {
                continue;
            }

            $lastBoughtPrice = (float) $latest->harga;
            $currentPrice = (float) $product->harga_beli;

            if ($currentPrice === $lastBoughtPrice) {
                continue;
            }

            $diff = $lastBoughtPrice - $currentPrice;
            $pctChange = $currentPrice > 0 ? round(($diff / $currentPrice) * 100, 1) : 0;

            $currentVsLastBought->push((object) [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'category_name' => $product->category->name ?? '-',
                'harga_terakhir_dibeli' => $lastBoughtPrice,
                'harga_beli_sekarang' => $currentPrice,
                'selisih' => $diff,
                'persen' => $pctChange,
                'tipe' => $diff > 0 ? 'naik' : 'turun',
            ]);
        }

        return $currentVsLastBought;
    }
}
