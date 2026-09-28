# StockKu - AI Agent Instructions

This file contains the core context, technology stack, and architectural guidelines for AI coding agents contributing to the **StockKu** repository.

## 🎯 Project Overview
StockKu is a modern, responsive Web-based Point of Sale (POS) and Inventory Management System. It is designed to be visually premium, fast, and serverless-first — running entirely on edge infrastructure with zero server management.

**Key Features:**
- **POS / Kasir**: Real-time cart management via React + Zustand, barcode scanner support, and offline (PWA) fallback with IndexedDB sync.
- **Inventory Management**: Stock mutations, low-stock alerts, purchase (restock) recording.
- **Attendance**: Clock in/out and leave requests (izin/sakit/cuti) with approval flow. **Attendance is mandatory**: users who have not clocked in (or have clocked out) enter read-only mode — all write actions are blocked until they clock in.
- **Reporting**: Sales, Profit/Loss, Stock Mutations, and Attendance reports.
- **Role-Based Access Control**: Admin, Manager, Kasir, Karyawan.

## 🛠️ Technology Stack

### Backend (API) — `apps/api/`
- **Runtime**: Cloudflare Workers (edge computing, 0ms cold start)
- **Framework**: Hono.js v4 (TypeScript, Web Standards)
- **ORM**: Drizzle ORM (lightweight, type-safe, SQL-like)
- **Validation**: Zod (schema validation + type inference)
- **Auth**: Supabase Auth (JWT tokens, verified in Hono middleware)

### Frontend (SPA) — `apps/web/`
- **Framework**: React 19 + Vite 6 (SPA, client-side routing)
- **Routing**: React Router v7
- **State Management**: Zustand (cart, UI state) + TanStack Query v5 (server data)
- **Styling**: Tailwind CSS v4
- **Forms**: React Hook Form + Zod
- **Charts**: Chart.js + react-chartjs-2
- **UI Primitives**: Radix UI (accessible, unstyled)

### Database & Infrastructure
- **Database**: Supabase (PostgreSQL 15, managed) — with Row Level Security (RLS)
- **File Storage**: Supabase Storage (receipts, exports)
- **Realtime**: Supabase Realtime (WebSocket — stock update notifications)
- **API Hosting**: Cloudflare Workers
- **SPA Hosting**: Cloudflare Pages
- **PDF Generation**: `@react-pdf/renderer` (client-side) or `pdf-lib` (Worker)
- **Excel Export**: `xlsx` / `exceljs` (client-side)
- **PWA**: `vite-plugin-pwa` — offline POS (cart persist in localStorage, sales queue in IndexedDB, sync via `/api/sales/sync`)

### Shared — `packages/shared/`
- Shared TypeScript types, Zod validators, and constants used by both `apps/api` and `apps/web`

### Monorepo Tooling
- **Package Manager**: pnpm (workspaces)
- **Build Orchestrator**: Turborepo
- **CI/CD**: GitHub Actions → auto-deploy to Cloudflare on push to `main`

## 🧱 Data Integrity Rules (MUST follow)
- **Stock guards are server-side**: `SaleService.createSale()` re-fetches products with `SELECT ... FOR UPDATE` inside `db.transaction()`. Never bypass with raw decrement.
- **`StockService.recordMovement()` throws** when `type = 'out'` would drive stock below zero, and throws for unknown types.
- **Returns are capped**: `sale_items.returned_qty` tracks cumulative returns; `SaleService.processReturn()` rejects over-return and sets sale status to `partial_return` / `returned`.
- **Money validation**: header `diskon` is clamped to `[0, subtotal]`, item discount cannot make item subtotal negative, and `bayar >= grand_total` is enforced in the service (never trust client-side checks).
- **Tests**: run `pnpm --filter api test` before finishing work on services. New behavior on sales/stock/returns must come with tests under `apps/api/src/__tests__/`.

## 🚪 Attendance Gate (MUST follow)
- Logic lives in `apps/api/src/middleware/attendance-gate.ts` (`requireAttendance` middleware).
- **Admin (owner) is fully exempt** — never gate admin accounts.
- Everyone else: GET requests are allowed, but any mutating request (POST/PUT/PATCH/DELETE) returns `403 ATTENDANCE_REQUIRED` if the user has not clocked in today.
- Frontend handles `403 ATTENDANCE_REQUIRED` by redirecting to `/attendance` and showing an amber "Mode Baca" banner via `<AttendanceBanner />` in `AppLayout.tsx`.
- The POS page checks attendance status on mount via TanStack Query and disables the checkout button if not attended.

## 🏗️ Architectural Guidelines
Please adhere to the following conventions when making changes or adding features:

### 1. Separation of Concerns (API → Service → DB)
- Keep **Route handlers** skinny. They should only handle HTTP parsing, input validation (Zod), and returning JSON responses.
- Complex business logic, database transactions, and data formatting MUST reside in `apps/api/src/services/` (e.g., `sale.service.ts`, `stock.service.ts`, `report.service.ts`).
- Use **Zod schemas** in `apps/api/src/validators/` for all incoming data validation, integrated via `@hono/zod-validator`.
- All database access goes through **Drizzle ORM** — never use raw SQL strings unless absolutely necessary for PostgreSQL-specific features.

