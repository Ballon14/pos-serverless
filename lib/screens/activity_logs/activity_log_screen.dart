import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../providers/system_provider.dart';

class ActivityLogScreen extends ConsumerStatefulWidget {
  const ActivityLogScreen({super.key});

  @override
  ConsumerState<ActivityLogScreen> createState() => _ActivityLogScreenState();
}

class _ActivityLogScreenState extends ConsumerState<ActivityLogScreen> {
  final _searchController = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Color _getActionColor(String action) {
    if (action.startsWith('auth.')) return Colors.blue;
    if (action.startsWith('sale.')) return AppColors.success;
    if (action.startsWith('purchase.')) return AppColors.primary;
    if (action.startsWith('stock.')) return Colors.purple;
    if (action.startsWith('product.')) return Colors.amber.shade800;
    if (action.startsWith('category.') || action.startsWith('supplier.')) return Colors.teal;
    return Colors.grey;
  }

  IconData _getActionIcon(String action) {
    if (action.startsWith('auth.')) return Icons.login_rounded;
    if (action.startsWith('sale.')) return Icons.receipt_long_rounded;
    if (action.startsWith('purchase.')) return Icons.shopping_bag_rounded;
    if (action.startsWith('stock.')) return Icons.tune_rounded;
    if (action.startsWith('product.')) return Icons.inventory_2_rounded;
    return Icons.history_rounded;
  }

  @override
  Widget build(BuildContext context) {
    final logsAsync = ref.watch(activityLogsProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Log Aktivitas Sistem', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Catatan audit setiap tindakan penting yang dilakukan pengguna', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded),
                  tooltip: 'Segarkan Log',
                  onPressed: () => ref.invalidate(activityLogsProvider),
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Search
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari aktivitas, aksi, atau nama pengguna...',
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              ),
              onChanged: (val) => setState(() => _search = val.trim().toLowerCase()),
            ),

            const SizedBox(height: 20),

            // Log List
            Expanded(
              child: logsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text('Gagal memuat log: $e')),
                data: (logs) {
                  final filtered = logs.where((l) {
                    if (_search.isEmpty) return true;
                    return l.description.toLowerCase().contains(_search) ||
                        l.userName.toLowerCase().contains(_search) ||
                        l.action.toLowerCase().contains(_search);
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.history_toggle_off_rounded, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text('Belum ada log aktivitas yang tercatat', style: TextStyle(color: Colors.grey, fontSize: 16)),
                        ],
                      ),
                    );
                  }

                  return Card(
                    clipBehavior: Clip.antiAlias,
                    child: ListView.separated(
                      itemCount: filtered.length,
                      separatorBuilder: (context, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final log = filtered[index];
                        final actionColor = _getActionColor(log.action);

                        return ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          leading: CircleAvatar(
                            backgroundColor: actionColor.withValues(alpha: 0.15),
                            child: Icon(_getActionIcon(log.action), color: actionColor, size: 20),
                          ),
                          title: Row(
                            children: [
                              Text(log.userName, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: actionColor.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  log.action,
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: actionColor),
                                ),
                              ),
                            ],
                          ),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(log.description, style: const TextStyle(fontSize: 13)),
                          ),
                          trailing: Text(
                            log.createdAt != null ? DateFormat('dd/MM HH:mm').format(log.createdAt!) : '',
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
