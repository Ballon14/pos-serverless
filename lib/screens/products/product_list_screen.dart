import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/theme.dart';
import '../../models/product_model.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../../utils/formatters.dart';

class ProductListScreen extends ConsumerStatefulWidget {
  const ProductListScreen({super.key});

  @override
  ConsumerState<ProductListScreen> createState() => _ProductListScreenState();
}

class _ProductListScreenState extends ConsumerState<ProductListScreen> {
  final _searchController = TextEditingController();
  String? _selectedCategory;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openProductFormDialog([ProductModel? existing]) {
    final nameCtrl = TextEditingController(text: existing?.name ?? '');
    final skuCtrl = TextEditingController(text: existing?.sku ?? '');
    final buyPriceCtrl = TextEditingController(text: existing?.hargaBeli.toInt().toString() ?? '0');
    final sellPriceCtrl = TextEditingController(text: existing?.hargaJual.toInt().toString() ?? '0');
    final stockCtrl = TextEditingController(text: existing?.stok.toString() ?? '0');
    final minStockCtrl = TextEditingController(text: existing?.minStok.toString() ?? '5');
    final unitCtrl = TextEditingController(text: existing?.satuan ?? 'pcs');
    String? categoryId = existing?.categoryId;

    final categories = ref.read(categoriesProvider).value ?? [];
    if (categoryId == null && categories.isNotEmpty) {
      categoryId = categories.first.id;
    }

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(existing == null ? 'Tambah Produk Baru' : 'Edit Produk'),
          content: SingleChildScrollView(
            child: SizedBox(
              width: 480,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(labelText: 'Nama Produk *'),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: skuCtrl,
                          decoration: InputDecoration(
                            labelText: 'SKU / Barcode *',
                            suffixIcon: IconButton(
                              icon: const Icon(Icons.auto_awesome_rounded, size: 18),
                              tooltip: 'Generate SKU Otomatis',
                              onPressed: () async {
                                if (categoryId != null) {
                                  final newSku = await ref.read(productServiceProvider).generateSku(categoryId!);
                                  skuCtrl.text = newSku;
                                }
                              },
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          initialValue: categoryId,
                          decoration: const InputDecoration(labelText: 'Kategori'),
                          items: categories.map((c) {
                            return DropdownMenuItem(value: c.id, child: Text(c.name));
                          }).toList(),
                          onChanged: (val) => setDialogState(() => categoryId = val),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: buyPriceCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'Harga Beli (Rp)'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: sellPriceCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'Harga Jual (Rp) *'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: stockCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'Stok'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: minStockCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'Batas Min. Stok'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: unitCtrl,
                          decoration: const InputDecoration(labelText: 'Satuan'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () async {
                if (nameCtrl.text.trim().isEmpty || skuCtrl.text.trim().isEmpty || categoryId == null) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Nama, SKU, dan Kategori wajib diisi!')),
                  );
                  return;
                }

                final productService = ref.read(productServiceProvider);
                final newProduct = ProductModel(
                  id: existing?.id ?? '',
                  categoryId: categoryId!,
                  name: nameCtrl.text.trim(),
                  sku: skuCtrl.text.trim(),
                  hargaBeli: double.tryParse(buyPriceCtrl.text) ?? 0.0,
                  hargaJual: double.tryParse(sellPriceCtrl.text) ?? 0.0,
                  stok: int.tryParse(stockCtrl.text) ?? 0,
                  minStok: int.tryParse(minStockCtrl.text) ?? 5,
                  satuan: unitCtrl.text.trim().isEmpty ? 'pcs' : unitCtrl.text.trim(),
                );

                try {
                  if (existing == null) {
                    await productService.createProduct(newProduct);
                  } else {
                    await productService.updateProduct(newProduct);
                  }
                  if (ctx.mounted) Navigator.pop(ctx);
                  ref.invalidate(productsProvider);
                  ref.invalidate(lowStockProductsProvider);
                } catch (e) {
                  if (ctx.mounted) {
                    ScaffoldMessenger.of(ctx).showSnackBar(
                      SnackBar(content: Text('Gagal menyimpan: $e')),
                    );
                  }
                }
              },
              child: const Text('Simpan'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final productsAsync = ref.watch(productsProvider);
    final categoriesAsync = ref.watch(categoriesProvider);
    final isLocked = ref.watch(isAttendanceGateLockedProvider);
    final user = ref.watch(authProvider).value;
    final canEdit = user?.canManageInventory == true && !isLocked;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Manajemen Produk',
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                    ),
                    SizedBox(height: 4),
                    Text('Daftar katalog barang, harga jual, dan stok', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                ElevatedButton.icon(
                  onPressed: canEdit ? () => _openProductFormDialog() : null,
                  icon: const Icon(Icons.add_rounded, size: 18),
                  label: const Text('Tambah Produk'),
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Filter Bar
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: const InputDecoration(
                      hintText: 'Cari produk berdasarkan nama atau SKU...',
                      prefixIcon: Icon(Icons.search_rounded, size: 20),
                    ),
                    onChanged: (val) {
                      ref.read(productFilterProvider.notifier).updateSearch(val);
                    },
                  ),
                ),
                const SizedBox(width: 14),
                categoriesAsync.when(
                  data: (cats) => Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: Theme.of(context).inputDecorationTheme.fillColor,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Theme.of(context).dividerColor),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String?>(
                        value: _selectedCategory,
                        hint: const Text('Semua Kategori', style: TextStyle(fontSize: 13)),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('Semua Kategori')),
                          ...cats.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                        ],
                        onChanged: (val) {
                          setState(() => _selectedCategory = val);
                          ref.read(productFilterProvider.notifier).updateCategory(val);
                        },
                      ),
                    ),
                  ),
                  loading: () => const SizedBox.shrink(),
                  error: (_, _) => const SizedBox.shrink(),
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Products Table Card
            Expanded(
              child: Card(
                child: productsAsync.when(
                  data: (products) {
                    if (products.isEmpty) {
                      return const Center(child: Text('Tidak ada produk yang terdaftar.'));
                    }

                    return SingleChildScrollView(
                      scrollDirection: Axis.vertical,
                      child: SizedBox(
                        width: double.infinity,
                        child: DataTable(
                          columns: const [
                            DataColumn(label: Text('Nama Produk', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('SKU', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Kategori', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Harga Beli', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Harga Jual', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Stok', style: TextStyle(fontWeight: FontWeight.bold))),
                            DataColumn(label: Text('Aksi', style: TextStyle(fontWeight: FontWeight.bold))),
                          ],
                          rows: products.map((p) {
                            return DataRow(
                              cells: [
                                DataCell(Text(p.name, style: const TextStyle(fontWeight: FontWeight.w600))),
                                DataCell(Text(p.sku)),
                                DataCell(Text(p.category?.name ?? '-')),
                                DataCell(Text(AppFormatters.formatRupiah(p.hargaBeli))),
                                DataCell(Text(AppFormatters.formatRupiah(p.hargaJual), style: const TextStyle(fontWeight: FontWeight.w600))),
                                DataCell(
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: (p.isLowStock ? AppColors.warning : AppColors.success).withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      '${p.stok} ${p.satuan}',
                                      style: TextStyle(
                                        color: p.isLowStock ? AppColors.warning : AppColors.success,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ),
                                DataCell(
                                  IconButton(
                                    icon: const Icon(Icons.edit_rounded, size: 18, color: AppColors.primary),
                                    onPressed: canEdit ? () => _openProductFormDialog(p) : null,
                                  ),
                                ),
                              ],
                            );
                          }).toList(),
                        ),
                      ),
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(child: Text('Gagal memuat: $err')),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
