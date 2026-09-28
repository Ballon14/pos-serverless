import React, { useEffect, useState } from 'react'
import { Printer, CheckCircle, X } from 'lucide-react'
import { formatRupiah, formatDateTime } from '../../lib/format'

interface ReceiptModalProps {
  sale: any
  onClose: () => void
}

export const ReceiptModal: React.FC<ReceiptModalProps> = ({ sale, onClose }) => {
  const [countdown, setCountdown] = useState(5)

  useEffect(() => {
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer)
          onClose()
          return 0
        }
        return prev - 1
      })
    }, 1000)

    return () => clearInterval(timer)
  }, [onClose])

  const handlePrint = () => {
    window.print()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
      <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
          <div className="flex items-center gap-2 text-emerald-400 font-bold">
            <CheckCircle className="w-5 h-5" />
            <span>Transaksi Berhasil</span>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-white p-1 rounded-lg">
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Receipt Printable Area */}
        <div id="receipt-print-area" className="p-6 bg-white text-slate-900 font-mono text-xs space-y-3">
          <div className="text-center pb-2 border-b border-dashed border-slate-300">
            <h2 className="font-bold text-sm tracking-wide">STOCKKU POS</h2>
            <p className="text-[10px] text-slate-500">Jl. Contoh Alamat No. 123</p>
            <p className="text-[10px] text-slate-500">{sale.invoiceNumber}</p>
            <p className="text-[10px] text-slate-500">{formatDateTime(sale.createdAt)}</p>
          </div>

          <div className="space-y-1.5 py-1">
            {sale.items?.map((item: any, idx: number) => (
              <div key={idx} className="flex justify-between items-start">
                <div className="pr-2">
                  <p className="font-semibold text-slate-800">{item.product?.name || 'Item'}</p>
                  <p className="text-[10px] text-slate-500">
                    {item.qty} x {formatRupiah(item.harga)}
                    {item.diskon > 0 && ` (Disc -${formatRupiah(item.diskon)})`}
                  </p>
                </div>
                <span className="font-medium text-slate-900">{formatRupiah(item.subtotal)}</span>
              </div>
            ))}
          </div>

          <div className="pt-2 border-t border-dashed border-slate-300 space-y-1 text-right">
            <div className="flex justify-between text-slate-600">
              <span>Subtotal:</span>
              <span>{formatRupiah(sale.subtotal)}</span>
            </div>
            {Number(sale.diskon) > 0 && (
              <div className="flex justify-between text-rose-600">
                <span>Diskon:</span>
                <span>-{formatRupiah(sale.diskon)}</span>
              </div>
            )}
            <div className="flex justify-between font-bold text-slate-900 text-sm pt-1 border-t border-slate-200">
              <span>Total:</span>
              <span>{formatRupiah(sale.grandTotal)}</span>
            </div>
            <div className="flex justify-between text-slate-600">
              <span>Bayar ({sale.paymentMethod}):</span>
              <span>{formatRupiah(sale.bayar)}</span>
            </div>
            <div className="flex justify-between font-bold text-emerald-600">
              <span>Kembalian:</span>
              <span>{formatRupiah(sale.kembalian)}</span>
            </div>
          </div>

          <div className="text-center pt-3 border-t border-dashed border-slate-300 text-[10px] text-slate-500">
            <p>Terima kasih atas kunjungan Anda!</p>
          </div>
        </div>

        {/* Modal Actions */}
        <div className="p-4 border-t border-slate-800 bg-slate-900 flex items-center justify-between gap-3">
          <button
            onClick={handlePrint}
            className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm transition-colors border border-slate-700"
          >
            <Printer className="w-4 h-4" />
            <span>Cetak Struk</span>
          </button>
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-colors shadow-lg shadow-indigo-600/25"
          >
            Selesai ({countdown}s)
          </button>
        </div>
      </div>
    </div>
  )
}
