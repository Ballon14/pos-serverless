import 'package:intl/intl.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/sale_model.dart';
import '../models/user_model.dart';

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
      'sumber': 'flutter',
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
  final SupabaseClient _supabase;

  SaleService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  /// Process checkout via Supabase Edge Function or direct fallback
  Future<SaleModel> checkout(SaleCheckoutRequest request) async {
    try {
      // 1. Attempt invoking Edge Function first
      final response = await _supabase.functions.invoke(
        'checkout',
        body: request.toJson(),
      );

      if (response.status == 200 && response.data != null) {
        final data = response.data is Map ? response.data as Map<String, dynamic> : {};
        if (data['sale'] != null) {
          return SaleModel.fromJson(Map<String, dynamic>.from(data['sale'] as Map));
        }
      }
    } catch (_) {
      // If Edge Function is not yet deployed, fallback to client transaction
    }

    return _fallbackDirectCheckout(request);
  }

  /// Direct client fallback checkout when Edge Function is not yet deployed
  Future<SaleModel> _fallbackDirectCheckout(SaleCheckoutRequest request) async {
    final user = _supabase.auth.currentUser;
    if (user == null) throw Exception('Silakan masuk terlebih dahulu');

    final now = DateTime.now();
    final datePrefix = DateFormat('yyyyMMdd').format(now);

    // Generate invoice number: INV-YYYYMMDD-XXXX
    final todaySales = await _supabase
        .from('sales')
        .select('invoice_number')
        .ilike('invoice_number', 'INV-$datePrefix-%')
        .order('created_at', ascending: false)
        .limit(1);

    int nextSeq = 1;
    if ((todaySales as List).isNotEmpty) {
      final lastInv = todaySales.first['invoice_number'] as String;
      final parts = lastInv.split('-');
      if (parts.length == 3) {
        nextSeq = (int.tryParse(parts[2]) ?? 0) + 1;
      }
    }
    final invoiceNumber = 'INV-$datePrefix-${nextSeq.toString().padLeft(4, '0')}';

    // 1. Insert sale header
    final saleData = await _supabase
        .from('sales')
        .insert({
          'invoice_number': invoiceNumber,
          'user_id': user.id,
          'subtotal': request.subtotal,
          'diskon': request.diskon,
          'grand_total': request.grandTotal,
          'bayar': request.bayar,
          'kembalian': request.kembalian,
          'payment_method': request.paymentMethod,
          'status': 'completed',
          'catatan': request.catatan,
          'sumber': 'flutter',
          'offline_id': request.offlineId,
        })
        .select()
        .single();

    final saleId = saleData['id'] as String;

    // 2. Insert items and update product stock + stock movements
    final List<SaleItemModel> savedItems = [];
    for (final item in request.items) {
      final itemData = await _supabase
          .from('sale_items')
          .insert({
            'sale_id': saleId,
            'product_id': item.productId,
            'qty': item.qty,
            'returned_qty': 0,
            'harga': item.harga,
            'harga_beli': item.hargaBeli,
            'diskon': item.diskon,
            'subtotal': item.subtotal,
          })
          .select('*, products(*)')
          .single();

      savedItems.add(SaleItemModel.fromJson(itemData));

      // Get current stock
      final prod = await _supabase.from('products').select('stok').eq('id', item.productId).single();
      final currentStock = (prod['stok'] as num).toInt();
      final newStock = currentStock - item.qty;

      // Update product stock
      await _supabase.from('products').update({
        'stok': newStock >= 0 ? newStock : 0,
        'updated_at': now.toIso8601String(),
      }).eq('id', item.productId);

      // Record stock movement
      await _supabase.from('stock_movements').insert({
        'product_id': item.productId,
        'type': 'out',
        'qty': item.qty,
        'stok_sebelum': currentStock,
        'stok_sesudah': newStock >= 0 ? newStock : 0,
        'reference_type': 'sale',
        'reference_id': saleId,
        'keterangan': 'Penjualan $invoiceNumber',
        'user_id': user.id,
      });
    }

    final fullSale = await getSaleById(saleId);
    return fullSale ?? SaleModel.fromJson(saleData).copyWith(items: savedItems);
  }

  /// Get sales list with filters
  Future<List<SaleModel>> getSales({
    String? startDate,
    String? endDate,
    String? status,
    int limit = 50,
    int offset = 0,
  }) async {
    var query = _supabase.from('sales').select('*, users(*), sale_items(*, products(*))');

    if (startDate != null && startDate.isNotEmpty) {
      query = query.gte('created_at', '${startDate}T00:00:00');
    }
    if (endDate != null && endDate.isNotEmpty) {
      query = query.lte('created_at', '${endDate}T23:59:59');
    }
    if (status != null && status.isNotEmpty && status != 'all') {
      query = query.eq('status', status);
    }

    final response = await query.order('created_at', ascending: false).range(offset, offset + limit - 1);
    return (response as List).map((json) => SaleModel.fromJson(json)).toList();
  }

  /// Get single sale detail by ID
  Future<SaleModel?> getSaleById(String id) async {
    final response = await _supabase
        .from('sales')
        .select('*, users(*), sale_items(*, products(*))')
        .eq('id', id)
        .maybeSingle();

    if (response == null) return null;
    return SaleModel.fromJson(response);
  }

  /// Process sale item return via Edge Function or direct
  Future<void> processReturn({
    required String saleId,
    required List<Map<String, dynamic>> returnItems,
    String? reason,
  }) async {
    try {
      final response = await _supabase.functions.invoke(
        'process-return',
        body: {
          'sale_id': saleId,
          'items': returnItems,
          'reason': reason,
        },
      );
      if (response.status == 200) return;
    } catch (_) {
      // Fallback
    }

    final user = _supabase.auth.currentUser;
    for (final ret in returnItems) {
      final saleItemId = ret['sale_item_id'] as String;
      final returnQty = ret['qty'] as int;

      // Update sale item returned qty
      final itemData = await _supabase.from('sale_items').select().eq('id', saleItemId).single();
      final currentRet = (itemData['returned_qty'] as num).toInt();
      final productId = itemData['product_id'] as String;

      await _supabase.from('sale_items').update({
        'returned_qty': currentRet + returnQty,
      }).eq('id', saleItemId);

      // Restore stock
      final prod = await _supabase.from('products').select('stok').eq('id', productId).single();
      final currentStock = (prod['stok'] as num).toInt();
      final restoredStock = currentStock + returnQty;

      await _supabase.from('products').update({'stok': restoredStock}).eq('id', productId);

      // Record stock movement
      await _supabase.from('stock_movements').insert({
        'product_id': productId,
        'type': 'return',
        'qty': returnQty,
        'stok_sebelum': currentStock,
        'stok_sesudah': restoredStock,
        'reference_type': 'sale_return',
        'reference_id': saleId,
        'keterangan': 'Retur item: ${reason ?? "-"}',
        'user_id': user?.id,
      });
    }

    // Update sale status to partial_return or returned
    await _supabase.from('sales').update({'status': 'returned'}).eq('id', saleId);
  }

  /// Get today summary stats (revenue, transactions count)
  Future<Map<String, dynamic>> getTodayStats() async {
    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final response = await _supabase
        .from('sales')
        .select('grand_total, status')
        .gte('created_at', '${today}T00:00:00')
        .lte('created_at', '${today}T23:59:59');

    double totalRevenue = 0.0;
    int totalTransactions = 0;

    for (final row in response as List) {
      if (row['status'] != 'cancelled') {
        totalRevenue += (row['grand_total'] as num?)?.toDouble() ?? 0.0;
        totalTransactions++;
      }
    }

    return {
      'totalRevenue': totalRevenue,
      'totalTransactions': totalTransactions,
    };
  }
}

extension SaleModelExtension on SaleModel {
  SaleModel copyWith({
    String? id,
    String? invoiceNumber,
    String? userId,
    double? subtotal,
    double? diskon,
    double? grandTotal,
    double? bayar,
    double? kembalian,
    String? paymentMethod,
    String? status,
    String? catatan,
    String? sumber,
    String? offlineId,
    DateTime? createdAt,
    DateTime? updatedAt,
    UserModel? user,
    List<SaleItemModel>? items,
  }) {
    return SaleModel(
      id: id ?? this.id,
      invoiceNumber: invoiceNumber ?? this.invoiceNumber,
      userId: userId ?? this.userId,
      subtotal: subtotal ?? this.subtotal,
      diskon: diskon ?? this.diskon,
      grandTotal: grandTotal ?? this.grandTotal,
      bayar: bayar ?? this.bayar,
      kembalian: kembalian ?? this.kembalian,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      status: status ?? this.status,
      catatan: catatan ?? this.catatan,
      sumber: sumber ?? this.sumber,
      offlineId: offlineId ?? this.offlineId,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      user: user ?? this.user,
      items: items ?? this.items,
    );
  }
}
