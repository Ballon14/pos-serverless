import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../config/theme.dart';
import '../providers/attendance_provider.dart';

class AttendanceBanner extends ConsumerWidget {
  const AttendanceBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isLocked = ref.watch(isAttendanceGateLockedProvider);

    if (!isLocked) {
      return const SizedBox.shrink();
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.warning.withValues(alpha: 0.15),
        border: Border(
          bottom: BorderSide(
            color: AppColors.warning.withValues(alpha: 0.3),
            width: 1,
          ),
        ),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.warning_amber_rounded,
            color: AppColors.warning,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Mode Baca — Anda belum melakukan Clock In hari ini. Transaksi kasir dan perubahan data dinonaktifkan.',
              style: TextStyle(
                color: Theme.of(context).brightness == Brightness.dark
                    ? const Color(0xFFFDE68A)
                    : const Color(0xFF92400E),
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          const SizedBox(width: 10),
          ElevatedButton.icon(
            onPressed: () {
              context.go('/attendance');
            },
            icon: const Icon(Icons.login_rounded, size: 16),
            label: const Text('Clock In'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.warning,
              foregroundColor: Colors.black,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
              visualDensity: VisualDensity.compact,
            ),
          ),
        ],
      ),
    );
  }
}
