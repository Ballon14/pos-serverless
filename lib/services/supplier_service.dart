import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/supplier_model.dart';

class SupplierService {
  final SupabaseClient _supabase;

  SupplierService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  Future<List<SupplierModel>> getSuppliers({bool onlyActive = true}) async {
    var query = _supabase.from('suppliers').select();
    if (onlyActive) {
      query = query.eq('is_active', true);
    }
    final response = await query.order('name');
    return (response as List).map((json) => SupplierModel.fromJson(json)).toList();
  }

  Future<SupplierModel?> getSupplierById(String id) async {
    final response = await _supabase.from('suppliers').select().eq('id', id).maybeSingle();
    if (response == null) return null;
    return SupplierModel.fromJson(response);
  }

  Future<SupplierModel> createSupplier({
    required String name,
    required String code,
    String? phone,
    String? email,
    String? address,
    String? contactPerson,
  }) async {
    final response = await _supabase
        .from('suppliers')
        .insert({
          'name': name,
          'code': code,
          'phone': phone,
          'email': email,
          'address': address,
          'contact_person': contactPerson,
          'is_active': true,
        })
        .select()
        .single();
    return SupplierModel.fromJson(response);
  }

  Future<SupplierModel> updateSupplier({
    required String id,
    String? name,
    String? code,
    String? phone,
    String? email,
    String? address,
    String? contactPerson,
    bool? isActive,
  }) async {
    final updates = <String, dynamic>{
      'updated_at': DateTime.now().toIso8601String(),
    };
    if (name != null) updates['name'] = name;
    if (code != null) updates['code'] = code;
    if (phone != null) updates['phone'] = phone;
    if (email != null) updates['email'] = email;
    if (address != null) updates['address'] = address;
    if (contactPerson != null) updates['contact_person'] = contactPerson;
    if (isActive != null) updates['is_active'] = isActive;

    final response = await _supabase.from('suppliers').update(updates).eq('id', id).select().single();
    return SupplierModel.fromJson(response);
  }

  Future<void> deleteSupplier(String id) async {
    await _supabase.from('suppliers').delete().eq('id', id);
  }
}
