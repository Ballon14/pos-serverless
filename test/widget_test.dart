import 'package:flutter_test/flutter_test.dart';
import 'package:stockku/models/product_model.dart';
import 'package:stockku/utils/formatters.dart';

void main() {
  group('AppFormatters tests', () {
    test('formatRupiah formats correctly', () {
      expect(AppFormatters.formatRupiah(150000), 'Rp 150.000');
      expect(AppFormatters.formatRupiah(0), 'Rp 0');
      expect(AppFormatters.formatRupiah(null), 'Rp 0');
    });

    test('formatNumber formats with thousand separators', () {
      expect(AppFormatters.formatNumber(1500), '1.500');
    });
  });

  group('ProductModel tests', () {
    test('GrosirTier effective price applies correctly', () {
      const product = ProductModel(
        id: '1',
        categoryId: 'cat1',
        name: 'Beras Premium 5kg',
        sku: 'BRS-001',
        hargaBeli: 60000,
        hargaJual: 75000,
        grosirTiers: [
          GrosirTier(minQty: 5, harga: 72000),
          GrosirTier(minQty: 10, harga: 70000),
        ],
        stok: 50,
      );

      // Normal price for qty < 5
      expect(product.getEffectivePrice(1), 75000);
      expect(product.getEffectivePrice(4), 75000);

      // Tier 1 for qty >= 5 and < 10
      expect(product.getEffectivePrice(5), 72000);
      expect(product.getEffectivePrice(8), 72000);

      // Tier 2 for qty >= 10
      expect(product.getEffectivePrice(10), 70000);
      expect(product.getEffectivePrice(20), 70000);
    });

    test('Low stock indicator works', () {
      const lowStockProduct = ProductModel(
        id: '2',
        categoryId: 'cat1',
        name: 'Minyak Goreng',
        sku: 'MYK-001',
        hargaBeli: 14000,
        hargaJual: 16000,
        stok: 3,
        minStok: 5,
      );

      expect(lowStockProduct.isLowStock, true);
      expect(lowStockProduct.isOutOfStock, false);
    });
  });
}
