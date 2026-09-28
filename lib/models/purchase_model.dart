import 'product_model.dart';
import 'supplier_model.dart';
import 'user_model.dart';

class PurchaseItemModel {
  final String id;
  final String purchaseId;
  final String productId;
  final int qty;
  final double harga;
  final double subtotal;
  final ProductModel? product;

  const PurchaseItemModel({
    required this.id,
    required this.purchaseId,
    required this.productId,
    required this.qty,
    required this.harga,
    required this.subtotal,
    this.product,
  });

  factory PurchaseItemModel.fromJson(Map<String, dynamic> json) {
    ProductModel? product;
    if (json['product'] != null && json['product'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['product'] as Map));
    }

    return PurchaseItemModel(
      id: json['id'] as String? ?? '',
      purchaseId: json['purchase_id'] as String? ?? '',
      productId: json['product_id'] as String? ?? '',
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      harga: (json['harga'] as num?)?.toDouble() ?? 0.0,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      product: product,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'purchase_id': purchaseId,
      'product_id': productId,
      'qty': qty,
      'harga': harga,
      'subtotal': subtotal,
    };
  }
}

class PurchaseModel {
  final String id;
  final String invoiceNumber;
  final String supplierId;
  final String userId;
  final String tanggal; // YYYY-MM-DD
  final double total;
  final String status; // 'pending' | 'received' | 'cancelled'
  final String? keterangan;
  final String? fotoNota;
  final DateTime? createdAt;
  final SupplierModel? supplier;
  final UserModel? user;
  final List<PurchaseItemModel> items;

  const PurchaseModel({
    required this.id,
    required this.invoiceNumber,
    required this.supplierId,
    required this.userId,
    required this.tanggal,
    required this.total,
    this.status = 'received',
    this.keterangan,
    this.fotoNota,
    this.createdAt,
    this.supplier,
    this.user,
    this.items = const [],
  });

  factory PurchaseModel.fromJson(Map<String, dynamic> json) {
    SupplierModel? supplier;
    if (json['supplier'] != null && json['supplier'] is Map) {
      supplier = SupplierModel.fromJson(Map<String, dynamic>.from(json['supplier'] as Map));
    }

    UserModel? user;
    if (json['user'] != null && json['user'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['user'] as Map));
    }

    List<PurchaseItemModel> items = [];
    if (json['items'] != null && json['items'] is List) {
      items = (json['items'] as List)
          .map((i) => PurchaseItemModel.fromJson(Map<String, dynamic>.from(i as Map)))
          .toList();
    }

    return PurchaseModel(
      id: json['id'] as String? ?? '',
      invoiceNumber: json['invoice_number'] as String? ?? '',
      supplierId: json['supplier_id'] as String? ?? '',
      userId: json['user_id'] as String? ?? '',
      tanggal: json['tanggal'] as String? ?? '',
      total: (json['total'] as num?)?.toDouble() ?? 0.0,
      status: json['status'] as String? ?? 'received',
      keterangan: json['keterangan'] as String?,
      fotoNota: json['foto_nota'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      supplier: supplier,
      user: user,
      items: items,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'invoice_number': invoiceNumber,
      'supplier_id': supplierId,
      'user_id': userId,
      'tanggal': tanggal,
      'total': total,
      'status': status,
      'keterangan': keterangan,
      'foto_nota': fotoNota,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      'items': items.map((i) => i.toJson()).toList(),
    };
  }

  PurchaseModel copyWith({
    String? id,
    String? invoiceNumber,
    String? supplierId,
    String? userId,
    String? tanggal,
    double? total,
    String? status,
    String? keterangan,
    String? fotoNota,
    DateTime? createdAt,
    SupplierModel? supplier,
    UserModel? user,
    List<PurchaseItemModel>? items,
  }) {
    return PurchaseModel(
      id: id ?? this.id,
      invoiceNumber: invoiceNumber ?? this.invoiceNumber,
      supplierId: supplierId ?? this.supplierId,
      userId: userId ?? this.userId,
      tanggal: tanggal ?? this.tanggal,
      total: total ?? this.total,
      status: status ?? this.status,
      keterangan: keterangan ?? this.keterangan,
      fotoNota: fotoNota ?? this.fotoNota,
      createdAt: createdAt ?? this.createdAt,
      supplier: supplier ?? this.supplier,
      user: user ?? this.user,
      items: items ?? this.items,
    );
  }
}
