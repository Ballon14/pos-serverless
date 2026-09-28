import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import '../models/product_model.dart';
import '../models/purchase_model.dart';
import '../models/stock_movement_model.dart';
import '../models/supplier_model.dart';
import '../models/user_model.dart';
import 'activity_log_service.dart';
import 'auth_service.dart';
import 'firebase_service.dart';

class PurchaseService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();
  final ActivityLogService _logger = ActivityLogService();

  Future<List<PurchaseModel>> getPurchases({
    String? startDate,
    String? endDate,
    String? supplierId,
    int limit = 100,
  }) async {
    final purchasesData = await _firebase.get('purchases');
    if (purchasesData == null || purchasesData is! Map) return [];

    final suppliersData = await _firebase.get('suppliers');
    final suppliersMap = <String, SupplierModel>{};
    if (suppliersData != null && suppliersData is Map) {
      for (final e in suppliersData.entries) {
        if (e.value is Map) {
          suppliersMap[e.key.toString()] = SupplierModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final usersData = await _firebase.get('users');
    final usersMap = <String, UserModel>{};
    if (usersData != null && usersData is Map) {
      for (final e in usersData.entries) {
        if (e.value is Map) {
          usersMap[e.key.toString()] = UserModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final productsData = await _firebase.get('products');
    final productsMap = <String, ProductModel>{};
    if (productsData != null && productsData is Map) {
      for (final e in productsData.entries) {
        if (e.value is Map) {
          productsMap[e.key.toString()] = ProductModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final list = <PurchaseModel>[];
    for (final entry in purchasesData.entries) {
      if (entry.value is Map) {
        final p = PurchaseModel.fromJson(Map<String, dynamic>.from(entry.value as Map));

        if (supplierId != null && supplierId.isNotEmpty && supplierId != 'all' && p.supplierId != supplierId) {
          continue;
        }

        if (startDate != null && startDate.isNotEmpty && p.tanggal.compareTo(startDate) < 0) {
          continue;
        }

        if (endDate != null && endDate.isNotEmpty && p.tanggal.compareTo(endDate) > 0) {
          continue;
        }

        final enrichedItems = p.items.map((item) {
          return PurchaseItemModel(
            id: item.id,
            purchaseId: item.purchaseId,
            productId: item.productId,
            qty: item.qty,
            harga: item.harga,
            subtotal: item.subtotal,
            product: productsMap[item.productId],
          );
        }).toList();

        list.add(p.copyWith(
          supplier: suppliersMap[p.supplierId],
          user: usersMap[p.userId],
          items: enrichedItems,
        ));
      }
    }

    list.sort((a, b) {
      final aDate = a.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });

    if (list.length > limit) {
      return list.sublist(0, limit);
    }
    return list;
  }

  Future<PurchaseModel> createPurchase({
    required String supplierId,
    required String tanggal,
    required List<Map<String, dynamic>> items, // [{product_id, qty, harga}]
    String? invoiceNumber,
    String? keterangan,
    String? fotoNota,
  }) async {
    final currentUser = AuthService().currentUser;
    final userId = currentUser?.id ?? '';
    final datePrefix = DateFormat('yyyyMMdd').format(DateTime.now());

    // 1. Generate Invoice Number if not provided
    String invNumber = invoiceNumber?.trim() ?? '';
    if (invNumber.isEmpty) {
      final purchasesData = await _firebase.get('purchases');
      int nextSeq = 1;
      if (purchasesData != null && purchasesData is Map) {
        for (final entry in purchasesData.entries) {
          if (entry.value is Map) {
            final inv = (entry.value['invoice_number'] as String? ?? '');
            if (inv.startsWith('PO-$datePrefix-')) {
              final parts = inv.split('-');
              if (parts.length == 3) {
                final seq = int.tryParse(parts[2]) ?? 0;
                if (seq >= nextSeq) nextSeq = seq + 1;
              }
            }
          }
        }
      }
      invNumber = 'PO-$datePrefix-${nextSeq.toString().padLeft(4, '0')}';
    }

    final purchaseId = _uuid.v4();
    double grandTotal = 0.0;
    final List<PurchaseItemModel> savedItems = [];
    final now = DateTime.now();

    // 2. Process items, add stock, and record stock movements
    for (final raw in items) {
      final productId = raw['product_id'] as String;
      final qty = (raw['qty'] as num).toInt();
      final harga = (raw['harga'] as num).toDouble();
      final subtotal = qty * harga;
      grandTotal += subtotal;

      final itemId = _uuid.v4();
      final pItem = PurchaseItemModel(
        id: itemId,
        purchaseId: purchaseId,
        productId: productId,
        qty: qty,
        harga: harga,
        subtotal: subtotal,
      );
      savedItems.add(pItem);

      // Fetch product to update stock and last buy price
      final prodData = await _firebase.get('products/$productId');
      if (prodData != null && prodData is Map) {
        final currentStock = (prodData['stok'] as num?)?.toInt() ?? 0;
        final newStock = currentStock + qty;

        // Update product stock and harga_beli
        await _firebase.patch('products/$productId', {
          'stok': newStock,
          'harga_beli': harga,
          'updated_at': now.toIso8601String(),
        });

        // Record stock movement (in)
        final movementId = _uuid.v4();
        final movement = StockMovementModel(
          id: movementId,
          productId: productId,
          type: 'in',
          qty: qty,
          stokSebelum: currentStock,
          stokSesudah: newStock,
          referenceType: 'purchase',
          referenceId: purchaseId,
          keterangan: 'Pembelian $invNumber',
          userId: userId,
          createdAt: now,
        );
        await _firebase.put('stock_movements/$movementId', movement.toJson());
      }
    }

    // 3. Save purchase record
    final purchase = PurchaseModel(
      id: purchaseId,
      invoiceNumber: invNumber,
      supplierId: supplierId,
      userId: userId,
      tanggal: tanggal,
      total: grandTotal,
      status: 'received',
      keterangan: keterangan,
      fotoNota: fotoNota,
      createdAt: now,
      items: savedItems,
    );

    await _firebase.put('purchases/$purchaseId', purchase.toJson());

    // 4. Activity log
    await _logger.log(
      'purchase.create',
      'Pembelian $invNumber dicatat (Total: Rp ${grandTotal.toInt()})',
    );

    return purchase;
  }
}
