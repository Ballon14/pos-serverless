# 🚀 StockKu — Panduan Migrasi Serverless

> **Dari**: Laravel 12 / PHP / MySQL / Blade + Livewire  
> **Ke**: Hono.js (TypeScript) / Supabase (PostgreSQL) / React + Vite SPA / Cloudflare Workers + Pages

---

## 📋 Daftar Isi

1. [Ringkasan Arsitektur Baru](#1-ringkasan-arsitektur-baru)
2. [Technology Stack Baru](#2-technology-stack-baru)
3. [Pemetaan Komponen (Laravel → Serverless)](#3-pemetaan-komponen-laravel--serverless)
4. [Struktur Monorepo](#4-struktur-monorepo)
5. [Backend — Hono.js API](#5-backend--honojs-api)
6. [Frontend — React + Vite SPA](#6-frontend--react--vite-spa)
7. [Database — Supabase (PostgreSQL)](#7-database--supabase-postgresql)
8. [Authentication & Authorization](#8-authentication--authorization)
9. [Migrasi Fitur per Modul](#9-migrasi-fitur-per-modul)
10. [Deployment — Cloudflare Workers + Pages](#10-deployment--cloudflare-workers--pages)
11. [PWA & Offline Support](#11-pwa--offline-support)
12. [Testing Strategy](#12-testing-strategy)
13. [Fase & Timeline Migrasi](#13-fase--timeline-migrasi)
14. [Risiko & Mitigasi](#14-risiko--mitigasi)
15. [Checklist Pre-Launch](#15-checklist-pre-launch)

---

## 1. Ringkasan Arsitektur Baru

```
┌─────────────────────────────────────────────────────────────┐
│                    Cloudflare CDN / Edge                     │
├──────────────────────┬──────────────────────────────────────┤
│  Cloudflare Pages    │     Cloudflare Workers               │
│  (React SPA)         │     (Hono.js API)                    │
│                      │                                      │
│  - Static assets     │  /api/auth/*     → Auth handlers     │
│  - index.html        │  /api/pos/*      → POS handlers      │
│  - JS/CSS bundles    │  /api/products/* → Product handlers   │
│  - PWA manifest      │  /api/stock/*    → Stock handlers     │
│  - Service Worker    │  /api/reports/*  → Report handlers    │
│                      │  /api/attendance/* → Attendance       │
└──────────┬───────────┴──────────┬───────────────────────────┘
           │                      │
           │    HTTPS / REST      │
           │                      ▼
     ┌─────┴─────────────────────────────────────────┐
     │              Supabase Platform                 │
     ├────────────┬──────────┬──────────┬────────────┤
     │ PostgreSQL │ Auth     │ Storage  │ Realtime   │
     │ (Database) │ (JWT)    │ (Files)  │ (WebSocket)│
     └────────────┴──────────┴──────────┴────────────┘
```

### Keunggulan Arsitektur Baru

| Aspek | Laravel (Sekarang) | Serverless (Baru) |
|-------|-------------------|-------------------|
| **Hosting** | VPS / dedicated server | Cloudflare edge (150+ lokasi) |
| **Scaling** | Manual (nginx + PHP-FPM) | Auto-scale per-request |
| **Cold start** | N/A (always-on) | ~1-5ms (Cloudflare Workers) |
| **Database** | MySQL (self-managed) | Supabase PostgreSQL (managed) |
| **Biaya idle** | ~$5-20/bulan VPS | $0 (free tier generous) |
| **Deploy** | SSH + git pull + artisan | `wrangler deploy` (< 30 detik) |
| **SSL/CDN** | Manual (Let's Encrypt + nginx) | Otomatis (Cloudflare) |

---

## 2. Technology Stack Baru

### Backend (API)
| Komponen | Teknologi | Alasan |
|----------|-----------|--------|
| Runtime | Cloudflare Workers | Edge computing, 0ms cold start, free 100K req/hari |
| Framework | **Hono.js v4** | Ultrafast, Web Standards, built for edge, middleware ecosystem |
| Language | **TypeScript** | Type safety, better DX, autocomplete |
| Validation | **Zod** | Schema validation, type inference |
| ORM/Query | **Drizzle ORM** | Lightweight, SQL-like, Supabase/PG kompatibel |
| Auth | **Supabase Auth** + Hono JWT middleware | Row Level Security (RLS), JWT tokens |

### Frontend (SPA)
| Komponen | Teknologi | Alasan |
|----------|-----------|--------|
| Framework | **React 19** | Ecosystem terbesar, komponen reusable |
| Build tool | **Vite 6** | HMR cepat, optimized build |
| Routing | **React Router v7** | Standard routing untuk SPA |
| State | **Zustand** | Ringan, simple, cocok untuk POS cart state |
| Data fetching | **TanStack Query v5** | Cache, retry, optimistic updates |
| Styling | **Tailwind CSS v4** | Konsisten dengan desain lama |
| Charts | **Chart.js + react-chartjs-2** | Sama seperti versi Laravel |
| Forms | **React Hook Form + Zod** | Validation terintegrasi |
| UI Components | **Radix UI** + custom styling | Accessible, unstyled primitives |

### Infrastructure
| Komponen | Teknologi |
|----------|-----------|
| API Hosting | Cloudflare Workers |
| SPA Hosting | Cloudflare Pages |
| Database | Supabase (PostgreSQL 15) |
| File Storage | Supabase Storage (receipt images, exports) |
| Realtime | Supabase Realtime (stock updates) |
| PDF Export | `@react-pdf/renderer` (client-side) atau Cloudflare Workers + `pdf-lib` |
| Excel Export | `xlsx` / `exceljs` (client-side) |

---

## 3. Pemetaan Komponen (Laravel → Serverless)

### Controllers → Hono Route Handlers

| Laravel | Hono.js |
|---------|---------|
| `SaleController.php` | `src/routes/sales.ts` |
| `ProductController.php` | `src/routes/products.ts` |
| `StockController.php` | `src/routes/stock.ts` |
| `PurchaseController.php` | `src/routes/purchases.ts` |
| `ReportController.php` | `src/routes/reports.ts` |
| `CategoryController.php` | `src/routes/categories.ts` |
| `SupplierController.php` | `src/routes/suppliers.ts` |
| `SettingController.php` | `src/routes/settings.ts` |
| `DashboardController.php` | `src/routes/dashboard.ts` |
| `ActivityLogController.php` | `src/routes/activity-logs.ts` |

### Services → Service Modules

| Laravel | Hono.js |
|---------|---------|
| `SaleService.php` | `src/services/sale.service.ts` |
| `StockService.php` | `src/services/stock.service.ts` |
| `ProductService.php` | `src/services/product.service.ts` |
| `ReportService.php` | `src/services/report.service.ts` |
| `PurchaseService.php` | `src/services/purchase.service.ts` |
| `CategoryService.php` | `src/services/category.service.ts` |
| `SupplierService.php` | `src/services/supplier.service.ts` |
| `ProductImportService.php` | `src/services/product-import.service.ts` |
| `ProductExportService.php` | `src/services/product-export.service.ts` |
| `ActivityLogger.php` | `src/services/activity-logger.service.ts` |
| `PriceChangeService.php` | `src/services/price-change.service.ts` |

### Models → Drizzle Schemas

| Laravel Model | Drizzle Schema |
|---------------|----------------|
| `User.php` | `src/db/schema/users.ts` |
| `Product.php` | `src/db/schema/products.ts` |
| `Category.php` | `src/db/schema/categories.ts` |
| `Sale.php` + `SaleItem.php` | `src/db/schema/sales.ts` |
| `Purchase.php` + `PurchaseItem.php` | `src/db/schema/purchases.ts` |
| `StockMovement.php` | `src/db/schema/stock-movements.ts` |
| `Supplier.php` | `src/db/schema/suppliers.ts` |
| `SaleReturn.php` + `SaleReturnItem.php` | `src/db/schema/sale-returns.ts` |
| `Setting.php` | `src/db/schema/settings.ts` |
| `ActivityLog.php` | `src/db/schema/activity-logs.ts` |
| `PriceChangeLog.php` | `src/db/schema/price-change-logs.ts` |

### Middleware → Hono Middleware

| Laravel Middleware | Hono Middleware |
|-------------------|-----------------|
| `auth` (Breeze) | `src/middleware/auth.ts` (JWT verify via Supabase) |
| `role:admin` (Spatie) | `src/middleware/rbac.ts` (`requireRole('admin')`) |
| `ensure-attended` | `src/middleware/attendance-gate.ts` |
| `PreventStaleCache.php` | Cloudflare Cache Rules (atau `src/middleware/cache-control.ts`) |

### Livewire Components → React Components

| Livewire | React |
|----------|-------|
| `PosTerminal.php` + `pos-terminal.blade.php` | `src/pages/PosTerminal.tsx` + `src/components/pos/*` |
| `RestockTerminal.php` + `restock-terminal.blade.php` | `src/pages/RestockTerminal.tsx` + `src/components/restock/*` |

### Blade Views → React Pages

| Blade View | React Page |
|-----------|------------|
| `layouts/app.blade.php` | `src/layouts/AppLayout.tsx` |
| `dashboard.blade.php` | `src/pages/Dashboard.tsx` |
| `products/index.blade.php` | `src/pages/products/ProductList.tsx` |
| `sales/index.blade.php` | `src/pages/sales/SaleList.tsx` |
| `reports/*.blade.php` | `src/pages/reports/*.tsx` |
| `settings/index.blade.php` | `src/pages/settings/Settings.tsx` |

---

## 4. Struktur Monorepo

```
pos-tokombaemi/
├── apps/
│   ├── api/                          # Hono.js — Cloudflare Workers
│   │   ├── src/
│   │   │   ├── index.ts              # Entry point, app bootstrap
│   │   │   ├── routes/
│   │   │   │   ├── auth.ts
│   │   │   │   ├── dashboard.ts
│   │   │   │   ├── products.ts
│   │   │   │   ├── categories.ts
│   │   │   │   ├── suppliers.ts
│   │   │   │   ├── sales.ts
│   │   │   │   ├── purchases.ts
│   │   │   │   ├── stock.ts
│   │   │   │   ├── reports.ts
│   │   │   │   ├── attendance.ts
│   │   │   │   ├── settings.ts
│   │   │   │   └── activity-logs.ts
│   │   │   ├── services/
│   │   │   │   ├── sale.service.ts
│   │   │   │   ├── stock.service.ts
│   │   │   │   ├── product.service.ts
│   │   │   │   ├── purchase.service.ts
│   │   │   │   ├── report.service.ts
│   │   │   │   ├── category.service.ts
│   │   │   │   ├── supplier.service.ts
│   │   │   │   ├── product-import.service.ts
│   │   │   │   ├── product-export.service.ts
│   │   │   │   ├── price-change.service.ts
│   │   │   │   └── activity-logger.service.ts
│   │   │   ├── middleware/
│   │   │   │   ├── auth.ts           # JWT verification via Supabase
│   │   │   │   ├── rbac.ts           # Role-based access control
│   │   │   │   └── attendance-gate.ts
│   │   │   ├── db/
│   │   │   │   ├── client.ts         # Drizzle + Supabase connection
│   │   │   │   ├── schema/           # Drizzle table schemas
│   │   │   │   │   ├── index.ts
│   │   │   │   │   ├── users.ts
│   │   │   │   │   ├── products.ts
│   │   │   │   │   ├── categories.ts
│   │   │   │   │   ├── sales.ts
│   │   │   │   │   ├── purchases.ts
│   │   │   │   │   ├── stock-movements.ts
│   │   │   │   │   ├── suppliers.ts
│   │   │   │   │   ├── sale-returns.ts
│   │   │   │   │   ├── settings.ts
│   │   │   │   │   ├── activity-logs.ts
│   │   │   │   │   └── price-change-logs.ts
│   │   │   │   └── migrations/       # Drizzle migrations
│   │   │   ├── validators/           # Zod schemas
│   │   │   │   ├── sale.validator.ts
│   │   │   │   ├── product.validator.ts
│   │   │   │   └── ...
│   │   │   ├── types/                # Shared TypeScript types
│   │   │   │   └── index.ts
│   │   │   └── utils/
│   │   │       ├── invoice.ts        # Invoice number generator
│   │   │       ├── money.ts          # Currency formatting
│   │   │       └── date.ts           # Asia/Jakarta helpers
│   │   ├── drizzle.config.ts
│   │   ├── wrangler.toml             # Cloudflare Workers config
│   │   ├── tsconfig.json
│   │   └── package.json
│   │
│   └── web/                          # React SPA — Cloudflare Pages
│       ├── src/
│       │   ├── main.tsx
│       │   ├── App.tsx
│       │   ├── router.tsx            # React Router v7 config
│       │   ├── api/                  # API client (fetch wrapper)
│       │   │   ├── client.ts         # Base fetch with auth headers
│       │   │   ├── products.api.ts
│       │   │   ├── sales.api.ts
│       │   │   ├── stock.api.ts
│       │   │   └── ...
│       │   ├── stores/               # Zustand stores
│       │   │   ├── auth.store.ts
│       │   │   ├── cart.store.ts     # POS cart state
│       │   │   └── ui.store.ts
│       │   ├── hooks/                # Custom React hooks
│       │   │   ├── useAuth.ts
│       │   │   ├── useProducts.ts
│       │   │   ├── useCart.ts
│       │   │   └── ...
│       │   ├── components/
│       │   │   ├── ui/               # Reusable UI primitives
│       │   │   │   ├── Button.tsx
│       │   │   │   ├── Input.tsx
│       │   │   │   ├── Modal.tsx
│       │   │   │   ├── Table.tsx
│       │   │   │   ├── Badge.tsx
│       │   │   │   ├── Toast.tsx
│       │   │   │   └── ...
│       │   │   ├── pos/              # POS-specific components
│       │   │   │   ├── Cart.tsx
│       │   │   │   ├── ProductGrid.tsx
│       │   │   │   ├── BarcodeInput.tsx
│       │   │   │   ├── PaymentModal.tsx
│       │   │   │   └── ReceiptPreview.tsx
│       │   │   ├── layout/
│       │   │   │   ├── AppLayout.tsx
│       │   │   │   ├── Sidebar.tsx
│       │   │   │   ├── Header.tsx
│       │   │   │   └── AttendanceBanner.tsx
│       │   │   └── reports/
│       │   │       ├── SalesChart.tsx
│       │   │       └── StockChart.tsx
│       │   ├── pages/
│       │   │   ├── Dashboard.tsx
│       │   │   ├── Login.tsx
│       │   │   ├── pos/
│       │   │   │   └── PosTerminal.tsx
│       │   │   ├── products/
│       │   │   │   ├── ProductList.tsx
│       │   │   │   ├── ProductCreate.tsx
│       │   │   │   └── ProductEdit.tsx
│       │   │   ├── categories/
│       │   │   ├── suppliers/
│       │   │   ├── sales/
│       │   │   ├── purchases/
│       │   │   ├── stock/
│       │   │   ├── reports/
│       │   │   ├── attendance/
│       │   │   └── settings/
│       │   ├── lib/
│       │   │   ├── supabase.ts       # Supabase client init
│       │   │   ├── format.ts         # Rp formatting, date utils
│       │   │   └── pdf.ts            # Client-side PDF generation
│       │   └── styles/
│       │       └── index.css         # Tailwind + custom styles
│       ├── public/
│       │   ├── manifest.json         # PWA manifest
│       │   ├── sw.js                 # Service Worker
│       │   └── icons/
│       ├── index.html
│       ├── vite.config.ts
│       ├── tailwind.config.ts
│       ├── tsconfig.json
│       └── package.json
│
├── packages/
│   └── shared/                       # Shared types & utils
│       ├── src/
│       │   ├── types/                # Shared TypeScript interfaces
│       │   │   ├── user.ts
│       │   │   ├── product.ts
│       │   │   ├── sale.ts
│       │   │   └── ...
│       │   ├── constants/
│       │   │   ├── roles.ts          # admin, manager, kasir, karyawan
│       │   │   └── invoice.ts
│       │   └── validators/           # Shared Zod schemas
│       │       ├── sale.schema.ts
│       │       └── product.schema.ts
│       ├── tsconfig.json
│       └── package.json
│
├── supabase/
│   ├── migrations/                   # SQL migrations
│   │   ├── 00001_create_users.sql
│   │   ├── 00002_create_master_data.sql
│   │   ├── 00003_create_transactions.sql
│   │   ├── 00004_create_permissions.sql
│   │   └── 00005_create_logs.sql
│   ├── seed.sql                      # Seed data
│   └── config.toml                   # Supabase local config
│
├── turbo.json                        # Turborepo config
├── package.json                      # Root workspace
├── pnpm-workspace.yaml
├── AGENTS.md
├── MIGRATION_SERVERLESS.md           # ← File ini
└── README.md
```

---

## 5. Backend — Hono.js API

### 5.1 Entry Point (`apps/api/src/index.ts`)

```typescript
import { Hono } from 'hono'
import { cors } from 'hono/cors'
import { logger } from 'hono/logger'
import { secureHeaders } from 'hono/secure-headers'

import { authRoutes } from './routes/auth'
import { productRoutes } from './routes/products'
import { saleRoutes } from './routes/sales'
import { stockRoutes } from './routes/stock'
import { reportRoutes } from './routes/reports'
import { attendanceRoutes } from './routes/attendance'
import { authMiddleware } from './middleware/auth'

type Bindings = {
  SUPABASE_URL: string
  SUPABASE_ANON_KEY: string
  SUPABASE_SERVICE_ROLE_KEY: string
  DATABASE_URL: string
}

const app = new Hono<{ Bindings: Bindings }>()

// Global middleware
app.use('*', logger())
app.use('*', secureHeaders())
app.use('*', cors({
  origin: ['https://stockku.pages.dev', 'http://localhost:5173'],
  credentials: true,
}))

// Public routes
app.route('/api/auth', authRoutes)

// Protected routes (require JWT)
app.use('/api/*', authMiddleware)
app.route('/api/products', productRoutes)
app.route('/api/sales', saleRoutes)
app.route('/api/stock', stockRoutes)
app.route('/api/reports', reportRoutes)
app.route('/api/attendance', attendanceRoutes)

// Health check
app.get('/health', (c) => c.json({ status: 'ok', timestamp: new Date().toISOString() }))

export default app
```

### 5.2 Contoh Route Handler — Sales

```typescript
// apps/api/src/routes/sales.ts
import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { createSaleSchema } from '../validators/sale.validator'
import { SaleService } from '../services/sale.service'
import { requireRole } from '../middleware/rbac'
import { requireAttendance } from '../middleware/attendance-gate'

const sales = new Hono()

// GET /api/sales — list sales (paginated)
sales.get('/', requireRole(['admin', 'manager', 'kasir']), async (c) => {
  const { page, perPage, search, dateFrom, dateTo } = c.req.query()
  const result = await SaleService.list(c, { page, perPage, search, dateFrom, dateTo })
  return c.json(result)
})

// POST /api/sales — create new sale (POS checkout)
sales.post('/',
  requireRole(['admin', 'kasir']),
  requireAttendance,
  zValidator('json', createSaleSchema),
  async (c) => {
    const data = c.req.valid('json')
    const userId = c.get('userId')
    const result = await SaleService.createSale(c, data, userId)
    return c.json(result, 201)
  }
)

// POST /api/sales/:id/return — process return
sales.post('/:id/return',
  requireRole(['admin', 'manager']),
  requireAttendance,
  async (c) => {
    const id = c.req.param('id')
    const body = await c.req.json()
    const result = await SaleService.processReturn(c, id, body)
    return c.json(result)
  }
)

export { sales as saleRoutes }
```

### 5.3 Contoh Service — SaleService

```typescript
// apps/api/src/services/sale.service.ts
import { eq, sql } from 'drizzle-orm'
import { getDb } from '../db/client'
import { sales, saleItems, products, stockMovements } from '../db/schema'
import { StockService } from './stock.service'

export class SaleService {
  /**
   * Migrasi dari Laravel SaleService::createSale()
   * - Tetap menggunakan transaction untuk data integrity
   * - lockForUpdate() → SELECT ... FOR UPDATE di Supabase/PG
   * - Invoice number tetap format INV-YYYYMMDD-0001
   */
  static async createSale(c: any, data: CreateSaleInput, userId: string) {
    const db = getDb(c)

    return await db.transaction(async (tx) => {
      // 1. Generate invoice number (per-day)
      const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })
      const prefix = `INV-${today.replace(/-/g, '')}`

      const lastInvoice = await tx
        .select({ no_invoice: sales.noInvoice })
        .from(sales)
        .where(sql`${sales.noInvoice} LIKE ${prefix + '%'}`)
        .orderBy(sql`${sales.noInvoice} DESC`)
        .limit(1)

      const sequence = lastInvoice.length > 0
        ? parseInt(lastInvoice[0].no_invoice.slice(-4)) + 1
        : 1
      const noInvoice = `${prefix}-${String(sequence).padStart(4, '0')}`

      // 2. Validate stock (SELECT ... FOR UPDATE equivalent)
      for (const item of data.items) {
        const [product] = await tx
          .select()
          .from(products)
          .where(eq(products.id, item.productId))
          .for('update')  // Drizzle PG: row-level lock

        if (!product) throw new Error(`Produk ID ${item.productId} tidak ditemukan`)
        if (product.stok < item.qty) {
          throw new Error(`Stok ${product.nama} tidak cukup (tersedia: ${product.stok})`)
        }
      }

      // 3. Validate money
      const subtotal = data.items.reduce((sum, item) => {
        const itemTotal = (item.hargaJual * item.qty) - (item.diskon || 0)
        if (itemTotal < 0) throw new Error('Diskon item melebihi subtotal item')
        return sum + itemTotal
      }, 0)

      const diskon = Math.max(0, Math.min(data.diskon || 0, subtotal))
      const grandTotal = subtotal - diskon
      if (data.bayar < grandTotal) {
        throw new Error('Pembayaran kurang dari total')
      }

      // 4. Insert sale header
      const [sale] = await tx.insert(sales).values({
        noInvoice,
        userId,
        subtotal,
        diskon,
        grandTotal,
        bayar: data.bayar,
        kembalian: data.bayar - grandTotal,
        status: 'completed',
      }).returning()

      // 5. Insert items + deduct stock
      for (const item of data.items) {
        await tx.insert(saleItems).values({
          saleId: sale.id,
          productId: item.productId,
          qty: item.qty,
          hargaJual: item.hargaJual,
          diskon: item.diskon || 0,
          subtotal: (item.hargaJual * item.qty) - (item.diskon || 0),
        })

        await StockService.recordMovement(tx, {
          productId: item.productId,
          type: 'out',
          qty: item.qty,
          reference: `sale:${sale.id}`,
          note: `Penjualan ${noInvoice}`,
        })
      }

      return sale
    })
  }
}
```

### 5.4 Middleware — Auth (Supabase JWT)

```typescript
// apps/api/src/middleware/auth.ts
import { createMiddleware } from 'hono/factory'
import { createClient } from '@supabase/supabase-js'

export const authMiddleware = createMiddleware(async (c, next) => {
  const authHeader = c.req.header('Authorization')
  if (!authHeader?.startsWith('Bearer ')) {
    return c.json({ error: 'Unauthorized' }, 401)
  }

  const token = authHeader.slice(7)
  const supabase = createClient(
    c.env.SUPABASE_URL,
    c.env.SUPABASE_ANON_KEY,
  )

  const { data: { user }, error } = await supabase.auth.getUser(token)
  if (error || !user) {
    return c.json({ error: 'Token tidak valid' }, 401)
  }

  // Check is_active
  const { data: profile } = await supabase
    .from('users')
    .select('id, name, is_active, role')
    .eq('auth_id', user.id)
    .single()

  if (!profile?.is_active) {
    return c.json({ error: 'Akun Anda dinonaktifkan' }, 403)
  }

  c.set('userId', profile.id)
  c.set('userRole', profile.role)
  c.set('user', profile)
  c.set('supabase', supabase)

  await next()
})
```

### 5.5 Middleware — RBAC

```typescript
// apps/api/src/middleware/rbac.ts
import { createMiddleware } from 'hono/factory'

export const requireRole = (roles: string[]) => {
  return createMiddleware(async (c, next) => {
    const userRole = c.get('userRole')
    if (!roles.includes(userRole)) {
      return c.json({ error: 'Akses ditolak' }, 403)
    }
    await next()
  })
}
```

### 5.6 Middleware — Attendance Gate

```typescript
// apps/api/src/middleware/attendance-gate.ts
import { createMiddleware } from 'hono/factory'
import { getDb } from '../db/client'
import { attendances } from '../db/schema'
import { eq, and, isNull, sql } from 'drizzle-orm'

export const requireAttendance = createMiddleware(async (c, next) => {
  const userRole = c.get('userRole')

  // Admin exempt
  if (userRole === 'admin') {
    await next()
    return
  }

  const userId = c.get('userId')
  const db = getDb(c)
  const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })

  // Check if user has clocked in today and NOT clocked out
  const [attendance] = await db
    .select()
    .from(attendances)
    .where(and(
      eq(attendances.userId, userId),
      sql`DATE(${attendances.clockIn} AT TIME ZONE 'Asia/Jakarta') = ${today}`,
      isNull(attendances.clockOut),
    ))
    .limit(1)

  if (!attendance) {
    return c.json({
      error: 'Anda belum absen masuk. Silakan absen terlebih dahulu.',
      code: 'ATTENDANCE_REQUIRED',
    }, 403)
  }

  await next()
})
```

---

## 6. Frontend — React + Vite SPA

### 6.1 Contoh POS Terminal (React)

```tsx
// apps/web/src/pages/pos/PosTerminal.tsx
import { useState, useRef, useEffect } from 'react'
import { useCartStore } from '@/stores/cart.store'
import { useProducts } from '@/hooks/useProducts'
import { BarcodeInput } from '@/components/pos/BarcodeInput'
import { Cart } from '@/components/pos/Cart'
import { ProductGrid } from '@/components/pos/ProductGrid'
import { PaymentModal } from '@/components/pos/PaymentModal'

export function PosTerminal() {
  const { items, addItem, removeItem, updateQty, clearCart, total } = useCartStore()
  const { data: products } = useProducts()
  const [showPayment, setShowPayment] = useState(false)
  const [search, setSearch] = useState('')

  const handleBarcodeScan = (code: string) => {
    const product = products?.find(
      (p) => p.barcode === code || p.sku?.toLowerCase() === code.toLowerCase()
    )
    if (product) {
      addItem(product)
    }
  }

  return (
    <div className="flex h-[calc(100vh-4rem)] gap-4 p-4">
      {/* Left: Product Grid */}
      <div className="flex-1 flex flex-col gap-4">
        <BarcodeInput onScan={handleBarcodeScan} />
        <input
          type="text"
          placeholder="Cari produk..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="input-primary"
        />
        <ProductGrid
          products={products}
          search={search}
          onSelect={addItem}
        />
      </div>

      {/* Right: Cart */}
      <div className="w-96 flex flex-col">
        <Cart
          items={items}
          onRemove={removeItem}
          onUpdateQty={updateQty}
          total={total}
        />
        <button
          onClick={() => setShowPayment(true)}
          disabled={items.length === 0}
          className="btn-primary mt-4 py-4 text-lg font-bold"
        >
          Bayar — Rp {total.toLocaleString('id-ID')}
        </button>
      </div>

      {showPayment && (
        <PaymentModal
          total={total}
          items={items}
          onClose={() => setShowPayment(false)}
          onSuccess={() => {
            clearCart()
            setShowPayment(false)
          }}
        />
      )}
    </div>
  )
}
```

### 6.2 Zustand Cart Store

```typescript
// apps/web/src/stores/cart.store.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface CartItem {
  productId: string
  nama: string
  hargaJual: number
  qty: number
  stok: number
  diskon: number
  barcode?: string
}

interface CartStore {
  items: CartItem[]
  addItem: (product: any) => void
  removeItem: (productId: string) => void
  updateQty: (productId: string, qty: number) => void
  updateDiskon: (productId: string, diskon: number) => void
  clearCart: () => void
  total: number
  subtotal: number
  headerDiskon: number
  setHeaderDiskon: (diskon: number) => void
}

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      headerDiskon: 0,

      addItem: (product) => set((state) => {
        const existing = state.items.find((i) => i.productId === product.id)
        if (existing) {
          if (existing.qty >= product.stok) return state
          return {
            items: state.items.map((i) =>
              i.productId === product.id ? { ...i, qty: i.qty + 1 } : i
            ),
          }
        }
        return {
          items: [...state.items, {
            productId: product.id,
            nama: product.nama,
            hargaJual: product.harga_jual,
            qty: 1,
            stok: product.stok,
            diskon: 0,
            barcode: product.barcode,
          }],
        }
      }),

      removeItem: (productId) => set((state) => ({
        items: state.items.filter((i) => i.productId !== productId),
      })),

      updateQty: (productId, qty) => set((state) => ({
        items: state.items.map((i) =>
          i.productId === productId ? { ...i, qty: Math.min(qty, i.stok) } : i
        ),
      })),

      updateDiskon: (productId, diskon) => set((state) => ({
        items: state.items.map((i) =>
          i.productId === productId ? { ...i, diskon: Math.max(0, diskon) } : i
        ),
      })),

      clearCart: () => set({ items: [], headerDiskon: 0 }),

      setHeaderDiskon: (diskon) => set({ headerDiskon: Math.max(0, diskon) }),

      get subtotal() {
        return get().items.reduce((sum, i) => sum + (i.hargaJual * i.qty) - i.diskon, 0)
      },

      get total() {
        const sub = get().items.reduce((sum, i) => sum + (i.hargaJual * i.qty) - i.diskon, 0)
        return sub - Math.min(get().headerDiskon, sub)
      },
    }),
    { name: 'stockku-cart' }  // Persist cart in localStorage (offline support)
  )
)
```

### 6.3 API Client

```typescript
// apps/web/src/api/client.ts
import { supabase } from '@/lib/supabase'

const API_BASE = import.meta.env.VITE_API_URL || 'https://api.stockku.workers.dev'

export async function apiClient<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const { data: { session } } = await supabase.auth.getSession()

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(session?.access_token && {
        Authorization: `Bearer ${session.access_token}`,
      }),
      ...options.headers,
    },
  })

  if (!res.ok) {
    const error = await res.json().catch(() => ({ error: 'Network error' }))

    // Handle attendance gate
    if (res.status === 403 && error.code === 'ATTENDANCE_REQUIRED') {
      window.location.href = '/attendance'
      throw new Error(error.error)
    }

    throw new Error(error.error || `HTTP ${res.status}`)
  }

  return res.json()
}
```

---

## 7. Database — Supabase (PostgreSQL)

### 7.1 Migrasi Schema (MySQL → PostgreSQL)

Perubahan utama dari MySQL ke PostgreSQL:

| MySQL | PostgreSQL (Supabase) |
|-------|----------------------|
| `AUTO_INCREMENT` | `GENERATED ALWAYS AS IDENTITY` atau `uuid_generate_v4()` |
| `UNSIGNED` | Tidak ada — gunakan `CHECK (col >= 0)` |
| `TINYINT(1)` | `BOOLEAN` |
| `DATETIME` | `TIMESTAMPTZ` |
| `TEXT` | `TEXT` (sama) |
| `JSON` | `JSONB` (lebih efisien) |
| `ENUM('a','b')` | `TEXT CHECK (col IN ('a','b'))` atau custom type |
| `DOUBLE(15,2)` | `NUMERIC(15,2)` |

### 7.2 Contoh Migration SQL

```sql
-- supabase/migrations/00003_create_transactions.sql

-- Sales header
CREATE TABLE sales (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  no_invoice VARCHAR(30) NOT NULL UNIQUE,
  user_id UUID NOT NULL REFERENCES users(id),
  subtotal NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
  diskon NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (diskon >= 0),
  grand_total NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (grand_total >= 0),
  bayar NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (bayar >= 0),
  kembalian NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (kembalian >= 0),
  status TEXT NOT NULL DEFAULT 'completed'
    CHECK (status IN ('completed', 'partial_return', 'returned', 'cancelled')),
  catatan TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Sale items
CREATE TABLE sale_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_id UUID NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id),
  qty INTEGER NOT NULL CHECK (qty > 0),
  harga_jual NUMERIC(15,2) NOT NULL CHECK (harga_jual >= 0),
  diskon NUMERIC(15,2) NOT NULL DEFAULT 0 CHECK (diskon >= 0),
  subtotal NUMERIC(15,2) NOT NULL CHECK (subtotal >= 0),
  returned_qty INTEGER NOT NULL DEFAULT 0 CHECK (returned_qty >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Stock movements
CREATE TABLE stock_movements (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id UUID NOT NULL REFERENCES products(id),
  type TEXT NOT NULL CHECK (type IN ('in', 'out', 'adjustment')),
  qty INTEGER NOT NULL CHECK (qty > 0),
  stock_before INTEGER NOT NULL,
  stock_after INTEGER NOT NULL,
  reference TEXT,
  note TEXT,
  user_id UUID REFERENCES users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_sales_user_id ON sales(user_id);
CREATE INDEX idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product_id ON sale_items(product_id);
CREATE INDEX idx_stock_movements_product_id ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_created_at ON stock_movements(created_at);
```

### 7.3 Row Level Security (RLS)

```sql
-- Supabase RLS policies
ALTER TABLE sales ENABLE ROW LEVEL SECURITY;

-- Admin & Manager: can see all sales
CREATE POLICY "admin_manager_view_all_sales" ON sales
  FOR SELECT
  USING (
    auth.jwt() ->> 'role' IN ('admin', 'manager')
  );

-- Kasir: can only see own sales
CREATE POLICY "kasir_view_own_sales" ON sales
  FOR SELECT
  USING (
    user_id = (auth.jwt() ->> 'user_id')::uuid
  );

-- Insert: only kasir & admin during active attendance
CREATE POLICY "create_sale" ON sales
  FOR INSERT
  WITH CHECK (
    auth.jwt() ->> 'role' IN ('admin', 'kasir')
  );
```

### 7.4 Drizzle Schema

```typescript
// apps/api/src/db/schema/sales.ts
import { pgTable, uuid, varchar, numeric, text, timestamp, integer, check } from 'drizzle-orm/pg-core'
import { sql } from 'drizzle-orm'
import { users } from './users'
import { products } from './products'

export const sales = pgTable('sales', {
  id: uuid('id').primaryKey().defaultRandom(),
  noInvoice: varchar('no_invoice', { length: 30 }).notNull().unique(),
  userId: uuid('user_id').notNull().references(() => users.id),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull().default('0'),
  diskon: numeric('diskon', { precision: 15, scale: 2 }).notNull().default('0'),
  grandTotal: numeric('grand_total', { precision: 15, scale: 2 }).notNull().default('0'),
  bayar: numeric('bayar', { precision: 15, scale: 2 }).notNull().default('0'),
  kembalian: numeric('kembalian', { precision: 15, scale: 2 }).notNull().default('0'),
  status: text('status').notNull().default('completed'),
  catatan: text('catatan'),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp('updated_at', { withTimezone: true }).notNull().defaultNow(),
})

export const saleItems = pgTable('sale_items', {
  id: uuid('id').primaryKey().defaultRandom(),
  saleId: uuid('sale_id').notNull().references(() => sales.id, { onDelete: 'cascade' }),
  productId: uuid('product_id').notNull().references(() => products.id),
  qty: integer('qty').notNull(),
  hargaJual: numeric('harga_jual', { precision: 15, scale: 2 }).notNull(),
  diskon: numeric('diskon', { precision: 15, scale: 2 }).notNull().default('0'),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull(),
  returnedQty: integer('returned_qty').notNull().default(0),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})
```

---

## 8. Authentication & Authorization

### 8.1 Login Flow

```
User → React Login Form → Supabase Auth (signInWithPassword)
  → JWT Token → Store di memory + Supabase session
  → Setiap API call → Authorization: Bearer <token>
  → Hono middleware verify → Supabase Auth getUser()
  → Check is_active → Set userId, role di context
```

### 8.2 Role Mapping

| Laravel (Spatie) | Supabase | Hono Middleware |
|-----------------|----------|-----------------|
| `$user->hasRole('admin')` | `user_metadata.role = 'admin'` | `requireRole(['admin'])` |
| `@role('kasir')` | React: `useAuth().role === 'kasir'` | — |
| `role:admin\|manager` | — | `requireRole(['admin', 'manager'])` |

### 8.3 Supabase Auth Setup

```typescript
// apps/web/src/lib/supabase.ts
import { createClient } from '@supabase/supabase-js'

export const supabase = createClient(
  import.meta.env.VITE_SUPABASE_URL,
  import.meta.env.VITE_SUPABASE_ANON_KEY,
)
```

---

## 9. Migrasi Fitur per Modul

### 9.1 POS / Kasir

| Fitur | Laravel (Sekarang) | Serverless (Baru) |
|-------|-------------------|-------------------|
| Cart management | Livewire `PosTerminal.php` (server state) | Zustand store (client state, localStorage persist) |
| Barcode scan | Alpine.js `@keydown.enter` → `$wire.addByBarcode()` | React `BarcodeInput` → Zustand `addItem()` |
| Search produk | Livewire `$wire.search` | React `useState` + TanStack Query filter |
| Checkout | Livewire → `SaleService::createSale()` | `POST /api/sales` → `SaleService.createSale()` |
| Print struk | Blade view → DomPDF | `@react-pdf/renderer` atau thermal print API |
| Offline mode | Service Worker + sync via `OfflineSyncController` | Service Worker + IndexedDB + sync API |

### 9.2 Inventory Management

| Fitur | Laravel | Serverless |
|-------|---------|------------|
| Product CRUD | `ProductController` + Blade forms | React forms + `POST/PUT/DELETE /api/products` |
| Stock mutations | `StockController` + `StockService` | `POST /api/stock/movements` |
| Low-stock alerts | Query `WHERE stok <= stok_minimum` | TanStack Query + Supabase Realtime subscription |
| Product import | `ProductImportService` (server CSV parse) | Client-side CSV parse + batch `POST /api/products/import` |
| Product export | `ProductExportService` (server Excel) | Client-side `xlsx` library |

### 9.3 Attendance (Absensi)

| Fitur | Laravel | Serverless |
|-------|---------|------------|
| Clock in/out | Controller + form POST | `POST /api/attendance/clock-in` / `clock-out` |
| Leave request | Form → Controller | React form → `POST /api/attendance/leave` |
| Approval flow | Manager/Admin approve via Controller | `PATCH /api/attendance/leave/:id/approve` |
| Read-only gate | `EnsureAttended` middleware + Blade banner | `requireAttendance` Hono middleware + React `AttendanceBanner` |

### 9.4 Reports

| Fitur | Laravel | Serverless |
|-------|---------|------------|
| Sales report | `ReportService` + Blade table | `GET /api/reports/sales` + React table |
| P/L report | `ReportService` + Blade | `GET /api/reports/profit-loss` + React |
| Stock report | Server query + Blade | `GET /api/reports/stock` + React |
| PDF export | `barryvdh/laravel-dompdf` | `@react-pdf/renderer` (client) atau `pdf-lib` (Worker) |
| Excel export | `openspout/openspout` | `xlsx` / `exceljs` (client-side) |
| Charts | `Chart.js` via Blade `<canvas>` | `react-chartjs-2` |

---

## 10. Deployment — Cloudflare Workers + Pages

### 10.1 Wrangler Config (API)

```toml
# apps/api/wrangler.toml
name = "stockku-api"
main = "src/index.ts"
compatibility_date = "2024-09-01"

[vars]
ENVIRONMENT = "production"

# Secrets (set via `wrangler secret put`):
# SUPABASE_URL
# SUPABASE_ANON_KEY
# SUPABASE_SERVICE_ROLE_KEY
# DATABASE_URL
```

### 10.2 Deploy Commands

```bash
# API (Cloudflare Workers)
cd apps/api
pnpm wrangler deploy

# Frontend (Cloudflare Pages)
cd apps/web
pnpm build
pnpm wrangler pages deploy dist

# Atau via CI/CD (GitHub Actions):
# Push to main → auto deploy kedua apps
```

### 10.3 GitHub Actions CI/CD

```yaml
# .github/workflows/deploy.yml
name: Deploy StockKu
on:
  push:
    branches: [main]

jobs:
  deploy-api:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v2
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: pnpm install --frozen-lockfile
      - run: pnpm --filter api run deploy
        env:
          CLOUDFLARE_API_TOKEN: ${{ secrets.CF_API_TOKEN }}

  deploy-web:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v2
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: pnpm install --frozen-lockfile
      - run: pnpm --filter web run build
      - uses: cloudflare/pages-action@v1
        with:
          apiToken: ${{ secrets.CF_API_TOKEN }}
          accountId: ${{ secrets.CF_ACCOUNT_ID }}
          projectName: stockku
          directory: apps/web/dist
```

### 10.4 Custom Domain

```
stockku.com          → Cloudflare Pages (React SPA)
api.stockku.com      → Cloudflare Workers (Hono.js API)
```

---

## 11. PWA & Offline Support

### 11.1 Strategi Offline

```
Online:
  React SPA → API calls → Supabase DB
  
Offline (Service Worker):
  React SPA → Zustand (localStorage) → IndexedDB queue
  
Sync (back online):
  IndexedDB queue → POST /api/sync → Supabase DB
```

### 11.2 Offline POS Flow

1. **Cart**: Zustand store persisted di `localStorage` — sudah offline-ready
2. **Product catalog**: Cache di IndexedDB saat online, serve dari cache saat offline
3. **Checkout offline**: Simpan sale ke IndexedDB, tandai `synced: false`
4. **Sync**: Saat online, batch POST semua unsynced sales ke `/api/sales/sync`
5. **Conflict resolution**: Server menolak jika stok tidak cukup → tampilkan error, user adjust

### 11.3 Service Worker (Vite PWA)

```typescript
// apps/web/vite.config.ts
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'StockKu POS',
        short_name: 'StockKu',
        theme_color: '#6366f1',
        background_color: '#0f172a',
        display: 'standalone',
        icons: [/* ... */],
      },
      workbox: {
        runtimeCaching: [
          {
            urlPattern: /\/api\/products/,
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'products-cache' },
          },
        ],
      },
    }),
  ],
})
```

---

## 12. Testing Strategy

### 12.1 Backend (Hono.js)

```bash
# Unit tests (Vitest)
pnpm --filter api test

# Integration tests (against local Supabase)
supabase start
pnpm --filter api test:integration
```

```typescript
// apps/api/src/__tests__/sales.test.ts
import { describe, it, expect } from 'vitest'
import app from '../index'

describe('POST /api/sales', () => {
  it('should create a sale and deduct stock', async () => {
    const res = await app.request('/api/sales', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${testToken}`,
      },
      body: JSON.stringify({
        items: [{ productId: 'xxx', qty: 2, hargaJual: 10000 }],
        diskon: 0,
        bayar: 20000,
      }),
    })
    expect(res.status).toBe(201)
    const data = await res.json()
    expect(data.noInvoice).toMatch(/^INV-\d{8}-\d{4}$/)
  })

  it('should reject if stock insufficient', async () => {
    // ...
  })

  it('should reject if payment < total', async () => {
    // ...
  })
})
```

### 12.2 Frontend (React)

```bash
# Component tests (Vitest + React Testing Library)
pnpm --filter web test

# E2E tests (Playwright)
pnpm --filter web test:e2e
```

---

## 13. Fase & Timeline Migrasi

### Fase 1: Foundation (Minggu 1-2)
- [ ] Setup monorepo (Turborepo + pnpm workspaces)
- [ ] Setup Supabase project + migrasi schema SQL
- [ ] Setup Hono.js boilerplate + Cloudflare Workers
- [ ] Setup React + Vite boilerplate + Cloudflare Pages
- [ ] Setup CI/CD (GitHub Actions)
- [ ] Implementasi Auth (Supabase Auth + Hono JWT middleware)
- [ ] Implementasi RBAC middleware

### Fase 2: Core API (Minggu 3-4)
- [ ] Migrasi semua Drizzle schemas
- [ ] Migrasi `ProductService` → `product.service.ts`
- [ ] Migrasi `CategoryService` → `category.service.ts`
- [ ] Migrasi `SupplierService` → `supplier.service.ts`
- [ ] Migrasi `StockService` → `stock.service.ts`
- [ ] Migrasi `SaleService` → `sale.service.ts` (termasuk return logic)
- [ ] Migrasi `PurchaseService` → `purchase.service.ts`
- [ ] Migrasi `ReportService` → `report.service.ts`
- [ ] Migrasi Attendance Gate logic
- [ ] Migrasi `ActivityLogger` → `activity-logger.service.ts`
- [ ] Unit tests untuk semua services

### Fase 3: Frontend (Minggu 5-7)
- [ ] Design system (Tailwind + Radix UI components)
- [ ] Layout (Sidebar, Header, AttendanceBanner)
- [ ] Login page
- [ ] Dashboard
- [ ] Product CRUD pages
- [ ] Category & Supplier pages
- [ ] POS Terminal (cart, barcode, payment, receipt)
- [ ] Purchase/Restock pages
- [ ] Stock movement pages
- [ ] Sales list + detail pages
- [ ] Report pages (charts + tables)
- [ ] Attendance pages (clock in/out, leave)
- [ ] Settings page
- [ ] Activity log page

### Fase 4: Polish & PWA (Minggu 8)
- [ ] PWA manifest + Service Worker
- [ ] Offline POS mode (IndexedDB + sync)
- [ ] Performance optimization (lazy loading, code splitting)
- [ ] E2E tests (Playwright)
- [ ] Migrasi data produksi (MySQL → Supabase)
- [ ] User acceptance testing (UAT)

### Fase 5: Launch (Minggu 9)
- [ ] DNS cutover → Cloudflare
- [ ] Monitor errors (Sentry/Cloudflare Analytics)
- [ ] Decommission VPS (setelah 2 minggu stable)

---

## 14. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| **Cloudflare Workers CPU limit** (10ms free, 50ms paid) | Report query besar timeout | Offload heavy queries ke Supabase Edge Functions atau gunakan pagination |
| **No file system di Workers** | PDF/Excel generation | Generate client-side (`@react-pdf/renderer`, `xlsx`) |
| **Supabase free tier limit** (500MB DB, 1GB storage) | Data besar | Monitor usage, upgrade ke Pro ($25/bulan) jika perlu |
| **Data migration complexity** | MySQL → PostgreSQL perbedaan tipe | Tulis migration script, test di staging dulu |
| **Livewire → React rewrite** | Effort besar | Mulai dari POS (fitur inti), fitur lain bertahap |
| **Offline sync conflicts** | Data inconsistency | Server-side validation tetap enforced, client retry with feedback |

---

## 15. Checklist Pre-Launch

- [ ] Semua API endpoints tested (unit + integration)
- [ ] Semua React pages functional
- [ ] Auth flow (login, logout, session refresh) working
- [ ] RBAC (admin, manager, kasir, karyawan) verified
- [ ] Attendance gate working (read-only mode + write block)
- [ ] POS checkout flow end-to-end
- [ ] Stock deduction correct
- [ ] Return flow correct
- [ ] Invoice numbering correct (INV-YYYYMMDD-0001)
- [ ] Reports generating correctly
- [ ] PDF/Excel export working
- [ ] PWA installable
- [ ] Offline POS mode tested
- [ ] Data migrated from MySQL
- [ ] Custom domain configured
- [ ] SSL/HTTPS active
- [ ] Error monitoring active
- [ ] Backup strategy for Supabase (PITR)

---

## ⚠️ Data Integrity Rules — TETAP BERLAKU

Semua aturan data integrity dari versi Laravel **TETAP HARUS diikuti** di versi serverless:

1. **Stock guards server-side** — `SaleService.createSale()` tetap menggunakan `SELECT ... FOR UPDATE` di dalam `db.transaction()`. Tidak boleh bypass dengan raw decrement.
2. **`StockService.recordMovement()` throws** — ketika `type = 'out'` akan membuat stok di bawah nol.
3. **Returns are capped** — `returned_qty` tracking + reject over-return.
4. **Money validation** — server-side clamping diskon, enforce `bayar >= grand_total`.
5. **Invoice uniqueness** — per-day sequential di dalam transaction.

---

> **Dokumen ini adalah panduan hidup.** Update seiring progress migrasi.
