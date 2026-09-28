import React, { useState, useEffect } from 'react'
import { LogOut, User as UserIcon, Shield, Clock } from 'lucide-react'
import { useAuthStore } from '../../stores/auth.store'

export const Header: React.FC = () => {
  const { user, attendance, logout } = useAuthStore()
  const [currentTime, setCurrentTime] = useState<string>('')

  useEffect(() => {
    const update = () => {
      const now = new Date()
      setCurrentTime(
        now.toLocaleTimeString('id-ID', {
          timeZone: 'Asia/Jakarta',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        }) + ' WIB'
      )
    }
    update()
    const timer = setInterval(update, 1000)
    return () => clearInterval(timer)
  }, [])

  return (
    <header className="h-16 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md px-6 flex items-center justify-between z-10">
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/25">
            <span className="font-extrabold text-white text-base">S</span>
          </div>
          <div>
            <h1 className="font-bold text-white text-base tracking-tight leading-tight">StockKu</h1>
            <p className="text-[11px] text-slate-400 font-medium">Serverless POS & Inventory</p>
          </div>
        </div>

        <div className="hidden md:flex items-center gap-2 ml-6 px-3 py-1 rounded-full bg-slate-800/60 border border-slate-700/50 text-xs text-slate-300 font-mono">
          <Clock className="w-3.5 h-3.5 text-indigo-400" />
          <span>{currentTime}</span>
        </div>
      </div>

      <div className="flex items-center gap-4">
        {/* Attendance Status Pill */}
        {user?.role !== 'admin' && (
          <div className="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border border-slate-700/60 bg-slate-800/40">
            <span
              className={`w-2 h-2 rounded-full ${
                attendance?.hasClockedIn && !attendance?.hasClockedOut
                  ? 'bg-emerald-400 animate-pulse'
                  : 'bg-amber-400'
              }`}
            />
            <span className="text-slate-300">
              {attendance?.hasClockedIn
                ? attendance.hasClockedOut
                  ? 'Sudah Absen Keluar'
                  : 'Aktif Masuk'
                : 'Belum Absen'}
            </span>
          </div>
        )}

        {/* User Role Badge */}
        <div className="flex items-center gap-3 pl-2 sm:border-l sm:border-slate-800">
          <div className="text-right hidden sm:block">
            <p className="text-sm font-semibold text-white leading-tight">{user?.name || 'Kasir'}</p>
            <div className="flex items-center justify-end gap-1 text-[11px] font-medium text-indigo-400 capitalize">
              <Shield className="w-3 h-3" />
              <span>{user?.role || 'kasir'}</span>
            </div>
          </div>

          <div className="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300">
            <UserIcon className="w-4 h-4" />
          </div>

          <button
            onClick={() => logout()}
            title="Keluar"
            className="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>
    </header>
  )
}
