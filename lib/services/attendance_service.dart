import 'package:intl/intl.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/attendance_model.dart';
import '../models/leave_request_model.dart';

class AttendanceService {
  final SupabaseClient _supabase;

  AttendanceService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  String get _todayDateString => DateFormat('yyyy-MM-dd').format(DateTime.now());

  /// Get attendance record for a user today (WIB)
  Future<AttendanceModel?> getTodayAttendance(String userId) async {
    final today = _todayDateString;
    final response = await _supabase
        .from('attendances')
        .select('*, users(*)')
        .eq('user_id', userId)
        .eq('tanggal', today)
        .maybeSingle();

    if (response == null) return null;
    return AttendanceModel.fromJson(response);
  }

  /// Clock in for today
  Future<AttendanceModel> clockIn({
    required String userId,
    String? note,
  }) async {
    final today = _todayDateString;
    final now = DateTime.now();

    // Check if already clocked in today
    final existing = await getTodayAttendance(userId);
    if (existing != null && existing.isClockedIn) {
      throw Exception('Anda sudah melakukan Clock In hari ini.');
    }

    final response = await _supabase
        .from('attendances')
        .insert({
          'user_id': userId,
          'tanggal': today,
          'clock_in': now.toIso8601String(),
          'status': 'hadir',
          'keterangan': note,
        })
        .select('*, users(*)')
        .single();

    return AttendanceModel.fromJson(response);
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

    final response = await _supabase
        .from('attendances')
        .update(updates)
        .eq('id', attendanceId)
        .select('*, users(*)')
        .single();

    return AttendanceModel.fromJson(response);
  }

  /// Get list of attendances with filters
  Future<List<AttendanceModel>> getAttendanceHistory({
    String? userId,
    String? startDate,
    String? endDate,
    int limit = 100,
  }) async {
    var query = _supabase.from('attendances').select('*, users(*)');

    if (userId != null && userId.isNotEmpty) {
      query = query.eq('user_id', userId);
    }
    if (startDate != null && startDate.isNotEmpty) {
      query = query.gte('tanggal', startDate);
    }
    if (endDate != null && endDate.isNotEmpty) {
      query = query.lte('tanggal', endDate);
    }

    final response = await query.order('tanggal', ascending: false).limit(limit);
    return (response as List).map((json) => AttendanceModel.fromJson(json)).toList();
  }

  /// Submit leave / permission / sick request
  Future<LeaveRequestModel> submitLeaveRequest({
    required String userId,
    required String type, // 'izin' | 'sakit' | 'cuti'
    required String startDate,
    required String endDate,
    required String reason,
  }) async {
    final response = await _supabase
        .from('leave_requests')
        .insert({
          'user_id': userId,
          'tipe': type,
          'tanggal_mulai': startDate,
          'tanggal_selesai': endDate,
          'alasan': reason,
          'status': 'pending',
        })
        .select('*, users(*)')
        .single();

    return LeaveRequestModel.fromJson(response);
  }

  /// Get list of leave requests
  Future<List<LeaveRequestModel>> getLeaveRequests({String? userId, String? status}) async {
    var query = _supabase.from('leave_requests').select('*, users(*)');

    if (userId != null && userId.isNotEmpty) {
      query = query.eq('user_id', userId);
    }
    if (status != null && status.isNotEmpty && status != 'all') {
      query = query.eq('status', status);
    }

    final response = await query.order('created_at', ascending: false);
    return (response as List).map((json) => LeaveRequestModel.fromJson(json)).toList();
  }

  /// Approve or reject leave request
  Future<LeaveRequestModel> updateLeaveStatus({
    required String requestId,
    required String status, // 'approved' | 'rejected'
    required String approvedByUserId,
  }) async {
    final response = await _supabase
        .from('leave_requests')
        .update({
          'status': status,
          'approved_by': approvedByUserId,
          'updated_at': DateTime.now().toIso8601String(),
        })
        .eq('id', requestId)
        .select('*, users(*)')
        .single();

    return LeaveRequestModel.fromJson(response);
  }
}
