import React from 'react'
import { Outlet } from 'react-router-dom'
import { Header } from './Header'
import { Sidebar } from './Sidebar'
import { AttendanceBanner } from './AttendanceBanner'
import { useToastStore } from '../../stores/toast.store'
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-react'

export const AppLayout: React.FC = () => {
  const { toasts, removeToast } = useToastStore()

  return (
    <div className="flex flex-col h-full bg-slate-950 text-slate-100">
      <Header />
      <AttendanceBanner />

      <div className="flex flex-1 overflow-hidden">
        <Sidebar />
        <main className="flex-1 overflow-y-auto p-6 bg-slate-950/60">
          <Outlet />
        </main>
      </div>

      {/* Global Toast Container */}
      <div className="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
        {toasts.map((toast) => {
          const icons = {
            success: <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />,
            error: <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />,
            warning: <AlertTriangle className="w-5 h-5 text-amber-400 shrink-0" />,
            info: <Info className="w-5 h-5 text-indigo-400 shrink-0" />,
          }

          const borderColors = {
            success: 'border-emerald-500/30 bg-slate-900/90 text-emerald-200',
            error: 'border-rose-500/30 bg-slate-900/90 text-rose-200',
            warning: 'border-amber-500/30 bg-slate-900/90 text-amber-200',
            info: 'border-indigo-500/30 bg-slate-900/90 text-indigo-200',
          }

          return (
            <div
              key={toast.id}
              className={`pointer-events-auto flex items-start gap-3 p-4 rounded-xl border shadow-xl backdrop-blur-md min-w-[300px] max-w-md animate-in slide-in-from-bottom-2 ${
                borderColors[toast.type]
              }`}
            >
              {icons[toast.type]}
              <div className="flex-1 pr-2">
                {toast.title && <p className="font-bold text-sm text-white mb-0.5">{toast.title}</p>}
                <p className="text-xs text-slate-300 leading-relaxed">{toast.message}</p>
              </div>
              <button
                onClick={() => removeToast(toast.id)}
                className="text-slate-400 hover:text-white p-1 rounded-lg"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          )
        })}
      </div>
    </div>
  )
}
