import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/sale_model.dart';
import '../../services/sale_service.dart';
import '../../utils/formatters.dart';
import '../../widgets/receipt_view.dart';
import 'sale_return_screen.dart';

final salesListProvider = FutureProvider.family<List<SaleModel>, Map<String, String?>>((ref, filters) async {
  final service = SaleService();
  return service.getSales(
    startDate: filters['start_date'],
    endDate: filters['end_date'],
    status: filters['status'],
    limit: 100,
  );
});

class SalesHistoryScreen extends ConsumerStatefulWidget {
  const SalesHistoryScreen({super.key});

  @override
  ConsumerState<SalesHistoryScreen> createState() => _SalesHistoryScreenState();
}

class _SalesHistoryScreenState extends ConsumerState<SalesHistoryScreen> {
  final _searchController = TextEditingController();
  String _search = '';
  String _selectedStatus = 'all';
  int _selectedDays = 30;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Map<String, String?> _buildFilters() {
    final now = DateTime.now();
    String? startDate;
    if (_selectedDays > 0) {
      startDate = DateFormat('yyyy-MM-dd').format(now.subtract(Duration(days: _selectedDays)));
    }
    return {
      'start_date': startDate,
      'end_date': DateFormat('yyyy-MM-dd').format(now),
      'status': _selectedStatus == 'all' ? null : _selectedStatus,
    };
  }

  void _showSaleDetail(SaleModel sale) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('Detail ${sale.invoiceNumber}'),
            _buildStatusBadge(sale.status),
          ],
        ),
        content: SizedBox(
          width: 500,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Kasir: ${sale.user?.name ?? 'Kasir'} • ${sale.createdAt != null ? DateFormat('dd MMM yyyy, HH:mm').format(sale.createdAt!) : ''}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey)),
              const Divider(height: 20),
              const Text('Item Pembelian:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 8),
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: sale.items.length,
                  itemBuilder: (context, i) {
                    final item = sale.items[i];
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item.product?.name ?? 'Produk (${item.productId})', style: const TextStyle(fontWeight: FontWeight.w600)),
                                Text('${item.qty}x @ ${AppFormatters.formatRupiah(item.harga)}${item.returnedQty > 0 ? ' (Diretur: ${item.returnedQty})' : ''}',
                                    style: const TextStyle(fontSize: 12, color: Colors.grey)),
                              ],
                            ),
                          ),
                          Text(AppFormatters.formatRupiah(item.subtotal), style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                    );
                  },
                ),
              ),
              const Divider(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Subtotal:'),
                  Text(AppFormatters.formatRupiah(sale.subtotal)),
                ],
              ),
              if (sale.diskon > 0)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Diskon:'),
                    Text('-${AppFormatters.formatRupiah(sale.diskon)}', style: const TextStyle(color: AppColors.error)),
                  ],
                ),
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Grand Total:', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(AppFormatters.formatRupiah(sale.grandTotal), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.primary)),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Metode Bayar (${sale.paymentMethod.toUpperCase()}):'),
                  Text('Bayar: ${AppFormatters.formatRupiah(sale.bayar)} • Kembali: ${AppFormatters.formatRupiah(sale.kembalian)}'),
                ],
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Tutup')),
          OutlinedButton.icon(
            icon: const Icon(Icons.print_rounded, size: 16),
            label: const Text('Cetak Ulang Struk'),
            onPressed: () {
              Navigator.pop(ctx);
              showDialog(
                context: context,
                builder: (_) => ReceiptDialog(sale: sale),
              );
            },
          ),
          if (sale.status != 'returned')
            ElevatedButton.icon(
              icon: const Icon(Icons.assignment_return_rounded, size: 16),
              label: const Text('Ajukan Retur'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.warning, foregroundColor: Colors.white),
              onPressed: () {
                Navigator.pop(ctx);
                _openReturnDialog(sale);
              },
            ),
        ],
      ),
    );
  }

  void _openReturnDialog(SaleModel sale) {
    showDialog(
      context: context,
      builder: (_) => ProcessReturnDialog(sale: sale),
    ).then((_) {
      ref.invalidate(salesListProvider);
    });
  }

  Widget _buildStatusBadge(String status) {
    Color bg = AppColors.success.withValues(alpha: 0.15);
    Color fg = AppColors.success;
    String label = 'Selesai';

    if (status == 'returned') {
      bg = AppColors.error.withValues(alpha: 0.15);
      fg = AppColors.error;
      label = 'Diretur Penuh';
    } else if (status == 'partial_return') {
      bg = AppColors.warning.withValues(alpha: 0.15);
      fg = AppColors.warning;
      label = 'Retur Sebagian';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: fg)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filters = _buildFilters();
    final salesAsync = ref.watch(salesListProvider(filters));
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Riwayat Penjualan', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                SizedBox(height: 4),
                Text('Semua transaksi kasir, detail invoice, cetak ulang struk, dan retur', style: TextStyle(color: Colors.grey, fontSize: 13)),
              ],
            ),

            const SizedBox(height: 20),

            // Filter bar
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Cari no. invoice atau kasir...',
                      prefixIcon: const Icon(Icons.search_rounded),
                      filled: true,
                      fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    ),
                    onChanged: (val) => setState(() => _search = val.trim().toLowerCase()),
                  ),
                ),
                const SizedBox(width: 12),
                DropdownButton<String>(
                  value: _selectedStatus,
                  items: const [
                    DropdownMenuItem(value: 'all', child: Text('Semua Status')),
                    DropdownMenuItem(value: 'completed', child: Text('Selesai')),
                    DropdownMenuItem(value: 'partial_return', child: Text('Retur Sebagian')),
                    DropdownMenuItem(value: 'returned', child: Text('Diretur')),
                  ],
                  onChanged: (val) => setState(() => _selectedStatus = val ?? 'all'),
                ),
                const SizedBox(width: 12),
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

            const SizedBox(height: 20),

            // Sales List
            Expanded(
              child: salesAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text('Gagal memuat transaksi: $e')),
                data: (sales) {
                  final filtered = sales.where((s) {
                    if (_search.isEmpty) return true;
                    return s.invoiceNumber.toLowerCase().contains(_search) ||
                        (s.user?.name.toLowerCase().contains(_search) ?? false);
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.receipt_long_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text('Belum ada transaksi pada rentang ini', style: TextStyle(color: Colors.grey, fontSize: 16)),
                        ],
                      ),
                    );
                  }

                  return Card(
                    clipBehavior: Clip.antiAlias,
                    child: ListView.separated(
                      itemCount: filtered.length,
                      separatorBuilder: (context, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final sale = filtered[index];
                        return ListTile(
                          onTap: () => _showSaleDetail(sale),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          leading: CircleAvatar(
                            backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                            child: const Icon(Icons.receipt_rounded, color: AppColors.primary),
                          ),
                          title: Row(
                            children: [
                              Text(sale.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(width: 10),
                              _buildStatusBadge(sale.status),
                            ],
                          ),
                          subtitle: Text(
                            'Kasir: ${sale.user?.name ?? 'Kasir'} • ${sale.items.length} item • ${sale.createdAt != null ? DateFormat('dd/MM/yyyy HH:mm').format(sale.createdAt!) : ''}',
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(AppFormatters.formatRupiah(sale.grandTotal), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primary)),
                                  Text(sale.paymentMethod.toUpperCase(), style: const TextStyle(fontSize: 11, color: Colors.grey)),
                                ],
                              ),
                              const SizedBox(width: 8),
                              const Icon(Icons.chevron_right_rounded, color: Colors.grey),
                            ],
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
