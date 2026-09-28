import 'package:uuid/uuid.dart';
import '../models/activity_log_model.dart';
import 'auth_service.dart';
import 'firebase_service.dart';

class ActivityLogService {
  final FirebaseService _firebase = FirebaseService();
  final Uuid _uuid = const Uuid();

  Future<void> log(String action, String description, {String? userId, String? userName}) async {
    try {
      final currentUser = AuthService().currentUser;
      final effectiveUserId = userId ?? currentUser?.id;
      final effectiveUserName = userName ?? currentUser?.name ?? 'Sistem';

      final logId = _uuid.v4();
      final now = DateTime.now();

      final model = ActivityLogModel(
        id: logId,
        userId: effectiveUserId,
        userName: effectiveUserName,
        action: action,
        description: description,
        createdAt: now,
      );

      await _firebase.put('activity_logs/$logId', model.toJson());
    } catch (_) {
      // Fire-and-forget logging to avoid breaking main workflow
    }
  }

  Future<List<ActivityLogModel>> getLogs({int limit = 100}) async {
    final data = await _firebase.get('activity_logs');
    if (data == null || data is! Map) return [];

    final list = <ActivityLogModel>[];
    for (final entry in data.entries) {
      if (entry.value is Map) {
        list.add(ActivityLogModel.fromJson(Map<String, dynamic>.from(entry.value as Map)));
      }
    }

    list.sort((a, b) {
      final aDate = a.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.createdAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });

    if (list.length > limit) {
      return list.sublist(0, limit);
    }
    return list;
  }
}