### 2. Hono.js Best Practices
- Use Hono's `createMiddleware` from `hono/factory` for all custom middleware.
- Environment bindings (secrets, KV) are accessed via `c.env.VARIABLE_NAME`.
- Use `c.set()` / `c.get()` for request-scoped context (userId, userRole, supabase client).
- Route files export a `Hono` instance that is mounted in `index.ts` via `app.route()`.
- Prefer returning `c.json()` for all responses with appropriate HTTP status codes.
- For Workers CPU limits: avoid heavy computation — offload to Supabase Edge Functions or paginate large queries.

### 3. React + Zustand Best Practices
- **Zustand** stores live in `apps/web/src/stores/`. Use the `persist` middleware for data that must survive page reloads (e.g., cart state).
- **TanStack Query** is used for all server data fetching — define queries in `apps/web/src/hooks/` (e.g., `useProducts.ts`, `useSales.ts`).
- Keep pages in `apps/web/src/pages/` and reusable components in `apps/web/src/components/`.
- API calls go through `apps/web/src/api/client.ts` which auto-attaches the Supabase JWT and handles `403 ATTENDANCE_REQUIRED` redirects.
- **Barcode scanning**: the POS barcode input uses a dedicated `<BarcodeInput />` component. On Enter, it calls `useCartStore.addItem()` directly. Do NOT debounce keystrokes for barcode input — scanners fire all characters nearly simultaneously.

### 4. Supabase Auth & RBAC
- Authentication uses **Supabase Auth** (`signInWithPassword`, `signUp`, `signOut`).
- JWT tokens are auto-managed by `@supabase/supabase-js` and attached to every API call via the `Authorization: Bearer <token>` header.
- Role information is stored in `users.role` column (not Supabase `user_metadata`).
- Hono middleware `requireRole(['admin', 'manager'])` checks `c.get('userRole')`.
- React-side: use `useAuth()` hook to get current user role; conditionally render UI with `{user.role === 'admin' && <AdminPanel />}`.

### 5. UI / UX & Aesthetics
- This app prioritizes a premium, modern aesthetic. Do NOT use generic red/blue/green colors.
- Stick to the curated Tailwind color palettes used in the project:
  - Primary accents: `indigo-500`, `purple-600`
  - Success/Money: `emerald-500`, `teal-600`
  - Warnings: `amber-500`
  - Backgrounds: `slate-50`, `slate-900`
- Utilize soft shadows (`shadow-sm`, `shadow-lg shadow-indigo-500/30`), rounded corners (`rounded-xl`, `rounded-2xl`), and glassmorphism where appropriate.
- Ensure all forms and inputs look clean and responsive.
- Use **Radix UI** for accessible primitives (Dialog, Dropdown, Tooltip, etc.) and style with Tailwind.

### 6. Notifications
- Use a global **Toast** component (React context + Zustand) for success/error messages.
- Success toasts auto-dismiss after 3 seconds. Error toasts require manual dismissal.
- The POS checkout success shows a centered popup with "Cetak Struk" link and auto-closes after 3 seconds.

### 7. Error Handling & Transactions
- Always wrap complex database operations (Sales checkout, Stock deductions) inside `db.transaction()` (Drizzle transaction).
- Use `SELECT ... FOR UPDATE` inside transactions for stock-critical operations.
- API errors return consistent JSON: `{ error: string, code?: string }` with appropriate HTTP status.
- Frontend catches errors via TanStack Query `onError` or try/catch and displays via Toast.

## 🚦 Important Notes for Agents
- The application uses `Asia/Jakarta` (WIB, UTC+7) timezone for all business logic (invoice dates, attendance, reports).
- **Session management**: Supabase Auth sessions auto-refresh. Users stay logged in until explicit logout or admin deactivation.
- **Account status**: `users.is_active` column. Inactive accounts get `403` from auth middleware ("Akun Anda dinonaktifkan"). The admin (owner) account can never be deactivated.
- Tailwind is processed by Vite; any change to Tailwind config or class names requires `pnpm --filter web build` (or `pnpm --filter web dev` for HMR).
- When generating new features, ensure Drizzle migrations are created (`pnpm --filter api drizzle-kit generate`) and seed data scripts are updated.
- **Never run seed scripts against the production Supabase database** — use it only in local dev (Supabase CLI: `supabase start`).
- PostgreSQL notes: string `=` is case-sensitive (use `ILIKE` or `LOWER()`), use `TIMESTAMPTZ` for all timestamps, `JSONB` for JSON columns, `NUMERIC(15,2)` for money.
- Invoice numbers are per-day prefixed (`INV-YYYYMMDD-0001`) and derived from `MAX()` inside a transaction.
- **Cloudflare Workers limits**: 10ms CPU (free) / 50ms CPU (paid). Keep route handlers fast. Paginate large queries. Heavy computation → Supabase Edge Functions.
- **UUID primary keys**: All tables use `UUID` (via `gen_random_uuid()`) instead of auto-increment integers.

## 🔄 Development Workflow (MUST follow, point by point)
1. **Understand the task** — read the relevant files first, then implement changes following the conventions above.
2. **Verify changes** — run `pnpm --filter api test` for backend changes and `pnpm --filter web build` for frontend changes, then fix any errors found.
3. **Check the diff** — run `git status` and `git diff`; make sure no secrets (tokens, passwords, `.env`) are staged.
4. **Commit changes** — write a concise commit message in the repo's existing style (e.g., `git add -A && git commit -m "..."`).
5. **Push to GitHub** — ALWAYS push the commit to the `main` branch after finishing work: `git push origin main`. If authentication fails, notify the user that a manual push is required.
6. **Report back** — summarize what was changed point by point (file → what changed → why), and confirm the push status.