-- Enable RLS on all tables
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

-- Helper function to check role from public.users table
CREATE OR REPLACE FUNCTION public.current_user_role()
RETURNS VARCHAR AS $$
  SELECT role FROM public.users WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Users policies
CREATE POLICY "Users can read own profile or admin/manager read all" ON public.users
  FOR SELECT USING (auth.uid() = id OR public.current_user_role() IN ('admin', 'manager'));

CREATE POLICY "Admin can update users" ON public.users
  FOR UPDATE USING (public.current_user_role() = 'admin');

-- Master data policies (Categories, Products, Suppliers: readable by all authenticated)
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

-- Settings: everyone can read, admin can update
CREATE POLICY "Authenticated users can read settings" ON public.settings
  FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admin can modify settings" ON public.settings
  FOR ALL USING (public.current_user_role() = 'admin');
