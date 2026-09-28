import 'product_model.dart';
import 'sale_model.dart';
import 'user_model.dart';

class SaleReturnItemModel {
  final String id;
  final String saleReturnId;
  final String productId;
  final int qty;
  final double harga;
  final double subtotal;
  final ProductModel? product;

  const SaleReturnItemModel({
    required this.id,
    required this.saleReturnId,
    required this.productId,
    required this.qty,
    required this.harga,
    required this.subtotal,
    this.product,
  });

  factory SaleReturnItemModel.fromJson(Map<String, dynamic> json) {
    ProductModel? product;
    if (json['product'] != null && json['product'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['product'] as Map));
    }

    return SaleReturnItemModel(
      id: json['id'] as String? ?? '',
      saleReturnId: json['sale_return_id'] as String? ?? '',
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
      'sale_return_id': saleReturnId,
      'product_id': productId,
      'qty': qty,
      'harga': harga,
      'subtotal': subtotal,
    };
  }
}

class SaleReturnModel {
  final String id;
  final String saleId;
  final String returnNumber;
  final double totalRefund;
  final String alasan;
  final String status; // 'pending' | 'approved' | 'rejected'
  final String? processedBy;
  final DateTime? createdAt;
  final SaleModel? sale;
  final UserModel? processor;
  final List<SaleReturnItemModel> items;

  const SaleReturnModel({
    required this.id,
    required this.saleId,
    required this.returnNumber,
    required this.totalRefund,
    required this.alasan,
    this.status = 'approved',
    this.processedBy,
    this.createdAt,
    this.sale,
    this.processor,
    this.items = const [],
  });

  factory SaleReturnModel.fromJson(Map<String, dynamic> json) {
    SaleModel? sale;
    if (json['sale'] != null && json['sale'] is Map) {
      sale = SaleModel.fromJson(Map<String, dynamic>.from(json['sale'] as Map));
    }

    UserModel? processor;
    if (json['processor'] != null && json['processor'] is Map) {
      processor = UserModel.fromJson(Map<String, dynamic>.from(json['processor'] as Map));
    }

    List<SaleReturnItemModel> items = [];
    if (json['items'] != null && json['items'] is List) {
      items = (json['items'] as List)
          .map((i) => SaleReturnItemModel.fromJson(Map<String, dynamic>.from(i as Map)))
          .toList();
    }

    return SaleReturnModel(
      id: json['id'] as String? ?? '',
      saleId: json['sale_id'] as String? ?? '',
      returnNumber: json['return_number'] as String? ?? '',
      totalRefund: (json['total_refund'] as num?)?.toDouble() ?? 0.0,
      alasan: json['alasan'] as String? ?? '',
      status: json['status'] as String? ?? 'approved',
      processedBy: json['processed_by'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      sale: sale,
      processor: processor,
      items: items,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'sale_id': saleId,
      'return_number': returnNumber,
      'total_refund': totalRefund,
      'alasan': alasan,
      'status': status,
      'processed_by': processedBy,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      'items': items.map((i) => i.toJson()).toList(),
    };
  }

  SaleReturnModel copyWith({
    String? id,
    String? saleId,
    String? returnNumber,
    double? totalRefund,
    String? alasan,
    String? status,
    String? processedBy,
    DateTime? createdAt,
    SaleModel? sale,
    UserModel? processor,
    List<SaleReturnItemModel>? items,
  }) {
    return SaleReturnModel(
      id: id ?? this.id,
      saleId: saleId ?? this.saleId,
      returnNumber: returnNumber ?? this.returnNumber,
      totalRefund: totalRefund ?? this.totalRefund,
      alasan: alasan ?? this.alasan,
      status: status ?? this.status,
      processedBy: processedBy ?? this.processedBy,
      createdAt: createdAt ?? this.createdAt,
      sale: sale ?? this.sale,
      processor: processor ?? this.processor,
      items: items ?? this.items,
    );
  }
}
