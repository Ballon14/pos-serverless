# StockKu - AI Agent Instructions

This file contains the core context, technology stack, and architectural guidelines for AI coding agents contributing to the **StockKu** repository.

## 🎯 Project Overview
StockKu is a modern, responsive multi-platform Point of Sale (POS) and Inventory Management System built with **Flutter** (Web, Android, iOS) and powered by **Supabase** (Database, Auth, Storage, Realtime, and Edge Functions). It is designed to be visually premium, fast, and serverless-first — running entirely on client devices and edge infrastructure with zero self-hosted servers.

**Key Features:**
- **POS / Kasir**: Real-time cart management via Riverpod, barcode scanner support (physical scanner for Web / camera scanner for Mobile via `mobile_scanner`), offline fallback with local storage (Hive) sync.
- **Inventory Management**: Stock mutations, low-stock alerts, purchase (restock) recording.
- **Attendance**: Clock in/out and leave requests (izin/sakit/cuti) with approval flow. **Attendance is mandatory**: users who have not clocked in (or have clocked out) enter read-only mode — write actions are blocked until they clock in.
- **Reporting**: Sales, Profit/Loss, Stock Mutations, and Attendance reports with PDF & Excel export.
- **Role-Based Access Control**: Admin, Manager, Kasir, Karyawan.

## 🛠️ Technology Stack

### Frontend / Client App (`lib/`)
- **Framework**: Flutter 3.x (Dart 3.x) targeting Web, Android, and iOS
- **State Management**: Flutter Riverpod 2.x (`flutter_riverpod`, `riverpod_annotation`, `riverpod_generator`)
- **Routing**: GoRouter (`go_router`) with auth & attendance redirect guards
- **Data Modeling**: `freezed` & `json_serializable` for immutable, type-safe data classes
- **UI & Styling**: Material 3 with custom curated color palette (Indigo / Purple / Slate / Emerald), Google Fonts (`google_fonts`), Shimmer loading, responsive breakpoints
- **Charts**: `fl_chart` for interactive financial & sales charts
- **Barcode Scanning**: Physical keyboard/scanner input for Web/Desktop, `mobile_scanner` for mobile camera
- **Offline & Local Cache**: `hive_flutter` for cart persistence & offline queue, `connectivity_plus` for network detection
- **Printing & Export**: `pdf` & `printing` for thermal receipt & reports, `excel` for spreadsheet export

### Backend & Infrastructure (`supabase/`)
- **Database**: Supabase (PostgreSQL 15, managed) with Row Level Security (RLS)
- **Authentication**: Supabase Auth (managed via `supabase_flutter`)
- **Direct Queries**: `supabase.from('...')` for read & standard CRUD operations guarded by Postgres RLS
- **Edge Functions (`supabase/functions/`)**: Deno / TypeScript for critical atomic transactions:
  - `checkout`: Stock validation with `SELECT ... FOR UPDATE`, invoice generation (`INV-YYYYMMDD-XXXX`), sale record, and stock deduction.
  - `process-return`: Return item capping, stock restoration, and mutation logging.
  - `generate-report`: Heavy analytical queries (if needed beyond client capacity).
- **File Storage**: Supabase Storage (receipts, avatars, product images)
- **Realtime**: Supabase Realtime (stock update notifications, sales broadcasts)

## 🧱 Data Integrity Rules (MUST follow)
- **Critical operations go through Supabase Edge Functions**: Checkout and return processing MUST execute in server-side transactions with row locking (`SELECT ... FOR UPDATE`). Never perform raw stock decrement directly from client-side code.
- **Stock deduction rules**: Stock must never fall below zero for outgoing operations. Stock movements (`stock_movements`) must always be recorded for every change.
- **Returns are capped**: `sale_items.returned_qty` tracks cumulative returns; over-returns must be rejected.
- **Money validation**: Discounts clamped to `[0, subtotal]`, subtotal cannot be negative, payment (`bayar`) must be `>= grand_total`.
- **Timezone**: The application uses `Asia/Jakarta` (WIB, UTC+7) for all business timestamps, invoice sequence resets, and attendance.

