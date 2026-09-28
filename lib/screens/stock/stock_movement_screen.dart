import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/product_model.dart';
import '../../models/stock_movement_model.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../../services/stock_service.dart';

final filteredStockMovementsProvider =
    FutureProvider.family<List<StockMovementModel>, Map<String, String?>>((ref, filters) async {
  final service = StockService();
  return service.getStockMovements(
    productId: filters['product_id'],
    type: filters['type'],
    limit: 100,
  );
});

class StockMovementScreen extends ConsumerStatefulWidget {
  final bool initialLowStockTab;

  const StockMovementScreen({super.key, this.initialLowStockTab = false});

  @override
  ConsumerState<StockMovementScreen> createState() => _StockMovementScreenState();
}

class _StockMovementScreenState extends ConsumerState<StockMovementScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String? _selectedProductId;
  String _selectedType = 'all';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this, initialIndex: widget.initialLowStockTab ? 1 : 0);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _openAdjustmentDialog([ProductModel? presetProduct]) {
    final products = ref.read(productsProvider).value ?? [];
    String? selectedProductId = presetProduct?.id ?? (products.isNotEmpty ? products.first.id : null);
    final deltaQtyCtrl = TextEditingController(text: '0');
    final reasonCtrl = TextEditingController(text: 'Koreksi stok / Opname');
    bool isDelta = true; // true: +/- delta, false: target fisik

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) {
          final curProduct = products.firstWhere(
            (p) => p.id == selectedProductId,
            orElse: () => products.isNotEmpty
                ? products.first
                : const ProductModel(id: '', categoryId: '', name: '', sku: '', hargaBeli: 0, hargaJual: 0),
          );

          return AlertDialog(
            title: const Text('Penyesuaian Stok (Stock Adjustment)'),
            content: SizedBox(
              width: 460,
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
                  Row(
                    children: [
                      Expanded(
                        child: ChoiceChip(
                          label: const Text('Tambah / Kurang (+/-)'),
                          selected: isDelta,
                          onSelected: (val) => setDialogState(() => isDelta = true),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ChoiceChip(
                          label: const Text('Set Stok Fisik Baru'),
                          selected: !isDelta,
                          onSelected: (val) => setDialogState(() => isDelta = false),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: deltaQtyCtrl,
                    keyboardType: const TextInputType.numberWithOptions(signed: true),
                    decoration: InputDecoration(
                      labelText: isDelta ? 'Jumlah Penyesuaian (+ atau -) *' : 'Jumlah Stok Fisik Baru *',
                      hintText: isDelta ? 'Misal: 5 atau -3' : 'Misal: 25',
                      helperText: isDelta
                          ? 'Gunakan tanda minus (-) untuk pengurangan stok rusak/hilang.'
                          : 'Stok saat ini: ${curProduct.stok} ${curProduct.satuan}',
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: reasonCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Alasan Penyesuaian *',
                      hintText: 'Misal: Barang pecah, salah hitung, stok opname',
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
              ElevatedButton(
                onPressed: () async {
                  final user = ref.read(authProvider).value;
                  final val = int.tryParse(deltaQtyCtrl.text);

                  if (selectedProductId == null || val == null || reasonCtrl.text.trim().isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Lengkapi semua data penyesuaian!')),
                    );
                    return;
                  }

                  try {
                    final stockService = StockService();
                    if (isDelta) {
                      await stockService.adjustStockDelta(
                        productId: selectedProductId!,
                        deltaQty: val,
                        reason: reasonCtrl.text.trim(),
                        userId: user?.id ?? '',
                      );
                    } else {
                      await stockService.adjustStock(
                        productId: selectedProductId!,
                        newStock: val,
                        reason: reasonCtrl.text.trim(),
                        userId: user?.id ?? '',
                      );
                    }

                    if (ctx.mounted) Navigator.pop(ctx);
                    ref.invalidate(filteredStockMovementsProvider);
                    ref.invalidate(productsProvider);
                    ref.invalidate(lowStockProductsProvider);

                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Penyesuaian stok berhasil disimpan!')),
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
          );
        },
      ),
    );
  }

  Widget _buildTypeBadge(String type) {
    Color bg = Colors.grey.withValues(alpha: 0.15);
    Color fg = Colors.grey;
    String label = type.toUpperCase();

    switch (type) {
      case 'in':
        bg = AppColors.success.withValues(alpha: 0.15);
        fg = AppColors.success;
        label = 'MASUK';
        break;
      case 'out':
        bg = AppColors.primary.withValues(alpha: 0.15);
        fg = AppColors.primary;
        label = 'KELUAR';
        break;
      case 'return':
        bg = AppColors.warning.withValues(alpha: 0.15);
        fg = AppColors.warning;
        label = 'RETUR';
        break;
      case 'adjustment':
        bg = Colors.purple.withValues(alpha: 0.15);
        fg = Colors.purple;
        label = 'PENYESUAIAN';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8)),
      child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: fg)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filters = {
      'product_id': _selectedProductId,
      'type': _selectedType == 'all' ? null : _selectedType,
    };
    final movementsAsync = ref.watch(filteredStockMovementsProvider(filters));
    final lowStockAsync = ref.watch(lowStockProductsProvider);
    final products = ref.watch(productsProvider).value ?? [];
    final isLocked = ref.watch(isAttendanceGateLockedProvider);
    final user = ref.watch(authProvider).value;
    final canAdjust = user?.canManageInventory == true && !isLocked;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Manajemen Stok', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Kartu stok, riwayat mutasi masuk/keluar, dan alarm stok menipis',
                        style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                if (canAdjust)
                  ElevatedButton.icon(
                    onPressed: () => _openAdjustmentDialog(),
                    icon: const Icon(Icons.tune_rounded, size: 18),
                    label: const Text('Penyesuaian Stok (Adjust)'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                    ),
                  ),
              ],
            ),

            const SizedBox(height: 16),

            // Tabs: Kartu Stok & Stok Menipis
            TabBar(
              controller: _tabController,
              tabs: [
                const Tab(text: 'Kartu Stok & Riwayat Mutasi'),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('Stok Menipis'),
                      const SizedBox(width: 8),
                      lowStockAsync.when(
                        data: (list) => list.isNotEmpty
                            ? Container(
                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
                                decoration: BoxDecoration(
                                  color: AppColors.warning,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text('${list.length}', style: const TextStyle(fontSize: 11, color: Colors.white, fontWeight: FontWeight.bold)),
                              )
                            : const SizedBox.shrink(),
                        loading: () => const SizedBox.shrink(),
                        error: (_, _) => const SizedBox.shrink(),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  // Tab 1: Kartu Stok
                  Column(
                    children: [
                      // Filter bar
                      Row(
                        children: [
                          Expanded(
                            child: DropdownButtonFormField<String?>(
                              initialValue: _selectedProductId,
                              decoration: InputDecoration(
                                labelText: 'Pilih Produk (Kartu Stok)',
                                isDense: true,
                                filled: true,
                                fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                              ),
                              items: [
                                const DropdownMenuItem(value: null, child: Text('Semua Produk')),
                                ...products.map((p) => DropdownMenuItem(value: p.id, child: Text(p.name))),
                              ],
                              onChanged: (val) => setState(() => _selectedProductId = val),
                            ),
                          ),
                          const SizedBox(width: 12),
                          DropdownButton<String>(
                            value: _selectedType,
                            items: const [
                              DropdownMenuItem(value: 'all', child: Text('Semua Tipe')),
                              DropdownMenuItem(value: 'in', child: Text('Masuk (Restock)')),
                              DropdownMenuItem(value: 'out', child: Text('Keluar (Penjualan)')),
                              DropdownMenuItem(value: 'return', child: Text('Retur')),
                              DropdownMenuItem(value: 'adjustment', child: Text('Penyesuaian')),
                            ],
                            onChanged: (val) => setState(() => _selectedType = val ?? 'all'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Expanded(
                        child: movementsAsync.when(
                          loading: () => const Center(child: CircularProgressIndicator()),
                          error: (e, _) => Center(child: Text('Gagal memuat mutasi stok: $e')),
                          data: (movements) {
                            if (movements.isEmpty) {
                              return Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.swap_horiz_rounded, size: 64, color: Colors.grey.shade400),
                                    const SizedBox(height: 12),
                                    const Text('Belum ada riwayat mutasi stok', style: TextStyle(color: Colors.grey, fontSize: 16)),
                                  ],
                                ),
                              );
                            }

                            return Card(
                              clipBehavior: Clip.antiAlias,
                              child: ListView.separated(
                                itemCount: movements.length,
                                separatorBuilder: (context, _) => const Divider(height: 1),
                                itemBuilder: (context, index) {
                                  final m = movements[index];
                                  final isPositive = m.stokSesudah >= m.stokSebelum;

                                  return ListTile(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    leading: CircleAvatar(
                                      backgroundColor: isPositive
                                          ? AppColors.success.withValues(alpha: 0.15)
                                          : AppColors.error.withValues(alpha: 0.15),
                                      child: Icon(
                                        isPositive ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                                        color: isPositive ? AppColors.success : AppColors.error,
                                      ),
                                    ),
                                    title: Row(
                                      children: [
                                        Text(m.product?.name ?? 'Produk (${m.productId})', style: const TextStyle(fontWeight: FontWeight.bold)),
                                        const SizedBox(width: 8),
                                        _buildTypeBadge(m.type),
                                      ],
                                    ),
                                    subtitle: Text(
                                      '${m.keterangan ?? '-'} • ${m.createdAt != null ? DateFormat('dd MMM yyyy, HH:mm').format(m.createdAt!) : ''}',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                    trailing: Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Text(
                                          '${isPositive ? '+' : '-'}${m.qty} pcs',
                                          style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 15,
                                            color: isPositive ? AppColors.success : AppColors.error,
                                          ),
                                        ),
                                        Text(
                                          'Stok: ${m.stokSebelum} → ${m.stokSesudah}',
                                          style: const TextStyle(fontSize: 11, color: Colors.grey),
                                        ),
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

                  // Tab 2: Stok Menipis
                  lowStockAsync.when(
                    loading: () => const Center(child: CircularProgressIndicator()),
                    error: (e, _) => Center(child: Text('Gagal: $e')),
                    data: (lowStockList) {
                      if (lowStockList.isEmpty) {
                        return Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.check_circle_outline_rounded, size: 64, color: AppColors.success),
                              const SizedBox(height: 12),
                              const Text('Semua stok produk dalam kondisi aman!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                              const SizedBox(height: 4),
                              const Text('Tidak ada produk yang berada di bawah batas minimum.', style: TextStyle(color: Colors.grey, fontSize: 13)),
                            ],
                          ),
                        );
                      }

                      return Card(
                        clipBehavior: Clip.antiAlias,
                        child: ListView.separated(
                          itemCount: lowStockList.length,
                          separatorBuilder: (context, _) => const Divider(height: 1),
                          itemBuilder: (context, index) {
                            final p = lowStockList[index];
                            return ListTile(
                              leading: const CircleAvatar(
                                backgroundColor: Colors.amber,
                                child: Icon(Icons.warning_amber_rounded, color: Colors.white),
                              ),
                              title: Text(p.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: Text('SKU: ${p.sku} • Batas Min: ${p.minStok} ${p.satuan}'),
                              trailing: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Text('${p.stok} ${p.satuan}',
                                          style: const TextStyle(color: AppColors.error, fontWeight: FontWeight.bold, fontSize: 16)),
                                      const Text('Sisa Stok', style: TextStyle(fontSize: 11, color: Colors.grey)),
                                    ],
                                  ),
                                  const SizedBox(width: 12),
                                  if (canAdjust)
                                    ElevatedButton(
                                      onPressed: () => _openAdjustmentDialog(p),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppColors.primary,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                      ),
                                      child: const Text('Adjust'),
                                    ),
                                ],
                              ),
                            );
                          },
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
