# 🦋 StockKu — Migration Plan: Flutter + Supabase Direct

## Ringkasan Perubahan

| Aspek | Sebelum | Sesudah |
|---|---|---|
| **Frontend** | React 19 + Vite 6 (SPA) | Flutter 3.x (Web + Android + iOS) |
| **Backend API** | Hono.js v4 on Cloudflare Workers | ❌ Dihapus — diganti Supabase Edge Functions |
| **State Management** | Zustand + TanStack Query | Riverpod 2.x + supabase_flutter |
| **Styling** | Tailwind CSS v4 | Flutter Material 3 + Custom Theme |
| **Auth** | Supabase Auth via Hono middleware | supabase_flutter langsung |
| **Shared Types** | `@stockku/shared` (TypeScript) | `lib/models/` (Dart classes + Freezed) |
| **Critical Logic** | Hono service layer (stock, invoice) | Supabase Edge Functions (Deno) |
| **Offline/PWA** | vite-plugin-pwa + IndexedDB | Hive/sqflite + connectivity_plus |
| **Monorepo** | pnpm + Turborepo | Single Flutter project |
| **Hosting** | Cloudflare Pages (SPA) + Workers (API) | Firebase Hosting / Cloudflare Pages (Web build) + Play Store / App Store |

---

## Fase 1: Setup Project & Infrastruktur (Hari 1)

### 1.1 Inisialisasi Flutter Project
```bash
# Dari root repo, buat project Flutter baru
flutter create --org com.tokombaemi --project-name stockku --platforms web,android,ios .

# Atau jika ingin memisahkan dari legacy:
# Pindahkan seluruh konten apps/ dan packages/ ke legacy-react/
```

### 1.2 Struktur Direktori Flutter
```
pos-tokombaemi/
├── android/                    # Android native
├── ios/                        # iOS native  
├── web/                        # Flutter Web shell
├── lib/
│   ├── main.dart               # Entry point
│   ├── app.dart                # MaterialApp + Router
│   ├── config/
│   │   ├── supabase_config.dart
│   │   ├── theme.dart          # Material 3 theme (indigo/purple palette)
│   │   └── router.dart         # GoRouter routes
│   ├── models/                 # Dart data classes (Freezed)
│   │   ├── user.dart
│   │   ├── product.dart
│   │   ├── sale.dart
│   │   ├── attendance.dart
│   │   ├── category.dart
│   │   ├── supplier.dart
│   │   └── ...
│   ├── services/               # Supabase query layer
│   │   ├── auth_service.dart
│   │   ├── product_service.dart
│   │   ├── sale_service.dart
│   │   ├── stock_service.dart
│   │   ├── attendance_service.dart
│   │   └── report_service.dart
│   ├── providers/              # Riverpod providers
│   │   ├── auth_provider.dart
│   │   ├── cart_provider.dart
│   │   ├── product_provider.dart
│   │   ├── attendance_provider.dart
│   │   └── ...
│   ├── screens/                # Full-page screens
│   │   ├── auth/
│   │   │   └── login_screen.dart
│   │   ├── pos/
│   │   │   └── pos_screen.dart
│   │   ├── dashboard/
│   │   │   └── dashboard_screen.dart
│   │   ├── products/
│   │   │   ├── product_list_screen.dart
│   │   │   └── product_form_screen.dart
│   │   ├── stock/
│   │   │   └── stock_movement_screen.dart
│   │   ├── attendance/
│   │   │   └── attendance_screen.dart
│   │   ├── reports/
│   │   │   └── report_screen.dart
│   │   └── settings/
│   │       └── settings_screen.dart
│   ├── widgets/                # Reusable components
│   │   ├── app_layout.dart
│   │   ├── sidebar.dart
│   │   ├── attendance_banner.dart
│   │   ├── barcode_input.dart
│   │   ├── payment_modal.dart
│   │   ├── receipt_view.dart
│   │   ├── toast_overlay.dart
│   │   └── ...
│   └── utils/
│       ├── constants.dart
│       ├── formatters.dart     # Currency, date formatting
│       └── validators.dart
├── supabase/
│   ├── functions/              # Edge Functions (pengganti Hono)
│   │   ├── checkout/
│   │   │   └── index.ts        # POS checkout + stock deduction
│   │   ├── process-return/
│   │   │   └── index.ts        # Return processing
│   │   └── generate-report/
│   │       └── index.ts        # Heavy report generation
│   ├── migrations/             # Tetap sama
│   └── seed.sql
├── legacy-react/               # Arsip React+Hono (opsional)
├── pubspec.yaml
├── analysis_options.yaml
├── AGENTS.md
└── README.md
```

