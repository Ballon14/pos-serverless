import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../screens/activity_logs/activity_log_screen.dart';
import '../screens/attendance/attendance_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/categories/category_screen.dart';
import '../screens/dashboard/dashboard_screen.dart';
import '../screens/pos/pos_screen.dart';
import '../screens/products/product_list_screen.dart';
import '../screens/purchases/purchase_screen.dart';
import '../screens/reports/report_screen.dart';
import '../screens/sales/sale_return_screen.dart';
import '../screens/sales/sales_history_screen.dart';
import '../screens/settings/settings_screen.dart';
import '../screens/stock/stock_movement_screen.dart';
import '../screens/suppliers/supplier_screen.dart';
import '../services/auth_service.dart';
import '../widgets/app_layout.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    redirect: (BuildContext context, GoRouterState state) {
      final isAuthenticated = AuthService().isAuthenticated;
      final isLoggingIn = state.matchedLocation == '/login';

      if (!isAuthenticated && !isLoggingIn) {
        return '/login';
      }

      if (isAuthenticated && isLoggingIn) {
        return '/';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) {
          return AppLayout(
            currentRoute: state.matchedLocation,
            child: child,
          );
        },
        routes: [
          GoRoute(
            path: '/',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/pos',
            builder: (context, state) => const PosScreen(),
          ),
          // Master Data
          GoRoute(
            path: '/categories',
            builder: (context, state) => const CategoryScreen(),
          ),
          GoRoute(
            path: '/products',
            builder: (context, state) => const ProductListScreen(),
          ),
          GoRoute(
            path: '/suppliers',
            builder: (context, state) => const SupplierScreen(),
          ),
          // Transaksi
          GoRoute(
            path: '/sales',
            builder: (context, state) => const SalesHistoryScreen(),
          ),
          GoRoute(
            path: '/sale-returns',
            builder: (context, state) => const SaleReturnScreen(),
          ),
          GoRoute(
            path: '/purchases',
            builder: (context, state) => const PurchaseScreen(),
          ),
          // Stok
          GoRoute(
            path: '/stock',
            builder: (context, state) => const StockMovementScreen(),
          ),
          GoRoute(
            path: '/stock-low',
            builder: (context, state) => const StockMovementScreen(initialLowStockTab: true),
          ),
          // Laporan
          GoRoute(
            path: '/reports',
            builder: (context, state) => const ReportScreen(),
          ),
          // Absensi
          GoRoute(
            path: '/attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          // Sistem
          GoRoute(
            path: '/activity-logs',
            builder: (context, state) => const ActivityLogScreen(),
          ),
          GoRoute(
            path: '/settings',
            builder: (context, state) => const SettingsScreen(),
          ),
        ],
      ),
    ],
  );
});
