/// Application-wide constants for StockKu POS
class AppConstants {
  AppConstants._();

  static const String appName = 'StockKu';
  static const String appVersion = '1.0.0';

  // Supabase Configuration
  // Can be overridden at build time using --dart-define=SUPABASE_URL=...
  static const String supabaseUrl = String.fromEnvironment(
    'SUPABASE_URL',
    defaultValue: 'https://bnkndcxmiyhcmapvusbx.supabase.co',
  );

  static const String supabaseAnonKey = String.fromEnvironment(
    'SUPABASE_ANON_KEY',
    defaultValue: 'sb_publishable_pecKd0cnAhPAYHYwFeCFLA_prOFSZFq',
  );

  // Timezone & Locale
  static const String defaultTimezone = 'Asia/Jakarta';
  static const String defaultLocale = 'id_ID';
  static const String currencySymbol = 'Rp';

  // Storage Buckets
  static const String productsBucket = 'products';
  static const String receiptsBucket = 'receipts';
  static const String avatarsBucket = 'avatars';

  // Hive Box Names
  static const String cartBoxName = 'stockku_cart_box';
  static const String offlineSalesBoxName = 'stockku_offline_sales_box';
  static const String settingsBoxName = 'stockku_settings_box';
}