### 1.3 Dependencies (`pubspec.yaml`)
```yaml
dependencies:
  flutter:
    sdk: flutter
  # Supabase
  supabase_flutter: ^2.8.0
  
  # State Management
  flutter_riverpod: ^2.6.0
  riverpod_annotation: ^2.6.0
  
  # Routing
  go_router: ^14.0.0
  
  # Data Classes
  freezed_annotation: ^2.4.0
  json_annotation: ^4.9.0
  
  # UI
  flutter_svg: ^2.0.0
  cached_network_image: ^3.4.0
  shimmer: ^3.0.0
  fl_chart: ^0.69.0          # Charts (pengganti Chart.js)
  google_fonts: ^6.2.0       # Inter, Roboto, Outfit
  
  # Barcode
  mobile_scanner: ^6.0.0     # Camera barcode scanner (mobile)
  # Untuk web: js interop atau manual input
  
  # Offline & Storage
  hive_flutter: ^1.1.0       # Local storage (cart persist)
  connectivity_plus: ^6.1.0  # Online/offline detection
  
  # PDF & Export
  pdf: ^3.11.0               # PDF generation
  printing: ^5.13.0          # PDF printing/sharing
  excel: ^4.0.0              # Excel export
  
  # Utils
  intl: ^0.19.0              # Date & number formatting (WIB)
  uuid: ^4.5.0
  
dev_dependencies:
  flutter_test:
    sdk: flutter
  build_runner: ^2.4.0
  freezed: ^2.5.0
  json_serializable: ^6.8.0
  riverpod_generator: ^2.6.0
  flutter_lints: ^5.0.0
```

### 1.4 Fix Supabase Auth (Bersamaan)
Jalankan skrip `supabase/fix_auth.sql` di Supabase SQL Editor sebelum memulai development Flutter.

---

## Fase 2: Core Infrastructure (Hari 1-2)

### 2.1 Supabase Client Config
- `lib/config/supabase_config.dart` — inisialisasi `Supabase.initialize()` di `main.dart`
- Environment variables via `--dart-define` atau `.env` file

### 2.2 Theme System (Material 3)
- Custom `ThemeData` yang meniru estetika premium saat ini:
  - Primary: `indigo-500` → `Color(0xFF6366F1)`
  - Secondary: `purple-600` → `Color(0xFF9333EA)`
  - Success: `emerald-500` → `Color(0xFF10B981)`
  - Warning: `amber-500` → `Color(0xFFF59E0B)`
  - Background dark: `slate-950` → `Color(0xFF020617)`
  - Surface: `slate-900` → `Color(0xFF0F172A)`
- Glassmorphism via `BackdropFilter` + `ClipRRect`
- Custom `TextTheme` dengan Google Fonts (Inter/Outfit)

### 2.3 Router (GoRouter)
```
/login          → LoginScreen
/               → DashboardScreen (redirect if not auth)
/pos            → PosScreen
/products       → ProductListScreen
/products/new   → ProductFormScreen
/products/:id   → ProductFormScreen (edit)
/stock          → StockMovementScreen
/attendance     → AttendanceScreen
/reports        → ReportScreen
/settings       → SettingsScreen
```
- `redirect` guard: cek `Supabase.instance.client.auth.currentSession`
- Attendance gate: cek status absensi sebelum akses write-capable screens

