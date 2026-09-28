import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/sale_model.dart';
import '../../models/sale_return_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/transaction_provider.dart';
import '../../services/sale_service.dart';
import '../../utils/formatters.dart';

class SaleReturnScreen extends ConsumerStatefulWidget {
  const SaleReturnScreen({super.key});

  @override
  ConsumerState<SaleReturnScreen> createState() => _SaleReturnScreenState();
}

class _SaleReturnScreenState extends ConsumerState<SaleReturnScreen> {
  final _searchController = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _showReturnDetail(SaleReturnModel ret) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Detail ${ret.returnNumber}'),
        content: SizedBox(
          width: 480,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Invoice Asal: ${ret.sale?.invoiceNumber ?? ret.saleId}', style: const TextStyle(fontWeight: FontWeight.bold)),
              Text('Diproses oleh: ${ret.processor?.name ?? 'Admin'} • ${ret.createdAt != null ? DateFormat('dd MMM yyyy, HH:mm').format(ret.createdAt!) : ''}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: Colors.amber.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline, size: 16, color: Colors.amber),
                    const SizedBox(width: 8),
                    Expanded(child: Text('Alasan Retur: ${ret.alasan}', style: const TextStyle(fontSize: 12))),
                  ],
                ),
              ),
              const Divider(height: 20),
              const Text('Item yang Diretur:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 8),
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: ret.items.length,
                  itemBuilder: (context, i) {
                    final item = ret.items[i];
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item.product?.name ?? 'Produk (${item.productId})', style: const TextStyle(fontWeight: FontWeight.w600)),
                                Text('${item.qty}x @ ${AppFormatters.formatRupiah(item.harga)}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
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
                  const Text('Total Refund / Pengembalian Uang:', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(AppFormatters.formatRupiah(ret.totalRefund),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.error)),
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

  void _openProcessReturnDialog() async {
    // Pick a sale first
    final sales = await SaleService().getSales(limit: 50);
    final returnableSales = sales.where((s) => s.status != 'returned').toList();

    if (!mounted) return;

    if (returnableSales.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tidak ada transaksi penjualan yang dapat diretur.')),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pilih Invoice untuk Diretur'),
        content: SizedBox(
          width: 480,
          height: 380,
          child: ListView.separated(
            itemCount: returnableSales.length,
            separatorBuilder: (context, _) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final sale = returnableSales[i];
              return ListTile(
                title: Text(sale.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.bold)),
                subtitle: Text('${sale.items.length} item • ${sale.createdAt != null ? DateFormat('dd/MM/yyyy HH:mm').format(sale.createdAt!) : ''}'),
                trailing: Text(AppFormatters.formatRupiah(sale.grandTotal), style: const TextStyle(fontWeight: FontWeight.bold)),
                onTap: () {
                  Navigator.pop(ctx);
                  showDialog(
                    context: context,
                    builder: (_) => ProcessReturnDialog(sale: sale),
                  ).then((_) => ref.invalidate(saleReturnsProvider));
                },
              );
            },
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final returnsAsync = ref.watch(saleReturnsProvider);
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
                    Text('Retur Penjualan', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Pencatatan pengembalian barang dari pelanggan dan pengembalian stok', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                if (user?.isAdmin == true || user?.isKasir == true)
                  ElevatedButton.icon(
                    onPressed: _openProcessReturnDialog,
                    icon: const Icon(Icons.assignment_return_rounded, size: 18),
                    label: const Text('Proses Retur Baru'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.warning,
                      foregroundColor: Colors.white,
                    ),
                  ),
              ],
            ),

            const SizedBox(height: 20),

            // Search
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari nomor retur atau invoice...',
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              ),
              onChanged: (val) => setState(() => _search = val.trim().toLowerCase()),
            ),

            const SizedBox(height: 20),

            // List of returns
            Expanded(
              child: returnsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text('Gagal memuat data retur: $e')),
                data: (returns) {
                  final filtered = returns.where((r) {
                    if (_search.isEmpty) return true;
                    return r.returnNumber.toLowerCase().contains(_search) ||
                        (r.sale?.invoiceNumber.toLowerCase().contains(_search) ?? false);
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.assignment_return_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text('Belum ada riwayat retur penjualan', style: TextStyle(color: Colors.grey, fontSize: 16)),
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
                        final ret = filtered[index];
                        return ListTile(
                          onTap: () => _showReturnDetail(ret),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          leading: CircleAvatar(
                            backgroundColor: AppColors.warning.withValues(alpha: 0.15),
                            child: const Icon(Icons.assignment_return_rounded, color: AppColors.warning),
                          ),
                          title: Row(
                            children: [
                              Text(ret.returnNumber, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(width: 8),
                              Text('(${ret.sale?.invoiceNumber ?? ret.saleId})', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                            ],
                          ),
                          subtitle: Text(
                            'Alasan: ${ret.alasan} • ${ret.items.length} item • ${ret.createdAt != null ? DateFormat('dd/MM/yyyy HH:mm').format(ret.createdAt!) : ''}',
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(AppFormatters.formatRupiah(ret.totalRefund),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.error)),
                                  const Text('Refund', style: TextStyle(fontSize: 11, color: Colors.grey)),
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

class ProcessReturnDialog extends ConsumerStatefulWidget {
  final SaleModel sale;

  const ProcessReturnDialog({super.key, required this.sale});

  @override
  ConsumerState<ProcessReturnDialog> createState() => _ProcessReturnDialogState();
}

class _ProcessReturnDialogState extends ConsumerState<ProcessReturnDialog> {
  final _alasanController = TextEditingController();
  final Map<String, int> _returnQtys = {};
  bool _restock = true;

  @override
  void initState() {
    super.initState();
    for (final it in widget.sale.items) {
      _returnQtys[it.productId] = 0;
    }
  }

  @override
  void dispose() {
    _alasanController.dispose();
    super.dispose();
  }

  double get _totalRefund {
    double total = 0.0;
    for (final it in widget.sale.items) {
      final qty = _returnQtys[it.productId] ?? 0;
      total += qty * it.harga;
    }
    return total;
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('Proses Retur: ${widget.sale.invoiceNumber}'),
      content: SingleChildScrollView(
        child: SizedBox(
          width: 520,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: _alasanController,
                decoration: const InputDecoration(
                  labelText: 'Alasan Retur *',
                  hintText: 'Misal: Barang cacat, kadaluarsa, salah ukuran',
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Checkbox(
                    value: _restock,
                    onChanged: (val) => setState(() => _restock = val ?? true),
                  ),
                  const Text('Kembalikan barang ke stok aktif (Restock ke toko)'),
                ],
              ),
              const Divider(height: 20),
              const Text('Tentukan Jumlah yang Diretur:', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              ...widget.sale.items.map((item) {
                final maxReturnable = item.qty - item.returnedQty;
                final currentReturnQty = _returnQtys[item.productId] ?? 0;

                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.product?.name ?? 'Produk (${item.productId})', style: const TextStyle(fontWeight: FontWeight.w600)),
                            Text('Beli: ${item.qty} • Sudah Diretur: ${item.returnedQty} • Sisa: $maxReturnable',
                                style: const TextStyle(fontSize: 11, color: Colors.grey)),
                          ],
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: maxReturnable <= 0
                            ? const Text('Sudah habis diretur', style: TextStyle(color: Colors.grey, fontSize: 12))
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.remove_circle_outline, size: 20),
                                    onPressed: currentReturnQty > 0
                                        ? () => setState(() => _returnQtys[item.productId] = currentReturnQty - 1)
                                        : null,
                                  ),
                                  Text('$currentReturnQty', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                  IconButton(
                                    icon: const Icon(Icons.add_circle_outline, size: 20),
                                    onPressed: currentReturnQty < maxReturnable
                                        ? () => setState(() => _returnQtys[item.productId] = currentReturnQty + 1)
                                        : null,
                                  ),
                                ],
                              ),
                      ),
                    ],
                  ),
                );
              }),
              const Divider(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total Pengembalian Uang (Refund):', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(AppFormatters.formatRupiah(_totalRefund),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.error)),
                ],
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        ElevatedButton(
          style: ElevatedButton.styleFrom(backgroundColor: AppColors.warning, foregroundColor: Colors.white),
          onPressed: () async {
            if (_alasanController.text.trim().isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Alasan retur wajib diisi!')),
              );
              return;
            }

            final itemsToReturn = <Map<String, dynamic>>[];
            for (final it in widget.sale.items) {
              final qty = _returnQtys[it.productId] ?? 0;
              if (qty > 0) {
                itemsToReturn.add({
                  'product_id': it.productId,
                  'qty': qty,
                  'harga': it.harga,
                });
              }
            }

            if (itemsToReturn.isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Pilih minimal 1 barang untuk diretur!')),
              );
              return;
            }

            try {
              final service = ref.read(saleReturnServiceProvider);
              await service.createSaleReturn(
                saleId: widget.sale.id,
                alasan: _alasanController.text.trim(),
                items: itemsToReturn,
                restockToInventory: _restock,
              );
              if (context.mounted) {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Retur berhasil diproses!')),
                );
              }
            } catch (e) {
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Gagal retur: $e')),
                );
              }
            }
          },
          child: const Text('Konfirmasi Retur'),
        ),
      ],
    );
  }
}
