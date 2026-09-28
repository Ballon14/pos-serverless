import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/sale_model.dart';
import '../../services/sale_service.dart';
import '../../utils/formatters.dart';

final salesHistoryProvider = FutureProvider.family<List<SaleModel>, int>((ref, days) async {
  final service = SaleService();
  final now = DateTime.now();
  final startDate = DateFormat('yyyy-MM-dd').format(now.subtract(Duration(days: days)));
  final endDate = DateFormat('yyyy-MM-dd').format(now);
  return service.getSales(startDate: startDate, endDate: endDate, limit: 100);
});

class ReportScreen extends ConsumerStatefulWidget {
  const ReportScreen({super.key});

  @override
  ConsumerState<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends ConsumerState<ReportScreen> {
  int _selectedDays = 7;

  @override
  Widget build(BuildContext context) {
    final salesAsync = ref.watch(salesHistoryProvider(_selectedDays));
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Laporan Penjualan', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Ringkasan transaksi dan pendapatan kasir', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                SegmentedButton<int>(
                  segments: const [
                    ButtonSegment(value: 0, label: Text('Hari Ini')),
                    ButtonSegment(value: 7, label: Text('7 Hari')),
                    ButtonSegment(value: 30, label: Text('30 Hari')),
                  ],
                  selected: {_selectedDays},
                  onSelectionChanged: (set) {
                    setState(() {
                      _selectedDays = set.first;
                    });
                  },
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Metrics Summary Cards
            salesAsync.when(
              data: (sales) {
                final totalRevenue = sales.fold(0.0, (sum, s) => sum + s.grandTotal);
                final totalCount = sales.length;
                final avgRevenue = totalCount > 0 ? totalRevenue / totalCount : 0.0;

                return Row(
                  children: [
                    Expanded(
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Total Pendapatan', style: TextStyle(color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary, fontSize: 13)),
                              const SizedBox(height: 6),
                              Text(AppFormatters.formatRupiah(totalRevenue), style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.success)),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Jumlah Transaksi', style: TextStyle(color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary, fontSize: 13)),
                              const SizedBox(height: 6),
                              Text('$totalCount Transaksi', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.primary)),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Rata-rata Transaksi', style: TextStyle(color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary, fontSize: 13)),
                              const SizedBox(height: 6),
                              Text(AppFormatters.formatRupiah(avgRevenue), style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.secondary)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              },
              loading: () => const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator())),
              error: (err, _) => Text('Error: $err'),
            ),

            const SizedBox(height: 28),

            const Text('Daftar Transaksi', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),

            // Transactions Table
            Card(
              child: salesAsync.when(
                data: (sales) {
                  if (sales.isEmpty) {
                    return const Padding(
                      padding: EdgeInsets.all(32),
                      child: Center(child: Text('Tidak ada riwayat transaksi pada rentang ini.')),
                    );
                  }

                  return SizedBox(
                    width: double.infinity,
                    child: DataTable(
                      columns: const [
                        DataColumn(label: Text('Invoice', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Waktu', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Kasir', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Metode', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Total', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Status', style: TextStyle(fontWeight: FontWeight.bold))),
                      ],
                      rows: sales.map((sale) {
                        return DataRow(
                          cells: [
                            DataCell(Text(sale.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.w600, color: AppColors.primary))),
                            DataCell(Text(AppFormatters.formatDateTime(sale.createdAt))),
                            DataCell(Text(sale.user?.name ?? '-')),
                            DataCell(Text(sale.paymentMethod.toUpperCase())),
                            DataCell(Text(AppFormatters.formatRupiah(sale.grandTotal), style: const TextStyle(fontWeight: FontWeight.bold))),
                            DataCell(
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppColors.success.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  sale.status.toUpperCase(),
                                  style: const TextStyle(color: AppColors.success, fontSize: 11, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ),
                          ],
                        );
                      }).toList(),
                    ),
                  );
                },
                loading: () => const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator())),
                error: (err, _) => Center(child: Text('Gagal memuat: $err')),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
