import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/theme.dart';
import '../../models/stock_movement_model.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../../services/stock_service.dart';
import '../../utils/formatters.dart';

final stockMovementsProvider = FutureProvider.autoDispose<List<StockMovementModel>>((ref) async {
  final service = StockService();
  return service.getStockMovements(limit: 100);
});

class StockMovementScreen extends ConsumerStatefulWidget {
  const StockMovementScreen({super.key});

  @override
  ConsumerState<StockMovementScreen> createState() => _StockMovementScreenState();
}

class _StockMovementScreenState extends ConsumerState<StockMovementScreen> {
  void _openAdjustmentDialog() {
    final products = ref.read(productsProvider).value ?? [];
    String? selectedProductId = products.isNotEmpty ? products.first.id : null;
    final newStockCtrl = TextEditingController();
    final reasonCtrl = TextEditingController(text: 'Stock Opname');

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Penyesuaian Stok (Stock Opname)'),
          content: SizedBox(
            width: 420,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                DropdownButtonFormField<String>(
                  initialValue: selectedProductId,
                  decoration: const InputDecoration(labelText: 'Pilih Produk *'),
                  items: products.map((p) {
                    return DropdownMenuItem(
                      value: p.id,
                      child: Text('${p.name} (Stok: ${p.stok} ${p.satuan})'),
                    );
                  }).toList(),
                  onChanged: (val) => setDialogState(() => selectedProductId = val),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: newStockCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Stok Fisik Baru (Hasil Hitung) *'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: reasonCtrl,
                  decoration: const InputDecoration(labelText: 'Alasan Penyesuaian *'),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () async {
                final user = ref.read(authProvider).value;
                final newStock = int.tryParse(newStockCtrl.text);

                if (selectedProductId == null || newStock == null || reasonCtrl.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Lengkapi semua data penyesuaian!')),
                  );
                  return;
                }

                try {
                  final stockService = StockService();
                  await stockService.adjustStock(
                    productId: selectedProductId!,
                    newStock: newStock,
                    reason: reasonCtrl.text.trim(),
                    userId: user?.id ?? '',
                  );

                  if (ctx.mounted) Navigator.pop(ctx);
                  ref.invalidate(stockMovementsProvider);
                  ref.invalidate(productsProvider);
                  ref.invalidate(lowStockProductsProvider);

                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Stok berhasil diperbarui!')),
                    );
                  }
                } catch (e) {
                  if (ctx.mounted) {
                    ScaffoldMessenger.of(ctx).showSnackBar(
                      SnackBar(content: Text('Gagal: $e')),
                    );
                  }
                }
              },
              child: const Text('Simpan Penyesuaian'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final movementsAsync = ref.watch(stockMovementsProvider);
    final isLocked = ref.watch(isAttendanceGateLockedProvider);
    final user = ref.watch(authProvider).value;
    final canAdjust = user?.canManageInventory == true && !isLocked;

    return Scaffold(
      body: Padding(
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
                    Text('Mutasi Stok', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Riwayat keluar masuk barang dan penyesuaian stok', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                ElevatedButton.icon(
                  onPressed: canAdjust ? _openAdjustmentDialog : null,
                  icon: const Icon(Icons.tune_rounded, size: 18),
                  label: const Text('Penyesuaian Stok'),
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                ),
              ],
            ),

            const SizedBox(height: 20),

            Expanded(
              child: Card(
                child: movementsAsync.when(
                  data: (movements) {
                    if (movements.isEmpty) {
                      return const Center(child: Text('Belum ada riwayat mutasi stok.'));
                    }

                    return SingleChildScrollView(
                      child: SizedBox(
                        width: double.infinity,
                        child: DataTable(
                          columns: const [
                            DataColumn(label: Text('Waktu', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Produk', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Tipe', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Jumlah', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Sebelum -> Sesudah', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Keterangan', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Petugas', style: TextStyle(fontWeight: FontWeight.bold))),
                          ],
                          rows: movements.map((m) {
                            final Color badgeColor;
                            final String typeLabel;
                            if (m.isIn) {
                              badgeColor = AppColors.success;
                              typeLabel = 'MASUK';
                            } else if (m.isOut) {
                              badgeColor = AppColors.error;
                              typeLabel = 'KELUAR';
                            } else if (m.isReturn) {
                              badgeColor = AppColors.secondary;
                              typeLabel = 'RETUR';
                            } else {
                              badgeColor = AppColors.warning;
                              typeLabel = 'PENYESUAIAN';
                            }

                            return DataRow(
                              cells: [
                                DataCell(Text(AppFormatters.formatDateTime(m.createdAt))),
                                DataCell(Text(m.product?.name ?? '-')),
                                DataCell(
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: badgeColor.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      typeLabel,
                                      style: TextStyle(color: badgeColor, fontSize: 11, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ),
                                DataCell(
                                  Text(
                                    '${m.isOut ? "-" : "+"}${m.qty}',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: m.isOut ? AppColors.error : AppColors.success,
                                    ),
                                  ),
                                ),
                                DataCell(Text('${m.stokSebelum} → ${m.stokSesudah}')),
                                DataCell(Text(m.keterangan ?? '-')),
                                DataCell(Text(m.user?.name ?? '-')),
                              ],
                            );
                          }).toList(),
                        ),
                      ),
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(child: Text('Gagal memuat mutasi: $err')),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
