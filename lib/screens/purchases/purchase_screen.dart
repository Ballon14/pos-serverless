import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/product_model.dart';
import '../../models/purchase_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../../providers/transaction_provider.dart';
import '../../utils/formatters.dart';

class PurchaseScreen extends ConsumerStatefulWidget {
  const PurchaseScreen({super.key});

  @override
  ConsumerState<PurchaseScreen> createState() => _PurchaseScreenState();
}

class _PurchaseScreenState extends ConsumerState<PurchaseScreen> {
  final _searchController = TextEditingController();
  String _search = '';
  String? _selectedSupplierId;
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
      'supplier_id': _selectedSupplierId,
    };
  }

  void _showPurchaseDetail(PurchaseModel purchase) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Detail ${purchase.invoiceNumber}'),
        content: SizedBox(
          width: 500,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Supplier: ${purchase.supplier?.name ?? 'Supplier'} • Tanggal: ${purchase.tanggal}',
                  style: const TextStyle(fontWeight: FontWeight.bold)),
              Text('Dicatat oleh: ${purchase.user?.name ?? 'Admin'} • Status: ${purchase.status.toUpperCase()}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey)),
              if (purchase.keterangan != null && purchase.keterangan!.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text('Catatan: ${purchase.keterangan}', style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic)),
              ],
              const Divider(height: 20),
              const Text('Daftar Barang Masuk (Restock):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 8),
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: purchase.items.length,
                  itemBuilder: (context, i) {
                    final item = purchase.items[i];
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item.product?.name ?? 'Produk (${item.productId})', style: const TextStyle(fontWeight: FontWeight.w600)),
                                Text('${item.qty} pcs @ ${AppFormatters.formatRupiah(item.harga)}',
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
                  const Text('Total Pembelian:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  Text(AppFormatters.formatRupiah(purchase.total),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.primary)),
                ],
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Tutup')),
        ],
      ),
    );
  }

  void _openCreatePurchaseDialog() {
    showDialog(
      context: context,
      builder: (_) => const CreatePurchaseDialog(),
    ).then((_) {
      ref.invalidate(purchasesProvider);
      ref.invalidate(productsProvider);
      ref.invalidate(lowStockProductsProvider);
    });
  }

  @override
  Widget build(BuildContext context) {
    final filters = _buildFilters();
    final purchasesAsync = ref.watch(purchasesProvider(filters));
    final suppliers = ref.watch(suppliersProvider).value ?? [];
    final user = ref.watch(authProvider).value;
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
                    Text('Pembelian (Restock)', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Pencatatan faktur pembelian barang dari supplier & penambahan stok otomatis',
                        style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                if (user?.isAdmin == true)
                  ElevatedButton.icon(
                    onPressed: _openCreatePurchaseDialog,
                    icon: const Icon(Icons.add_shopping_cart_rounded, size: 18),
                    label: const Text('Catat Pembelian Baru'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                    ),
                  ),
              ],
            ),

            const SizedBox(height: 20),

            // Filters
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Cari no. faktur / supplier...',
                      prefixIcon: const Icon(Icons.search_rounded),
                      filled: true,
                      fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    ),
                    onChanged: (val) => setState(() => _search = val.trim().toLowerCase()),
                  ),
                ),
                const SizedBox(width: 12),
                DropdownButton<String>(
                  value: _selectedSupplierId,
                  hint: const Text('Semua Supplier'),
                  items: [
                    const DropdownMenuItem(value: null, child: Text('Semua Supplier')),
                    ...suppliers.map((s) => DropdownMenuItem(value: s.id, child: Text(s.name))),
                  ],
                  onChanged: (val) => setState(() => _selectedSupplierId = val),
                ),
                const SizedBox(width: 12),
                SegmentedButton<int>(
                  segments: const [
                    ButtonSegment(value: 7, label: Text('7 Hari')),
                    ButtonSegment(value: 30, label: Text('30 Hari')),
                    ButtonSegment(value: 0, label: Text('Semua')),
                  ],
                  selected: {_selectedDays},
                  onSelectionChanged: (set) => setState(() => _selectedDays = set.first),
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Purchases List
            Expanded(
              child: purchasesAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text('Gagal memuat pembelian: $e')),
                data: (purchases) {
                  final filtered = purchases.where((p) {
                    if (_search.isEmpty) return true;
                    return p.invoiceNumber.toLowerCase().contains(_search) ||
                        (p.supplier?.name.toLowerCase().contains(_search) ?? false);
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.shopping_bag_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text('Belum ada data pembelian barang', style: TextStyle(color: Colors.grey, fontSize: 16)),
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
                        final purchase = filtered[index];
                        return ListTile(
                          onTap: () => _showPurchaseDetail(purchase),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          leading: CircleAvatar(
                            backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                            child: const Icon(Icons.inventory_rounded, color: AppColors.primary),
                          ),
                          title: Row(
                            children: [
                              Text(purchase.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.success.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text(
                                  purchase.status.toUpperCase(),
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.success),
                                ),
                              ),
                            ],
                          ),
                          subtitle: Text(
                            'Supplier: ${purchase.supplier?.name ?? 'Supplier'} • ${purchase.items.length} macam barang • ${purchase.tanggal}',
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(AppFormatters.formatRupiah(purchase.total),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primary)),
                                  const Text('Total Pembelian', style: TextStyle(fontSize: 11, color: Colors.grey)),
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

class CreatePurchaseDialog extends ConsumerStatefulWidget {
  const CreatePurchaseDialog({super.key});

  @override
  ConsumerState<CreatePurchaseDialog> createState() => _CreatePurchaseDialogState();
}

class _CreatePurchaseDialogState extends ConsumerState<CreatePurchaseDialog> {
  final _invNumberCtrl = TextEditingController();
  final _keteranganCtrl = TextEditingController();
  String _tanggal = DateFormat('yyyy-MM-dd').format(DateTime.now());
  String? _selectedSupplierId;

  final List<_PurchaseDraftItem> _draftItems = [];

  double get _grandTotal => _draftItems.fold(0.0, (sum, i) => sum + (i.qty * i.harga));

  void _addDraftItem(ProductModel product) {
    setState(() {
      _draftItems.add(_PurchaseDraftItem(
        productId: product.id,
        productName: product.name,
        qty: 1,
        harga: product.hargaBeli,
      ));
    });
  }

  @override
  void dispose() {
    _invNumberCtrl.dispose();
    _keteranganCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final suppliers = ref.watch(suppliersProvider).value ?? [];
    final products = ref.watch(productsProvider).value ?? [];

    return AlertDialog(
      title: const Text('Catat Faktur Pembelian (Restock)'),
      content: SingleChildScrollView(
        child: SizedBox(
          width: 580,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: DropdownButtonFormField<String>(
                      initialValue: _selectedSupplierId,
                      decoration: const InputDecoration(labelText: 'Supplier *'),
                      items: suppliers.map((s) => DropdownMenuItem(value: s.id, child: Text(s.name))).toList(),
                      onChanged: (val) => setState(() => _selectedSupplierId = val),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _invNumberCtrl,
                      decoration: const InputDecoration(
                        labelText: 'No. Faktur (Opsional)',
                        hintText: 'Auto / dari nota',
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      initialValue: _tanggal,
                      decoration: const InputDecoration(labelText: 'Tanggal (YYYY-MM-DD) *'),
                      onChanged: (val) => _tanggal = val.trim(),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _keteranganCtrl,
                      decoration: const InputDecoration(labelText: 'Keterangan'),
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Daftar Barang yang Dibeli:', style: TextStyle(fontWeight: FontWeight.bold)),
                  PopupMenuButton<ProductModel>(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.add_rounded, size: 16, color: AppColors.primary),
                          SizedBox(width: 4),
                          Text('Pilih Produk', style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 13)),
                        ],
                      ),
                    ),
                    itemBuilder: (ctx) {
                      return products.map((p) {
                        return PopupMenuItem(
                          value: p,
                          child: Text('${p.name} (Stok: ${p.stok})'),
                        );
                      }).toList();
                    },
                    onSelected: (p) => _addDraftItem(p),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              if (_draftItems.isEmpty)
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Center(
                    child: Text('Belum ada produk yang dipilih. Klik tombol "+ Pilih Produk" di atas.',
                        style: TextStyle(color: Colors.grey, fontSize: 12)),
                  ),
                )
              else
                ..._draftItems.asMap().entries.map((entry) {
                  final idx = entry.key;
                  final item = entry.value;
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: Text(item.productName, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                        ),
                        SizedBox(
                          width: 80,
                          child: TextField(
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(labelText: 'Qty', isDense: true),
                            controller: TextEditingController(text: item.qty.toString()),
                            onChanged: (val) {
                              item.qty = int.tryParse(val) ?? 1;
                              setState(() {});
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        SizedBox(
                          width: 120,
                          child: TextField(
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(labelText: 'Harga Beli (Rp)', isDense: true),
                            controller: TextEditingController(text: item.harga.toInt().toString()),
                            onChanged: (val) {
                              item.harga = double.tryParse(val) ?? 0.0;
                              setState(() {});
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(AppFormatters.formatRupiah(item.qty * item.harga), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, size: 18, color: Colors.grey),
                          onPressed: () => setState(() => _draftItems.removeAt(idx)),
                        ),
                      ],
                    ),
                  );
                }),
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total Pembelian:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  Text(AppFormatters.formatRupiah(_grandTotal),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.primary)),
                ],
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        ElevatedButton(
          onPressed: () async {
            if (_selectedSupplierId == null) {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih supplier terlebih dahulu!')));
              return;
            }
            if (_draftItems.isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tambahkan minimal 1 produk!')));
              return;
            }

            try {
              final service = ref.read(purchaseServiceProvider);
              final itemsList = _draftItems.map((e) => {
                'product_id': e.productId,
                'qty': e.qty,
                'harga': e.harga,
              }).toList();

              await service.createPurchase(
                supplierId: _selectedSupplierId!,
                tanggal: _tanggal,
                items: itemsList,
                invoiceNumber: _invNumberCtrl.text.trim().isEmpty ? null : _invNumberCtrl.text.trim(),
                keterangan: _keteranganCtrl.text.trim().isEmpty ? null : _keteranganCtrl.text.trim(),
              );

              if (context.mounted) {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pembelian & restock stok berhasil dicatat!')));
              }
            } catch (e) {
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e')));
              }
            }
          },
          child: const Text('Simpan Pembelian'),
        ),
      ],
    );
  }
}

class _PurchaseDraftItem {
  final String productId;
  final String productName;
  int qty;
  double harga;

  _PurchaseDraftItem({
    required this.productId,
    required this.productName,
    required this.qty,
    required this.harga,
  });
}