### 2.4 Data Models (Freezed)
Port semua tipe dari `@stockku/shared`:
- `User`, `Product`, `Category`, `Supplier`
- `Sale`, `SaleItem`, `SaleReturn`, `SaleReturnItem`
- `Purchase`, `PurchaseItem`
- `StockMovement`, `Attendance`, `LeaveRequest`
- `Settings`, `ActivityLog`, `PriceChangeLog`

---

## Fase 3: Auth & Attendance (Hari 2-3)

### 3.1 Auth Service
- `signInWithPassword()` via `supabase_flutter`
- Session auto-refresh (handled by SDK)
- `AuthProvider` (Riverpod): menyimpan `User`, `role`, `isAuthenticated`
- Logout: `supabase.auth.signOut()`

### 3.2 Auth State (Riverpod)
```dart
@riverpod
class Auth extends _$Auth {
  // Stream dari supabase.auth.onAuthStateChange
  // Auto-fetch public.users profile saat session berubah
}
```

### 3.3 Attendance Gate
- `AttendanceProvider`: fetch status hari ini (`GET /rest/v1/attendances?date=eq.today`)
- `AttendanceBanner` widget: amber banner jika belum clock-in
- GoRouter redirect: block write-screens jika belum absen (kecuali admin)
- Clock in/out langsung via Supabase client (`insert` / `update`)

---

## Fase 4: Supabase Edge Functions — Critical Logic (Hari 3-4)

### 4.1 `checkout` Edge Function
Menggantikan `SaleService.createSale()` dari Hono:
```typescript
// supabase/functions/checkout/index.ts
// - Menerima cart items + payment info
// - SELECT ... FOR UPDATE pada products (stock guard)
// - Generate invoice number (INV-YYYYMMDD-XXXX)
// - INSERT sale + sale_items
// - UPDATE product stock
// - Record stock_movements
// - Return sale data + receipt info
```

### 4.2 `process-return` Edge Function
Menggantikan `SaleService.processReturn()`:
```typescript
// - Validasi returned_qty <= qty - already_returned
// - Update sale status (partial_return / returned)
// - Restore stock
// - Record stock_movements
```

### 4.3 `generate-report` Edge Function (opsional)
Untuk report yang perlu query berat yang melebihi kapasitas client.

### 4.4 Deploy Edge Functions
```bash
supabase functions deploy checkout
supabase functions deploy process-return
```

Flutter memanggil via:
```dart
final response = await supabase.functions.invoke('checkout', body: {...});
```

---

## Fase 5: POS Screen (Hari 4-5)

### 5.1 CartProvider (Riverpod + Hive)
- Menggantikan Zustand cart store
- Persist ke Hive (mirip localStorage)
- `addItem()`, `removeItem()`, `updateQty()`, `applyDiscount()`, `clear()`
- Grosir tier auto-detection

### 5.2 POS UI
- Split layout: product grid (kiri) + cart panel (kanan)
- Barcode input field (web: keyboard, mobile: camera scanner via `mobile_scanner`)
- Product search & category filter
- Cart list dengan qty adjustment
- Payment modal (bayar, kembalian)
- Receipt view (PDF via `pdf` package)

### 5.3 Offline Mode
- `connectivity_plus` untuk deteksi online/offline
- Saat offline: simpan sale ke Hive queue
- Saat online: sync queue ke Edge Function `checkout`

---

## Fase 6: CRUD Screens (Hari 5-7)

### 6.1 Dashboard
- Statistik penjualan hari ini (Supabase RPC atau direct query)
- Chart penjualan 7 hari (fl_chart)
- Produk stok rendah
- Aktivitas terbaru

### 6.2 Products
- List dengan search, filter kategori, pagination
- Form create/edit (dengan validasi)
- Harga beli, harga jual, grosir tiers (dynamic form)

### 6.3 Stock Movements
- Riwayat mutasi stok
- Manual adjustment (admin/manager only)

