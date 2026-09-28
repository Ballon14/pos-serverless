import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import '../models/product_model.dart';
import '../models/sale_model.dart';
import '../models/stock_movement_model.dart';
import '../models/user_model.dart';
import 'auth_service.dart';
import 'firebase_service.dart';

class SaleCheckoutRequest {
  final List<SaleCheckoutItem> items;
  final double subtotal;
  final double diskon;
  final double grandTotal;
  final double bayar;
  final double kembalian;
  final String paymentMethod;
  final String? catatan;
  final String? offlineId;

  const SaleCheckoutRequest({
    required this.items,
    required this.subtotal,
    this.diskon = 0.0,
    required this.grandTotal,
    required this.bayar,
    required this.kembalian,
    this.paymentMethod = 'tunai',
    this.catatan,
    this.offlineId,
  });

  Map<String, dynamic> toJson() {
    return {
      'items': items.map((i) => i.toJson()).toList(),
      'subtotal': subtotal,
      'diskon': diskon,
      'grand_total': grandTotal,
      'bayar': bayar,
      'kembalian': kembalian,
      'payment_method': paymentMethod,
      'catatan': catatan,
      'offline_id': offlineId,
      'sumber': 'flutter_firebase',
    };
  }
}

class SaleCheckoutItem {
  final String productId;
  final int qty;
  final double harga;
  final double hargaBeli;
  final double diskon;
  final double subtotal;

  const SaleCheckoutItem({
    required this.productId,
    required this.qty,
    required this.harga,
    this.hargaBeli = 0.0,
    this.diskon = 0.0,
    required this.subtotal,
  });

  Map<String, dynamic> toJson() {
    return {
      'product_id': productId,
      'qty': qty,
      'harga': harga,
      'harga_beli': hargaBeli,
      'diskon': diskon,
      'subtotal': subtotal,
    };
  }
}

class SaleService {
  final FirebaseService _firebase = FirebaseService();
  final AuthService _auth = AuthService();
  final Uuid _uuid = const Uuid();

  /// Process checkout and save directly to Firebase Realtime Database
  Future<SaleModel> checkout(SaleCheckoutRequest request) async {
    final user = _auth.currentUser;
    if (user == null) throw Exception('Silakan masuk terlebih dahulu');

    final now = DateTime.now();
    final datePrefix = DateFormat('yyyyMMdd').format(now);

    // 1. Generate unique invoice number: INV-YYYYMMDD-XXXX
    final salesData = await _firebase.get('sales');
    int nextSeq = 1;
    if (salesData != null && salesData is Map) {
      for (final entry in salesData.entries) {
        if (entry.value is Map) {
          final inv = (entry.value['invoice_number'] as String? ?? '');
          if (inv.startsWith('INV-$datePrefix-')) {
            final parts = inv.split('-');
            if (parts.length == 3) {
              final seq = int.tryParse(parts[2]) ?? 0;
              if (seq >= nextSeq) nextSeq = seq + 1;
            }
          }
        }
      }
    }
    final invoiceNumber = 'INV-$datePrefix-${nextSeq.toString().padLeft(4, '0')}';

    final saleId = _uuid.v4();

    // 2. Process items, deduct stock, and record stock movements
    final List<SaleItemModel> savedItems = [];
    final List<Map<String, dynamic>> itemsJsonList = [];

    for (final item in request.items) {
      final itemId = _uuid.v4();

      // Deduct product stock in Firebase RTDB
      final prodData = await _firebase.get('products/${item.productId}');
      ProductModel? prod;
      int currentStock = 0;
      if (prodData != null && prodData is Map) {
        prod = ProductModel.fromJson(Map<String, dynamic>.from(prodData));
        currentStock = prod.stok;
      }

      final newStock = (currentStock - item.qty).clamp(0, 9999999);
      await _firebase.patch('products/${item.productId}', {
        'stok': newStock,
        'updated_at': now.toIso8601String(),
      });

      // Record stock movement
      final movementId = _uuid.v4();
      final movement = StockMovementModel(
        id: movementId,
        productId: item.productId,
        type: 'out',
        qty: item.qty,
        stokSebelum: currentStock,
        stokSesudah: newStock,
        referenceType: 'sale',
        referenceId: saleId,
        keterangan: 'Penjualan $invoiceNumber',
        userId: user.id,
        createdAt: now,
      );
      await _firebase.put('stock_movements/$movementId', movement.toJson());

      final saleItem = SaleItemModel(
        id: itemId,
        saleId: saleId,
        productId: item.productId,
        qty: item.qty,
        returnedQty: 0,
        harga: item.harga,
        hargaBeli: item.hargaBeli,
        diskon: item.diskon,
        subtotal: item.subtotal,
        product: prod,
        createdAt: now,
      );

      savedItems.add(saleItem);
      itemsJsonList.add(saleItem.toJson());
    }

    // 3. Save sale header to Firebase RTDB /sales/$saleId
    final saleModel = SaleModel(
      id: saleId,
      invoiceNumber: invoiceNumber,
      userId: user.id,
      subtotal: request.subtotal,
      diskon: request.diskon,
      grandTotal: request.grandTotal,
      bayar: request.bayar,
      kembalian: request.kembalian,
      paymentMethod: request.paymentMethod,
      status: 'completed',
      catatan: request.catatan,
      sumber: 'flutter_firebase',
      offlineId: request.offlineId,
      createdAt: now,
      updatedAt: now,
      user: user,
      items: savedItems,
    );

    final saleMap = saleModel.toJson();
    saleMap['items'] = itemsJsonList;

    await _firebase.put('sales/$saleId', saleMap);

    return saleModel;
  }

