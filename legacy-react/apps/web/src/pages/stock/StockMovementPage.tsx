import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Layers, Plus, ArrowUpRight, ArrowDownLeft, RefreshCw, SlidersHorizontal } from 'lucide-react'
import { apiClient } from '../../api/client'
import { formatDateTime } from '../../lib/format'
import { useAuthStore } from '../../stores/auth.store'
import { useToastStore } from '../../stores/toast.store'
import type { Product } from '@stockku/shared'

export const StockMovementPage: React.FC = () => {
  const queryClient = useQueryClient()
  const { user } = useAuthStore()
  const { addToast } = useToastStore()

  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false)
  const [selectedProductId, setSelectedProductId] = useState('')
  const [adjustType, setAdjustType] = useState<'in' | 'out' | 'adjustment'>('in')
  const [adjustQty, setAdjustQty] = useState(1)
  const [adjustNote, setAdjustNote] = useState('')

  // Fetch movements
  const { data: movementsData, isLoading } = useQuery({
    queryKey: ['stockMovements'],
    queryFn: () => apiClient<{ data: any[] }>('/api/stock/movements?limit=100'),
  })

  // Fetch products for adjustment dropdown
  const { data: productsData } = useQuery({
    queryKey: ['productsList'],
    queryFn: () => apiClient<{ data: Product[] }>('/api/products?limit=200'),
  })

  const products = productsData?.data || []
  const movements = movementsData?.data || []

  // Mutation for adjustment
  const adjustMutation = useMutation({
    mutationFn: async () => {
      return apiClient('/api/stock/adjustment', {
        method: 'POST',
        body: JSON.stringify({
          productId: selectedProductId,
          type: adjustType,
          qty: adjustQty,
          keterangan: adjustNote || 'Penyesuaian stok manual',
        }),
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stockMovements'] })
      queryClient.invalidateQueries({ queryKey: ['productsList'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
      setIsAdjustModalOpen(false)
      addToast({
        title: 'Berhasil',
        message: 'Mutasi stok berhasil dicatat',
        type: 'success',
      })
    },
  })

  const handleAdjustSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedProductId || adjustQty <= 0) return
    adjustMutation.mutate()
  }

  const canAdjust = user?.role === 'admin' || user?.role === 'manager'

  const getTypeBadge = (type: string) => {
    switch (type) {
      case 'in':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px]">
            <ArrowDownLeft className="w-3 h-3" />
            <span>Masuk</span>
          </span>
        )
      case 'out':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 font-bold text-[10px]">
            <ArrowUpRight className="w-3 h-3" />
            <span>Keluar</span>
          </span>
        )
      case 'return':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-bold text-[10px]">
            <RefreshCw className="w-3 h-3" />
            <span>Retur</span>
          </span>
        )
      default:
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold text-[10px]">
            <SlidersHorizontal className="w-3 h-3" />
            <span>Penyesuaian</span>
          </span>
        )
    }
  }

  return (
    <div className="space-y-5">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-extrabold text-white tracking-tight">Riwayat Mutasi Stok</h1>
          <p className="text-xs text-slate-400">
            Log pergerakan barang masuk, keluar dari kasir, retur, dan penyesuaian.
          </p>
        </div>

        {canAdjust && (
          <button
            onClick={() => setIsAdjustModalOpen(true)}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/25 transition-all self-start sm:self-auto"
          >
            <Plus className="w-4 h-4" />
            <span>Catat Mutasi / Koreksi</span>
          </button>
        )}
      </div>

      {/* Movements Table */}
      <div className="rounded-3xl bg-slate-900/60 border border-slate-800 shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
              <tr>
                <th className="py-3 px-4">Waktu</th>
                <th className="py-3 px-4">SKU</th>
                <th className="py-3 px-4">Nama Produk</th>
                <th className="py-3 px-4">Tipe Mutasi</th>
                <th className="py-3 px-4">Jumlah</th>
                <th className="py-3 px-4">Stok Sebelum</th>
                <th className="py-3 px-4">Stok Sesudah</th>
                <th className="py-3 px-4">Keterangan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 font-mono">
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-slate-500 font-sans">
                    Memuat log mutasi stok...
                  </td>
                </tr>
              ) : movements.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-slate-500 font-sans">
                    Belum ada riwayat mutasi stok.
                  </td>
                </tr>
              ) : (
                movements.map((m) => (
                  <tr key={m.id} className="hover:bg-slate-800/30 transition-colors">
                    <td className="py-3 px-4 text-slate-400">{formatDateTime(m.createdAt)}</td>
                    <td className="py-3 px-4 font-medium text-slate-300">{m.sku}</td>
                    <td className="py-3 px-4 font-sans font-semibold text-white">{m.productName}</td>
                    <td className="py-3 px-4">{getTypeBadge(m.type)}</td>
                    <td className="py-3 px-4 font-bold text-white">
                      {m.type === 'out' ? `-${m.qty}` : `+${m.qty}`}
                    </td>
                    <td className="py-3 px-4 text-slate-400">{m.stokSebelum}</td>
                    <td className="py-3 px-4 font-bold text-emerald-400">{m.stokSesudah}</td>
                    <td className="py-3 px-4 font-sans text-slate-300">{m.keterangan || '-'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Manual Adjust Modal */}
      {isAdjustModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
              <h2 className="font-bold text-white text-base">Koreksi / Mutasi Stok Manual</h2>
              <button
                onClick={() => setIsAdjustModalOpen(false)}
                className="text-slate-400 hover:text-white"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAdjustSubmit} className="p-6 space-y-4 text-xs">
              <div>
                <label className="block font-semibold text-slate-300 mb-1">Pilih Produk</label>
                <select
                  required
                  value={selectedProductId}
                  onChange={(e) => setSelectedProductId(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                >
                  <option value="">-- Pilih Produk --</option>
                  {products.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} (Stok: {p.stok})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-300 mb-1">Tipe Mutasi</label>
                <select
                  value={adjustType}
                  onChange={(e) => setAdjustType(e.target.value as any)}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                >
                  <option value="in">Barang Masuk (+)</option>
                  <option value="out">Barang Keluar / Rusak (-)</option>
                  <option value="adjustment">Penyesuaian Opname</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-300 mb-1">Jumlah (Qty)</label>
                <input
                  type="number"
                  min={1}
                  required
                  value={adjustQty}
                  onChange={(e) => setAdjustQty(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-300 mb-1">Catatan / Alasan</label>
                <textarea
                  rows={2}
                  value={adjustNote}
                  onChange={(e) => setAdjustNote(e.target.value)}
                  placeholder="Misal: Rusak kemasan / Stock Opname bulanan"
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                />
              </div>

              <div className="pt-2 flex gap-2">
                <button
                  type="button"
                  onClick={() => setIsAdjustModalOpen(false)}
                  className="flex-1 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold hover:bg-slate-700 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={adjustMutation.isPending || !selectedProductId}
                  className="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-600/25"
                >
                  {adjustMutation.isPending ? 'Menyimpan...' : 'Simpan Mutasi'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