### 6.4 Attendance
- Clock in / clock out
- Riwayat absensi
- Leave request form + approval (admin/manager)

### 6.5 Reports
- Sales report (date range filter)
- Profit/loss
- Stock mutations
- Attendance summary
- Export PDF / Excel

### 6.6 Settings
- Store name, address, phone
- Receipt footer
- User management (admin only)

---

## Fase 7: Polish & Deploy (Hari 7-8)

### 7.1 UI Polish
- Smooth page transitions (Hero, FadeTransition)
- Shimmer loading states
- Pull-to-refresh
- Responsive layout (web vs mobile breakpoints)
- Dark mode toggle

### 7.2 Testing
- Unit tests: services, providers
- Widget tests: critical screens (POS, Login)
- Integration tests: checkout flow

### 7.3 Build & Deploy
```bash
# Web
flutter build web --release
# Deploy ke Cloudflare Pages atau Firebase Hosting

# Android
flutter build apk --release
# atau flutter build appbundle --release

# iOS
flutter build ios --release
```

---

## Fase 8: Cleanup (Hari 8)

### 8.1 Arsip Legacy Code
```bash
# Pindahkan React + Hono ke arsip
mkdir legacy-react
mv apps/ legacy-react/
mv packages/ legacy-react/
mv turbo.json legacy-react/
mv pnpm-workspace.yaml legacy-react/
```

### 8.2 Update Repository
- Update `README.md`
- Update `AGENTS.md` (sudah disiapkan)
- Commit & push

---

## Mapping Komponen: React → Flutter

| React Component | Flutter Widget |
|---|---|
| `LoginPage.tsx` | `LoginScreen` |
| `DashboardPage.tsx` | `DashboardScreen` |
| `PosPage.tsx` | `PosScreen` |
| `ProductListPage.tsx` | `ProductListScreen` |
| `StockMovementPage.tsx` | `StockMovementScreen` |
| `AttendancePage.tsx` | `AttendanceScreen` |
| `ReportPage.tsx` | `ReportScreen` |
| `SettingsPage.tsx` | `SettingsScreen` |
| `AppLayout.tsx` | `AppLayout` (Scaffold + Drawer/Sidebar) |
| `AttendanceBanner.tsx` | `AttendanceBanner` |
| `BarcodeInput.tsx` | `BarcodeInput` + `MobileScanner` |
| `PaymentModal.tsx` | `PaymentDialog` (showDialog) |
| `ReceiptModal.tsx` | `ReceiptView` (PDF preview) |
| Zustand `cart.store` | Riverpod `CartNotifier` + Hive |
| Zustand `auth.store` | Riverpod `AuthNotifier` |
| Zustand `toast.store` | `ScaffoldMessenger` / overlay |
| TanStack Query hooks | Riverpod `FutureProvider` / `StreamProvider` |

## Mapping Service: Hono → Supabase Direct / Edge Function

| Hono Service | Pengganti |
|---|---|
| `auth.route.ts` | `supabase_flutter` auth langsung |
| `product.route.ts` | `supabase.from('products').select/insert/update/delete` |
| `category.route.ts` | `supabase.from('categories').select/insert/update/delete` |
| `supplier.route.ts` | `supabase.from('suppliers').select/insert/update/delete` |
| `sale.route.ts` (checkout) | **Edge Function `checkout`** |
| `sale.route.ts` (return) | **Edge Function `process-return`** |
| `stock.route.ts` | Direct query + RLS |
| `attendance.route.ts` | Direct query + RLS |
| `report.route.ts` | Direct query / Edge Function |
| `user.route.ts` | Direct query + admin RLS |
| Auth middleware | `supabase_flutter` session |
| Role middleware | RLS policies (sudah ada) |
| Attendance gate | Client-side check + RLS |

---

> [!IMPORTANT]
> **Operasi kritis** (checkout, return, invoice generation) **wajib** menggunakan Supabase Edge Functions — bukan direct client query — untuk menjamin konsistensi data dengan database transaction + row locking.
