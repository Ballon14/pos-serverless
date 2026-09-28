import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/product_model.dart';
import '../../models/sale_model.dart';
import '../../models/stock_movement_model.dart';
import '../../providers/product_provider.dart';
import '../../services/sale_service.dart';
import '../../services/stock_service.dart';
import '../../utils/formatters.dart';

final reportSalesProvider = FutureProvider.family<List<SaleModel>, int>((ref, days) async {
  final service = SaleService();
  final now = DateTime.now();
  String? startDate;
  if (days > 0) {
    startDate = DateFormat('yyyy-MM-dd').format(now.subtract(Duration(days: days)));
  }
  final endDate = DateFormat('yyyy-MM-dd').format(now);
  return service.getSales(startDate: startDate, endDate: endDate, limit: 200);
});

final reportStockMovementsProvider = FutureProvider.family<List<StockMovementModel>, int>((ref, days) async {
  final service = StockService();
  return service.getStockMovements(limit: 200);
});

class ReportScreen extends ConsumerStatefulWidget {
  const ReportScreen({super.key});

  @override
  ConsumerState<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends ConsumerState<ReportScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int _selectedDays = 30;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final salesAsync = ref.watch(reportSalesProvider(_selectedDays));
    final stockMovementsAsync = ref.watch(reportStockMovementsProvider(_selectedDays));
    final products = ref.watch(productsProvider).value ?? [];
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Pusat Laporan & Analitik', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Laporan Penjualan, Laba Rugi, Mutasi Stok, dan Perubahan Harga Beli',
                        style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                SegmentedButton<int>(
                  segments: const [
                    ButtonSegment(value: 0, label: Text('Hari Ini')),
                    ButtonSegment(value: 7, label: Text('7 Hari')),
                    ButtonSegment(value: 30, label: Text('30 Hari')),
                  ],
                  selected: {_selectedDays},
                  onSelectionChanged: (set) => setState(() => _selectedDays = set.first),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Tab Bar
            TabBar(
              controller: _tabController,
              isScrollable: true,
              tabAlignment: TabAlignment.start,
              tabs: const [
                Tab(icon: Icon(Icons.receipt_long_rounded), text: 'Laporan Penjualan'),
                Tab(icon: Icon(Icons.account_balance_wallet_rounded), text: 'Laba Rugi (P&L)'),
                Tab(icon: Icon(Icons.swap_horiz_rounded), text: 'Mutasi Stok'),
                Tab(icon: Icon(Icons.price_change_rounded), text: 'Perubahan Harga'),
              ],
            ),

            const SizedBox(height: 16),

            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  // Tab 1: Laporan Penjualan
                  _buildSalesReportView(salesAsync, isDark),

                  // Tab 2: Laba Rugi
                  _buildProfitLossReportView(salesAsync, isDark),

                  // Tab 3: Mutasi Stok
                  _buildStockReportView(stockMovementsAsync),

                  // Tab 4: Perubahan Harga
                  _buildPriceChangeReportView(products),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSalesReportView(AsyncValue<List<SaleModel>> salesAsync, bool isDark) {
    return salesAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Gagal memuat laporan: $e')),
      data: (sales) {
        final totalRevenue = sales.fold(0.0, (sum, s) => sum + s.grandTotal);
        final totalCount = sales.length;
        final avgBasket = totalCount > 0 ? totalRevenue / totalCount : 0.0;
        final totalDiscount = sales.fold(0.0, (sum, s) => sum + s.diskon);

        return ListView(
          children: [
            // KPI Summary (Responsive Grid)
            LayoutBuilder(
              builder: (context, constraints) {
                final isWide = constraints.maxWidth >= 900;
                final crossAxisCount = isWide ? 4 : 2;
                return GridView.count(
                  crossAxisCount: crossAxisCount,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: isWide ? 2.2 : 1.9,
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Total Omzet / Penjualan', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text(AppFormatters.formatRupiah(totalRevenue),
                                  style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.success)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Jumlah Transaksi', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text('$totalCount Struk', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Rata-rata Transaksi', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text(AppFormatters.formatRupiah(avgBasket), style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Total Diskon', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text(AppFormatters.formatRupiah(totalDiscount),
                                  style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.error)),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),

            const SizedBox(height: 16),

            // Table of sales
            Card(
              clipBehavior: Clip.antiAlias,
              child: ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: sales.length,
                separatorBuilder: (context, _) => const Divider(height: 1),
                itemBuilder: (context, i) {
                  final s = sales[i];
                  return ListTile(
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(Icons.receipt_rounded, color: AppColors.primary, size: 20),
                    ),
                    title: Text(s.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('Kasir: ${s.user?.name ?? 'Kasir'} • ${s.items.length} item • ${s.paymentMethod.toUpperCase()}'),
                    trailing: Text(AppFormatters.formatRupiah(s.grandTotal),
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primary)),
                  );
                },
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildProfitLossReportView(AsyncValue<List<SaleModel>> salesAsync, bool isDark) {
    return salesAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Gagal: $e')),
      data: (sales) {
        double grossRevenue = 0.0;
        double totalCogs = 0.0; // HPP (Harga Pokok Penjualan)
        double totalDiskon = 0.0;

        for (final sale in sales) {
          grossRevenue += sale.subtotal;
          totalDiskon += sale.diskon;

          for (final item in sale.items) {
            final effectiveQty = item.qty - item.returnedQty;
            totalCogs += effectiveQty * item.hargaBeli;
          }
        }

        final netSales = grossRevenue - totalDiskon;
        final grossProfit = netSales - totalCogs;
        final marginPercent = netSales > 0 ? (grossProfit / netSales) * 100 : 0.0;

        return ListView(
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Ringkasan Laba Rugi (Profit & Loss Statement)',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    const Text('Perhitungan laba kotor berdasarkan omzet, diskon, dan Harga Pokok Penjualan (HPP)',
                        style: TextStyle(fontSize: 12, color: Colors.grey)),
                    const Divider(height: 32),

                    // Gross Revenue
                    _buildPlRow('Pendapatan Penjualan Kotor (Gross Revenue)', AppFormatters.formatRupiah(grossRevenue), false),
                    const SizedBox(height: 12),

                    // Diskon
                    _buildPlRow('Potongan Penjualan / Diskon (-)', '- ${AppFormatters.formatRupiah(totalDiskon)}', false, color: AppColors.error),
                    const Divider(height: 24),

                    // Net Revenue
                    _buildPlRow('Pendapatan Penjualan Bersih (Net Sales)', AppFormatters.formatRupiah(netSales), true),
                    const SizedBox(height: 16),

                    // COGS / HPP
                    _buildPlRow('Harga Pokok Penjualan (HPP / COGS) (-)', '- ${AppFormatters.formatRupiah(totalCogs)}', false, color: Colors.orange.shade800),
                    const Divider(height: 32),

                    // Gross Profit
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Laba Kotor (Gross Profit)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                            Text('Margin: ${marginPercent.toStringAsFixed(1)}%',
                                style: TextStyle(fontSize: 13, color: grossProfit >= 0 ? AppColors.success : AppColors.error, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        Text(
                          AppFormatters.formatRupiah(grossProfit),
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: grossProfit >= 0 ? AppColors.success : AppColors.error,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPlRow(String label, String value, bool isBold, {Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: isBold ? 15 : 14),
            overflow: TextOverflow.ellipsis,
          ),
        ),
        const SizedBox(width: 8),
        Text(value, style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.w600, fontSize: isBold ? 16 : 14, color: color)),
      ],
    );
  }

  Widget _buildStockReportView(AsyncValue<List<StockMovementModel>> movementsAsync) {
    return movementsAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Gagal: $e')),
      data: (movements) {
        int totalIn = 0;
        int totalOut = 0;
        int totalAdjust = 0;
        int totalReturn = 0;

        for (final m in movements) {
          if (m.type == 'in') totalIn += m.qty;
          if (m.type == 'out') totalOut += m.qty;
          if (m.type == 'adjustment') totalAdjust += m.qty;
          if (m.type == 'return') totalReturn += m.qty;
        }

        return ListView(
          children: [
            // KPI Stock Summary (Responsive Grid)
            LayoutBuilder(
              builder: (context, constraints) {
                final isWide = constraints.maxWidth >= 900;
                final crossAxisCount = isWide ? 4 : 2;
                return GridView.count(
                  crossAxisCount: crossAxisCount,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: isWide ? 2.2 : 1.9,
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Total Masuk (Restock)', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text('+$totalIn pcs', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.success)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Total Keluar (Terjual)', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text('-$totalOut pcs', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.primary)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Total Retur', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text('+$totalReturn pcs', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: AppColors.warning)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('Penyesuaian (Opname)', style: TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 4),
                            FittedBox(
                              alignment: Alignment.centerLeft,
                              fit: BoxFit.scaleDown,
                              child: Text('$totalAdjust pcs', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Colors.purple)),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            Card(
              clipBehavior: Clip.antiAlias,
              child: ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: movements.length,
                separatorBuilder: (context, _) => const Divider(height: 1),
                itemBuilder: (context, i) {
                  final m = movements[i];
                  return ListTile(
                    title: Text(m.product?.name ?? 'Produk (${m.productId})', style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('${m.keterangan ?? '-'} • ${m.type.toUpperCase()}'),
                    trailing: Text('Stok: ${m.stokSebelum} → ${m.stokSesudah}', style: const TextStyle(fontWeight: FontWeight.bold)),
                  );
                },
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPriceChangeReportView(List<ProductModel> products) {
    return ListView(
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                const Icon(Icons.info_outline, color: AppColors.primary),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Laporan ini melacak selisih antara Harga Beli (HPP) dan Harga Jual produk untuk mengawasi margin keuntungan toko.',
                    style: TextStyle(fontSize: 13),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        Card(
          clipBehavior: Clip.antiAlias,
          child: ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: products.length,
            separatorBuilder: (context, _) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final p = products[i];
              final margin = p.hargaJual - p.hargaBeli;
              final marginPct = p.hargaBeli > 0 ? (margin / p.hargaBeli) * 100 : 0.0;

              return ListTile(
                title: Text(p.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                subtitle: Text('SKU: ${p.sku} • Kategori: ${p.category?.name ?? '-'}'),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('Beli: ${AppFormatters.formatRupiah(p.hargaBeli)} • Jual: ${AppFormatters.formatRupiah(p.hargaJual)}',
                            style: const TextStyle(fontSize: 12)),
                        Text('Margin: +${AppFormatters.formatRupiah(margin)} (${marginPct.toStringAsFixed(1)}%)',
                            style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.success, fontSize: 13)),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
