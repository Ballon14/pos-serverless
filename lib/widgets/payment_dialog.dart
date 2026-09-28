import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../config/theme.dart';
import '../providers/cart_provider.dart';
import '../services/sale_service.dart';
import '../utils/formatters.dart';

class PaymentDialog extends ConsumerStatefulWidget {
  const PaymentDialog({super.key});

  @override
  ConsumerState<PaymentDialog> createState() => _PaymentDialogState();
}

class _PaymentDialogState extends ConsumerState<PaymentDialog> {
  final _paidController = TextEditingController();
  final _notesController = TextEditingController();
  String _paymentMethod = 'tunai';
  bool _isProcessing = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    final cart = ref.read(cartProvider);
    _paidController.text = cart.grandTotal.toInt().toString();
  }

  @override
  void dispose() {
    _paidController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _setCashAmount(double amount) {
    setState(() {
      _paidController.text = amount.toInt().toString();
    });
  }

  Future<void> _handlePayment() async {
    final cart = ref.read(cartProvider);
    final paidAmount = double.tryParse(_paidController.text) ?? 0.0;

    if (paidAmount < cart.grandTotal) {
      setState(() {
        _errorMessage = 'Jumlah pembayaran kurang dari total tagihan!';
      });
      return;
    }

    setState(() {
      _isProcessing = true;
      _errorMessage = null;
    });

    try {
      final saleService = SaleService();
      final checkoutItems = cart.items.map((item) {
        return SaleCheckoutItem(
          productId: item.product.id,
          qty: item.qty,
          harga: item.unitPrice,
          hargaBeli: item.product.hargaBeli,
          diskon: item.discount,
          subtotal: item.subtotal,
        );
      }).toList();

      final request = SaleCheckoutRequest(
        items: checkoutItems,
        subtotal: cart.subtotal,
        diskon: cart.discount,
        grandTotal: cart.grandTotal,
        bayar: paidAmount,
        kembalian: paidAmount - cart.grandTotal,
        paymentMethod: _paymentMethod,
        catatan: _notesController.text.trim().isEmpty ? null : _notesController.text.trim(),
      );

      final sale = await saleService.checkout(request);

      if (mounted) {
        // Clear cart
        ref.read(cartProvider.notifier).clear();
        Navigator.pop(context, sale);
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      });
    } finally {
      if (mounted) {
        setState(() {
          _isProcessing = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final paidAmount = double.tryParse(_paidController.text) ?? 0.0;
    final change = (paidAmount - cart.grandTotal).clamp(0.0, double.infinity);

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Konfirmasi Pembayaran',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, size: 20),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Total Amount Card
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                ),
                child: Column(
                  children: [
                    const Text('Total yang Harus Dibayar', style: TextStyle(fontSize: 12, color: AppColors.primary)),
                    const SizedBox(height: 4),
                    Text(
                      AppFormatters.formatRupiah(cart.grandTotal),
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: AppColors.primary,
                        letterSpacing: -0.5,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Error Message
              if (_errorMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.error.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    _errorMessage!,
                    style: const TextStyle(color: AppColors.error, fontSize: 12, fontWeight: FontWeight.w500),
                  ),
                ),
                const SizedBox(height: 14),
              ],

              // Payment Method Selector
              const Text('Metode Pembayaran', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              Row(
                children: [
                  _methodButton('tunai', 'Tunai / Cash', Icons.money_rounded),
                  const SizedBox(width: 8),
                  _methodButton('qris', 'QRIS', Icons.qr_code_2_rounded),
                  const SizedBox(width: 8),
                  _methodButton('transfer', 'Transfer', Icons.account_balance_rounded),
                ],
              ),

              const SizedBox(height: 16),

              // Cash Received Input
              const Text('Jumlah Uang Diterima', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _paidController,
                keyboardType: TextInputType.number,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                decoration: const InputDecoration(
                  prefixText: 'Rp ',
                  prefixStyle: TextStyle(fontWeight: FontWeight.bold),
                ),
                onChanged: (_) => setState(() {}),
              ),

              const SizedBox(height: 10),

              // Quick Cash Buttons
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _quickCashChip('Uang Pas', cart.grandTotal),
                  _quickCashChip('50.000', 50000),
                  _quickCashChip('100.000', 100000),
                  _quickCashChip('200.000', 200000),
                ],
              ),

              const SizedBox(height: 16),

              // Kembalian Card
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkSurface : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Kembalian', style: TextStyle(fontWeight: FontWeight.w600)),
                    Text(
                      AppFormatters.formatRupiah(change),
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: paidAmount >= cart.grandTotal ? AppColors.success : AppColors.error,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Confirm Button
              ElevatedButton(
                onPressed: _isProcessing ? null : _handlePayment,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.success,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: _isProcessing
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.check_circle_rounded, size: 20),
                          SizedBox(width: 8),
                          Text('Selesaikan Pembayaran', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                        ],
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _methodButton(String method, String label, IconData icon) {
    final isSelected = _paymentMethod == method;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          setState(() {
            _paymentMethod = method;
          });
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primary.withValues(alpha: 0.15) : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isSelected ? AppColors.primary : AppColors.darkBorder,
              width: isSelected ? 2 : 1,
            ),
          ),
          child: Column(
            children: [
              Icon(icon, size: 20, color: isSelected ? AppColors.primary : null),
              const SizedBox(height: 4),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                  color: isSelected ? AppColors.primary : null,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _quickCashChip(String label, double amount) {
    return ActionChip(
      label: Text(label, style: const TextStyle(fontSize: 12)),
      onPressed: () => _setCashAmount(amount),
    );
  }
}
