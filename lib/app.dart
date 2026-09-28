import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'config/router.dart';
import 'config/theme.dart';

class TokoMbaEmiApp extends ConsumerWidget {
  const TokoMbaEmiApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'Toko Mba Emi - POS & Inventory',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.dark, // Default to premium dark theme
      routerConfig: router,
    );
  }
}

typedef StockKuApp = TokoMbaEmiApp;
