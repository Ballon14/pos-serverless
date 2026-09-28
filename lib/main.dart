import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'app.dart';
import 'config/supabase_config.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Supabase Client
  await SupabaseConfig.initialize();

  // Initialize Hive for local caching
  await Hive.initFlutter();

  runApp(
    const ProviderScope(
      child: StockKuApp(),
    ),
  );
}
