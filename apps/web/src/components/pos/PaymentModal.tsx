import React, { useState } from 'react'
import { X, CreditCard, Banknote, Check } from 'lucide-react'
import { formatRupiah } from '../../lib/format'
import { useCartStore } from '../../stores/cart.store'
import { apiClient } from '../../api/client'

interface PaymentModalProps {
  onClose: () => void
  onSuccess: (saleData: any) => void
}

export const PaymentModal: React.FC<PaymentModalProps> = ({ onClose, onSuccess }) => {
  const { items, headerDiskon, getGrandTotal, clearCart } = useCartStore()
  const grandTotal = getGrandTotal()

  const [bayar, setBayar] = useState<number>(grandTotal)
  const [paymentMethod, setPaymentMethod] = useState<'cash' | 'qris'>('cash')
  const [catatan, setCatatan] = useState<string>('')
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)

  const kembalian = Math.max(0, bayar - grandTotal)
  const isSufficient = bayar >= grandTotal

  const quickAmounts = [
    { label: 'Uang Pas', value: grandTotal },
    { label: 'Rp 20.000', value: 20000 },
    { label: 'Rp 50.000', value: 50000 },
    { label: 'Rp 100.000', value: 100000 },
    { label: 'Rp 200.000', value: 200000 },
  ].filter((q) => q.value >= grandTotal || q.label === 'Uang Pas')

  const handleCheckout = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!isSufficient || isSubmitting) return

    setIsSubmitting(true)
    try {
      const payload = {
        items: items.map((i) => ({
          productId: i.product.id,
          qty: i.qty,
          harga: i.harga,
          diskon: i.diskon,
        })),
        diskon: headerDiskon,
        bayar,
        paymentMethod,
        catatan,
      }

      const res = await apiClient<{ data: any }>('/api/sales', {
        method: 'POST',
        body: JSON.stringify(payload),
      })

      clearCart()
      onSuccess(res.data)
    } catch (err) {
      console.error('Checkout error:', err)
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
      <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
        {/* Header */}
        <div className="p-5 border-b border-slate-800 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-white">Pembayaran Kasir</h2>
            <p className="text-xs text-slate-400">Pilih metode pembayaran dan masukkan nominal bayar</p>
          </div>
          <button onClick={onClose} className="p-2 text-slate-400 hover:text-white rounded-xl">
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleCheckout} className="p-6 space-y-5">
          {/* Total Tagihan */}
          <div className="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 text-center">
            <p className="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Total Tagihan</p>
            <p className="text-3xl font-extrabold text-white mt-1">{formatRupiah(grandTotal)}</p>
          </div>

          {/* Payment Method Selector */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-2">Metode Pembayaran</label>
            <div className="grid grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setPaymentMethod('cash')}
                className={`flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-semibold transition-all ${
                  paymentMethod === 'cash'
                    ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                    : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700/60'
                }`}
              >
                <Banknote className="w-4 h-4" />
                <span>Tunai (Cash)</span>
              </button>
              <button
                type="button"
                onClick={() => {
                  setPaymentMethod('qris')
                  setBayar(grandTotal) // QRIS is always exact
                }}
                className={`flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-semibold transition-all ${
                  paymentMethod === 'qris'
                    ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-600/25'
                    : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700/60'
                }`}
              >
                <CreditCard className="w-4 h-4" />
                <span>QRIS / Non-Tunai</span>
              </button>
            </div>
          </div>

          {/* Nominal Bayar Input (if cash) */}
          {paymentMethod === 'cash' && (
            <div className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Nominal Diterima
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400">
                    Rp
                  </span>
                  <input
                    type="number"
                    min={0}
                    value={bayar || ''}
                    onChange={(e) => setBayar(Number(e.target.value))}
                    className="w-full pl-11 pr-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono text-lg font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="0"
                  />
                </div>
              </div>

              {/* Quick Cash Buttons */}
              <div className="flex flex-wrap gap-2">
                {quickAmounts.map((q, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setBayar(q.value)}
                    className="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-200 border border-slate-700 transition-colors"
                  >
                    {q.label}
                  </button>
                ))}
              </div>

              {/* Kembalian Display */}
              <div className="p-3 rounded-xl bg-slate-800/60 border border-slate-700/60 flex items-center justify-between text-sm">
                <span className="text-slate-400">Kembalian:</span>
                <span
                  className={`font-mono font-bold text-base ${
                    isSufficient ? 'text-emerald-400' : 'text-rose-400'
                  }`}
                >
                  {isSufficient ? formatRupiah(kembalian) : 'Uang Kurang!'}
                </span>
              </div>
            </div>
          )}

          {/* Catatan Transaksi */}
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Catatan (Opsional)
            </label>
            <input
              type="text"
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              placeholder="Contoh: Meja 4 / Bungkus"
              className="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          {/* Submit Action */}
          <button
            type="submit"
            disabled={!isSufficient || isSubmitting}
            className="w-full py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold text-base transition-all shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2"
          >
            <Check className="w-5 h-5" />
            <span>{isSubmitting ? 'Memproses Transaksi...' : 'Konfirmasi & Bayar'}</span>
          </button>
        </form>
      </div>
    </div>
  )
}
