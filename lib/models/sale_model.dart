import 'product_model.dart';
import 'user_model.dart';

class SaleItemModel {
  final String id;
  final String saleId;
  final String productId;
  final int qty;
  final int returnedQty;
  final double harga;
  final double hargaBeli;
  final double diskon;
  final double subtotal;
  final ProductModel? product;
  final DateTime? createdAt;

  const SaleItemModel({
    required this.id,
    required this.saleId,
    required this.productId,
    required this.qty,
    this.returnedQty = 0,
    required this.harga,
    this.hargaBeli = 0.0,
    this.diskon = 0.0,
    required this.subtotal,
    this.product,
    this.createdAt,
  });

  int get returnableQty => qty - returnedQty;

  factory SaleItemModel.fromJson(Map<String, dynamic> json) {
    ProductModel? product;
    if (json['products'] != null && json['products'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['products'] as Map));
    } else if (json['product'] != null && json['product'] is Map) {
      product = ProductModel.fromJson(Map<String, dynamic>.from(json['product'] as Map));
    }

    return SaleItemModel(
      id: json['id'] as String? ?? '',
      saleId: json['sale_id'] as String? ?? '',
      productId: json['product_id'] as String? ?? '',
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      returnedQty: (json['returned_qty'] as num?)?.toInt() ?? 0,
      harga: (json['harga'] as num?)?.toDouble() ?? 0.0,
      hargaBeli: (json['harga_beli'] as num?)?.toDouble() ?? 0.0,
      diskon: (json['diskon'] as num?)?.toDouble() ?? 0.0,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      product: product,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'sale_id': saleId,
      'product_id': productId,
      'qty': qty,
      'returned_qty': returnedQty,
      'harga': harga,
      'harga_beli': hargaBeli,
      'diskon': diskon,
      'subtotal': subtotal,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
    };
  }
}

class SaleModel {
  final String id;
  final String invoiceNumber;
  final String userId;
  final double subtotal;
  final double diskon;
  final double grandTotal;
  final double bayar;
  final double kembalian;
  final String paymentMethod;
  final String status; // 'completed' | 'returned' | 'partial_return'
  final String? catatan;
  final String? sumber;
  final String? offlineId;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final UserModel? user;
  final List<SaleItemModel> items;

  const SaleModel({
    required this.id,
    required this.invoiceNumber,
    required this.userId,
    required this.subtotal,
    this.diskon = 0.0,
    required this.grandTotal,
    required this.bayar,
    required this.kembalian,
    this.paymentMethod = 'tunai',
    this.status = 'completed',
    this.catatan,
    this.sumber = 'web',
    this.offlineId,
    this.createdAt,
    this.updatedAt,
    this.user,
    this.items = const [],
  });

  bool get isCompleted => status == 'completed';
  bool get isReturned => status == 'returned';
  bool get isPartialReturn => status == 'partial_return';

  factory SaleModel.fromJson(Map<String, dynamic> json) {
    UserModel? user;
    if (json['users'] != null && json['users'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['users'] as Map));
    } else if (json['user'] != null && json['user'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['user'] as Map));
    }

    List<SaleItemModel> items = [];
    if (json['sale_items'] != null && json['sale_items'] is List) {
      items = (json['sale_items'] as List)
          .map((item) => SaleItemModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList();
    } else if (json['items'] != null && json['items'] is List) {
      items = (json['items'] as List)
          .map((item) => SaleItemModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList();
    }

    return SaleModel(
      id: json['id'] as String,
      invoiceNumber: json['invoice_number'] as String? ?? '',
      userId: json['user_id'] as String? ?? '',
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      diskon: (json['diskon'] as num?)?.toDouble() ?? 0.0,
      grandTotal: (json['grand_total'] as num?)?.toDouble() ?? 0.0,
      bayar: (json['bayar'] as num?)?.toDouble() ?? 0.0,
      kembalian: (json['kembalian'] as num?)?.toDouble() ?? 0.0,
      paymentMethod: json['payment_method'] as String? ?? 'tunai',
      status: json['status'] as String? ?? 'completed',
      catatan: json['catatan'] as String?,
      sumber: json['sumber'] as String? ?? 'web',
      offlineId: json['offline_id'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
      user: user,
      items: items,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'invoice_number': invoiceNumber,
      'user_id': userId,
      'subtotal': subtotal,
      'diskon': diskon,
      'grand_total': grandTotal,
      'bayar': bayar,
      'kembalian': kembalian,
      'payment_method': paymentMethod,
      'status': status,
      'catatan': catatan,
      'sumber': sumber,
      'offline_id': offlineId,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }
}
