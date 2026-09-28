-- ========================================================
-- STOCKKU FULL DATABASE SEEDER (100% KOMPLIT)
-- Diporting lengkap dari Laravel DatabaseSeeder
-- ========================================================

-- Enable pgcrypto for password hashing
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 1. SEED DEFAULT USERS KE AUTH.USERS
-- Password default: password123
INSERT INTO auth.users (
  id,
  instance_id,
  email,
  encrypted_password,
  email_confirmed_at,
  raw_app_meta_data,
  raw_user_meta_data,
  created_at,
  updated_at,
  role,
  aud,
  confirmation_token,
  recovery_token,
  email_change_token_new,
  email_change_token_current,
  email_change,
  phone,
  phone_change,
  phone_change_token,
  reauthentication_token
) VALUES
  (
    '00000000-0000-0000-0000-000000000001',
    '00000000-0000-0000-0000-000000000000',
    'admin@tokombaemi.com',
    crypt('password123', gen_salt('bf')),
    NOW(),
    '{"provider":"email","providers":["email"]}',
    '{"name":"Administrator","role":"admin"}',
    NOW(),
    NOW(),
    'authenticated',
    'authenticated',
    '', '', '', '', '', '', '', '', ''
  ),
  (
    '00000000-0000-0000-0000-000000000002',
    '00000000-0000-0000-0000-000000000000',
    'kasir1@tokombaemi.com',
    crypt('password123', gen_salt('bf')),
    NOW(),
    '{"provider":"email","providers":["email"]}',
    '{"name":"Siti Kasir","role":"kasir"}',
    NOW(),
    NOW(),
    'authenticated',
    'authenticated',
    '', '', '', '', '', '', '', '', ''
  ),
  (
    '00000000-0000-0000-0000-000000000003',
    '00000000-0000-0000-0000-000000000000',
    'kasir2@tokombaemi.com',
    crypt('password123', gen_salt('bf')),
    NOW(),
    '{"provider":"email","providers":["email"]}',
    '{"name":"Budi Kasir","role":"kasir"}',
    NOW(),
    NOW(),
    'authenticated',
    'authenticated',
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

-- Pastikan tabel public.users juga terisi
INSERT INTO public.users (id, name, email, role, is_active) VALUES
  ('00000000-0000-0000-0000-000000000001', 'Administrator', 'admin@tokombaemi.com', 'admin', true),
  ('00000000-0000-0000-0000-000000000002', 'Siti Kasir', 'kasir1@tokombaemi.com', 'kasir', true),
  ('00000000-0000-0000-0000-000000000003', 'Budi Kasir', 'kasir2@tokombaemi.com', 'kasir', true)
ON CONFLICT (id) DO UPDATE SET
  role = EXCLUDED.role,
  name = EXCLUDED.name;

-- 2. SEED SETTINGS
INSERT INTO public.settings (key, value) VALUES
  ('store_name', 'Toko Mba Emi'),
  ('store_address', 'Jl. Raya Utama No. 45, Jakarta'),
  ('store_phone', '0812-3456-7890'),
  ('receipt_footer', 'Terima kasih atas kunjungan Anda di Toko Mba Emi!')
ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;

-- 3. SEED 5 KATEGORI LENGKAP
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

-- 4. SEED 3 SUPPLIER LENGKAP
INSERT INTO public.suppliers (id, name, code, phone, email, address, contact_person, is_active) VALUES
  ('20000000-0000-0000-0000-000000000001', 'PT Indofood Sukses Makmur', 'SUP-001', '021-5795-8822', 'supplier@indofood.com', 'Jakarta', 'Bpk. Salim', true),
  ('20000000-0000-0000-0000-000000000002', 'PT Wings Surya', 'SUP-002', '031-8431-234', 'supplier@wings.com', 'Surabaya', 'Ibu Rahma', true),
  ('20000000-0000-0000-0000-000000000003', 'CV Aneka Jaya', 'SUP-003', '0274-567890', 'anekajaya@email.com', 'Yogyakarta', 'Bpk. Joko', true)
ON CONFLICT (id) DO UPDATE SET
  name = EXCLUDED.name,
  code = EXCLUDED.code;

-- 5. SEED 12 PRODUK LENGKAP DENGAN GROSIR TIERS & STOK
INSERT INTO public.products (id, category_id, name, sku, harga_beli, harga_jual, grosir_tiers, stok, min_stok, satuan, is_active) VALUES
  -- Kategori Makanan
  (
    '30000000-0000-0000-0000-000000000001',
    '10000000-0000-0000-0000-000000000001',
    'Indomie Goreng',
    'MKN-001',
    2500,
    3500,
    '[{"minQty": 40, "harga": 3200}]'::jsonb,
    100,
    20,
    'pcs',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000002',
    '10000000-0000-0000-0000-000000000001',
    'Chitato Original 68g',
    'MKN-002',
    8000,
    11000,
    '[{"minQty": 10, "harga": 10000}]'::jsonb,
    50,
    10,
    'pcs',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000003',
    '10000000-0000-0000-0000-000000000001',
    'Roti Sari Roti Tawar',
    'MKN-003',
    12000,
    15000,
    '[]'::jsonb,
    30,
    5,
    'pcs',
    true
  ),

  -- Kategori Minuman
  (
    '30000000-0000-0000-0000-000000000004',
    '10000000-0000-0000-0000-000000000002',
    'Aqua 600ml',
    'MNM-001',
    2000,
    3000,
    '[{"minQty": 24, "harga": 2600}]'::jsonb,
    200,
    50,
    'botol',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000005',
    '10000000-0000-0000-0000-000000000002',
    'Teh Pucuk Harum 350ml',
    'MNM-002',
    2500,
    4000,
    '[{"minQty": 24, "harga": 3500}]'::jsonb,
    80,
    20,
    'botol',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000006',
    '10000000-0000-0000-0000-000000000002',
    'Coca Cola 390ml',
    'MNM-003',
    4000,
    6000,
    '[{"minQty": 12, "harga": 5200}]'::jsonb,
    60,
    15,
    'botol',
    true
  ),

  -- Kategori Kebersihan
  (
    '30000000-0000-0000-0000-000000000007',
    '10000000-0000-0000-0000-000000000003',
    'Sabun Cuci Sunlight 800ml',
    'KBR-001',
    10000,
    14000,
    '[]'::jsonb,
    40,
    10,
    'botol',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000008',
    '10000000-0000-0000-0000-000000000003',
    'Pewangi So Klin 900ml',
    'KBR-002',
    12000,
    16000,
    '[]'::jsonb,
    35,
    8,
    'botol',
    true
  ),

  -- Kategori Sembako
  (
    '30000000-0000-0000-0000-000000000009',
    '10000000-0000-0000-0000-000000000004',
    'Beras Premium 5kg',
    'SMB-001',
    55000,
    65000,
    '[]'::jsonb,
    25,
    5,
    'karung',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000010',
    '10000000-0000-0000-0000-000000000004',
    'Gula Pasir 1kg',
    'SMB-002',
    12000,
    15000,
    '[{"minQty": 10, "harga": 14000}]'::jsonb,
    40,
    10,
    'kg',
    true
  ),
  (
    '30000000-0000-0000-0000-000000000011',
    '10000000-0000-0000-0000-000000000004',
    'Minyak Goreng Bimoli 2L',
    'SMB-003',
    28000,
    34000,
    '[{"minQty": 6, "harga": 32000}]'::jsonb,
    20,
    5,
    'botol',
    true
  ),

  -- Kategori Alat Tulis
  (
    '30000000-0000-0000-0000-000000000012',
    '10000000-0000-0000-0000-000000000005',
    'Pulpen Standard AE7',
    'ATK-001',
    2000,
    3500,
    '[{"minQty": 12, "harga": 3000}]'::jsonb,
    3, -- Stok 3, min_stok 10 (otomatis memicu peringatan stok menipis di dashboard!)
    10,
    'pcs',
    true
  )
ON CONFLICT (id) DO UPDATE SET
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  harga_beli = EXCLUDED.harga_beli,
  harga_jual = EXCLUDED.harga_jual,
  grosir_tiers = EXCLUDED.grosir_tiers,
  stok = EXCLUDED.stok,
  min_stok = EXCLUDED.min_stok;

-- 6. SEED CONTOH PEMBELIAN RESTOCK (PURCHASE)
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

-- 7. SEED CONTOH MUTASI STOK AWAL
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
