/// Application-wide constants for Toko Mba Emi POS
class AppConstants {
  AppConstants._();

  static const String appName = 'Toko Mba Emi';
  static const String appVersion = '1.0.0';

  // Firebase Realtime Database Configuration
  // Can be overridden at build time using --dart-define=FIREBASE_RTDB_URL=...
  static const String firebaseRtdbUrl = String.fromEnvironment(
    'FIREBASE_RTDB_URL',
    defaultValue: 'https://possystem-6b4b7-default-rtdb.asia-southeast1.firebasedatabase.app',
  );

  // Timezone & Locale
  static const String defaultTimezone = 'Asia/Jakarta';
  static const String defaultLocale = 'id_ID';
  static const String currencySymbol = 'Rp';

  // Hive Box Names
  static const String sessionBoxName = 'stockku_session_box';
  static const String cartBoxName = 'stockku_cart_box';
  static const String offlineSalesBoxName = 'stockku_offline_sales_box';
  static const String settingsBoxName = 'stockku_settings_box';
}
