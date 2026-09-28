import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/category_model.dart';
import '../models/product_model.dart';
import '../services/category_service.dart';
import '../services/product_service.dart';

final productServiceProvider = Provider<ProductService>((ref) {
  return ProductService();
});

final categoryServiceProvider = Provider<CategoryService>((ref) {
  return CategoryService();
});

final categoriesProvider = FutureProvider<List<CategoryModel>>((ref) async {
  final service = ref.watch(categoryServiceProvider);
  return service.getCategories();
});

class ProductFilter {
  final String search;
  final String? categoryId;

  const ProductFilter({
    this.search = '',
    this.categoryId,
  });

  ProductFilter copyWith({
    String? search,
    String? categoryId,
  }) {
    return ProductFilter(
      search: search ?? this.search,
      categoryId: categoryId ?? this.categoryId,
    );
  }
}

class ProductFilterNotifier extends Notifier<ProductFilter> {
  @override
  ProductFilter build() => const ProductFilter();

  void updateSearch(String search) {
    state = state.copyWith(search: search);
  }

  void updateCategory(String? categoryId) {
    state = state.copyWith(categoryId: categoryId);
  }

  void setFilter(ProductFilter filter) {
    state = filter;
  }
}

final productFilterProvider =
    NotifierProvider<ProductFilterNotifier, ProductFilter>(ProductFilterNotifier.new);

final productsProvider = FutureProvider<List<ProductModel>>((ref) async {
  final service = ref.watch(productServiceProvider);
  final filter = ref.watch(productFilterProvider);

  return service.getProducts(
    search: filter.search,
    categoryId: filter.categoryId,
  );
});

final lowStockProductsProvider = FutureProvider<List<ProductModel>>((ref) async {
  final service = ref.watch(productServiceProvider);
  return service.getLowStockProducts();
});
