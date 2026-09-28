import React from 'react'
import { createBrowserRouter, Navigate } from 'react-router-dom'
import { AppLayout } from './components/layout/AppLayout'
import { DashboardPage } from './pages/DashboardPage'
import { PosPage } from './pages/pos/PosPage'
import { ProductListPage } from './pages/products/ProductListPage'
import { StockMovementPage } from './pages/stock/StockMovementPage'
import { AttendancePage } from './pages/attendance/AttendancePage'
import { ReportPage } from './pages/reports/ReportPage'
import { SettingsPage } from './pages/settings/SettingsPage'
import { LoginPage } from './pages/auth/LoginPage'
import { useAuthStore } from './stores/auth.store'

const ProtectedRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isAuthenticated, isLoading } = useAuthStore()

  if (isLoading) {
    return (
      <div className="h-screen w-screen flex items-center justify-center bg-slate-950 text-white font-mono text-xs">
        Memuat sesi...
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/',
    element: (
      <ProtectedRoute>
        <AppLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <DashboardPage /> },
      { path: 'pos', element: <PosPage /> },
      { path: 'products', element: <ProductListPage /> },
      { path: 'stock', element: <StockMovementPage /> },
      { path: 'attendance', element: <AttendancePage /> },
      { path: 'reports', element: <ReportPage /> },
      { path: 'settings', element: <SettingsPage /> },
    ],
  },
  {
    path: '*',
    element: <Navigate to="/" replace />,
  },
])
