import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/theme.dart';
import '../../models/product_model.dart';
import '../../models/sale_model.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/cart_provider.dart';
import '../../providers/product_provider.dart';
import '../../utils/formatters.dart';
import '../../widgets/payment_dialog.dart';
import '../../widgets/receipt_view.dart';

class PosScreen extends ConsumerStatefulWidget {
  const PosScreen({super.key});

  @override
  ConsumerState<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends ConsumerState<PosScreen> {
  final _searchController = TextEditingController();
  final _barcodeController = TextEditingController();
  final _barcodeFocusNode = FocusNode();
  String? _selectedCategory;

  @override
  void dispose() {
    _searchController.dispose();
    _barcodeController.dispose();
    _barcodeFocusNode.dispose();
    super.dispose();
  }

  void _onBarcodeSubmitted(String code) async {
    if (code.trim().isEmpty) return;

    final productService = ref.read(productServiceProvider);
    try {
      final product = await productService.getProductBySku(code.trim());
      if (product != null) {
        if (product.stok <= 0) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('Stok produk ${product.name} telah habis!')),
            );
          }
        } else {
          ref.read(cartProvider.notifier).addItem(product);
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Produk dengan barcode "$code" tidak ditemukan!')),
          );
        }
      }
    } catch (_) {}

    _barcodeController.clear();
    _barcodeFocusNode.requestFocus();
  }

  Future<void> _openPaymentModal() async {
    final sale = await showDialog<SaleModel>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const PaymentDialog(),
    );

    if (sale != null && mounted) {
      await showDialog(
        context: context,
        builder: (ctx) => ReceiptDialog(sale: sale),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLocked = ref.watch(isAttendanceGateLockedProvider);
    final categoriesAsync = ref.watch(categoriesProvider);
    final productsAsync = ref.watch(productsProvider);
    final cart = ref.watch(cartProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final screenWidth = MediaQuery.of(context).size.width;
    final isWide = screenWidth >= 1000;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: Row(
        children: [
          // Left: Product Catalog & Search
          Expanded(
            flex: isWide ? 6 : 5,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Search & Barcode Bar
                  Row(
                    children: [
                      // Search Input
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          decoration: InputDecoration(
                            hintText: 'Cari nama produk atau SKU...',
                            prefixIcon: const Icon(Icons.search_rounded, size: 20),
                            suffixIcon: _searchController.text.isNotEmpty
                                  ? IconButton(
                                    icon: const Icon(Icons.clear_rounded, size: 18),
                                    onPressed: () {
                                      _searchController.clear();
                                      ref.read(productFilterProvider.notifier).updateSearch('');
                                    },
                                  )
                                : null,
                          ),
                          onChanged: (val) {
                            ref.read(productFilterProvider.notifier).updateSearch(val);
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Barcode Scan Input
                      SizedBox(
                        width: 220,
                        child: TextField(
                          controller: _barcodeController,
                          focusNode: _barcodeFocusNode,
                          decoration: const InputDecoration(
                            hintText: 'Scan barcode...',
                            prefixIcon: Icon(Icons.qr_code_scanner_rounded, size: 20, color: AppColors.primary),
                          ),
                          onSubmitted: _onBarcodeSubmitted,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  // Categories Filter Chips
                  categoriesAsync.when(
                    data: (categories) {
                      return SizedBox(
                        height: 38,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: ChoiceChip(
                                label: const Text('Semua'),
                                selected: _selectedCategory == null,
                                onSelected: (_) {
                                  setState(() => _selectedCategory = null);
                                  ref.read(productFilterProvider.notifier).updateCategory(null);
                                },
                              ),
                            ),
                            ...categories.map((c) {
                              final isSelected = _selectedCategory == c.id;
                              return Padding(
                                padding: const EdgeInsets.only(right: 8),
                                child: ChoiceChip(
                                  label: Text(c.name),
                                  selected: isSelected,
                                  onSelected: (_) {
                                    setState(() => _selectedCategory = c.id);
                                    ref.read(productFilterProvider.notifier).updateCategory(c.id);
                                  },
                                ),
                              );
                            }),
                          ],
                        ),
                      );
                    },
                    loading: () => const SizedBox(height: 38),
                    error: (_, _) => const SizedBox.shrink(),
                  ),

                  const SizedBox(height: 16),

                  // Products Grid
                  Expanded(
                    child: productsAsync.when(
                      data: (products) {
                        if (products.isEmpty) {
                          return const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.search_off_rounded, size: 48, color: Colors.grey),
                                SizedBox(height: 12),
                                Text('Tidak ada produk yang cocok.', style: TextStyle(color: Colors.grey)),
                              ],
                            ),
                          );
                        }

                        return GridView.builder(
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: isWide ? 4 : 3,
                            childAspectRatio: 0.85,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                          itemCount: products.length,
                          itemBuilder: (context, index) {
                            final product = products[index];
                            return _ProductCard(
                              product: product,
                              onTap: () {
                                if (product.stok <= 0) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text('Stok ${product.name} telah habis!')),
                                  );
                                  return;
                                }
                                ref.read(cartProvider.notifier).addItem(product);
                              },
                            );
                          },
                        );
                      },
                      loading: () => const Center(child: CircularProgressIndicator()),
                      error: (err, _) => Center(child: Text('Gagal memuat produk: $err')),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Vertical Divider
          const VerticalDivider(width: 1),

          // Right: Cart Panel
          Container(
            width: isWide ? 380 : 340,
            color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
            child: Column(
              children: [
                // Cart Header
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.shopping_cart_rounded, size: 20, color: AppColors.primary),
                          const SizedBox(width: 8),
                          const Text(
                            'Keranjang',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              '${cart.totalItemCount}',
                              style: const TextStyle(
                                color: AppColors.primary,
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (!cart.isEmpty)
                        TextButton(
                          onPressed: () => ref.read(cartProvider.notifier).clear(),
                          style: TextButton.styleFrom(foregroundColor: AppColors.error),
                          child: const Text('Kosongkan', style: TextStyle(fontSize: 12)),
                        ),
                    ],
                  ),
                ),

                const Divider(height: 1),

                // Cart Items List
                Expanded(
                  child: cart.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.shopping_basket_outlined,
                                size: 54,
                                color: isDark ? AppColors.darkBorder : Colors.grey.shade300,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'Keranjang masih kosong',
                                style: TextStyle(
                                  color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                                  fontSize: 13,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Pilih produk dari katalog di sebelah kiri',
                                style: TextStyle(
                                  color: isDark ? AppColors.darkBorder : Colors.grey.shade400,
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          itemCount: cart.items.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            final item = cart.items[index];
                            return _CartItemTile(item: item);
                          },
                        ),
                ),

                const Divider(height: 1),

                // Cart Summary & Checkout
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Subtotal',
                            style: TextStyle(
                              fontSize: 13,
                              color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                            ),
                          ),
                          Text(
                            AppFormatters.formatRupiah(cart.subtotal),
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Total Tagihan',
                            style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                          ),
                          Text(
                            AppFormatters.formatRupiah(cart.grandTotal),
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Checkout button (Guarded by attendance)
                      ElevatedButton(
                        onPressed: (cart.isEmpty || isLocked) ? null : _openPaymentModal,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: isLocked ? AppColors.warning : AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          elevation: 3,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(isLocked ? Icons.lock_clock_rounded : Icons.payment_rounded, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              isLocked
                                  ? 'Clock In Wajib Sebelum Transaksi'
                                  : 'Bayar (${AppFormatters.formatRupiah(cart.grandTotal)})',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  final ProductModel product;
  final VoidCallback onTap;

  const _ProductCard({required this.product, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: (product.stok <= 0 ? AppColors.error : AppColors.success)
                              .withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          product.stok <= 0 ? 'Habis' : 'Stok: ${product.stok}',
                          style: TextStyle(
                            color: product.stok <= 0 ? AppColors.error : AppColors.success,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      if (product.grosirTiers.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.secondary.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Text(
                            'Grosir',
                            style: TextStyle(
                              color: AppColors.secondary,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    product.name,
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    product.sku,
                    style: TextStyle(
                      color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    AppFormatters.formatRupiah(product.hargaJual),
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                  CircleAvatar(
                    radius: 14,
                    backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                    child: const Icon(Icons.add_rounded, size: 16, color: AppColors.primary),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CartItemTile extends ConsumerWidget {
  final CartItem item;

  const _CartItemTile({required this.item});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifier = ref.read(cartProvider.notifier);
    final hasGrosir = item.product.grosirTiers.isNotEmpty && item.unitPrice < item.product.hargaJual;

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Theme.of(context).cardTheme.color,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  item.product.name,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded, size: 16),
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                color: Colors.grey,
                onPressed: () => notifier.removeItem(item.product.id),
              ),
            ],
          ),
          Row(
            children: [
              Text(
                AppFormatters.formatRupiah(item.unitPrice),
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
              ),
              if (hasGrosir) ...[
                const SizedBox(width: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                  decoration: BoxDecoration(
                    color: AppColors.secondary.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: const Text('Tier Grosir', style: TextStyle(fontSize: 9, color: AppColors.secondary, fontWeight: FontWeight.bold)),
                ),
              ],
            ],
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Qty Counter Stepper
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Theme.of(context).dividerColor),
                ),
                child: Row(
                  children: [
                    InkWell(
                      onTap: () => notifier.decrementQty(item.product.id),
                      child: const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        child: Icon(Icons.remove, size: 14),
                      ),
                    ),
                    Text(
                      '${item.qty}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    InkWell(
                      onTap: () {
                        if (item.qty >= item.product.stok) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Maksimal stok tercapai (${item.product.stok})')),
                          );
                          return;
                        }
                        notifier.incrementQty(item.product.id);
                      },
                      child: const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        child: Icon(Icons.add, size: 14),
                      ),
                    ),
                  ],
                ),
              ),
              // Subtotal
              Text(
                AppFormatters.formatRupiah(item.subtotal),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primary),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
