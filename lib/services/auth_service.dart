import 'dart:async';
import 'dart:convert';
import 'package:hive_flutter/hive_flutter.dart';
import '../config/constants.dart';
import '../models/user_model.dart';
import 'firebase_service.dart';

class AuthService {
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  final FirebaseService _firebase = FirebaseService();
  final _authStateController = StreamController<UserModel?>.broadcast();

  Stream<UserModel?> get onAuthStateChange => _authStateController.stream;

  Box get _sessionBox => Hive.box(AppConstants.sessionBoxName);

  UserModel? get currentUser {
    if (!Hive.isBoxOpen(AppConstants.sessionBoxName)) return null;
    final jsonStr = _sessionBox.get('current_user') as String?;
    if (jsonStr == null) return null;
    try {
      final map = jsonDecode(jsonStr) as Map<String, dynamic>;
      return UserModel.fromJson(map);
    } catch (_) {
      return null;
    }
  }

  bool get isAuthenticated => currentUser != null;

  /// Sign in with email and password against Firebase RTDB /users
  Future<UserModel> signIn({required String email, required String password}) async {
    final cleanEmail = email.trim().toLowerCase();

    // Query users from Firebase Realtime Database
    final usersData = await _firebase.get('users');
    if (usersData == null || usersData is! Map) {
      throw Exception('Data pengguna tidak ditemukan di server.');
    }

    Map<String, dynamic>? matchedUser;
    for (final entry in usersData.entries) {
      if (entry.value is Map) {
        final u = Map<String, dynamic>.from(entry.value as Map);
        if ((u['email'] as String? ?? '').toLowerCase() == cleanEmail) {
          matchedUser = u;
          break;
        }
      }
    }

    if (matchedUser == null) {
      throw Exception('Email tidak terdaftar!');
    }

    final storedPassword = matchedUser['password'] as String? ?? '';
    if (storedPassword != password) {
      throw Exception('Kata sandi yang Anda masukkan salah!');
    }

    final userModel = UserModel.fromJson(matchedUser);

    if (!userModel.isActive) {
      throw Exception('Akun Anda telah dinonaktifkan. Hubungi Administrator.');
    }

    // Persist session to Hive
    await _sessionBox.put('current_user', jsonEncode(userModel.toJson()));
    _authStateController.add(userModel);

    return userModel;
  }

  /// Sign out current user
  Future<void> signOut() async {
    if (Hive.isBoxOpen(AppConstants.sessionBoxName)) {
      await _sessionBox.delete('current_user');
    }
    _authStateController.add(null);
  }

  /// Get user profile by ID from Firebase RTDB
  Future<UserModel?> getUserProfile(String userId) async {
    final data = await _firebase.get('users/$userId');
    if (data == null || data is! Map) return null;
    return UserModel.fromJson(Map<String, dynamic>.from(data));
  }
}
