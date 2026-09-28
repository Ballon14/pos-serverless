import React from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, Clock } from 'lucide-react'
import { useAuthStore } from '../../stores/auth.store'

export const AttendanceBanner: React.FC = () => {
  const { user, attendance } = useAuthStore()

  // Admin is fully exempt
  if (!user || user.role === 'admin') {
    return null
  }

  // If already clocked in and not clocked out, no banner needed
  if (attendance?.hasClockedIn && !attendance?.hasClockedOut) {
    return null
  }

  const isClockedOut = attendance?.hasClockedOut

  return (
    <div className="bg-amber-500/15 border-b border-amber-500/30 px-4 py-2.5 text-amber-200">
      <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs sm:text-sm">
        <div className="flex items-center gap-2">
          <AlertTriangle className="w-4 h-4 text-amber-400 shrink-0" />
          <span>
            <strong className="font-semibold text-amber-300">Mode Baca Aktif:</strong>{' '}
            {isClockedOut
              ? 'Anda sudah absen keluar hari ini. Transaksi mutasi dan checkout dinonaktifkan.'
              : 'Anda belum melakukan absen masuk hari ini. Semua aksi perubahan data dikunci.'}
          </span>
        </div>
        {!isClockedOut && (
          <Link
            to="/attendance"
            className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-500 text-slate-950 font-bold hover:bg-amber-400 transition-colors shadow-sm"
          >
            <Clock className="w-3.5 h-3.5" />
            <span>Absen Masuk Sekarang</span>
          </Link>
        )}
      </div>
    </div>
  )
}
