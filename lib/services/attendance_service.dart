import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';
import '../models/attendance_model.dart';
import '../models/leave_request_model.dart';
import '../models/user_model.dart';
import 'firebase_service.dart';

class AttendanceService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();

  String get _todayDateString => DateFormat('yyyy-MM-dd').format(DateTime.now());

  /// Get attendance record for a user today (WIB)
  Future<AttendanceModel?> getTodayAttendance(String userId) async {
    final today = _todayDateString;
    final data = await _firebase.get('attendances');
    if (data == null || data is! Map) return null;

    for (final entry in data.entries) {
      if (entry.value is Map) {
        final map = Map<String, dynamic>.from(entry.value as Map);
        if (map['user_id'] == userId && map['tanggal'] == today) {
          return AttendanceModel.fromJson(map);
        }
      }
    }
    return null;
  }

  /// Clock in for today
  Future<AttendanceModel> clockIn({
    required String userId,
    String? note,
  }) async {
    final today = _todayDateString;
    final now = DateTime.now();

    final existing = await getTodayAttendance(userId);
    if (existing != null && existing.isClockedIn) {
      throw Exception('Anda sudah melakukan Clock In hari ini.');
    }

    final id = _uuid.v4();
    final attendance = AttendanceModel(
      id: id,
      userId: userId,
      tanggal: today,
      clockIn: now,
      status: 'hadir',
      keterangan: note,
      createdAt: now,
      updatedAt: now,
    );

    await _firebase.put('attendances/$id', attendance.toJson());
    return attendance;
  }

  /// Clock out for today
  Future<AttendanceModel> clockOut({
    required String attendanceId,
    String? note,
  }) async {
    final now = DateTime.now();
    final updates = <String, dynamic>{
      'clock_out': now.toIso8601String(),
      'updated_at': now.toIso8601String(),
    };
    if (note != null && note.isNotEmpty) {
      updates['keterangan'] = note;
    }

    await _firebase.patch('attendances/$attendanceId', updates);
    final data = await _firebase.get('attendances/$attendanceId');
    return AttendanceModel.fromJson(Map<String, dynamic>.from(data as Map));
  }

  /// Get list of attendances with filters
  Future<List<AttendanceModel>> getAttendanceHistory({
    String? userId,
    String? startDate,
    String? endDate,
    int limit = 100,
  }) async {
    final data = await _firebase.get('attendances');
    if (data == null || data is! Map) return [];

    final usersData = await _firebase.get('users');
    final usersMap = <String, UserModel>{};
    if (usersData != null && usersData is Map) {
      for (final e in usersData.entries) {
        if (e.value is Map) {
          usersMap[e.key.toString()] = UserModel.fromJson(Map<String, dynamic>.from(e.value as Map));
        }
      }
    }

    final list = <AttendanceModel>[];
    for (final entry in data.entries) {
      if (entry.value is Map) {
        final a = AttendanceModel.fromJson(Map<String, dynamic>.from(entry.value as Map));

        if (userId != null && userId.isNotEmpty && a.userId != userId) {
          continue;
        }
        if (startDate != null && startDate.isNotEmpty && a.tanggal.compareTo(startDate) < 0) {
          continue;
        }
        if (endDate != null && endDate.isNotEmpty && a.tanggal.compareTo(endDate) > 0) {
          continue;
        }

        final user = usersMap[a.userId];
        list.add(AttendanceModel(
          id: a.id,
          userId: a.userId,
          tanggal: a.tanggal,
          clockIn: a.clockIn,
          clockOut: a.clockOut,
          status: a.status,
          keterangan: a.keterangan,
          createdAt: a.createdAt,
          updatedAt: a.updatedAt,
          user: user,
        ));
      }
    }

    list.sort((a, b) => b.tanggal.compareTo(a.tanggal));
    return list.take(limit).toList();
  }

  /// Submit leave / permission / sick request
  Future<LeaveRequestModel> submitLeaveRequest({
    required String userId,
    required String type,
    required String startDate,
    required String endDate,
    required String reason,
  }) async {
    final id = _uuid.v4();
    final now = DateTime.now();
    final request = LeaveRequestModel(
      id: id,
      userId: userId,
      tipe: type,
      tanggalMulai: startDate,
      tanggalSelesai: endDate,
      alasan: reason,
      status: 'pending',
      createdAt: now,
      updatedAt: now,
    );

    await _firebase.put('leave_requests/$id', request.toJson());
    return request;
  }

  /// Get list of leave requests
  Future<List<LeaveRequestModel>> getLeaveRequests({String? userId, String? status}) async {
    final data = await _firebase.get('leave_requests');
    if (data == null || data is! Map) return [];

    final list = <LeaveRequestModel>[];
    for (final entry in data.entries) {
      if (entry.value is Map) {
        final r = LeaveRequestModel.fromJson(Map<String, dynamic>.from(entry.value as Map));
        if (userId != null && userId.isNotEmpty && r.userId != userId) continue;
        if (status != null && status.isNotEmpty && status != 'all' && r.status != status) continue;
        list.add(r);
      }
    }

    list.sort((a, b) {
      final aDate = a.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });
    return list;
  }

  /// Approve or reject leave request
  Future<LeaveRequestModel> updateLeaveStatus({
    required String requestId,
    required String status,
    required String approvedByUserId,
  }) async {
    final now = DateTime.now().toIso8601String();
    await _firebase.patch('leave_requests/$requestId', {
      'status': status,
      'approved_by': approvedByUserId,
      'updated_at': now,
    });

    final data = await _firebase.get('leave_requests/$requestId');
    return LeaveRequestModel.fromJson(Map<String, dynamic>.from(data as Map));
  }
}
