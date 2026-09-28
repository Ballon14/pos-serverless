import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../config/theme.dart';
import '../providers/auth_provider.dart';
import '../providers/product_provider.dart';

class NavSection {
  final String title;
  final List<NavEntry> entries;
  final bool adminOnly;

  const NavSection({
    required this.title,
    required this.entries,
    this.adminOnly = false,
  });
}

class NavEntry {
  final String title;
  final IconData icon;
  final String route;
  final bool adminOnly;
  final bool isLowStockBadge;

  const NavEntry({
    required this.title,
    required this.icon,
    required this.route,
    this.adminOnly = false,
    this.isLowStockBadge = false,
  });
}

final List<NavSection> kNavSections = [
  const NavSection(
    title: 'UTAMA',
    entries: [
      NavEntry(title: 'Dashboard', icon: Icons.dashboard_rounded, route: '/'),
      NavEntry(title: 'Kasir (POS)', icon: Icons.point_of_sale_rounded, route: '/pos'),
    ],
  ),
  const NavSection(
    title: 'MASTER DATA',
    adminOnly: true,
    entries: [
      NavEntry(title: 'Kategori', icon: Icons.folder_open_rounded, route: '/categories', adminOnly: true),
      NavEntry(title: 'Produk', icon: Icons.inventory_2_rounded, route: '/products', adminOnly: true),
      NavEntry(title: 'Supplier', icon: Icons.storefront_rounded, route: '/suppliers', adminOnly: true),
    ],
  ),
  const NavSection(
    title: 'TRANSAKSI',
    entries: [
      NavEntry(title: 'Riwayat Penjualan', icon: Icons.receipt_long_rounded, route: '/sales'),
      NavEntry(title: 'Retur Penjualan', icon: Icons.assignment_return_rounded, route: '/sale-returns', adminOnly: true),
      NavEntry(title: 'Pembelian', icon: Icons.shopping_bag_rounded, route: '/purchases', adminOnly: true),
    ],
  ),
  const NavSection(
    title: 'STOK',
    adminOnly: true,
    entries: [
      NavEntry(title: 'Kartu Stok', icon: Icons.swap_horiz_rounded, route: '/stock', adminOnly: true),
      NavEntry(title: 'Stok Menipis', icon: Icons.warning_amber_rounded, route: '/stock-low', adminOnly: true, isLowStockBadge: true),
    ],
  ),
  const NavSection(
    title: 'LAPORAN',
    entries: [
      NavEntry(title: 'Laporan', icon: Icons.analytics_rounded, route: '/reports'),
    ],
  ),
  const NavSection(
    title: 'ABSENSI',
    entries: [
      NavEntry(title: 'Presensi & Izin', icon: Icons.badge_rounded, route: '/attendance'),
    ],
  ),
  const NavSection(
    title: 'SISTEM',
    adminOnly: true,
    entries: [
      NavEntry(title: 'Log Aktivitas', icon: Icons.history_rounded, route: '/activity-logs', adminOnly: true),
      NavEntry(title: 'Pengaturan Sistem', icon: Icons.settings_rounded, route: '/settings', adminOnly: true),
    ],
  ),
];

class AppSidebar extends ConsumerWidget {
  final String currentRoute;

  const AppSidebar({super.key, required this.currentRoute});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).value;
    final lowStockCount = ref.watch(lowStockProductsProvider).value?.length ?? 0;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      width: 260,
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
        border: Border(
          right: BorderSide(
            color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
            width: 1,
          ),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Brand Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [AppColors.primary, AppColors.secondary],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.store_mall_directory_rounded,
                    color: Colors.white,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Toko Mba Emi',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.5,
                      ),
                    ),
                    Text(
                      'Point of Sale & Stok',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const Divider(height: 1),

          // Menu list with sections
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              children: [
                for (final section in kNavSections) ...[
                  if (!section.adminOnly || (user != null && user.isAdmin)) ...[
                    Padding(
                      padding: const EdgeInsets.only(left: 12, top: 14, bottom: 6),
                      child: Text(
                        section.title,
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 1.0,
                          color: isDark ? Colors.grey.shade500 : Colors.grey.shade600,
                        ),
                      ),
                    ),
                    for (final item in section.entries) ...[
                      if (!item.adminOnly || (user != null && user.isAdmin)) ...[
                        _buildNavItem(context, item, isDark, lowStockCount),
                      ],
                    ],
                  ],
                ],
              ],
            ),
          ),

          const Divider(height: 1),

          // User Profile & Logout at Bottom
          if (user != null)
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: AppColors.primary.withValues(alpha: 0.2),
                    child: Text(
                      user.name.isNotEmpty ? user.name[0].toUpperCase() : 'U',
                      style: const TextStyle(
                        color: AppColors.primary,
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user.name,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Container(
                          margin: const EdgeInsets.only(top: 2),
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            user.role.toUpperCase(),
                            style: const TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.logout_rounded, size: 20),
                    tooltip: 'Keluar',
                    color: AppColors.error,
                    onPressed: () async {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (ctx) => AlertDialog(
                          title: const Text('Keluar dari Akun?'),
                          content: const Text('Apakah Anda yakin ingin keluar dari Toko Mba Emi?'),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(ctx, false),
                              child: const Text('Batal'),
                            ),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
                              onPressed: () => Navigator.pop(ctx, true),
                              child: const Text('Keluar'),
                            ),
                          ],
                        ),
                      );

                      if (confirm == true) {
                        await ref.read(authProvider.notifier).logout();
                        if (context.mounted) {
                          context.go('/login');
                        }
                      }
                    },
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildNavItem(BuildContext context, NavEntry item, bool isDark, int lowStockCount) {
    final isActive = currentRoute == item.route ||
        (item.route != '/' && currentRoute.startsWith(item.route));

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(10),
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () {
            context.go(item.route);
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
            decoration: BoxDecoration(
              color: isActive
                  ? AppColors.primary.withValues(alpha: 0.15)
                  : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              border: isActive
                  ? Border.all(color: AppColors.primary.withValues(alpha: 0.3), width: 1)
                  : null,
            ),
            child: Row(
              children: [
                Icon(
                  item.icon,
                  size: 19,
                  color: isActive
                      ? AppColors.primary
                      : (isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    item.title,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                      color: isActive
                          ? AppColors.primary
                          : (isDark ? AppColors.darkTextPrimary : AppColors.lightTextPrimary),
                    ),
                  ),
                ),
                if (item.isLowStockBadge && lowStockCount > 0)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.warning,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '$lowStockCount',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
