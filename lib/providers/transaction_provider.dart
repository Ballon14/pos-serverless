import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/purchase_model.dart';
import '../models/sale_return_model.dart';
import '../models/supplier_model.dart';
import '../services/purchase_service.dart';
import '../services/sale_return_service.dart';
import '../services/supplier_service.dart';

final supplierServiceProvider = Provider<SupplierService>((ref) {
  return SupplierService();
});

final suppliersProvider = FutureProvider<List<SupplierModel>>((ref) async {
  final service = ref.watch(supplierServiceProvider);
  return service.getSuppliers();
});

final purchaseServiceProvider = Provider<PurchaseService>((ref) {
  return PurchaseService();
});

final purchasesProvider = FutureProvider.family<List<PurchaseModel>, Map<String, String?>>((ref, filters) async {
  final service = ref.watch(purchaseServiceProvider);
  return service.getPurchases(
    startDate: filters['start_date'],
    endDate: filters['end_date'],
    supplierId: filters['supplier_id'],
  );
});

final saleReturnServiceProvider = Provider<SaleReturnService>((ref) {
  return SaleReturnService();
});

final saleReturnsProvider = FutureProvider<List<SaleReturnModel>>((ref) async {
  final service = ref.watch(saleReturnServiceProvider);
  return service.getSaleReturns();
});
