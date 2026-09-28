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
    RETURNS trigger
    LANGUAGE plpgsql
    SECURITY DEFINER SET search_path = public
    AS $$
    BEGIN
    INSERT INTO public.users (id, name, email, role, is_active)
    VALUES (
        new.id,
        COALESCE(new.raw_user_meta_data->>'name', split_part(new.email, '@', 1)),
        new.email,
        COALESCE(new.raw_user_meta_data->>'role', CASE WHEN new.email ILIKE '%admin%' THEN 'admin' ELSE 'kasir' END),
        true
    )
    ON CONFLICT (id) DO UPDATE
    SET
        name = EXCLUDED.name,
        email = EXCLUDED.email;
    RETURN new;
    EXCEPTION
    WHEN OTHERS THEN
        RETURN new;
    END;
    $$;

    DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
    CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
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
    -- 7. COMPLETE INITIAL SEED DATA
    -- ========================================================
    CREATE EXTENSION IF NOT EXISTS "pgcrypto";

    -- Default Users (Password: password123)
    INSERT INTO auth.users (
    id, instance_id, email, encrypted_password, email_confirmed_at,
    raw_app_meta_data, raw_user_meta_data, created_at, updated_at, role, aud,
    confirmation_token, recovery_token, email_change_token_new, email_change_token_current,
    email_change, phone, phone_change, phone_change_token, reauthentication_token
    ) VALUES
    (
        '00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000000',
        'admin@tokombaemi.com', crypt('password123', gen_salt('bf')), NOW(),
        '{"provider":"email","providers":["email"]}', '{"name":"Administrator","role":"admin"}',
        NOW(), NOW(), 'authenticated', 'authenticated',
        '', '', '', '', '', '', '', '', ''
    ),
    (
        '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000000',
        'kasir1@tokombaemi.com', crypt('password123', gen_salt('bf')), NOW(),
        '{"provider":"email","providers":["email"]}', '{"name":"Siti Kasir","role":"kasir"}',
        NOW(), NOW(), 'authenticated', 'authenticated',
        '', '', '', '', '', '', '', '', ''
    ),
    (
        '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000000',
        'kasir2@tokombaemi.com', crypt('password123', gen_salt('bf')), NOW(),
        '{"provider":"email","providers":["email"]}', '{"name":"Budi Kasir","role":"kasir"}',
        NOW(), NOW(), 'authenticated', 'authenticated',
        '', '', '', '', '', '', '', '', ''
    )
    ON CONFLICT (id) DO UPDATE SET
        confirmation_token = '',
        recovery_token = '',
        email_change_token_new = '',
        email_change_token_current = '',
        email_change = '',
        reauthentication_token = '',
        phone = '',
        phone_change = '',
        phone_change_token = '';

    -- Identities untuk Supabase GoTrue Auth Service
    INSERT INTO auth.identities (
        id, user_id, identity_data, provider, provider_id, last_sign_in_at, created_at, updated_at
    ) VALUES
    (
        gen_random_uuid(),
        '00000000-0000-0000-0000-000000000001',
        jsonb_build_object('sub', '00000000-0000-0000-0000-000000000001', 'email', 'admin@tokombaemi.com', 'email_verified', true),
        'email',
        '00000000-0000-0000-0000-000000000001',
        NOW(), NOW(), NOW()
    ),
    (
        gen_random_uuid(),
        '00000000-0000-0000-0000-000000000002',
        jsonb_build_object('sub', '00000000-0000-0000-0000-000000000002', 'email', 'kasir1@tokombaemi.com', 'email_verified', true),
        'email',
        '00000000-0000-0000-0000-000000000002',
        NOW(), NOW(), NOW()
    ),
    (
        gen_random_uuid(),
        '00000000-0000-0000-0000-000000000003',
        jsonb_build_object('sub', '00000000-0000-0000-0000-000000000003', 'email', 'kasir2@tokombaemi.com', 'email_verified', true),
        'email',
        '00000000-0000-0000-0000-000000000003',
        NOW(), NOW(), NOW()
    )
    ON CONFLICT (provider_id, provider) DO NOTHING;

    INSERT INTO public.users (id, name, email, role, is_active) VALUES
    ('00000000-0000-0000-0000-000000000001', 'Administrator', 'admin@tokombaemi.com', 'admin', true),
    ('00000000-0000-0000-0000-000000000002', 'Siti Kasir', 'kasir1@tokombaemi.com', 'kasir', true),
    ('00000000-0000-0000-0000-000000000003', 'Budi Kasir', 'kasir2@tokombaemi.com', 'kasir', true)
    ON CONFLICT (id) DO UPDATE SET
    role = EXCLUDED.role,
    name = EXCLUDED.name;

    -- Settings
    INSERT INTO public.settings (key, value) VALUES
    ('store_name', 'Toko Mba Emi'),
    ('store_address', 'Jl. Raya Utama No. 45, Jakarta'),
    ('store_phone', '0812-3456-7890'),
    ('receipt_footer', 'Terima kasih atas kunjungan Anda di Toko Mba Emi!')
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;

    -- 5 Categories
    INSERT INTO public.categories (id, name, slug, description, is_active) VALUES
    ('10000000-0000-0000-0000-000000000001', 'Makanan', 'makanan', 'Produk makanan ringan & berat', true),
    ('10000000-0000-0000-0000-000000000002', 'Minuman', 'minuman', 'Minuman kemasan & segar', true),
    ('10000000-0000-0000-0000-000000000003', 'Kebersihan', 'kebersihan', 'Produk kebersihan rumah tangga', true),
    ('10000000-0000-0000-0000-000000000004', 'Sembako', 'sembako', 'Kebutuhan pokok sehari-hari', true),
    ('10000000-0000-0000-0000-000000000005', 'Alat Tulis', 'alat-tulis', 'Peralatan tulis dan kantor', true)
    ON CONFLICT (id) DO UPDATE SET
    name = EXCLUDED.name,
    slug = EXCLUDED.slug,
    description = EXCLUDED.description;

    -- 3 Suppliers
    INSERT INTO public.suppliers (id, name, code, phone, email, address, contact_person, is_active) VALUES
    ('20000000-0000-0000-0000-000000000001', 'PT Indofood Sukses Makmur', 'SUP-001', '021-5795-8822', 'supplier@indofood.com', 'Jakarta', 'Bpk. Salim', true),
    ('20000000-0000-0000-0000-000000000002', 'PT Wings Surya', 'SUP-002', '031-8431-234', 'supplier@wings.com', 'Surabaya', 'Ibu Rahma', true),
    ('20000000-0000-0000-0000-000000000003', 'CV Aneka Jaya', 'SUP-003', '0274-567890', 'anekajaya@email.com', 'Yogyakarta', 'Bpk. Joko', true)
    ON CONFLICT (id) DO UPDATE SET
    name = EXCLUDED.name,
    code = EXCLUDED.code;

    -- 12 Products with Grosir Tiers
    INSERT INTO public.products (id, category_id, name, sku, harga_beli, harga_jual, grosir_tiers, stok, min_stok, satuan, is_active) VALUES
    ('30000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', 'Indomie Goreng', 'MKN-001', 2500, 3500, '[{"minQty": 40, "harga": 3200}]'::jsonb, 100, 20, 'pcs', true),
    ('30000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', 'Chitato Original 68g', 'MKN-002', 8000, 11000, '[{"minQty": 10, "harga": 10000}]'::jsonb, 50, 10, 'pcs', true),
    ('30000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000001', 'Roti Sari Roti Tawar', 'MKN-003', 12000, 15000, '[]'::jsonb, 30, 5, 'pcs', true),
    ('30000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000002', 'Aqua 600ml', 'MNM-001', 2000, 3000, '[{"minQty": 24, "harga": 2600}]'::jsonb, 200, 50, 'botol', true),
    ('30000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000002', 'Teh Pucuk Harum 350ml', 'MNM-002', 2500, 4000, '[{"minQty": 24, "harga": 3500}]'::jsonb, 80, 20, 'botol', true),
    ('30000000-0000-0000-0000-000000000006', '10000000-0000-0000-0000-000000000002', 'Coca Cola 390ml', 'MNM-003', 4000, 6000, '[{"minQty": 12, "harga": 5200}]'::jsonb, 60, 15, 'botol', true),
    ('30000000-0000-0000-0000-000000000007', '10000000-0000-0000-0000-000000000003', 'Sabun Cuci Sunlight 800ml', 'KBR-001', 10000, 14000, '[]'::jsonb, 40, 10, 'botol', true),
    ('30000000-0000-0000-0000-000000000008', '10000000-0000-0000-0000-000000000003', 'Pewangi So Klin 900ml', 'KBR-002', 12000, 16000, '[]'::jsonb, 35, 8, 'botol', true),
    ('30000000-0000-0000-0000-000000000009', '10000000-0000-0000-0000-000000000004', 'Beras Premium 5kg', 'SMB-001', 55000, 65000, '[]'::jsonb, 25, 5, 'karung', true),
    ('30000000-0000-0000-0000-000000000010', '10000000-0000-0000-0000-000000000004', 'Gula Pasir 1kg', 'SMB-002', 12000, 15000, '[{"minQty": 10, "harga": 14000}]'::jsonb, 40, 10, 'kg', true),
    ('30000000-0000-0000-0000-000000000011', '10000000-0000-0000-0000-000000000004', 'Minyak Goreng Bimoli 2L', 'SMB-003', 28000, 34000, '[{"minQty": 6, "harga": 32000}]'::jsonb, 20, 5, 'botol', true),
    ('30000000-0000-0000-0000-000000000012', '10000000-0000-0000-0000-000000000005', 'Pulpen Standard AE7', 'ATK-001', 2000, 3500, '[{"minQty": 12, "harga": 3000}]'::jsonb, 3, 10, 'pcs', true)
    ON CONFLICT (id) DO UPDATE SET
    name = EXCLUDED.name,
    sku = EXCLUDED.sku,
    harga_beli = EXCLUDED.harga_beli,
    harga_jual = EXCLUDED.harga_jual,
    grosir_tiers = EXCLUDED.grosir_tiers,
    stok = EXCLUDED.stok,
    min_stok = EXCLUDED.min_stok;

    -- Initial Purchase
    INSERT INTO public.purchases (
    id, invoice_number, supplier_id, user_id, tanggal, total, status, keterangan
    ) VALUES (
    '40000000-0000-0000-0000-000000000001',
    'PO-20260901-0001',
    '20000000-0000-0000-0000-000000000001',
    '00000000-0000-0000-0000-000000000001',
    '2026-09-01',
    250000,
    'received',
    'Pembelian rutin Indomie'
    ) ON CONFLICT (id) DO NOTHING;

    INSERT INTO public.purchase_items (
    id, purchase_id, product_id, qty, harga, subtotal
    ) VALUES (
    '50000000-0000-0000-0000-000000000001',
    '40000000-0000-0000-0000-000000000001',
    '30000000-0000-0000-0000-000000000001',
    100,
    2500,
    250000
    ) ON CONFLICT (id) DO NOTHING;

    -- Initial Stock Movement
    INSERT INTO public.stock_movements (
    id, product_id, type, qty, stok_sebelum, stok_sesudah, reference_type, reference_id, keterangan, user_id
    ) VALUES (
    '60000000-0000-0000-0000-000000000001',
    '30000000-0000-0000-0000-000000000001',
    'in',
    100,
    0,
    100,
    'purchase',
    '40000000-0000-0000-0000-000000000001',
    'Pembelian PO-20260901-0001',
    '00000000-0000-0000-0000-000000000001'
    ) ON CONFLICT (id) DO NOTHING;

