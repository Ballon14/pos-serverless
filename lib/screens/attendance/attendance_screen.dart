import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/theme.dart';
import '../../models/attendance_model.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../utils/formatters.dart';

final attendanceHistoryProvider = FutureProvider.autoDispose<List<AttendanceModel>>((ref) async {
  final service = ref.watch(attendanceServiceProvider);
  final user = ref.watch(authProvider).value;
  return service.getAttendanceHistory(userId: user?.isAdmin == true ? null : user?.id);
});

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  late Timer _clockTimer;
  DateTime _currentTime = DateTime.now();

  @override
  void initState() {
    super.initState();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        setState(() {
          _currentTime = DateTime.now();
        });
      }
    });
  }

  @override
  void dispose() {
    _clockTimer.cancel();
    super.dispose();
  }

  Future<void> _handleClockIn() async {
    try {
      await ref.read(todayAttendanceProvider.notifier).clockIn();
      ref.invalidate(attendanceHistoryProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Berhasil Clock In! Mode transaksi telah aktif.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal Clock In: $e')),
        );
      }
    }
  }

  Future<void> _handleClockOut() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Clock Out?'),
        content: const Text('Setelah Clock Out, aplikasi akan kembali ke Mode Baca.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.warning),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Clock Out Sekarang'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await ref.read(todayAttendanceProvider.notifier).clockOut();
        ref.invalidate(attendanceHistoryProvider);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Berhasil Clock Out. Sampai jumpa besok!')),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Gagal Clock Out: $e')),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final attendanceAsync = ref.watch(todayAttendanceProvider);
    final historyAsync = ref.watch(attendanceHistoryProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final dateFormatted = DateFormat('EEEE, dd MMMM yyyy', 'id_ID').format(_currentTime);
    final timeFormatted = DateFormat('HH:mm:ss').format(_currentTime);

    final attendance = attendanceAsync.value;
    final isClockedIn = attendance != null && attendance.isClockedIn;
    final isClockedOut = attendance != null && attendance.isClockedOut;

    return Scaffold(
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Presensi & Absensi Pegawai', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            const Text('Catat kehadiran harian untuk membuka akses kasir dan transaksi toko', style: TextStyle(color: Colors.grey, fontSize: 13)),

            const SizedBox(height: 24),

            // Clock In/Out Prominent Card
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            dateFormatted,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: isDark ? AppColors.darkTextSecondary : AppColors.lightTextSecondary,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            timeFormatted,
                            style: const TextStyle(
                              fontSize: 42,
                              fontWeight: FontWeight.bold,
                              letterSpacing: -1,
                            ),
                          ),
                          const SizedBox(height: 8),
                          if (isClockedIn && !isClockedOut)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.success.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                'Sedang Bekerja • Masuk: ${AppFormatters.formatTime(attendance.clockIn)}',
                                style: const TextStyle(color: AppColors.success, fontWeight: FontWeight.bold, fontSize: 12),
                              ),
                            )
                          else if (isClockedOut)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.primary.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                'Selesai Bekerja • Pulang: ${AppFormatters.formatTime(attendance.clockOut)}',
                                style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 12),
                              ),
                            )
                          else
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.warning.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text(
                                'Belum Clock In • Mode Baca Aktif',
                                style: TextStyle(color: AppColors.warning, fontWeight: FontWeight.bold, fontSize: 12),
                              ),
                            ),
                        ],
                      ),
                    ),

                    // Action Button
                    if (!isClockedIn)
                      ElevatedButton.icon(
                        onPressed: _handleClockIn,
                        icon: const Icon(Icons.login_rounded, size: 22),
                        label: const Text('Clock In Sekarang'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.success,
                          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 20),
                          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        ),
                      )
                    else if (!isClockedOut)
                      ElevatedButton.icon(
                        onPressed: _handleClockOut,
                        icon: const Icon(Icons.logout_rounded, size: 22),
                        label: const Text('Clock Out (Pulang)'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.warning,
                          foregroundColor: Colors.black,
                          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 20),
                          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        ),
                      )
                    else
                      const Chip(
                        avatar: Icon(Icons.check_circle_rounded, color: AppColors.success),
                        label: Text('Presensi Hari Ini Lengkap'),
                      ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 32),

            // Attendance History Table
            const Text('Riwayat Kehadiran', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),

            Card(
              child: historyAsync.when(
                data: (list) {
                  if (list.isEmpty) {
                    return const Padding(
                      padding: EdgeInsets.all(32),
                      child: Center(child: Text('Belum ada riwayat kehadiran.')),
                    );
                  }

                  return SizedBox(
                    width: double.infinity,
                    child: DataTable(
                      columns: const [
                        DataColumn(label: Text('Tanggal', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Nama Pegawai', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Clock In', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Clock Out', style: TextStyle(fontWeight: FontWeight.bold))),
                        DataColumn(label: Text('Status', style: TextStyle(fontWeight: FontWeight.bold))),
                      ],
                      rows: list.map((item) {
                        return DataRow(
                          cells: [
                            DataCell(Text(AppFormatters.formatDate(item.tanggal))),
                            DataCell(Text(item.user?.name ?? '-')),
                            DataCell(Text(AppFormatters.formatTime(item.clockIn))),
                            DataCell(Text(AppFormatters.formatTime(item.clockOut))),
                            DataCell(
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppColors.success.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  item.status.toUpperCase(),
                                  style: const TextStyle(
                                    color: AppColors.success,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        );
                      }).toList(),
                    ),
                  );
                },
                loading: () => const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator())),
                error: (err, _) => Center(child: Text('Gagal memuat: $err')),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
