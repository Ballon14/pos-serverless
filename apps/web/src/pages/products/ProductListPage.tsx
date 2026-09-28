import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Search, Edit2, Package, Tag } from 'lucide-react'
import { apiClient } from '../../api/client'
import { formatRupiah } from '../../lib/format'
import { useAuthStore } from '../../stores/auth.store'
import { useToastStore } from '../../stores/toast.store'
import type { Product, Category } from '@stockku/shared'

export const ProductListPage: React.FC = () => {
  const queryClient = useQueryClient()
  const { user } = useAuthStore()
  const { addToast } = useToastStore()

  const [search, setSearch] = useState('')
  const [editingProduct, setEditingProduct] = useState<Product | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)

  // Fetch Products
  const { data: productsData, isLoading } = useQuery({
    queryKey: ['productsList'],
    queryFn: () => apiClient<{ data: Product[] }>('/api/products?limit=200'),
  })

  // Fetch Categories for modal dropdown
  const { data: categoriesData } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      // Mock or fetch categories if route exists
      return {
        data: [
          { id: '11111111-1111-1111-1111-111111111111', name: 'Makanan & Minuman' },
          { id: '22222222-2222-2222-2222-222222222222', name: 'Kebutuhan Rumah' },
          { id: '33333333-3333-3333-3333-333333333333', name: 'Alat Tulis Kantor' },
        ],
      }
    },
  })

  // Form state
  const [formData, setFormData] = useState({
    name: '',
    sku: '',
    categoryId: '11111111-1111-1111-1111-111111111111',
    hargaBeli: 0,
    hargaJual: 0,
    stok: 0,
    minStok: 5,
    satuan: 'pcs',
  })

  const openAddModal = () => {
    setEditingProduct(null)
    setFormData({
      name: '',
      sku: '',
      categoryId: '11111111-1111-1111-1111-111111111111',
      hargaBeli: 0,
      hargaJual: 0,
      stok: 0,
      minStok: 5,
      satuan: 'pcs',
    })
    setIsModalOpen(true)
  }

  const openEditModal = (p: Product) => {
    setEditingProduct(p)
    setFormData({
      name: p.name,
      sku: p.sku,
      categoryId: p.categoryId,
      hargaBeli: Number(p.hargaBeli),
      hargaJual: Number(p.hargaJual),
      stok: p.stok,
      minStok: p.minStok,
      satuan: p.satuan,
    })
    setIsModalOpen(true)
  }

  // Save mutation
  const saveMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      if (editingProduct) {
        return apiClient(`/api/products/${editingProduct.id}`, {
          method: 'PUT',
          body: JSON.stringify(data),
        })
      } else {
        return apiClient('/api/products', {
          method: 'POST',
          body: JSON.stringify(data),
        })
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['productsList'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
      setIsModalOpen(false)
      addToast({
        title: 'Berhasil',
        message: editingProduct ? 'Produk berhasil diperbarui' : 'Produk baru berhasil ditambahkan',
        type: 'success',
      })
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    saveMutation.mutate(formData)
  }

  const products = productsData?.data || []
  const filtered = products.filter(
    (p) =>
      p.name.toLowerCase().includes(search.toLowerCase()) ||
      p.sku.toLowerCase().includes(search.toLowerCase())
  )

  const canEdit = user?.role === 'admin' || user?.role === 'manager'

  return (
    <div className="space-y-5">
      {/* Header and Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-extrabold text-white tracking-tight">Katalog Produk</h1>
          <p className="text-xs text-slate-400">Kelola informasi produk, SKU, dan harga jual/beli.</p>
        </div>

        {canEdit && (
          <button
            onClick={openAddModal}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/25 transition-all self-start sm:self-auto"
          >
            <Plus className="w-4 h-4" />
            <span>Tambah Produk</span>
          </button>
        )}
      </div>

      {/* Search Input */}
      <div className="relative max-w-md">
        <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Cari berdasarkan nama atau SKU..."
          className="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      {/* Products Table */}
      <div className="rounded-3xl bg-slate-900/60 border border-slate-800 shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
              <tr>
                <th className="py-3 px-4">SKU</th>
                <th className="py-3 px-4">Nama Produk</th>
                <th className="py-3 px-4">Harga Beli</th>
                <th className="py-3 px-4">Harga Jual</th>
                <th className="py-3 px-4">Stok</th>
                <th className="py-3 px-4">Satuan</th>
                {canEdit && <th className="py-3 px-4 text-center">Aksi</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60">
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-500">
                    Memuat katalog produk...
                  </td>
                </tr>
              ) : filtered.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-500">
                    Tidak ada produk ditemukan.
                  </td>
                </tr>
              ) : (
                filtered.map((prod) => (
                  <tr key={prod.id} className="hover:bg-slate-800/30 transition-colors">
                    <td className="py-3.5 px-4 font-mono font-medium text-slate-300">{prod.sku}</td>
                    <td className="py-3.5 px-4 font-semibold text-white">{prod.name}</td>
                    <td className="py-3.5 px-4 font-mono text-slate-400">
                      {formatRupiah(prod.hargaBeli)}
                    </td>
                    <td className="py-3.5 px-4 font-mono font-bold text-emerald-400">
                      {formatRupiah(prod.hargaJual)}
                    </td>
                    <td className="py-3.5 px-4">
                      <span
                        className={`px-2 py-0.5 rounded-full font-bold font-mono ${
                          prod.stok <= prod.minStok
                            ? 'bg-rose-500/20 text-rose-300'
                            : 'bg-slate-800 text-slate-300'
                        }`}
                      >
                        {prod.stok}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-slate-400">{prod.satuan}</td>
                    {canEdit && (
                      <td className="py-3.5 px-4 text-center">
                        <button
                          onClick={() => openEditModal(prod)}
                          className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-800 transition-colors"
                        >
                          <Edit2 className="w-3.5 h-3.5" />
                        </button>
                      </td>
                    )}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Add/Edit */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
              <h2 className="font-bold text-white text-base">
                {editingProduct ? 'Edit Produk' : 'Tambah Produk Baru'}
              </h2>
              <button
                onClick={() => setIsModalOpen(false)}
                className="text-slate-400 hover:text-white"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4 text-xs">
              <div>
                <label className="block font-semibold text-slate-300 mb-1">Nama Produk</label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">SKU / Barcode</label>
                  <input
                    type="text"
                    required
                    value={formData.sku}
                    onChange={(e) => setFormData({ ...formData, sku: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Satuan</label>
                  <input
                    type="text"
                    value={formData.satuan}
                    onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Harga Beli</label>
                  <input
                    type="number"
                    min={0}
                    value={formData.hargaBeli}
                    onChange={(e) => setFormData({ ...formData, hargaBeli: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Harga Jual</label>
                  <input
                    type="number"
                    min={0}
                    value={formData.hargaJual}
                    onChange={(e) => setFormData({ ...formData, hargaJual: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Stok Awal</label>
                  <input
                    type="number"
                    min={0}
                    disabled={!!editingProduct}
                    value={formData.stok}
                    onChange={(e) => setFormData({ ...formData, stok: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs disabled:opacity-50"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Min. Stok Peringatan</label>
                  <input
                    type="number"
                    min={0}
                    value={formData.minStok}
                    onChange={(e) => setFormData({ ...formData, minStok: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-xs"
                  />
                </div>
              </div>

              <div className="pt-3 flex gap-2">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="flex-1 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold hover:bg-slate-700 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={saveMutation.isPending}
                  className="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-600/25"
                >
                  {saveMutation.isPending ? 'Menyimpan...' : 'Simpan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
