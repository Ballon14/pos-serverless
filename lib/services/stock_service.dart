import 'package:uuid/uuid.dart';
import '../models/product_model.dart';
import '../models/stock_movement_model.dart';
import '../models/user_model.dart';
import 'firebase_service.dart';

class StockService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();

  Future<List<StockMovementModel>> getStockMovements({
    String? productId,
    String? type,
    int limit = 50,
    int offset = 0,
  }) async {
    final movementsData = await _firebase.get('stock_movements');
    if (movementsData == null || movementsData is! Map) return [];

    final productsData = await _firebase.get('products');
    final usersData = await _firebase.get('users');

    final productsMap = <String, ProductModel>{};
    if (productsData != null && productsData is Map) {
      for (final e in productsData.entries) {
        if (e.value is Map) {
          productsMap[e.key.toString()] = ProductModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final usersMap = <String, UserModel>{};
    if (usersData != null && usersData is Map) {
      for (final e in usersData.entries) {
        if (e.value is Map) {
          usersMap[e.key.toString()] = UserModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final list = <StockMovementModel>[];
    for (final entry in movementsData.entries) {
      if (entry.value is Map) {
        final m = StockMovementModel.fromJson(Map<String, dynamic>.from(entry.value as Map));

        if (productId != null && productId.isNotEmpty && m.productId != productId) {
          continue;
        }

        if (type != null && type.isNotEmpty && type != 'all' && m.type != type) {
          continue;
        }

        final product = productsMap[m.productId];
        final user = m.userId != null ? usersMap[m.userId] : null;

        list.add(StockMovementModel(
          id: m.id,
          productId: m.productId,
          type: m.type,
          qty: m.qty,
          stokSebelum: m.stokSebelum,
          stokSesudah: m.stokSesudah,
          referenceType: m.referenceType,
          referenceId: m.referenceId,
          keterangan: m.keterangan,
          userId: m.userId,
          createdAt: m.createdAt,
          product: product,
          user: user,
        ));
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

  /// Adjust stock manually (e.g. stock opname)
  Future<StockMovementModel> adjustStock({
    required String productId,
    required int newStock,
    required String reason,
    required String userId,
  }) async {
    // 1. Get current product
    final prodData = await _firebase.get('products/$productId');
    if (prodData == null || prodData is! Map) {
      throw Exception('Produk tidak ditemukan di database.');
    }
    final int currentStock = (prodData['stok'] as num?)?.toInt() ?? 0;
    final int diff = newStock - currentStock;

    if (diff == 0) {
      throw Exception('Stok baru sama dengan stok saat ini.');
    }

    final now = DateTime.now().toIso8601String();

    // 2. Update stock in Firebase RTDB
    await _firebase.patch('products/$productId', {
      'stok': newStock,
      'updated_at': now,
    });

    // 3. Record stock movement
    final movementId = _uuid.v4();
    final movement = StockMovementModel(
      id: movementId,
      productId: productId,
      type: 'adjustment',
      qty: diff.abs(),
      stokSebelum: currentStock,
      stokSesudah: newStock,
      referenceType: 'manual_adjustment',
      keterangan: reason,
      userId: userId,
      createdAt: DateTime.parse(now),
    );

    await _firebase.put('stock_movements/$movementId', movement.toJson());
    return movement;
  }
}
