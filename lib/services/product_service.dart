import 'package:uuid/uuid.dart';
import '../models/category_model.dart';
import '../models/product_model.dart';
import 'firebase_service.dart';

import 'activity_log_service.dart';

class ProductService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();
  final ActivityLogService _logger = ActivityLogService();

  Future<List<ProductModel>> getProducts({
    String? search,
    String? categoryId,
    bool onlyActive = true,
    int limit = 100,
    int offset = 0,
  }) async {
    final productsData = await _firebase.get('products');
    if (productsData == null || productsData is! Map) return [];

    final categoriesData = await _firebase.get('categories');
    final Map<String, CategoryModel> categoriesMap = {};
    if (categoriesData != null && categoriesData is Map) {
      for (final entry in categoriesData.entries) {
        if (entry.value is Map) {
          categoriesMap[entry.key.toString()] =
              CategoryModel.fromJson(Map<String, dynamic>.from(entry.value as Map));
        }
      }
    }

    final list = <ProductModel>[];
    final cleanSearch = search?.trim().toLowerCase();

    for (final entry in productsData.entries) {
      if (entry.value is Map) {
        final map = Map<String, dynamic>.from(entry.value as Map);
        final p = ProductModel.fromJson(map);

        if (onlyActive && !p.isActive) continue;

        if (categoryId != null && categoryId.isNotEmpty && categoryId != 'all') {
          if (p.categoryId != categoryId) continue;
        }

        if (cleanSearch != null && cleanSearch.isNotEmpty) {
          final matchName = p.name.toLowerCase().contains(cleanSearch);
          final matchSku = p.sku.toLowerCase().contains(cleanSearch);
          if (!matchName && !matchSku) continue;
        }

        final category = categoriesMap[p.categoryId];
        list.add(p.copyWith(category: category));
      }
    }

    list.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));

    if (offset >= list.length) return [];
    final endIndex = (offset + limit).clamp(0, list.length);
    return list.sublist(offset, endIndex);
  }

  Future<ProductModel?> getProductById(String id) async {
    final data = await _firebase.get('products/$id');
    if (data == null || data is! Map) return null;
    final p = ProductModel.fromJson(Map<String, dynamic>.from(data));

    final catData = await _firebase.get('categories/${p.categoryId}');
    if (catData != null && catData is Map) {
      return p.copyWith(category: CategoryModel.fromJson(Map<String, dynamic>.from(catData)));
    }
    return p;
  }

  Future<ProductModel?> getProductBySku(String sku) async {
    final productsData = await _firebase.get('products');
    if (productsData == null || productsData is! Map) return null;

    final cleanSku = sku.trim().toLowerCase();
    for (final entry in productsData.entries) {
      if (entry.value is Map) {
        final p = ProductModel.fromJson(Map<String, dynamic>.from(entry.value as Map));
        if (p.isActive && p.sku.toLowerCase() == cleanSku) {
          return p;
        }
      }
    }
    return null;
  }

  Future<List<ProductModel>> getLowStockProducts() async {
    final all = await getProducts(onlyActive: true, limit: 1000);
    final lowStock = all.where((p) => p.isLowStock).toList();
    lowStock.sort((a, b) => a.stok.compareTo(b.stok));
    return lowStock;
  }

  Future<String> generateSku(String categoryId) async {
    final catData = await _firebase.get('categories/$categoryId');
    String prefix = 'PRD';
    if (catData != null && catData is Map) {
      final name = (catData['name'] as String? ?? 'PRD').trim();
      final words = name.split(' ');
      if (words.length >= 2) {
        prefix = (words[0][0] + words[1][0]).toUpperCase();
      } else if (name.length >= 3) {
        prefix = name.substring(0, 3).toUpperCase();
      } else {
        prefix = name.toUpperCase();
      }
    }

    final productsData = await _firebase.get('products');
    int count = 1;
    if (productsData != null && productsData is Map) {
      for (final entry in productsData.entries) {
        if (entry.value is Map) {
          final sku = (entry.value['sku'] as String? ?? '');
          if (sku.startsWith('$prefix-')) {
            final parts = sku.split('-');
            if (parts.length >= 2) {
              final numVal = int.tryParse(parts.last) ?? 0;
              if (numVal >= count) count = numVal + 1;
            }
          }
        }
      }
    }
    return '$prefix-${count.toString().padLeft(3, '0')}';
  }

  Future<ProductModel> createProduct(ProductModel product) async {
    final id = product.id.isNotEmpty ? product.id : _uuid.v4();
    final now = DateTime.now().toIso8601String();
    final toSave = product.copyWith(
      id: id,
      createdAt: DateTime.parse(now),
    );

    await _firebase.put('products/$id', toSave.toJson());
    await _logger.log('product.create', 'Produk "${product.name}" (SKU: ${product.sku}) ditambahkan.');
    return toSave;
  }

  Future<ProductModel> updateProduct(ProductModel product) async {
    final now = DateTime.now().toIso8601String();
    final toSave = product.copyWith(updatedAt: DateTime.parse(now));
    await _firebase.put('products/${product.id}', toSave.toJson());
    await _logger.log('product.update', 'Produk "${product.name}" (SKU: ${product.sku}) diperbarui.');
    return toSave;
  }

  Future<void> deleteProduct(String id) async {
    final prodData = await _firebase.get('products/$id');
    final name = prodData != null && prodData is Map ? (prodData['name'] ?? id) : id;
    await _firebase.patch('products/$id', {
      'is_active': false,
      'updated_at': DateTime.now().toIso8601String(),
    });
    await _logger.log('product.delete', 'Produk "$name" dinonaktifkan/dihapus.');
  }
}
