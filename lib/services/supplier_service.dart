import 'package:uuid/uuid.dart';
import '../models/supplier_model.dart';
import 'firebase_service.dart';

import 'activity_log_service.dart';

class SupplierService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();
  final ActivityLogService _logger = ActivityLogService();

  Future<List<SupplierModel>> getSuppliers({bool onlyActive = true}) async {
    final data = await _firebase.get('suppliers');
    if (data == null || data is! Map) return [];

    final list = <SupplierModel>[];
    for (final entry in data.entries) {
      if (entry.value is Map) {
        final supplier = SupplierModel.fromJson(Map<String, dynamic>.from(entry.value as Map));
        if (!onlyActive || supplier.isActive) {
          list.add(supplier);
        }
      }
    }

    list.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));
    return list;
  }

  Future<SupplierModel?> getSupplierById(String id) async {
    final data = await _firebase.get('suppliers/$id');
    if (data == null || data is! Map) return null;
    return SupplierModel.fromJson(Map<String, dynamic>.from(data));
  }

  Future<SupplierModel> createSupplier({
    required String name,
    required String code,
    String? phone,
    String? email,
    String? address,
    String? contactPerson,
  }) async {
    final id = _uuid.v4();
    final now = DateTime.now().toIso8601String();
    final supplier = SupplierModel(
      id: id,
      name: name,
      code: code,
      phone: phone,
      email: email,
      address: address,
      contactPerson: contactPerson,
      isActive: true,
      createdAt: DateTime.parse(now),
    );

    await _firebase.put('suppliers/$id', supplier.toJson());
    await _logger.log('supplier.create', 'Supplier "$name" ($code) ditambahkan.');
    return supplier;
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

    await _firebase.patch('suppliers/$id', updates);
    final updated = await getSupplierById(id);
    await _logger.log('supplier.update', 'Supplier "${updated?.name ?? id}" diperbarui.');
    return updated!;
  }

  Future<void> deleteSupplier(String id) async {
    final sup = await getSupplierById(id);
    final name = sup?.name ?? id;
    await _firebase.delete('suppliers/$id');
    await _logger.log('supplier.delete', 'Supplier "$name" dihapus.');
  }
}
