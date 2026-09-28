import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  Search,
  Plus,
  Minus,
  Trash2,
  Tag,
  CreditCard,
  AlertCircle,
  Package,
} from 'lucide-react'
import { BarcodeInput } from '../../components/pos/BarcodeInput'
import { PaymentModal } from '../../components/pos/PaymentModal'
import { ReceiptModal } from '../../components/pos/ReceiptModal'
import { useCartStore } from '../../stores/cart.store'
import { useAuthStore } from '../../stores/auth.store'
import { apiClient } from '../../api/client'
import { formatRupiah } from '../../lib/format'
import type { Product } from '@stockku/shared'

export const PosPage: React.FC = () => {
  const { user, attendance } = useAuthStore()
  const {
    items,
    addItem,
    removeItem,
    updateQty,
    updateItemDiskon,
    headerDiskon,
    setHeaderDiskon,
    getSubtotal,
    getGrandTotal,
    clearCart,
  } = useCartStore()

  const [search, setSearch] = useState('')
  const [selectedCategory, setSelectedCategory] = useState<string>('all')
  const [showPayment, setShowPayment] = useState(false)
  const [completedSale, setCompletedSale] = useState<any>(null)

  // Attendance validation: non-admin cannot checkout if not clocked in or already clocked out
  const isAttendanceBlocked =
    user?.role !== 'admin' && (!attendance?.hasClockedIn || !!attendance?.hasClockedOut)

  // Fetch Products query
  const { data: productsData, isLoading } = useQuery({
    queryKey: ['products'],
    queryFn: () => apiClient<{ data: Product[] }>('/api/products?limit=200'),
  })

  const products = productsData?.data || []

  // Filter products by category and search
  const filteredProducts = products.filter((product) => {
    const matchesSearch =
      product.name.toLowerCase().includes(search.toLowerCase()) ||
      product.sku.toLowerCase().includes(search.toLowerCase())
    const matchesCategory =
      selectedCategory === 'all' || product.categoryId === selectedCategory
    return matchesSearch && matchesCategory && product.isActive
  })

  // Barcode scan handler (exact SKU or Barcode)
  const handleBarcodeScan = (code: string) => {
    const matched = products.find(
      (p) => p.sku.toLowerCase() === code.toLowerCase() || p.sku === code
    )
    if (matched) {
      if (matched.stok <= 0) {
        alert(`Stok produk ${matched.name} habis!`)
        return
      }
      addItem(matched)
    } else {
      alert(`Produk dengan barcode/SKU "${code}" tidak ditemukan.`)
    }
  }

  return (
    <div className="flex h-full gap-5 overflow-hidden -m-6 p-6">
      {/* Left: Products & Scanner Catalog */}
      <div className="flex-1 flex flex-col gap-4 overflow-hidden">
        {/* Top: Barcode Input */}
        <BarcodeInput onScan={handleBarcodeScan} disabled={isAttendanceBlocked} />

        {/* Filter & Search Bar */}
        <div className="flex items-center gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama barang atau SKU..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        {/* Product Cards Grid */}
        <div className="flex-1 overflow-y-auto pr-1">
          {isLoading ? (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
              {[...Array(8)].map((_, i) => (
                <div key={i} className="h-32 rounded-2xl bg-slate-900 animate-pulse border border-slate-800" />
              ))}
            </div>
          ) : filteredProducts.length === 0 ? (
            <div className="h-64 flex flex-col items-center justify-center text-slate-500 gap-2">
              <Package className="w-10 h-10 stroke-1" />
              <p className="text-sm font-medium">Tidak ada produk yang cocok</p>
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 pb-4">
              {filteredProducts.map((product) => {
                const inCart = items.find((i) => i.product.id === product.id)
                const isOutOfStock = product.stok <= 0

                return (
                  <button
                    key={product.id}
                    disabled={isOutOfStock || isAttendanceBlocked}
                    onClick={() => addItem(product)}
                    className={`relative p-3.5 rounded-2xl text-left border flex flex-col justify-between transition-all group ${
                      isOutOfStock
                        ? 'bg-slate-900/40 border-slate-800/40 opacity-60 cursor-not-allowed'
                        : 'bg-slate-900/80 hover:bg-slate-800/80 border-slate-800 hover:border-indigo-500/50 hover:shadow-lg hover:shadow-indigo-500/10'
                    }`}
                  >
                    <div>
                      <div className="flex items-center justify-between gap-1 mb-1">
                        <span className="text-[10px] font-mono font-medium text-slate-400 bg-slate-800/80 px-1.5 py-0.5 rounded">
                          {product.sku}
                        </span>
                        <span
                          className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                            product.stok <= product.minStok
                              ? 'bg-amber-500/20 text-amber-300'
                              : 'bg-slate-800 text-slate-300'
                          }`}
                        >
                          Sisa: {product.stok}
                        </span>
                      </div>
                      <h3 className="font-semibold text-white text-xs sm:text-sm line-clamp-2 leading-snug group-hover:text-indigo-300 transition-colors">
                        {product.name}
                      </h3>
                    </div>

                    <div className="mt-3 pt-2 border-t border-slate-800/60 flex items-center justify-between">
                      <span className="font-bold text-sm sm:text-base text-emerald-400 font-mono">
                        {formatRupiah(product.hargaJual)}
                      </span>
                      {inCart && (
                        <span className="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-md">
                          {inCart.qty}
                        </span>
                      )}
                    </div>
                  </button>
                )
              })}
            </div>
          )}
        </div>
      </div>

      {/* Right: Cart Drawer */}
      <div className="w-96 bg-slate-900/90 border border-slate-800 rounded-3xl flex flex-col shadow-2xl overflow-hidden shrink-0">
        {/* Cart Header */}
        <div className="p-4 border-b border-slate-800 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="font-bold text-white text-sm">Keranjang Transaksi</span>
            <span className="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-semibold">
              {items.length} item
            </span>
          </div>
          {items.length > 0 && (
            <button
              onClick={() => clearCart()}
              className="text-xs text-rose-400 hover:text-rose-300 transition-colors"
            >
              Kosongkan
            </button>
          )}
        </div>

        {/* Cart Items List */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {items.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-slate-500 gap-2">
              <Package className="w-10 h-10 stroke-1" />
              <p className="text-xs">Keranjang masih kosong</p>
            </div>
          ) : (
            items.map((item) => (
              <div
                key={item.product.id}
                className="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 flex flex-col gap-2"
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-semibold text-white truncate">{item.product.name}</p>
                    <p className="text-[11px] text-slate-400 font-mono">
                      {formatRupiah(item.harga)}
                    </p>
                  </div>
                  <button
                    onClick={() => removeItem(item.product.id)}
                    className="text-slate-500 hover:text-rose-400 transition-colors p-1"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>

                <div className="flex items-center justify-between pt-1">
                  {/* Qty Controls */}
                  <div className="flex items-center gap-1 bg-slate-900 border border-slate-800 rounded-lg p-0.5">
                    <button
                      onClick={() => updateQty(item.product.id, item.qty - 1)}
                      className="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white rounded"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-8 text-center text-xs font-mono font-bold text-white">
                      {item.qty}
                    </span>
                    <button
                      onClick={() => updateQty(item.product.id, item.qty + 1)}
                      disabled={item.qty >= item.product.stok}
                      className="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-white rounded disabled:opacity-40"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>

                  {/* Subtotal */}
                  <span className="font-bold text-sm font-mono text-emerald-400">
                    {formatRupiah(item.subtotal)}
                  </span>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Cart Summary & Checkout */}
        <div className="p-4 border-t border-slate-800 bg-slate-950/40 space-y-3">
          <div className="space-y-1.5 text-xs">
            <div className="flex justify-between text-slate-400">
              <span>Subtotal:</span>
              <span className="font-mono text-slate-200">{formatRupiah(getSubtotal())}</span>
            </div>

            <div className="flex items-center justify-between gap-2">
              <span className="text-slate-400 flex items-center gap-1">
                <Tag className="w-3 h-3" />
                <span>Diskon Transaksi:</span>
              </span>
              <input
                type="number"
                min={0}
                max={getSubtotal()}
                value={headerDiskon || ''}
                onChange={(e) => setHeaderDiskon(Number(e.target.value))}
                placeholder="0"
                className="w-24 px-2 py-1 bg-slate-900 border border-slate-700 rounded text-right font-mono text-xs text-white"
              />
            </div>

            <div className="pt-2 border-t border-slate-800 flex justify-between items-baseline font-bold text-base">
              <span className="text-white">Grand Total:</span>
              <span className="font-mono text-lg text-emerald-400">
                {formatRupiah(getGrandTotal())}
              </span>
            </div>
          </div>

          {/* Checkout Button */}
          {isAttendanceBlocked ? (
            <div className="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>Checkout dinonaktifkan. Silakan absen masuk terlebih dahulu.</span>
            </div>
          ) : (
            <button
              onClick={() => setShowPayment(true)}
              disabled={items.length === 0}
              className="w-full py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold text-sm transition-all shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2"
            >
              <CreditCard className="w-4 h-4" />
              <span>Bayar Transaksi</span>
            </button>
          )}
        </div>
      </div>

      {/* Payment Modal */}
      {showPayment && (
        <PaymentModal
          onClose={() => setShowPayment(false)}
          onSuccess={(sale) => {
            setShowPayment(false)
            setCompletedSale(sale)
          }}
        />
      )}

      {/* Receipt Print Modal */}
      {completedSale && (
        <ReceiptModal sale={completedSale} onClose={() => setCompletedSale(null)} />
      )}
    </div>
  )
}
