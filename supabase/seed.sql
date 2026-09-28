-- Seed initial default settings
INSERT INTO public.settings (key, value) VALUES
  ('store_name', 'StockKu POS'),
  ('store_address', 'Jl. Contoh Alamat No. 123, Jakarta'),
  ('store_phone', '081234567890'),
  ('receipt_footer', 'Terima kasih atas kunjungan Anda!')
ON CONFLICT (key) DO NOTHING;

-- Seed Sample Categories
INSERT INTO public.categories (id, name, slug, description, is_active) VALUES
  ('11111111-1111-1111-1111-111111111111', 'Makanan & Minuman', 'makanan-minuman', 'Produk konsumsi harian', true),
  ('22222222-2222-2222-2222-222222222222', 'Kebutuhan Rumah', 'kebutuhan-rumah', 'Peralatan dan kebutuhan rumah tangga', true),
  ('33333333-3333-3333-3333-333333333333', 'Alat Tulis Kantor', 'atk', 'Perlengkapan sekolah dan kantor', true)
ON CONFLICT (id) DO NOTHING;

-- Seed Sample Suppliers
INSERT INTO public.suppliers (id, name, code, phone, email, address, contact_person, is_active) VALUES
  ('44444444-4444-4444-4444-444444444444', 'PT Sumber Pangan Sejahtera', 'SUP-001', '0215551234', 'order@sumberpangan.com', 'Kawasan Industri Pulogadung', 'Budi Santoso', true),
  ('55555555-5555-5555-5555-555555555555', 'CV Maju Jaya Abadi', 'SUP-002', '0215555678', 'sales@majujaya.com', 'Kawasan Pergudangan Pluit', 'Dewi Lestari', true)
ON CONFLICT (id) DO NOTHING;

-- Seed Sample Products
INSERT INTO public.products (id, category_id, name, sku, harga_beli, harga_jual, grosir_tiers, stok, min_stok, satuan, is_active) VALUES
  ('66666666-6666-6666-6666-666666666666', '11111111-1111-1111-1111-111111111111', 'Kopi Susu Gula Aren 250ml', 'KOP-001', 8000, 15000, '[{"minQty": 10, "harga": 13000}]'::jsonb, 50, 5, 'botol', true),
  ('77777777-7777-7777-7777-777777777777', '11111111-1111-1111-1111-111111111111', 'Roti Coklat Keju', 'ROT-001', 5000, 9000, '[{"minQty": 5, "harga": 8000}]'::jsonb, 30, 5, 'pcs', true),
  ('88888888-8888-8888-8888-888888888888', '22222222-2222-2222-2222-222222222222', 'Sabun Cuci Piring 750ml', 'SBN-001', 10000, 14500, '[]'::jsonb, 40, 10, 'pouch', true),
  ('99999999-9999-9999-9999-999999999999', '33333333-3333-3333-3333-333333333333', 'Buku Tulis 58 Lembar', 'BKU-001', 3000, 5000, '[{"minQty": 10, "harga": 4500}]'::jsonb, 100, 20, 'buku', true)
ON CONFLICT (id) DO NOTHING;
