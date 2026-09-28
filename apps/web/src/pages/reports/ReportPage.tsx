import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { BarChart3, TrendingUp, DollarSign, Calendar, Package } from 'lucide-react'
import { apiClient } from '../../api/client'
import { formatRupiah } from '../../lib/format'

export const ReportPage: React.FC = () => {
  const [startDate, setStartDate] = useState(
    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toLocaleDateString('en-CA')
  )
  const [endDate, setEndDate] = useState(new Date().toLocaleDateString('en-CA'))

  // Sales Summary
  const { data: salesData, isLoading: isSalesLoading } = useQuery({
    queryKey: ['reportSales', startDate, endDate],
    queryFn: () => apiClient<{ data: any }>(`/api/reports/sales?startDate=${startDate}&endDate=${endDate}`),
  })

  // Profit Loss
  const { data: plData, isLoading: isPlLoading } = useQuery({
    queryKey: ['reportProfitLoss', startDate, endDate],
    queryFn: () => apiClient<{ data: any }>(`/api/reports/profit-loss?startDate=${startDate}&endDate=${endDate}`),
  })

  // Top Products
  const { data: topData, isLoading: isTopLoading } = useQuery({
    queryKey: ['reportTopProducts'],
    queryFn: () => apiClient<{ data: any[] }>('/api/reports/top-products?limit=10'),
  })

  const sales = salesData?.data || { totalTransaksi: 0, totalSubtotal: 0, totalDiskon: 0, totalGrandTotal: 0 }
  const pl = plData?.data || { revenue: 0, cogs: 0, grossProfit: 0, marginPercentage: 0 }
  const topProducts = topData?.data || []

  return (
    <div className="space-y-6">
      {/* Header and Filter */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-extrabold text-white tracking-tight">Laporan Keuangan & Penjualan</h1>
          <p className="text-xs text-slate-400">Analisis omset, estimasi laba kotor, dan produk terlaris.</p>
        </div>

        {/* Date Filter */}
        <div className="flex items-center gap-2 bg-slate-900 border border-slate-800 p-1.5 rounded-2xl text-xs">
          <Calendar className="w-4 h-4 text-indigo-400 ml-2" />
          <input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="bg-transparent text-white focus:outline-none px-2 py-1 font-mono text-xs"
          />
          <span className="text-slate-500">s/d</span>
          <input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="bg-transparent text-white focus:outline-none px-2 py-1 font-mono text-xs"
          />
        </div>
      </div>

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm">
          <p className="text-xs font-semibold text-slate-400">Total Pendapatan (Omset)</p>
          <p className="text-2xl font-extrabold text-emerald-400 font-mono mt-1">
            {formatRupiah(pl.revenue)}
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm">
          <p className="text-xs font-semibold text-slate-400">Harga Pokok Penjualan (HPP)</p>
          <p className="text-2xl font-extrabold text-rose-400 font-mono mt-1">
            {formatRupiah(pl.cogs)}
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm">
          <p className="text-xs font-semibold text-slate-400">Estimasi Laba Kotor</p>
          <p className="text-2xl font-extrabold text-indigo-400 font-mono mt-1">
            {formatRupiah(pl.grossProfit)}
          </p>
        </div>

        <div className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm">
          <p className="text-xs font-semibold text-slate-400">Margin Keuntungan</p>
          <p className="text-2xl font-extrabold text-purple-400 font-mono mt-1">
            {pl.marginPercentage ? pl.marginPercentage.toFixed(1) : 0}%
          </p>
        </div>
      </div>

      {/* Top Products Table */}
      <div className="rounded-3xl bg-slate-900/60 border border-slate-800 shadow-md overflow-hidden">
        <div className="p-4 border-b border-slate-800 flex items-center gap-2">
          <TrendingUp className="w-4 h-4 text-indigo-400" />
          <h2 className="font-bold text-white text-sm">Produk Paling Banyak Terjual</h2>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
              <tr>
                <th className="py-3 px-4">Peringkat</th>
                <th className="py-3 px-4">SKU</th>
                <th className="py-3 px-4">Nama Produk</th>
                <th className="py-3 px-4">Jumlah Terjual</th>
                <th className="py-3 px-4">Total Penjualan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 font-mono">
              {isTopLoading ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-slate-500 font-sans">
                    Memuat data produk terlaris...
                  </td>
                </tr>
              ) : topProducts.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-slate-500 font-sans">
                    Belum ada data penjualan tercatat.
                  </td>
                </tr>
              ) : (
                topProducts.map((p, idx) => (
                  <tr key={p.productId} className="hover:bg-slate-800/30 transition-colors">
                    <td className="py-3.5 px-4 font-bold text-slate-400">#{idx + 1}</td>
                    <td className="py-3.5 px-4 text-slate-300">{p.sku}</td>
                    <td className="py-3.5 px-4 font-sans font-semibold text-white">{p.productName}</td>
                    <td className="py-3.5 px-4 font-bold text-indigo-400">{p.totalSold}</td>
                    <td className="py-3.5 px-4 text-emerald-400">{formatRupiah(p.totalSales)}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
