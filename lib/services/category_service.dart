import 'package:uuid/uuid.dart';
import '../models/category_model.dart';
import 'firebase_service.dart';

class CategoryService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();

  Future<List<CategoryModel>> getCategories({bool onlyActive = true}) async {
    final data = await _firebase.get('categories');
    if (data == null || data is! Map) return [];

    final list = <CategoryModel>[];
    for (final entry in data.entries) {
      if (entry.value is Map) {
        final cat = CategoryModel.fromJson(Map<String, dynamic>.from(entry.value as Map));
        if (!onlyActive || cat.isActive) {
          list.add(cat);
        }
      }
    }

    list.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));
    return list;
  }

  Future<CategoryModel?> getCategoryById(String id) async {
    final data = await _firebase.get('categories/$id');
    if (data == null || data is! Map) return null;
    return CategoryModel.fromJson(Map<String, dynamic>.from(data));
  }

  Future<CategoryModel> createCategory({
    required String name,
    required String slug,
    String? description,
  }) async {
    final id = _uuid.v4();
    final now = DateTime.now().toIso8601String();
    final category = CategoryModel(
      id: id,
      name: name,
      slug: slug,
      description: description,
      isActive: true,
      createdAt: DateTime.parse(now),
    );

    await _firebase.put('categories/$id', category.toJson());
    return category;
  }

  Future<CategoryModel> updateCategory({
    required String id,
    String? name,
    String? slug,
    String? description,
    bool? isActive,
  }) async {
    final updates = <String, dynamic>{
      'updated_at': DateTime.now().toIso8601String(),
    };
    if (name != null) updates['name'] = name;
    if (slug != null) updates['slug'] = slug;
    if (description != null) updates['description'] = description;
    if (isActive != null) updates['is_active'] = isActive;

    await _firebase.patch('categories/$id', updates);
    final updated = await getCategoryById(id);
    return updated!;
  }

  Future<void> deleteCategory(String id) async {
    await _firebase.patch('categories/$id', {
      'is_active': false,
      'updated_at': DateTime.now().toIso8601String(),
    });
  }
}
