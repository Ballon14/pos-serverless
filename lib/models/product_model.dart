import 'category_model.dart';

class GrosirTier {
  final int minQty;
  final double harga;

  const GrosirTier({
    required this.minQty,
    required this.harga,
  });

  factory GrosirTier.fromJson(Map<String, dynamic> json) {
    return GrosirTier(
      minQty: (json['min_qty'] ?? json['minQty'] ?? 0) as int,
      harga: (json['harga'] as num?)?.toDouble() ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'min_qty': minQty,
      'harga': harga,
    };
  }
}

class ProductModel {
  final String id;
  final String categoryId;
  final String name;
  final String sku;
  final double hargaBeli;
  final double hargaJual;
  final List<GrosirTier> grosirTiers;
  final int stok;
  final int minStok;
  final String satuan;
  final String? foto;
  final String? deskripsi;
  final bool isActive;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final CategoryModel? category;

  const ProductModel({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.sku,
    required this.hargaBeli,
    required this.hargaJual,
    this.grosirTiers = const [],
    this.stok = 0,
    this.minStok = 5,
    this.satuan = 'pcs',
    this.foto,
    this.deskripsi,
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
    this.category,
  });

  bool get isLowStock => stok <= minStok;
  bool get isOutOfStock => stok <= 0;

  /// Calculate price based on quantity with grosir tier detection
  double getEffectivePrice(int qty) {
    if (grosirTiers.isEmpty) return hargaJual;

    // Find applicable tiers sorted by minQty descending
    final applicableTiers = grosirTiers.where((t) => qty >= t.minQty).toList()
      ..sort((a, b) => b.minQty.compareTo(a.minQty));

    if (applicableTiers.isNotEmpty) {
      return applicableTiers.first.harga;
    }
    return hargaJual;
  }

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    List<GrosirTier> tiers = [];
    if (json['grosir_tiers'] != null && json['grosir_tiers'] is List) {
      tiers = (json['grosir_tiers'] as List)
          .map((item) => GrosirTier.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList();
    }

    CategoryModel? category;
    if (json['categories'] != null && json['categories'] is Map) {
      category = CategoryModel.fromJson(Map<String, dynamic>.from(json['categories'] as Map));
    } else if (json['category'] != null && json['category'] is Map) {
      category = CategoryModel.fromJson(Map<String, dynamic>.from(json['category'] as Map));
    }

    return ProductModel(
      id: json['id'] as String,
      categoryId: json['category_id'] as String? ?? '',
      name: json['name'] as String? ?? '',
      sku: json['sku'] as String? ?? '',
      hargaBeli: (json['harga_beli'] as num?)?.toDouble() ?? 0.0,
      hargaJual: (json['harga_jual'] as num?)?.toDouble() ?? 0.0,
      grosirTiers: tiers,
      stok: (json['stok'] as num?)?.toInt() ?? 0,
      minStok: (json['min_stok'] as num?)?.toInt() ?? 5,
      satuan: json['satuan'] as String? ?? 'pcs',
      foto: json['foto'] as String?,
      deskripsi: json['deskripsi'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
      category: category,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'category_id': categoryId,
      'name': name,
      'sku': sku,
      'harga_beli': hargaBeli,
      'harga_jual': hargaJual,
      'grosir_tiers': grosirTiers.map((t) => t.toJson()).toList(),
      'stok': stok,
      'min_stok': minStok,
      'satuan': satuan,
      'foto': foto,
      'deskripsi': deskripsi,
      'is_active': isActive,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }

  ProductModel copyWith({
    String? id,
    String? categoryId,
    String? name,
    String? sku,
    double? hargaBeli,
    double? hargaJual,
    List<GrosirTier>? grosirTiers,
    int? stok,
    int? minStok,
    String? satuan,
    String? foto,
    String? deskripsi,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
    CategoryModel? category,
  }) {
    return ProductModel(
      id: id ?? this.id,
      categoryId: categoryId ?? this.categoryId,
      name: name ?? this.name,
      sku: sku ?? this.sku,
      hargaBeli: hargaBeli ?? this.hargaBeli,
      hargaJual: hargaJual ?? this.hargaJual,
      grosirTiers: grosirTiers ?? this.grosirTiers,
      stok: stok ?? this.stok,
      minStok: minStok ?? this.minStok,
      satuan: satuan ?? this.satuan,
      foto: foto ?? this.foto,
      deskripsi: deskripsi ?? this.deskripsi,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      category: category ?? this.category,
    );
  }
}
