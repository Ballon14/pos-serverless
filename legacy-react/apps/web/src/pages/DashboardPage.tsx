import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  TrendingUp,
  ShoppingCart,
  AlertTriangle,
  Clock,
  ArrowRight,
  Package,
} from 'lucide-react'
import { apiClient } from '../api/client'
import { formatRupiah } from '../lib/format'
import { useAuthStore } from '../stores/auth.store'
import type { Product } from '@stockku/shared'

export const DashboardPage: React.FC = () => {
  const { user, attendance } = useAuthStore()

  const todayStr = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })

  // Fetch sales summary for today
  const { data: salesReport } = useQuery({
    queryKey: ['salesSummary', todayStr],
    queryFn: () => apiClient<{ data: any }>(`/api/reports/sales?startDate=${todayStr}&endDate=${todayStr}`),
  })

  // Fetch low stock products
  const { data: lowStockProducts } = useQuery({
    queryKey: ['lowStockProducts'],
    queryFn: () => apiClient<{ data: Product[] }>('/api/products?isLowStock=true&limit=5'),
  })

  const summary = salesReport?.data || { totalTransaksi: 0, totalGrandTotal: 0 }
  const lowStock = lowStockProducts?.data || []

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="p-6 rounded-3xl bg-gradient-to-r from-indigo-900/60 via-slate-900/80 to-purple-900/40 border border-indigo-500/20 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
            Selamat Datang, {user?.name || 'Kasir'}!
          </h1>
          <p className="text-xs sm:text-sm text-slate-300 mt-1">
            Ringkasan operasional toko dan transaksi POS hari ini.
          </p>
        </div>
        <Link
          to="/pos"
          className="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition-all self-start sm:self-auto"
        >
          <ShoppingCart className="w-4 h-4" />
          <span>Buka Kasir POS</span>
        </Link>
      </div>

      {/* Metrics Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-slate-400">Omset Hari Ini</p>
            <p className="text-2xl font-extrabold text-emerald-400 font-mono mt-1">
              {formatRupiah(summary.totalGrandTotal)}
            </p>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <TrendingUp className="w-6 h-6" />
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-slate-400">Transaksi Hari Ini</p>
            <p className="text-2xl font-extrabold text-white font-mono mt-1">
              {summary.totalTransaksi} <span className="text-xs text-slate-400 font-sans">struk</span>
            </p>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
            <ShoppingCart className="w-6 h-6" />
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-slate-400">Produk Menipis</p>
            <p className="text-2xl font-extrabold text-amber-400 font-mono mt-1">
              {lowStock.length} <span className="text-xs text-slate-400 font-sans">item</span>
            </p>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <AlertTriangle className="w-6 h-6" />
          </div>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-slate-400">Status Absensi</p>
            <p className="text-base font-bold text-white mt-1 capitalize">
              {attendance?.hasClockedIn
                ? attendance.hasClockedOut
                  ? 'Sudah Pulang'
                  : 'Aktif Masuk'
                : 'Belum Absen'}
            </p>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
            <Clock className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Low Stock Alerts Table */}
      <div className="p-5 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-md">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-5 h-5 text-amber-400" />
            <h2 className="font-bold text-white text-base">Peringatan Stok Minimum</h2>
          </div>
          <Link
            to="/products"
            className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1"
          >
            <span>Semua Produk</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {lowStock.length === 0 ? (
          <div className="py-8 text-center text-slate-500 text-xs">
            Tidak ada produk yang di bawah batas minimum stok.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                <tr>
                  <th className="py-3 px-4">SKU</th>
                  <th className="py-3 px-4">Nama Produk</th>
                  <th className="py-3 px-4">Sisa Stok</th>
                  <th className="py-3 px-4">Min. Stok</th>
                  <th className="py-3 px-4">Harga Jual</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {lowStock.map((prod) => (
                  <tr key={prod.id} className="hover:bg-slate-800/30 transition-colors">
                    <td className="py-3 px-4 font-mono font-medium text-slate-300">{prod.sku}</td>
                    <td className="py-3 px-4 font-semibold text-white">{prod.name}</td>
                    <td className="py-3 px-4">
                      <span className="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 font-bold font-mono">
                        {prod.stok} {prod.satuan}
                      </span>
                    </td>
                    <td className="py-3 px-4 font-mono text-slate-400">{prod.minStok}</td>
                    <td className="py-3 px-4 font-mono font-medium text-emerald-400">
                      {formatRupiah(prod.hargaJual)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
