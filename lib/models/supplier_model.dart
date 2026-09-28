class SupplierModel {
  final String id;
  final String name;
  final String code;
  final String? phone;
  final String? email;
  final String? address;
  final String? contactPerson;
  final bool isActive;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const SupplierModel({
    required this.id,
    required this.name,
    required this.code,
    this.phone,
    this.email,
    this.address,
    this.contactPerson,
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
  });

  factory SupplierModel.fromJson(Map<String, dynamic> json) {
    return SupplierModel(
      id: json['id'] as String,
      name: json['name'] as String? ?? '',
      code: json['code'] as String? ?? '',
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      address: json['address'] as String?,
      contactPerson: json['contact_person'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'code': code,
      'phone': phone,
      'email': email,
      'address': address,
      'contact_person': contactPerson,
      'is_active': isActive,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }

  SupplierModel copyWith({
    String? id,
    String? name,
    String? code,
    String? phone,
    String? email,
    String? address,
    String? contactPerson,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return SupplierModel(
      id: id ?? this.id,
      name: name ?? this.name,
      code: code ?? this.code,
      phone: phone ?? this.phone,
      email: email ?? this.email,
      address: address ?? this.address,
      contactPerson: contactPerson ?? this.contactPerson,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}
