import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import '../models/product_model.dart';
import '../models/sale_model.dart';
import '../models/sale_return_model.dart';
import '../models/stock_movement_model.dart';
import '../models/user_model.dart';
import 'activity_log_service.dart';
import 'auth_service.dart';
import 'firebase_service.dart';

class SaleReturnService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();
  final ActivityLogService _logger = ActivityLogService();

  Future<List<SaleReturnModel>> getSaleReturns({int limit = 100}) async {
    final returnsData = await _firebase.get('sale_returns');
    if (returnsData == null || returnsData is! Map) return [];

    final salesData = await _firebase.get('sales');
    final salesMap = <String, SaleModel>{};
    if (salesData != null && salesData is Map) {
      for (final e in salesData.entries) {
        if (e.value is Map) {
          salesMap[e.key.toString()] = SaleModel.fromJson(Map<String, dynamic>.from(e.value as Map));
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

    final list = <SaleReturnModel>[];
    for (final entry in returnsData.entries) {
      if (entry.value is Map) {
        final r = SaleReturnModel.fromJson(Map<String, dynamic>.from(entry.value as Map));

        final enrichedItems = r.items.map((i) {
          return SaleReturnItemModel(
            id: i.id,
            saleReturnId: i.saleReturnId,
            productId: i.productId,
            qty: i.qty,
            harga: i.harga,
            subtotal: i.subtotal,
            product: productsMap[i.productId],
          );
        }).toList();

        list.add(r.copyWith(
          sale: salesMap[r.saleId],
          processor: r.processedBy != null ? usersMap[r.processedBy] : null,
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

  Future<SaleReturnModel> createSaleReturn({
    required String saleId,
    required String alasan,
    required List<Map<String, dynamic>> items, // [{product_id, qty, harga}]
    bool restockToInventory = true,
  }) async {
    final currentUser = AuthService().currentUser;
    final userId = currentUser?.id ?? '';
    final datePrefix = DateFormat('yyyyMMdd').format(DateTime.now());

    // 1. Generate Return Number: RET-YYYYMMDD-XXXX
    final returnsData = await _firebase.get('sale_returns');
    int nextSeq = 1;
    if (returnsData != null && returnsData is Map) {
      for (final entry in returnsData.entries) {
        if (entry.value is Map) {
          final ret = (entry.value['return_number'] as String? ?? '');
          if (ret.startsWith('RET-$datePrefix-')) {
            final parts = ret.split('-');
            if (parts.length == 3) {
              final seq = int.tryParse(parts[2]) ?? 0;
              if (seq >= nextSeq) nextSeq = seq + 1;
            }
          }
        }
      }
    }
    final returnNumber = 'RET-$datePrefix-${nextSeq.toString().padLeft(4, '0')}';
    final returnId = _uuid.v4();
    final now = DateTime.now();

    // 2. Fetch target sale to validate and update
    final saleData = await _firebase.get('sales/$saleId');
    if (saleData == null || saleData is! Map) {
      throw Exception('Penjualan dengan ID $saleId tidak ditemukan');
    }
    final sale = SaleModel.fromJson(Map<String, dynamic>.from(saleData));

    double totalRefund = 0.0;
    final List<SaleReturnItemModel> returnItems = [];

    // Map existing sale items by product_id
    final saleItemsMap = <String, SaleItemModel>{};
    for (final it in sale.items) {
      saleItemsMap[it.productId] = it;
    }

    final updatedSaleItems = <SaleItemModel>[];

    for (final it in sale.items) {
      final retReq = items.firstWhere(
        (element) => element['product_id'] == it.productId,
        orElse: () => {},
      );

      if (retReq.isNotEmpty) {
        final retQty = (retReq['qty'] as num).toInt();
        final maxReturnable = it.qty - it.returnedQty;
        if (retQty > maxReturnable) {
          throw Exception('Jumlah retur untuk produk melebihi sisa pembelian ($maxReturnable pcs)');
        }

        final itemSubtotal = retQty * it.harga;
        totalRefund += itemSubtotal;

        final returnItemId = _uuid.v4();
        returnItems.add(SaleReturnItemModel(
          id: returnItemId,
          saleReturnId: returnId,
          productId: it.productId,
          qty: retQty,
          harga: it.harga,
          subtotal: itemSubtotal,
        ));

        // Update item with new returnedQty
        final newReturnedQty = it.returnedQty + retQty;
        updatedSaleItems.add(SaleItemModel(
          id: it.id,
          saleId: it.saleId,
          productId: it.productId,
          qty: it.qty,
          returnedQty: newReturnedQty,
          harga: it.harga,
          hargaBeli: it.hargaBeli,
          diskon: it.diskon,
          subtotal: it.subtotal,
          createdAt: it.createdAt,
        ));

        // Restore stock if requested
        if (restockToInventory) {
          final prodData = await _firebase.get('products/${it.productId}');
          if (prodData != null && prodData is Map) {
            final curStock = (prodData['stok'] as num?)?.toInt() ?? 0;
            final newStock = curStock + retQty;
            await _firebase.patch('products/${it.productId}', {
              'stok': newStock,
              'updated_at': now.toIso8601String(),
            });

            // Record stock movement (return)
            final mId = _uuid.v4();
            final movement = StockMovementModel(
              id: mId,
              productId: it.productId,
              type: 'return',
              qty: retQty,
              stokSebelum: curStock,
              stokSesudah: newStock,
              referenceType: 'sale_return',
              referenceId: returnId,
              keterangan: 'Retur $returnNumber dari invoice ${sale.invoiceNumber}',
              userId: userId,
              createdAt: now,
            );
            await _firebase.put('stock_movements/$mId', movement.toJson());
          }
        }
      } else {
        updatedSaleItems.add(it);
      }
    }

    // Determine new sale status
    bool isAllReturned = true;
    for (final it in updatedSaleItems) {
      if (it.returnedQty < it.qty) {
        isAllReturned = false;
        break;
      }
    }
    final newStatus = isAllReturned ? 'returned' : 'partial_return';

    // 3. Update Sale in Firebase
    await _firebase.patch('sales/$saleId', {
      'status': newStatus,
      'sale_items': updatedSaleItems.map((e) => e.toJson()).toList(),
      'updated_at': now.toIso8601String(),
    });

    // 4. Create Sale Return record
    final saleReturn = SaleReturnModel(
      id: returnId,
      saleId: saleId,
      returnNumber: returnNumber,
      totalRefund: totalRefund,
      alasan: alasan,
      status: 'approved',
      processedBy: userId,
      createdAt: now,
      items: returnItems,
    );
    await _firebase.put('sale_returns/$returnId', saleReturn.toJson());

    // 5. Activity Log
    await _logger.log(
      'sale.return',
      'Retur $returnNumber diproses untuk ${sale.invoiceNumber} (Total Refund: Rp ${totalRefund.toInt()})',
    );

    return saleReturn;
  }
}
