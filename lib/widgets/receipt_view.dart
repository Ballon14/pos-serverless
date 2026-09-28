import 'package:flutter/material.dart';
import '../config/theme.dart';
import '../models/sale_model.dart';
import '../utils/formatters.dart';

class ReceiptDialog extends StatelessWidget {
  final SaleModel sale;

  const ReceiptDialog({super.key, required this.sale});

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 400),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Success Icon & Title
              const Center(
                child: CircleAvatar(
                  radius: 30,
                  backgroundColor: Color(0xFFD1FAE5),
                  child: Icon(Icons.check_circle_rounded, color: AppColors.success, size: 36),
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Transaksi Berhasil!',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 4),
              Text(
                sale.invoiceNumber,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.primary),
              ),
              const SizedBox(height: 20),

              const Divider(),

              // Items List
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: sale.items.length,
                  itemBuilder: (context, index) {
                    final item = sale.items[index];
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item.product?.name ?? 'Produk',
                                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                Text(
                                  '${item.qty}x @ ${AppFormatters.formatRupiah(item.harga)}',
                                  style: const TextStyle(fontSize: 11, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                          Text(
                            AppFormatters.formatRupiah(item.subtotal),
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),

              const Divider(),

              // Summary
              _summaryRow('Subtotal', AppFormatters.formatRupiah(sale.subtotal)),
              if (sale.diskon > 0)
                _summaryRow('Diskon', '- ${AppFormatters.formatRupiah(sale.diskon)}', color: AppColors.error),
              const SizedBox(height: 4),
              _summaryRow('Total', AppFormatters.formatRupiah(sale.grandTotal), isBold: true),
              _summaryRow('Bayar (${sale.paymentMethod.toUpperCase()})', AppFormatters.formatRupiah(sale.bayar)),
              _summaryRow('Kembalian', AppFormatters.formatRupiah(sale.kembalian), color: AppColors.success, isBold: true),

              const SizedBox(height: 24),

              // Action Buttons
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      icon: const Icon(Icons.print_rounded, size: 16),
                      label: const Text('Cetak Struk'),
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Menyiapkan printer struk...')),
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Selesai'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _summaryRow(String label, String value, {bool isBold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 12, fontWeight: isBold ? FontWeight.bold : FontWeight.normal)),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
