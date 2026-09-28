import React from 'react'
import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  Layers,
  Clock,
  BarChart3,
  Settings,
} from 'lucide-react'
import { useAuthStore } from '../../stores/auth.store'

interface NavItem {
  name: string
  to: string
  icon: React.ElementType
  roles?: string[]
}

const navItems: NavItem[] = [
  { name: 'Dashboard', to: '/', icon: LayoutDashboard },
  { name: 'Kasir / POS', to: '/pos', icon: ShoppingCart },
  { name: 'Produk', to: '/products', icon: Package },
  { name: 'Mutasi Stok', to: '/stock', icon: Layers },
  { name: 'Absensi', to: '/attendance', icon: Clock },
  { name: 'Laporan', to: '/reports', icon: BarChart3, roles: ['admin', 'manager'] },
  { name: 'Pengaturan', to: '/settings', icon: Settings, roles: ['admin'] },
]

export const Sidebar: React.FC = () => {
  const { role } = useAuthStore()

  const filteredNavItems = navItems.filter((item) => {
    if (!item.roles) return true
    return role ? item.roles.includes(role) : false
  })

  return (
    <aside className="w-64 border-r border-slate-800 bg-slate-900/50 backdrop-blur-md flex flex-col p-4 shrink-0">
      <div className="space-y-1">
        {filteredNavItems.map((item) => {
          const Icon = item.icon
          return (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 ${
                  isActive
                    ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'
                }`
              }
            >
              <Icon className="w-5 h-5 shrink-0" />
              <span>{item.name}</span>
            </NavLink>
          )
        })}
      </div>

      <div className="mt-auto p-3 rounded-2xl bg-gradient-to-b from-slate-800/60 to-slate-800/20 border border-slate-700/40 text-xs">
        <p className="font-semibold text-slate-200">Mode Serverless</p>
        <p className="text-slate-400 mt-0.5">Cloudflare Workers + Edge Supabase</p>
      </div>
    </aside>
  )
}
