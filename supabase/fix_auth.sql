-- ========================================================
-- STOCKKU - PERBAIKAN SUPABASE AUTH (Database error querying schema)
-- Jalankan skrip ini langsung di Supabase Dashboard -> SQL Editor
-- URL: https://supabase.com/dashboard/project/bnkndcxmiyhcmapvusbx/sql/new
-- ========================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. Perbaiki kolom-kolom token di auth.users yang bernilai NULL
-- Supabase GoTrue Go engine memerlukan empty string (''), bukan NULL.
-- Jika bernilai NULL, driver Go gagal melakukan scan struct dan memunculkan error:
-- "Database error querying schema".
UPDATE auth.users
SET 
    confirmation_token = COALESCE(confirmation_token, ''),
    recovery_token = COALESCE(recovery_token, ''),
    email_change_token_new = COALESCE(email_change_token_new, ''),
    email_change_token_current = COALESCE(email_change_token_current, ''),
    email_change = COALESCE(email_change, ''),
    reauthentication_token = COALESCE(reauthentication_token, ''),
    phone = COALESCE(phone, ''),
    phone_change = COALESCE(phone_change, ''),
    phone_change_token = COALESCE(phone_change_token, ''),
    encrypted_password = crypt('password123', gen_salt('bf'))
WHERE confirmation_token IS NULL 
   OR recovery_token IS NULL 
   OR email_change IS NULL
   OR encrypted_password IS NULL;

-- 2. Pastikan tabel auth.identities terisi
-- Supabase GoTrue mewajibkan adanya data identity di auth.identities untuk login email/password
INSERT INTO auth.identities (
    id,
    user_id,
    identity_data,
    provider,
    provider_id,
    last_sign_in_at,
    created_at,
    updated_at
)
SELECT 
    gen_random_uuid(),
    id,
    jsonb_build_object('sub', id::text, 'email', email, 'email_verified', true),
    'email',
    id::text,
    NOW(),
    NOW(),
    NOW()
FROM auth.users
WHERE NOT EXISTS (
    SELECT 1 FROM auth.identities WHERE auth.identities.user_id = auth.users.id
);

-- 3. Perbaiki fungsi trigger auth agar aman dan tidak membatalkan transaksi login
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
    -- Mencegah transaksi GoTrue abort jika terjadi konflik profile
    RETURN new;
END;
$$;

-- Pasang trigger HANYA pada INSERT (bukan UPDATE)
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- 4. Pastikan public.users profil untuk admin dan kasir terdaftar dan aktif
INSERT INTO public.users (id, name, email, role, is_active)
VALUES 
    ('00000000-0000-0000-0000-000000000001', 'Administrator', 'admin@tokombaemi.com', 'admin', true),
    ('00000000-0000-0000-0000-000000000002', 'Siti Kasir', 'kasir1@tokombaemi.com', 'kasir', true),
    ('00000000-0000-0000-0000-000000000003', 'Budi Kasir', 'kasir2@tokombaemi.com', 'kasir', true)
ON CONFLICT (id) DO UPDATE
SET 
    name = EXCLUDED.name,
    email = EXCLUDED.email,
    role = EXCLUDED.role,
    is_active = true;
