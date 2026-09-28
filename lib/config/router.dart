import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../screens/attendance/attendance_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/dashboard/dashboard_screen.dart';
import '../screens/pos/pos_screen.dart';
import '../screens/products/product_list_screen.dart';
import '../screens/reports/report_screen.dart';
import '../screens/settings/settings_screen.dart';
import '../screens/stock/stock_movement_screen.dart';
import '../widgets/app_layout.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    redirect: (BuildContext context, GoRouterState state) {
      final session = Supabase.instance.client.auth.currentSession;
      final isLoggingIn = state.matchedLocation == '/login';

      if (session == null && !isLoggingIn) {
        return '/login';
      }

      if (session != null && isLoggingIn) {
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
          GoRoute(
            path: '/products',
            builder: (context, state) => const ProductListScreen(),
          ),
          GoRoute(
            path: '/stock',
            builder: (context, state) => const StockMovementScreen(),
          ),
          GoRoute(
            path: '/attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          GoRoute(
            path: '/reports',
            builder: (context, state) => const ReportScreen(),
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
