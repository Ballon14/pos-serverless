import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/product_model.dart';

class ProductService {
  final SupabaseClient _supabase;

  ProductService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  Future<List<ProductModel>> getProducts({
    String? search,
    String? categoryId,
    bool onlyActive = true,
    int limit = 100,
    int offset = 0,
  }) async {
    var query = _supabase.from('products').select('*, categories(*)');

    if (onlyActive) {
      query = query.eq('is_active', true);
    }

    if (categoryId != null && categoryId.isNotEmpty && categoryId != 'all') {
      query = query.eq('category_id', categoryId);
    }

    if (search != null && search.trim().isNotEmpty) {
      query = query.or('name.ilike.%${search.trim()}%,sku.ilike.%${search.trim()}%');
    }

    final response = await query.order('name').range(offset, offset + limit - 1);
    return (response as List).map((json) => ProductModel.fromJson(json)).toList();
  }

  Future<ProductModel?> getProductById(String id) async {
    final response = await _supabase.from('products').select('*, categories(*)').eq('id', id).maybeSingle();
    if (response == null) return null;
    return ProductModel.fromJson(response);
  }

  Future<ProductModel?> getProductBySku(String sku) async {
    final response = await _supabase
        .from('products')
        .select('*, categories(*)')
        .eq('sku', sku.trim())
        .eq('is_active', true)
        .maybeSingle();
    if (response == null) return null;
    return ProductModel.fromJson(response);
  }

  Future<List<ProductModel>> getLowStockProducts() async {
    // Products where stok <= min_stok
    final response = await _supabase
        .from('products')
        .select('*, categories(*)')
        .eq('is_active', true)
        .order('stok')
        .limit(20);

    return (response as List)
        .map((json) => ProductModel.fromJson(json))
        .where((p) => p.isLowStock)
        .toList();
  }

  Future<ProductModel> createProduct(ProductModel product) async {
    final data = product.toJson();
    data.remove('id'); // let Postgres generate UUID
    final response = await _supabase.from('products').insert(data).select('*, categories(*)').single();
    return ProductModel.fromJson(response);
  }

  Future<ProductModel> updateProduct(ProductModel product) async {
    final data = product.toJson();
    data['updated_at'] = DateTime.now().toIso8601String();
    final response = await _supabase
        .from('products')
        .update(data)
        .eq('id', product.id)
        .select('*, categories(*)')
        .single();
    return ProductModel.fromJson(response);
  }

  Future<void> deleteProduct(String id) async {
    // Soft-delete or hard delete
    await _supabase.from('products').update({
      'is_active': false,
      'updated_at': DateTime.now().toIso8601String(),
    }).eq('id', id);
  }
}
