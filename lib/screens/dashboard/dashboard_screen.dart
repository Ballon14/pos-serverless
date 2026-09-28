import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/theme.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../../services/sale_service.dart';
import '../../utils/formatters.dart';

final todayStatsFutureProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final saleService = SaleService();
  return saleService.getTodayStats();
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).value;
    final statsAsync = ref.watch(todayStatsFutureProvider);
    final lowStockAsync = ref.watch(lowStockProductsProvider);
    final attendanceAsync = ref.watch(todayAttendanceProvider);
    final isLocked = ref.watch(isAttendanceGateLockedProvider);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Welcome Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Halo, ${user?.name ?? "Pengguna"} 👋',
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Berikut ringkasan performa toko hari ini',
                      style: TextStyle(
                        color: Theme.of(context).brightness == Brightness.dark
                            ? AppColors.darkTextSecondary
                            : AppColors.lightTextSecondary,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
                ElevatedButton.icon(
                  onPressed: isLocked
                      ? null
                      : () {
                          context.go('/pos');
                        },
                  icon: const Icon(Icons.point_of_sale_rounded, size: 18),
                  label: const Text('Buka Kasir'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 28),

            // Stat Cards Grid
            LayoutBuilder(
              builder: (context, constraints) {
                final isWide = constraints.maxWidth >= 800;
                final crossAxisCount = isWide ? 4 : 2;

                return GridView.count(
                  crossAxisCount: crossAxisCount,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  childAspectRatio: isWide ? 1.7 : 1.3,
                  children: [
                    // Card 1: Penjualan Hari Ini
                    _StatCard(
                      title: 'Penjualan Hari Ini',
                      value: statsAsync.when(
                        data: (stats) => AppFormatters.formatRupiah(stats['totalRevenue']),
                        loading: () => 'Memuat...',
                        error: (_, _) => 'Rp 0',
                      ),
                      subtitle: statsAsync.when(
                        data: (stats) => '${stats['totalTransactions']} Transaksi',
                        loading: () => '',
                        error: (_, _) => '',
                      ),
                      icon: Icons.payments_rounded,
                      iconColor: AppColors.success,
                      iconBgColor: AppColors.success.withValues(alpha: 0.15),
                    ),

                    // Card 2: Status Absensi
                    _StatCard(
                      title: 'Status Absensi',
                      value: attendanceAsync.when(
                        data: (att) => att != null && att.isClockedIn ? 'Hadir' : 'Belum Absen',
                        loading: () => 'Memuat...',
                        error: (_, _) => 'Error',
                      ),
                      subtitle: attendanceAsync.when(
                        data: (att) => att != null && att.clockIn != null
                            ? 'Masuk ${AppFormatters.formatTime(att.clockIn)}'
                            : 'Wajib Clock In',
                        loading: () => '',
                        error: (_, _) => '',
                      ),
                      icon: Icons.access_time_filled_rounded,
                      iconColor: attendanceAsync.value?.isClockedIn == true
                          ? AppColors.primary
                          : AppColors.warning,
                      iconBgColor: (attendanceAsync.value?.isClockedIn == true
                              ? AppColors.primary
                              : AppColors.warning)
                          .withValues(alpha: 0.15),
                    ),

                    // Card 3: Stok Menipis
                    _StatCard(
                      title: 'Stok Menipis',
                      value: lowStockAsync.when(
                        data: (list) => '${list.length} Produk',
                        loading: () => 'Memuat...',
                        error: (_, _) => '0 Produk',
                      ),
                      subtitle: 'Perlu restock segera',
                      icon: Icons.warning_amber_rounded,
                      iconColor: AppColors.warning,
                      iconBgColor: AppColors.warning.withValues(alpha: 0.15),
                    ),

                    // Card 4: Peran Akun
                    _StatCard(
                      title: 'Peran Akun',
                      value: user?.role.toUpperCase() ?? 'KASIR',
                      subtitle: user?.email ?? '',
                      icon: Icons.shield_rounded,
                      iconColor: AppColors.secondary,
                      iconBgColor: AppColors.secondary.withValues(alpha: 0.15),
                    ),
                  ],
                );
              },
            ),

            const SizedBox(height: 32),

            // Low Stock Warning Section
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Peringatan Stok Rendah',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: () {
                    context.go('/products');
                  },
                  child: const Text('Lihat Semua Produk →'),
                ),
              ],
            ),
            const SizedBox(height: 12),

            lowStockAsync.when(
              data: (products) {
                if (products.isEmpty) {
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(32),
                      child: Center(
                        child: Column(
                          children: [
                            Icon(Icons.check_circle_rounded, size: 40, color: AppColors.success),
                            const SizedBox(height: 12),
                            const Text(
                              'Semua stok aman! Tidak ada produk di bawah batas minimum.',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                return Card(
                  child: ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: products.take(5).length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final item = products[index];
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: AppColors.warning.withValues(alpha: 0.15),
                          child: const Icon(Icons.inventory_2_rounded, color: AppColors.warning, size: 20),
                        ),
                        title: Text(item.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                        subtitle: Text('SKU: ${item.sku} • Min: ${item.minStok} ${item.satuan}'),
                        trailing: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.error.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Sisa ${item.stok} ${item.satuan}',
                            style: const TextStyle(
                              color: AppColors.error,
                              fontWeight: FontWeight.bold,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                );
              },
              loading: () => const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator())),
              error: (err, _) => Text('Gagal memuat: $err'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  final Color iconColor;
  final Color iconBgColor;

  const _StatCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.icon,
    required this.iconColor,
    required this.iconBgColor,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: iconBgColor,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, color: iconColor, size: 20),
                ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    letterSpacing: -0.5,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (subtitle.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 11,
                      color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
