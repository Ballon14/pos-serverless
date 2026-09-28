import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'attendance_banner.dart';
import 'sidebar.dart';

class AppLayout extends ConsumerWidget {
  final Widget child;
  final String currentRoute;

  const AppLayout({
    super.key,
    required this.child,
    required this.currentRoute,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth >= 900;

    if (isDesktop) {
      return Scaffold(
        body: Row(
          children: [
            AppSidebar(currentRoute: currentRoute),
            Expanded(
              child: Column(
                children: [
                  const AttendanceBanner(),
                  Expanded(child: child),
                ],
              ),
            ),
          ],
        ),
      );
    }

    // Mobile / Tablet view
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Toko Mba Emi',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        elevation: 0,
      ),
      drawer: Drawer(
        child: AppSidebar(currentRoute: currentRoute),
      ),
      body: Column(
        children: [
          const AttendanceBanner(),
          Expanded(child: child),
        ],
      ),
    );
  }
}
