import 'product_model.dart';
import 'user_model.dart';

class StockMovementModel {
  final String id;
  final String productId;
  final String type; // 'in' | 'out' | 'return' | 'adjustment'
  final int qty;
  final int stokSebelum;
  final int stokSesudah;
  final String? referenceType;
  final String? referenceId;
  final String? keterangan;
  final String? userId;
  final DateTime? createdAt;
  final ProductModel? product;
  final UserModel? user;

  const StockMovementModel({
    required this.id,
    required this.productId,
    required this.type,
    required this.qty,
    required this.stokSebelum,
    required this.stokSesudah,
    this.referenceType,
    this.referenceId,
    this.keterangan,
    this.userId,
    this.createdAt,
    this.product,
    this.user,
  });

  bool get isIn => type == 'in';
  bool get isOut => type == 'out';
  bool get isReturn => type == 'return';
  bool get isAdjustment => type == 'adjustment';

  factory StockMovementModel.fromJson(Map<String, dynamic> json) {
    ProductModel? product;
    if (json['products'] != null && json['products'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['products'] as Map));
    } else if (json['product'] != null && json['product'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['product'] as Map));
    }

    UserModel? user;
    if (json['users'] != null && json['users'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['users'] as Map));
    } else if (json['user'] != null && json['user'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['user'] as Map));
    }

    return StockMovementModel(
      id: json['id'] as String,
      productId: json['product_id'] as String? ?? '',
      type: json['type'] as String? ?? 'in',
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      stokSebelum: (json['stok_sebelum'] as num?)?.toInt() ?? 0,
      stokSesudah: (json['stok_sesudah'] as num?)?.toInt() ?? 0,
      referenceType: json['reference_type'] as String?,
      referenceId: json['reference_id'] as String?,
      keterangan: json['keterangan'] as String?,
      userId: json['user_id'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      product: product,
      user: user,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'product_id': productId,
      'type': type,
      'qty': qty,
      'stok_sebelum': stokSebelum,
      'stok_sesudah': stokSesudah,
      'reference_type': referenceType,
      'reference_id': referenceId,
      'keterangan': keterangan,
      'user_id': userId,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
    };
  }
}
