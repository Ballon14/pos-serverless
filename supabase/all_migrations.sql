-- ========================================================
-- STOCKKU SERVERLESS POS - CONSOLIDATED DATABASE MIGRATION
-- Jalankan skrip ini langsung di Supabase Dashboard -> SQL Editor
-- URL Project: https://supabase.com/dashboard/project/bnkndcxmiyhcmapvusbx
-- ========================================================

-- 1. Enable Extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ========================================================
-- 2. USERS & AUTH INTEGRATION
-- ========================================================
CREATE TABLE IF NOT EXISTS public.users (
  id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  role VARCHAR(50) NOT NULL DEFAULT 'kasir' CHECK (role IN ('admin', 'manager', 'kasir', 'karyawan')),
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger AS $$
BEGIN
  INSERT INTO public.users (id, name, email, role, is_active)
  VALUES (
    new.id,
    COALESCE(new.raw_user_meta_data->>'name', split_part(new.email, '@', 1)),
    new.email,
    COALESCE(new.raw_user_meta_data->>'role', 'kasir'),
    true
  )
  ON CONFLICT (id) DO UPDATE
  SET
    name = EXCLUDED.name,
    email = EXCLUDED.email;
  RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT OR UPDATE ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

CREATE INDEX IF NOT EXISTS idx_users_role ON public.users(role);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON public.users(is_active);

-- ========================================================
-- 3. MASTER DATA (Categories, Suppliers, Products)
-- ========================================================
CREATE TABLE IF NOT EXISTS public.categories (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.suppliers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(255) NOT NULL,
  code VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(50),
  email VARCHAR(255),
  address TEXT,
  contact_person VARCHAR(255),
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.products (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  category_id UUID NOT NULL REFERENCES public.categories(id) ON DELETE RESTRICT,
  name VARCHAR(255) NOT NULL,
  sku VARCHAR(100) NOT NULL UNIQUE,
  harga_beli NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (harga_beli >= 0),
  harga_jual NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (harga_jual >= 0),
  grosir_tiers JSONB DEFAULT '[]'::jsonb,
  stok INTEGER NOT NULL DEFAULT 0 CHECK (stok >= 0),
  min_stok INTEGER NOT NULL DEFAULT 5 CHECK (min_stok >= 0),
  satuan VARCHAR(50) NOT NULL DEFAULT 'pcs',
  foto TEXT,
  deskripsi TEXT,
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_products_category ON public.products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_sku ON public.products(sku);
CREATE INDEX IF NOT EXISTS idx_products_stok ON public.products(stok);
CREATE INDEX IF NOT EXISTS idx_products_is_active ON public.products(is_active);

-- ========================================================
-- 4. TRANSACTIONS (Sales, Purchases, Movements, Returns)
-- ========================================================
CREATE TABLE IF NOT EXISTS public.sales (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT,
  subtotal NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
  diskon NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (diskon >= 0),
  grand_total NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (grand_total >= 0),
  bayar NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (bayar >= 0),
  kembalian NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (kembalian >= 0),
  payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
  status VARCHAR(50) NOT NULL DEFAULT 'completed' CHECK (status IN ('completed', 'returned', 'partial_return')),
  catatan TEXT,
  sumber VARCHAR(50),
  offline_id VARCHAR(100) UNIQUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT check_payment_ge_total CHECK (bayar >= grand_total),
  CONSTRAINT check_discount_le_subtotal CHECK (diskon <= subtotal)
);

CREATE TABLE IF NOT EXISTS public.sale_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_id UUID NOT NULL REFERENCES public.sales(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE RESTRICT,
  qty INTEGER NOT NULL CHECK (qty > 0),
  returned_qty INTEGER NOT NULL DEFAULT 0 CHECK (returned_qty >= 0),
  harga NUMERIC(15, 2) NOT NULL CHECK (harga >= 0),
  harga_beli NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (harga_beli >= 0),
  diskon NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (diskon >= 0),
  subtotal NUMERIC(15, 2) NOT NULL CHECK (subtotal >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT check_returned_le_qty CHECK (returned_qty <= qty)
);

CREATE TABLE IF NOT EXISTS public.sale_returns (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_id UUID NOT NULL REFERENCES public.sales(id) ON DELETE CASCADE,
  return_number VARCHAR(50) NOT NULL UNIQUE,
  total_refund NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (total_refund >= 0),
  alasan TEXT,
  status VARCHAR(50) NOT NULL DEFAULT 'approved' CHECK (status IN ('pending', 'approved', 'rejected')),
  processed_by UUID REFERENCES public.users(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.sale_return_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sale_return_id UUID NOT NULL REFERENCES public.sale_returns(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE RESTRICT,
  qty INTEGER NOT NULL CHECK (qty > 0),
  harga NUMERIC(15, 2) NOT NULL CHECK (harga >= 0),
  subtotal NUMERIC(15, 2) NOT NULL CHECK (subtotal >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.purchases (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  supplier_id UUID NOT NULL REFERENCES public.suppliers(id) ON DELETE RESTRICT,
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT,
  tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
  total NUMERIC(15, 2) NOT NULL DEFAULT 0 CHECK (total >= 0),
  status VARCHAR(50) NOT NULL DEFAULT 'received' CHECK (status IN ('pending', 'received', 'cancelled')),
  keterangan TEXT,
  foto_nota TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.purchase_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  purchase_id UUID NOT NULL REFERENCES public.purchases(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE RESTRICT,
  qty INTEGER NOT NULL CHECK (qty > 0),
  harga NUMERIC(15, 2) NOT NULL CHECK (harga >= 0),
  subtotal NUMERIC(15, 2) NOT NULL CHECK (subtotal >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.stock_movements (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE RESTRICT,
  type VARCHAR(50) NOT NULL CHECK (type IN ('in', 'out', 'return', 'adjustment')),
  qty INTEGER NOT NULL CHECK (qty > 0),
  stok_sebelum INTEGER NOT NULL CHECK (stok_sebelum >= 0),
  stok_sesudah INTEGER NOT NULL CHECK (stok_sesudah >= 0),
  reference_type VARCHAR(100),
  reference_id VARCHAR(100),
  keterangan TEXT,
  user_id UUID REFERENCES public.users(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sales_created_at ON public.sales(created_at);
CREATE INDEX IF NOT EXISTS idx_sales_user_id ON public.sales(user_id);
CREATE INDEX IF NOT EXISTS idx_sales_invoice ON public.sales(invoice_number);
CREATE INDEX IF NOT EXISTS idx_sale_items_sale_id ON public.sale_items(sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product_id ON public.sale_items(product_id);
CREATE INDEX IF NOT EXISTS idx_purchases_tanggal ON public.purchases(tanggal);
CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON public.stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_created ON public.stock_movements(created_at);

-- ========================================================
-- 5. ATTENDANCE & SETTINGS
-- ========================================================
CREATE TABLE IF NOT EXISTS public.attendances (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
  clock_in TIMESTAMPTZ,
  clock_out TIMESTAMPTZ,
  status VARCHAR(50) NOT NULL DEFAULT 'hadir' CHECK (status IN ('hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpha')),
  keterangan TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT unique_user_attendance_per_day UNIQUE (user_id, tanggal)
);

CREATE TABLE IF NOT EXISTS public.leave_requests (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  tipe VARCHAR(50) NOT NULL CHECK (tipe IN ('izin', 'sakit', 'cuti')),
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  alasan TEXT NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
  approved_by UUID REFERENCES public.users(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.settings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  key VARCHAR(100) NOT NULL UNIQUE,
  value TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.activity_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES public.users(id) ON DELETE SET NULL,
  role VARCHAR(50),
  action VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.price_change_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
  harga_lama NUMERIC(15, 2) NOT NULL,
  harga_baru NUMERIC(15, 2) NOT NULL,
  sumber VARCHAR(50) NOT NULL,
  reference_type VARCHAR(100),
  reference_id VARCHAR(100),
  user_id UUID REFERENCES public.users(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_attendances_date ON public.attendances(tanggal);
CREATE INDEX IF NOT EXISTS idx_attendances_user_date ON public.attendances(user_id, tanggal);
CREATE INDEX IF NOT EXISTS idx_leave_requests_user ON public.leave_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON public.activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_price_change_logs_product ON public.price_change_logs(product_id);

-- ========================================================
-- 6. ROW LEVEL SECURITY (RLS) POLICIES
-- ========================================================
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.suppliers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sale_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sale_returns ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sale_return_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.purchases ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.purchase_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.stock_movements ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.attendances ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.leave_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.price_change_logs ENABLE ROW LEVEL SECURITY;

CREATE OR REPLACE FUNCTION public.current_user_role()
RETURNS VARCHAR AS $$
  SELECT role FROM public.users WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Users policies
CREATE POLICY "Users can read own profile or admin/manager read all" ON public.users
  FOR SELECT USING (auth.uid() = id OR public.current_user_role() IN ('admin', 'manager'));

CREATE POLICY "Admin can update users" ON public.users
  FOR UPDATE USING (public.current_user_role() = 'admin');

-- Master data policies
CREATE POLICY "Authenticated users can read categories" ON public.categories
  FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin and manager can manage categories" ON public.categories
  FOR ALL USING (public.current_user_role() IN ('admin', 'manager'));

CREATE POLICY "Authenticated users can read suppliers" ON public.suppliers
  FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin and manager can manage suppliers" ON public.suppliers
  FOR ALL USING (public.current_user_role() IN ('admin', 'manager'));

CREATE POLICY "Authenticated users can read products" ON public.products
  FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin and manager can manage products" ON public.products
  FOR ALL USING (public.current_user_role() IN ('admin', 'manager'));

-- Sales policies
CREATE POLICY "Kasir, manager and admin can view sales" ON public.sales
  FOR SELECT USING (auth.uid() = user_id OR public.current_user_role() IN ('admin', 'manager'));
CREATE POLICY "Kasir and admin can insert sales" ON public.sales
  FOR INSERT WITH CHECK (auth.uid() = user_id AND public.current_user_role() IN ('admin', 'kasir'));

CREATE POLICY "View sale items for authorized sales" ON public.sale_items
  FOR SELECT USING (
    EXISTS (SELECT 1 FROM public.sales WHERE sales.id = sale_items.sale_id AND (sales.user_id = auth.uid() OR public.current_user_role() IN ('admin', 'manager')))
  );
CREATE POLICY "Insert sale items" ON public.sale_items
  FOR INSERT WITH CHECK (
    EXISTS (SELECT 1 FROM public.sales WHERE sales.id = sale_items.sale_id AND sales.user_id = auth.uid())
  );

-- Attendances policies
CREATE POLICY "Users can view own attendances, manager/admin view all" ON public.attendances
  FOR SELECT USING (auth.uid() = user_id OR public.current_user_role() IN ('admin', 'manager'));
CREATE POLICY "Users can clock in/out for themselves" ON public.attendances
  FOR INSERT WITH CHECK (auth.uid() = user_id);
CREATE POLICY "Users can update own attendance" ON public.attendances
  FOR UPDATE USING (auth.uid() = user_id OR public.current_user_role() IN ('admin', 'manager'));

-- Settings policies
CREATE POLICY "Authenticated users can read settings" ON public.settings
  FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin can modify settings" ON public.settings
  FOR ALL USING (public.current_user_role() = 'admin');

-- ========================================================
-- 7. INITIAL SEED DATA
-- ========================================================
INSERT INTO public.settings (key, value) VALUES
  ('store_name', 'StockKu POS'),
  ('store_address', 'Jl. Contoh Alamat No. 123, Jakarta'),
  ('store_phone', '081234567890'),
  ('receipt_footer', 'Terima kasih atas kunjungan Anda!')
ON CONFLICT (key) DO NOTHING;

INSERT INTO public.categories (id, name, slug, description, is_active) VALUES
  ('11111111-1111-1111-1111-111111111111', 'Makanan & Minuman', 'makanan-minuman', 'Produk konsumsi harian', true),
  ('22222222-2222-2222-2222-222222222222', 'Kebutuhan Rumah', 'kebutuhan-rumah', 'Peralatan dan kebutuhan rumah tangga', true),
  ('33333333-3333-3333-3333-333333333333', 'Alat Tulis Kantor', 'atk', 'Perlengkapan sekolah dan kantor', true)
ON CONFLICT (id) DO NOTHING;

INSERT INTO public.suppliers (id, name, code, phone, email, address, contact_person, is_active) VALUES
  ('44444444-4444-4444-4444-444444444444', 'PT Sumber Pangan Sejahtera', 'SUP-001', '0215551234', 'order@sumberpangan.com', 'Kawasan Industri Pulogadung', 'Budi Santoso', true),
  ('55555555-5555-5555-5555-555555555555', 'CV Maju Jaya Abadi', 'SUP-002', '0215555678', 'sales@majujaya.com', 'Kawasan Pergudangan Pluit', 'Dewi Lestari', true)
ON CONFLICT (id) DO NOTHING;

INSERT INTO public.products (id, category_id, name, sku, harga_beli, harga_jual, grosir_tiers, stok, min_stok, satuan, is_active) VALUES
  ('66666666-6666-6666-6666-666666666666', '11111111-1111-1111-1111-111111111111', 'Kopi Susu Gula Aren 250ml', 'KOP-001', 8000, 15000, '[{"minQty": 10, "harga": 13000}]'::jsonb, 50, 5, 'botol', true),
  ('77777777-7777-7777-7777-777777777777', '11111111-1111-1111-1111-111111111111', 'Roti Coklat Keju', 'ROT-001', 5000, 9000, '[{"minQty": 5, "harga": 8000}]'::jsonb, 30, 5, 'pcs', true),
  ('88888888-8888-8888-8888-888888888888', '22222222-2222-2222-2222-222222222222', 'Sabun Cuci Piring 750ml', 'SBN-001', 10000, 14500, '[]'::jsonb, 40, 10, 'pouch', true),
  ('99999999-9999-9999-9999-999999999999', '33333333-3333-3333-3333-333333333333', 'Buku Tulis 58 Lembar', 'BKU-001', 3000, 5000, '[{"minQty": 10, "harga": 4500}]'::jsonb, 100, 20, 'buku', true)
ON CONFLICT (id) DO NOTHING;
