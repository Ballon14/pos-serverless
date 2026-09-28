import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/category_model.dart';

class CategoryService {
  final SupabaseClient _supabase;

  CategoryService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  Future<List<CategoryModel>> getCategories({bool onlyActive = true}) async {
    var query = _supabase.from('categories').select();
    if (onlyActive) {
      query = query.eq('is_active', true);
    }
    final response = await query.order('name');
    return (response as List).map((json) => CategoryModel.fromJson(json)).toList();
  }

  Future<CategoryModel?> getCategoryById(String id) async {
    final response = await _supabase.from('categories').select().eq('id', id).maybeSingle();
    if (response == null) return null;
    return CategoryModel.fromJson(response);
  }

  Future<CategoryModel> createCategory({
    required String name,
    required String slug,
    String? description,
  }) async {
    final response = await _supabase
        .from('categories')
        .insert({
          'name': name,
          'slug': slug,
          'description': description,
          'is_active': true,
        })
        .select()
        .single();
    return CategoryModel.fromJson(response);
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

    final response = await _supabase.from('categories').update(updates).eq('id', id).select().single();
    return CategoryModel.fromJson(response);
  }

  Future<void> deleteCategory(String id) async {
    await _supabase.from('categories').delete().eq('id', id);
  }
}
