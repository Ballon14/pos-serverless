class ActivityLogModel {
  final String id;
  final String? userId;
  final String userName;
  final String action;
  final String description;
  final DateTime? createdAt;

  const ActivityLogModel({
    required this.id,
    this.userId,
    required this.userName,
    required this.action,
    required this.description,
    this.createdAt,
  });

  factory ActivityLogModel.fromJson(Map<String, dynamic> json) {
    return ActivityLogModel(
      id: json['id'] as String? ?? '',
      userId: json['user_id'] as String?,
      userName: json['user_name'] as String? ?? 'Sistem',
      action: json['action'] as String? ?? '',
      description: json['description'] as String? ?? '',
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'user_name': userName,
      'action': action,
      'description': description,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
    };
  }
}