  /// Get sales list with filters
  Future<List<SaleModel>> getSales({
    String? startDate,
    String? endDate,
    String? status,
    int limit = 50,
    int offset = 0,
  }) async {
    final salesData = await _firebase.get('sales');
    if (salesData == null || salesData is! Map) return [];

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

    final list = <SaleModel>[];
    for (final entry in salesData.entries) {
      if (entry.value is Map) {
        final map = Map<String, dynamic>.from(entry.value as Map);
        final sale = SaleModel.fromJson(map);

        if (status != null && status.isNotEmpty && status != 'all' && sale.status != status) {
          continue;
        }

        if (startDate != null && startDate.isNotEmpty && sale.createdAt != null) {
          final start = DateTime.tryParse('${startDate}T00:00:00');
          if (start != null && sale.createdAt!.isBefore(start)) continue;
        }

        if (endDate != null && endDate.isNotEmpty && sale.createdAt != null) {
          final end = DateTime.tryParse('${endDate}T23:59:59');
          if (end != null && sale.createdAt!.isAfter(end)) continue;
        }

        final enrichedItems = sale.items.map((i) {
          return SaleItemModel(
            id: i.id,
            saleId: i.saleId,
            productId: i.productId,
            qty: i.qty,
            returnedQty: i.returnedQty,
            harga: i.harga,
            hargaBeli: i.hargaBeli,
            diskon: i.diskon,
            subtotal: i.subtotal,
            product: productsMap[i.productId],
            createdAt: i.createdAt,
          );
        }).toList();

        final user = usersMap[sale.userId];
        list.add(sale.copyWith(user: user, items: enrichedItems));
      }
    }

    list.sort((a, b) {
      final aDate = a.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });

    if (offset >= list.length) return [];
    final endIndex = (offset + limit).clamp(0, list.length);
    return list.sublist(offset, endIndex);
  }

  /// Get single sale detail by ID
  Future<SaleModel?> getSaleById(String id) async {
    final data = await _firebase.get('sales/$id');
    if (data == null || data is! Map) return null;
    return SaleModel.fromJson(Map<String, dynamic>.from(data));
  }

  /// Process return of items
  Future<void> processReturn({
    required String saleId,
    required List<Map<String, dynamic>> returnItems,
    String? reason,
  }) async {
    final user = _auth.currentUser;
    for (final ret in returnItems) {
      final productId = ret['product_id'] as String;
      final returnQty = ret['qty'] as int;

      // Restore stock in Firebase RTDB
      final prodData = await _firebase.get('products/$productId');
      if (prodData != null && prodData is Map) {
        final currentStock = (prodData['stok'] as num?)?.toInt() ?? 0;
        final restoredStock = currentStock + returnQty;

        await _firebase.patch('products/$productId', {'stok': restoredStock});

        // Record stock movement
        final movementId = _uuid.v4();
        final movement = StockMovementModel(
          id: movementId,
          productId: productId,
          type: 'return',
          qty: returnQty,
          stokSebelum: currentStock,
          stokSesudah: restoredStock,
          referenceType: 'sale_return',
          referenceId: saleId,
          keterangan: 'Retur item: ${reason ?? "-"}',
          userId: user?.id,
          createdAt: DateTime.now(),
        );
        await _firebase.put('stock_movements/$movementId', movement.toJson());
      }
    }

    await _firebase.patch('sales/$saleId', {'status': 'returned'});
  }

  /// Get today summary stats (revenue, transactions count)
  Future<Map<String, dynamic>> getTodayStats() async {
    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final sales = await getSales(startDate: today, endDate: today, limit: 1000);

    double totalRevenue = 0.0;
    int totalTransactions = 0;

    for (final s in sales) {
      if (s.status != 'cancelled') {
        totalRevenue += s.grandTotal;
        totalTransactions++;
      }
    }

    return {
      'totalRevenue': totalRevenue,
      'totalTransactions': totalTransactions,
    };
  }
}
