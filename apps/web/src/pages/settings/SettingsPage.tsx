import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Settings as SettingsIcon, Save } from 'lucide-react'
import { apiClient } from '../../api/client'
import { useToastStore } from '../../stores/toast.store'

export const SettingsPage: React.FC = () => {
  const queryClient = useQueryClient()
  const { addToast } = useToastStore()

  const { data: settingsData, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient<{ data: Record<string, string | null> }>('/api/settings'),
  })

  const [form, setForm] = useState<Record<string, string>>({
    store_name: '',
    store_address: '',
    store_phone: '',
    receipt_footer: '',
  })

  React.useEffect(() => {
    if (settingsData?.data) {
      setForm({
        store_name: settingsData.data.store_name || '',
        store_address: settingsData.data.store_address || '',
        store_phone: settingsData.data.store_phone || '',
        receipt_footer: settingsData.data.receipt_footer || '',
      })
    }
  }, [settingsData])

  const mutation = useMutation({
    mutationFn: async (payload: Record<string, string>) => {
      return apiClient('/api/settings', {
        method: 'PUT',
        body: JSON.stringify(payload),
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] })
      addToast({
        title: 'Berhasil',
        message: 'Pengaturan toko berhasil disimpan',
        type: 'success',
      })
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate(form)
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-xl font-extrabold text-white tracking-tight">Pengaturan Toko & POS</h1>
        <p className="text-xs text-slate-400">Konfigurasi nama toko, alamat pada struk, dan kontak.</p>
      </div>

      <div className="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block font-semibold text-slate-300 mb-1">Nama Toko</label>
            <input
              type="text"
              required
              value={form.store_name}
              onChange={(e) => setForm({ ...form, store_name: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
            />
          </div>

          <div>
            <label className="block font-semibold text-slate-300 mb-1">Alamat Toko</label>
            <textarea
              rows={2}
              value={form.store_address}
              onChange={(e) => setForm({ ...form, store_address: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
            />
          </div>

          <div>
            <label className="block font-semibold text-slate-300 mb-1">Nomor Telepon / WhatsApp</label>
            <input
              type="text"
              value={form.store_phone}
              onChange={(e) => setForm({ ...form, store_phone: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
            />
          </div>

          <div>
            <label className="block font-semibold text-slate-300 mb-1">Pesan Footer Struk</label>
            <input
              type="text"
              value={form.receipt_footer}
              onChange={(e) => setForm({ ...form, receipt_footer: e.target.value })}
              placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar"
              className="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs"
            />
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={mutation.isPending}
              className="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 transition-all"
            >
              <Save className="w-4 h-4" />
              <span>{mutation.isPending ? 'Menyimpan...' : 'Simpan Pengaturan'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
