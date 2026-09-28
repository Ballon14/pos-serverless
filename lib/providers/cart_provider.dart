import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/product_model.dart';

class CartItem {
  final ProductModel product;
  final int qty;
  final double discount;

  const CartItem({
    required this.product,
    required this.qty,
    this.discount = 0.0,
  });

  /// Automatically derives price based on wholesale tiers
  double get unitPrice => product.getEffectivePrice(qty);

  double get originalSubtotal => unitPrice * qty;

  double get subtotal => (originalSubtotal - discount).clamp(0.0, double.infinity);

  CartItem copyWith({
    ProductModel? product,
    int? qty,
    double? discount,
  }) {
    return CartItem(
      product: product ?? this.product,
      qty: qty ?? this.qty,
      discount: discount ?? this.discount,
    );
  }
}

class CartState {
  final List<CartItem> items;
  final double discount;
  final String paymentMethod;
  final double paidAmount;
  final String? notes;

  const CartState({
    this.items = const [],
    this.discount = 0.0,
    this.paymentMethod = 'tunai',
    this.paidAmount = 0.0,
    this.notes,
  });

  int get totalItemCount => items.fold(0, (sum, item) => sum + item.qty);

  double get subtotal => items.fold(0.0, (sum, item) => sum + item.subtotal);

  double get grandTotal => (subtotal - discount).clamp(0.0, double.infinity);

  double get change => (paidAmount - grandTotal).clamp(0.0, double.infinity);

  bool get isPaymentValid => paidAmount >= grandTotal && grandTotal > 0;

  bool get isEmpty => items.isEmpty;

  CartState copyWith({
    List<CartItem>? items,
    double? discount,
    String? paymentMethod,
    double? paidAmount,
    String? notes,
  }) {
    return CartState(
      items: items ?? this.items,
      discount: discount ?? this.discount,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      paidAmount: paidAmount ?? this.paidAmount,
      notes: notes ?? this.notes,
    );
  }
}

class CartNotifier extends Notifier<CartState> {
  @override
  CartState build() => const CartState();

  void addItem(ProductModel product, [int qty = 1]) {
    final existingIndex = state.items.indexWhere((item) => item.product.id == product.id);

    if (existingIndex >= 0) {
      final existingItem = state.items[existingIndex];
      final newQty = existingItem.qty + qty;
      updateQty(product.id, newQty);
    } else {
      final newItem = CartItem(product: product, qty: qty);
      state = state.copyWith(items: [...state.items, newItem]);
    }
  }

  void removeItem(String productId) {
    state = state.copyWith(
      items: state.items.where((item) => item.product.id != productId).toList(),
    );
  }

  void updateQty(String productId, int newQty) {
    if (newQty <= 0) {
      removeItem(productId);
      return;
    }

    final updatedItems = state.items.map((item) {
      if (item.product.id == productId) {
        return item.copyWith(qty: newQty);
      }
      return item;
    }).toList();

    state = state.copyWith(items: updatedItems);
  }

  void incrementQty(String productId) {
    final item = state.items.firstWhere((i) => i.product.id == productId);
    updateQty(productId, item.qty + 1);
  }

  void decrementQty(String productId) {
    final item = state.items.firstWhere((i) => i.product.id == productId);
    updateQty(productId, item.qty - 1);
  }

  void setGlobalDiscount(double discount) {
    state = state.copyWith(discount: discount.clamp(0.0, state.subtotal));
  }

  void setItemDiscount(String productId, double discount) {
    final updatedItems = state.items.map((item) {
      if (item.product.id == productId) {
        return item.copyWith(discount: discount.clamp(0.0, item.originalSubtotal));
      }
      return item;
    }).toList();

    state = state.copyWith(items: updatedItems);
  }

  void setPaymentMethod(String method) {
    state = state.copyWith(paymentMethod: method);
  }

  void setPaidAmount(double amount) {
    state = state.copyWith(paidAmount: amount);
  }

  void setNotes(String? notes) {
    state = state.copyWith(notes: notes);
  }

  void clear() {
    state = const CartState();
  }
}

final cartProvider = NotifierProvider<CartNotifier, CartState>(CartNotifier.new);
