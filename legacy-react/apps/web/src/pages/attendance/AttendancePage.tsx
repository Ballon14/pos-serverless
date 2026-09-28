import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Clock, LogIn, LogOut, FileText, CheckCircle2, AlertTriangle, Calendar } from 'lucide-react'
import { apiClient } from '../../api/client'
import { formatDateTime, formatDate } from '../../lib/format'
import { useAuthStore } from '../../stores/auth.store'
import { useToastStore } from '../../stores/toast.store'

export const AttendancePage: React.FC = () => {
  const queryClient = useQueryClient()
  const { user, attendance, refreshAttendance } = useAuthStore()
  const { addToast } = useToastStore()

  const [clockInNote, setClockInNote] = useState('')
  const [clockOutNote, setClockOutNote] = useState('')
  const [isLeaveModalOpen, setIsLeaveModalOpen] = useState(false)
  const [leaveData, setLeaveData] = useState({
    tipe: 'izin' as 'izin' | 'sakit' | 'cuti',
    tanggalMulai: new Date().toLocaleDateString('en-CA'),
    tanggalSelesai: new Date().toLocaleDateString('en-CA'),
    alasan: '',
  })

  // Fetch Attendance History
  const { data: historyData, isLoading } = useQuery({
    queryKey: ['attendanceHistory'],
    queryFn: () => apiClient<{ data: any[] }>('/api/attendance/history'),
  })

  // Clock In Mutation
  const clockInMutation = useMutation({
    mutationFn: async () => {
      return apiClient('/api/attendance/clock-in', {
        method: 'POST',
        body: JSON.stringify({ keterangan: clockInNote }),
      })
    },
    onSuccess: async () => {
      await refreshAttendance()
      queryClient.invalidateQueries({ queryKey: ['attendanceHistory'] })
      addToast({
        title: 'Absensi Masuk Berhasil',
        message: 'Status aktif telah diperbarui. Mode baca telah dinonaktifkan.',
        type: 'success',
      })
    },
  })

  // Clock Out Mutation
  const clockOutMutation = useMutation({
    mutationFn: async () => {
      return apiClient('/api/attendance/clock-out', {
        method: 'POST',
        body: JSON.stringify({ keterangan: clockOutNote }),
      })
    },
    onSuccess: async () => {
      await refreshAttendance()
      queryClient.invalidateQueries({ queryKey: ['attendanceHistory'] })
      addToast({
        title: 'Absensi Keluar Berhasil',
        message: 'Terima kasih atas kerja keras Anda hari ini!',
        type: 'info',
      })
    },
  })

  // Leave Request Mutation
  const leaveMutation = useMutation({
    mutationFn: async () => {
      return apiClient('/api/attendance/leave', {
        method: 'POST',
        body: JSON.stringify(leaveData),
      })
    },
    onSuccess: () => {
      setIsLeaveModalOpen(false)
      addToast({
        title: 'Pengajuan Terkirim',
        message: 'Pengajuan izin/cuti berhasil dikirim untuk persetujuan.',
        type: 'success',
      })
    },
  })

  const history = historyData?.data || []

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-extrabold text-white tracking-tight">Presensi & Absensi Karyawan</h1>
          <p className="text-xs text-slate-400">
            Catat jam kehadiran harian untuk membuka akses mutasi data kasir dan inventori.
          </p>
        </div>

        <button
          onClick={() => setIsLeaveModalOpen(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition-all self-start sm:self-auto"
        >
          <FileText className="w-4 h-4 text-indigo-400" />
          <span>Pengajuan Izin / Cuti</span>
        </button>
      </div>

      {/* Clock In / Out Action Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
        {/* Clock In Card */}
        <div className="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
              <LogIn className="w-6 h-6" />
            </div>
            <div>
              <h2 className="font-bold text-white text-base">Absen Masuk (Clock In)</h2>
              <p className="text-xs text-slate-400">Wajib dilakukan sebelum memulai shift kasir</p>
            </div>
          </div>

          {attendance?.hasClockedIn ? (
            <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-400" />
              <span>
                Sudah absen masuk pada{' '}
                <strong>{attendance.clockInTime ? formatDateTime(attendance.clockInTime) : '-'}</strong>
              </span>
            </div>
          ) : (
            <div className="space-y-3">
              <input
                type="text"
                value={clockInNote}
                onChange={(e) => setClockInNote(e.target.value)}
                placeholder="Catatan masuk (opsional)..."
                className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
              />
              <button
                onClick={() => clockInMutation.mutate()}
                disabled={clockInMutation.isPending}
                className="w-full py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition-all"
              >
                {clockInMutation.isPending ? 'Menyimpan...' : 'Klik untuk Absen Masuk'}
              </button>
            </div>
          )}
        </div>

        {/* Clock Out Card */}
        <div className="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
              <LogOut className="w-6 h-6" />
            </div>
            <div>
              <h2 className="font-bold text-white text-base">Absen Keluar (Clock Out)</h2>
              <p className="text-xs text-slate-400">Tandai akhir jam kerja dan selesaikan shift</p>
            </div>
          </div>

          {!attendance?.hasClockedIn ? (
            <div className="p-4 rounded-2xl bg-slate-800/40 border border-slate-800 text-slate-400 text-xs">
              Lakukan absen masuk terlebih dahulu sebelum dapat absen keluar.
            </div>
          ) : attendance?.hasClockedOut ? (
            <div className="p-4 rounded-2xl bg-slate-800/60 border border-slate-700 text-slate-300 text-xs flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 shrink-0 text-slate-400" />
              <span>
                Sudah absen keluar pada{' '}
                <strong>{attendance.clockOutTime ? formatDateTime(attendance.clockOutTime) : '-'}</strong>
              </span>
            </div>
          ) : (
            <div className="space-y-3">
              <input
                type="text"
                value={clockOutNote}
                onChange={(e) => setClockOutNote(e.target.value)}
                placeholder="Catatan pulang (opsional)..."
                className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
              />
              <button
                onClick={() => clockOutMutation.mutate()}
                disabled={clockOutMutation.isPending}
                className="w-full py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm shadow-lg shadow-rose-600/25 transition-all"
              >
                {clockOutMutation.isPending ? 'Menyimpan...' : 'Klik untuk Absen Keluar'}
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Attendance History Table */}
      <div className="rounded-3xl bg-slate-900/60 border border-slate-800 shadow-md overflow-hidden">
        <div className="p-4 border-b border-slate-800 flex items-center gap-2">
          <Calendar className="w-4 h-4 text-indigo-400" />
          <h2 className="font-bold text-white text-sm">Riwayat Absensi Terakhir</h2>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
              <tr>
                <th className="py-3 px-4">Tanggal</th>
                <th className="py-3 px-4">Jam Masuk</th>
                <th className="py-3 px-4">Jam Keluar</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4">Keterangan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 font-mono">
              {isLoading ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-slate-500 font-sans">
                    Memuat riwayat...
                  </td>
                </tr>
              ) : history.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-slate-500 font-sans">
                    Belum ada catatan absensi sebelumnya.
                  </td>
                </tr>
              ) : (
                history.map((row) => (
                  <tr key={row.id} className="hover:bg-slate-800/30 transition-colors">
                    <td className="py-3.5 px-4 font-semibold text-white">{formatDate(row.tanggal)}</td>
                    <td className="py-3.5 px-4 text-emerald-400 font-medium">
                      {row.clockIn ? formatDateTime(row.clockIn) : '-'}
                    </td>
                    <td className="py-3.5 px-4 text-rose-400 font-medium">
                      {row.clockOut ? formatDateTime(row.clockOut) : '-'}
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-bold font-sans capitalize">
                        {row.status}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 font-sans text-slate-400">{row.keterangan || '-'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Leave Request Modal */}
      {isLeaveModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
              <h2 className="font-bold text-white text-base">Pengajuan Izin / Cuti / Sakit</h2>
              <button
                onClick={() => setIsLeaveModalOpen(false)}
                className="text-slate-400 hover:text-white"
              >
                ✕
              </button>
            </div>

            <form
              onSubmit={(e) => {
                e.preventDefault()
                leaveMutation.mutate()
              }}
              className="p-6 space-y-4 text-xs"
            >
              <div>
                <label className="block font-semibold text-slate-300 mb-1">Tipe Pengajuan</label>
                <select
                  value={leaveData.tipe}
                  onChange={(e) => setLeaveData({ ...leaveData, tipe: e.target.value as any })}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                >
                  <option value="izin">Izin</option>
                  <option value="sakit">Sakit</option>
                  <option value="cuti">Cuti</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Mulai Tanggal</label>
                  <input
                    type="date"
                    required
                    value={leaveData.tanggalMulai}
                    onChange={(e) => setLeaveData({ ...leaveData, tanggalMulai: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-300 mb-1">Sampai Tanggal</label>
                  <input
                    type="date"
                    required
                    value={leaveData.tanggalSelesai}
                    onChange={(e) => setLeaveData({ ...leaveData, tanggalSelesai: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-300 mb-1">Alasan Pengajuan</label>
                <textarea
                  rows={3}
                  required
                  value={leaveData.alasan}
                  onChange={(e) => setLeaveData({ ...leaveData, alasan: e.target.value })}
                  placeholder="Jelaskan keperluan atau keterangan sakit..."
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
                />
              </div>

              <div className="pt-2 flex gap-2">
                <button
                  type="button"
                  onClick={() => setIsLeaveModalOpen(false)}
                  className="flex-1 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold hover:bg-slate-700 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={leaveMutation.isPending}
                  className="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-600/25"
                >
                  {leaveMutation.isPending ? 'Mengirim...' : 'Kirim Pengajuan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
