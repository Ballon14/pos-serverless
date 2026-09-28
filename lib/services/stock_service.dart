import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/stock_movement_model.dart';

class StockService {
  final SupabaseClient _supabase;

  StockService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  Future<List<StockMovementModel>> getStockMovements({
    String? productId,
    String? type,
    int limit = 50,
    int offset = 0,
  }) async {
    var query = _supabase.from('stock_movements').select('*, products(*), users(*)');

    if (productId != null && productId.isNotEmpty) {
      query = query.eq('product_id', productId);
    }

    if (type != null && type.isNotEmpty && type != 'all') {
      query = query.eq('type', type);
    }

    final response = await query.order('created_at', ascending: false).range(offset, offset + limit - 1);
    return (response as List).map((json) => StockMovementModel.fromJson(json)).toList();
  }

  /// Adjust stock manually (e.g. stock opname / penyesuaian)
  Future<StockMovementModel> adjustStock({
    required String productId,
    required int newStock,
    required String reason,
    required String userId,
  }) async {
    // 1. Get current product stock
    final productData = await _supabase.from('products').select('stok').eq('id', productId).single();
    final int currentStock = (productData['stok'] as num).toInt();
    final int diff = newStock - currentStock;

    if (diff == 0) {
      throw Exception('Stok baru sama dengan stok saat ini.');
    }

    // 2. Update product stock
    await _supabase.from('products').update({
      'stok': newStock,
      'updated_at': DateTime.now().toIso8601String(),
    }).eq('id', productId);

    // 3. Record stock movement
    final movement = await _supabase
        .from('stock_movements')
        .insert({
          'product_id': productId,
          'type': 'adjustment',
          'qty': diff.abs(),
          'stok_sebelum': currentStock,
          'stok_sesudah': newStock,
          'reference_type': 'manual_adjustment',
          'keterangan': reason,
          'user_id': userId,
        })
        .select('*, products(*), users(*)')
        .single();

    return StockMovementModel.fromJson(movement);
  }
}
