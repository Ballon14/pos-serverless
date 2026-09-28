import 'package:supabase_flutter/supabase_flutter.dart';
import '../config/supabase_config.dart';
import '../models/user_model.dart';

class AuthService {
  final SupabaseClient _supabase;

  AuthService({SupabaseClient? supabase}) : _supabase = supabase ?? SupabaseConfig.client;

  User? get currentAuthUser => _supabase.auth.currentUser;
  bool get isAuthenticated => currentAuthUser != null;
  Stream<AuthState> get onAuthStateChange => _supabase.auth.onAuthStateChange;

  /// Sign in with email and password
  Future<UserModel> signIn({required String email, required String password}) async {
    final response = await _supabase.auth.signInWithPassword(
      email: email.trim(),
      password: password,
    );

    final user = response.user;
    if (user == null) {
      throw const AuthException('Gagal masuk: Pengguna tidak ditemukan');
    }

    final profile = await getUserProfile(user.id);
    if (profile == null) {
      throw const AuthException('Profil pengguna tidak ditemukan');
    }

    if (!profile.isActive) {
      await signOut();
      throw const AuthException('Akun Anda telah dinonaktifkan. Hubungi Administrator.');
    }

    return profile;
  }

  /// Fetch user profile from public.users table
  Future<UserModel?> getUserProfile(String userId) async {
    final data = await _supabase
        .from('users')
        .select()
        .eq('id', userId)
        .maybeSingle();

    if (data == null) return null;
    return UserModel.fromJson(data);
  }

  /// Sign out current user
  Future<void> signOut() async {
    await _supabase.auth.signOut();
  }
}
