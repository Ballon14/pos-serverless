import 'user_model.dart';

class AttendanceModel {
  final String id;
  final String userId;
  final String tanggal;
  final DateTime? clockIn;
  final DateTime? clockOut;
  final String status; // 'hadir' | 'terlambat' | 'izin' | 'sakit' | 'cuti' | 'alpha'
  final String? keterangan;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final UserModel? user;

  const AttendanceModel({
    required this.id,
    required this.userId,
    required this.tanggal,
    this.clockIn,
    this.clockOut,
    this.status = 'hadir',
    this.keterangan,
    this.createdAt,
    this.updatedAt,
    this.user,
  });

  bool get isClockedIn => clockIn != null;
  bool get isClockedOut => clockOut != null;
  bool get isCurrentlyWorking => isClockedIn && !isClockedOut;

  factory AttendanceModel.fromJson(Map<String, dynamic> json) {
    UserModel? user;
    if (json['users'] != null && json['users'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['users'] as Map));
    } else if (json['user'] != null && json['user'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['user'] as Map));
    }

    return AttendanceModel(
      id: json['id'] as String,
      userId: json['user_id'] as String? ?? '',
      tanggal: json['tanggal'] as String? ?? '',
      clockIn: json['clock_in'] != null ? DateTime.tryParse(json['clock_in'].toString()) : null,
      clockOut: json['clock_out'] != null ? DateTime.tryParse(json['clock_out'].toString()) : null,
      status: json['status'] as String? ?? 'hadir',
      keterangan: json['keterangan'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
      user: user,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'tanggal': tanggal,
      if (clockIn != null) 'clock_in': clockIn!.toIso8601String(),
      if (clockOut != null) 'clock_out': clockOut!.toIso8601String(),
      'status': status,
      'keterangan': keterangan,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }
}
