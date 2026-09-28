import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'app.dart';
import 'config/constants.dart';
import 'services/firebase_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Hive for offline caching & session storage
  await Hive.initFlutter();
  await Hive.openBox(AppConstants.sessionBoxName);
  await Hive.openBox(AppConstants.cartBoxName);
  await Hive.openBox(AppConstants.settingsBoxName);

  // Auto-seed Firebase Realtime Database if empty
  await FirebaseService().seedInitialDataIfEmpty();

  runApp(
    const ProviderScope(
      child: StockKuApp(),
    ),
  );
}