## 🚪 Attendance Gate (MUST follow)
- **Admin (owner) is fully exempt** — never gate admin accounts.
- Non-admin staff who have not clocked in for today (WIB) are placed in **Read-Only Mode**:
  - The app displays an amber warning banner (`AttendanceBanner`).
  - Write actions (POS checkout, stock mutation, product edit) are disabled.
  - POS screen disables the checkout button and displays a prompt to clock in first.
  - Navigation guards redirect or warn users when attempting write workflows.

## 🏗️ Architectural Guidelines

### 1. Project Directory Structure
```
lib/
├── app.dart                   # MaterialApp.router configuration
├── main.dart                  # Supabase & Hive initialization, ProviderScope entry
├── config/
│   ├── constants.dart         # App-wide constants, strings, endpoints
│   ├── router.dart            # GoRouter configuration & route guards
│   ├── supabase_config.dart   # Supabase client config & credentials
│   └── theme.dart             # Material 3 custom dark/light theme
├── models/                    # Freezed data models matching database schema
│   ├── attendance.dart
│   ├── category.dart
│   ├── product.dart
│   ├── sale.dart
│   ├── stock_movement.dart
│   ├── supplier.dart
│   └── user.dart
├── services/                  # Supabase SDK service wrappers & Edge Function calls
│   ├── attendance_service.dart
│   ├── auth_service.dart
│   ├── product_service.dart
│   ├── report_service.dart
│   ├── sale_service.dart
│   └── stock_service.dart
├── providers/                 # Riverpod notifiers & state management
│   ├── attendance_provider.dart
│   ├── auth_provider.dart
│   ├── cart_provider.dart
│   └── product_provider.dart
├── screens/                   # Top-level screen views
│   ├── attendance/
│   ├── auth/
│   ├── dashboard/
│   ├── pos/
│   ├── products/
│   ├── reports/
│   ├── settings/
│   └── stock/
├── widgets/                   # Modular, reusable Flutter widgets
│   ├── attendance_banner.dart
│   ├── barcode_input.dart
│   ├── custom_button.dart
│   ├── payment_dialog.dart
│   ├── receipt_view.dart
│   └── sidebar_navigation.dart
└── utils/                     # Helpers (currency, date, validation)
    ├── formatters.dart
    └── validators.dart
```

### 2. Riverpod State Management
- Use `flutter_riverpod` with code generation (`@riverpod`) where appropriate, or standard `Notifier` / `AsyncNotifier`.
- Keep business logic in Notifiers/Services, keeping widgets declarative and clean.
- Handle `AsyncValue` cleanly with `.when(data: ..., loading: ..., error: ...)` and shimmer loaders.
- Cart state is saved locally with `Hive` so items survive accidental refresh or offline state.

### 3. UI / UX & Design Standards
- Premium, modern aesthetic matching StockKu branding:
  - Primary: `Color(0xFF6366F1)` (Indigo)
  - Secondary: `Color(0xFF9333EA)` (Purple)
  - Success/Money: `Color(0xFF10B981)` (Emerald)
  - Warning: `Color(0xFFF59E0B)` (Amber)
  - Surface/Background: Slate tones (`Color(0xFF0F172A)` dark, `Color(0xFFF8FAFC)` light)
- Responsive layout: adapt between desktop/tablet sidebars and mobile bottom navigation or drawers.
- Barcode scanning: instant keystroke capture on desktop/web, dedicated camera scanner modal on mobile.

### 4. Database & RLS Conventions
- Supabase table names are lowercase plural: `products`, `sales`, `sale_items`, `users`, `attendances`.
- Primary keys are UUIDs (`gen_random_uuid()`).
- All financial numbers use `NUMERIC(15,2)` or integer cents.
- Dates use `TIMESTAMPTZ`.

## 🔄 Development Workflow (MUST follow, point by point)
1. **Understand the task** — read relevant Flutter models, services, and screen files first.
2. **Verify changes** — run `flutter analyze` and `flutter test` to ensure zero compilation or lint errors.
3. **Check the diff** — run `git status` and `git diff`; ensure no secrets or local config files are committed.
4. **Commit changes** — write a concise commit message in the repo's existing style (`git add -A && git commit -m "..."`).
5. **Push to GitHub** — ALWAYS push to `main` branch after finishing work: `git push origin main`.
6. **Report back** — summarize what was changed point by point (file → what changed → why), and confirm git push status.