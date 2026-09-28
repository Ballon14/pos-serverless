import 'package:uuid/uuid.dart';
import '../models/category_model.dart';
import 'activity_log_service.dart';
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

  Future<void> toggleActive(String id) async {
    final cat = await getCategoryById(id);
    if (cat != null) {
      final newStatus = !cat.isActive;
      await _firebase.patch('categories/$id', {
        'is_active': newStatus,
        'updated_at': DateTime.now().toIso8601String(),
      });
      await ActivityLogService().log(
        'category.toggle',
        'Status kategori "${cat.name}" diubah menjadi ${newStatus ? 'Aktif' : 'Nonaktif'}.',
      );
    }
  }

  Future<void> deleteCategory(String id) async {
    final cat = await getCategoryById(id);
    final name = cat?.name ?? id;
    await _firebase.delete('categories/$id');
    await ActivityLogService().log('category.delete', 'Kategori "$name" dihapus.');
  }
}
