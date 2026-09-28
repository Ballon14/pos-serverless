import 'user_model.dart';

class LeaveRequestModel {
  final String id;
  final String userId;
  final String tipe; // 'izin' | 'sakit' | 'cuti'
  final String tanggalMulai;
  final String tanggalSelesai;
  final String alasan;
  final String status; // 'pending' | 'approved' | 'rejected'
  final String? approvedBy;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final UserModel? user;

  const LeaveRequestModel({
    required this.id,
    required this.userId,
    required this.tipe,
    required this.tanggalMulai,
    required this.tanggalSelesai,
    required this.alasan,
    this.status = 'pending',
    this.approvedBy,
    this.createdAt,
    this.updatedAt,
    this.user,
  });

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';

  factory LeaveRequestModel.fromJson(Map<String, dynamic> json) {
    UserModel? user;
    if (json['users'] != null && json['users'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['users'] as Map));
    } else if (json['user'] != null && json['user'] is Map) {
      user = UserModel.fromJson(Map<String, dynamic>.from(json['user'] as Map));
    }

    return LeaveRequestModel(
      id: json['id'] as String,
      userId: json['user_id'] as String? ?? '',
      tipe: json['tipe'] as String? ?? 'izin',
      tanggalMulai: json['tanggal_mulai'] as String? ?? '',
      tanggalSelesai: json['tanggal_selesai'] as String? ?? '',
      alasan: json['alasan'] as String? ?? '',
      status: json['status'] as String? ?? 'pending',
      approvedBy: json['approved_by'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
      user: user,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'tipe': tipe,
      'tanggal_mulai': tanggalMulai,
      'tanggal_selesai': tanggalSelesai,
      'alasan': alasan,
      'status': status,
      'approved_by': approvedBy,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }
}
