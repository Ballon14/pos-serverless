import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/attendance_model.dart';
import '../services/attendance_service.dart';
import 'auth_provider.dart';

final attendanceServiceProvider = Provider<AttendanceService>((ref) {
  return AttendanceService();
});

class TodayAttendanceNotifier extends AsyncNotifier<AttendanceModel?> {
  @override
  Future<AttendanceModel?> build() async {
    final user = ref.watch(authProvider).value;
    if (user == null) return null;
    final service = ref.watch(attendanceServiceProvider);
    return service.getTodayAttendance(user.id);
  }

  Future<void> clockIn({String? note}) async {
    final user = ref.read(authProvider).value;
    if (user == null) throw Exception('Silakan masuk terlebih dahulu');

    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final service = ref.read(attendanceServiceProvider);
      return service.clockIn(userId: user.id, note: note);
    });
    if (state.hasError) {
      throw state.error!;
    }
  }

  Future<void> clockOut({String? note}) async {
    final current = state.value;
    if (current == null) throw Exception('Data absensi tidak ditemukan');

    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final service = ref.read(attendanceServiceProvider);
      return service.clockOut(attendanceId: current.id, note: note);
    });
    if (state.hasError) {
      throw state.error!;
    }
  }
}

final todayAttendanceProvider =
    AsyncNotifierProvider<TodayAttendanceNotifier, AttendanceModel?>(TodayAttendanceNotifier.new);

/// Returns true if user is non-admin and has NOT clocked in today (Read-Only Mode)
final isAttendanceGateLockedProvider = Provider<bool>((ref) {
  final authAsync = ref.watch(authProvider);
  final user = authAsync.value;
  if (user == null) return false;

  // Admin is fully exempt
  if (user.isAdmin) return false;

  final attendanceAsync = ref.watch(todayAttendanceProvider);
  final attendance = attendanceAsync.value;

  // If attendance is null or not clocked in (or clocked out), gate is locked
  if (attendance == null || !attendance.isClockedIn || attendance.isClockedOut) {
    return true;
  }

  return false;
});
