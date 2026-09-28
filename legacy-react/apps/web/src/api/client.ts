import { supabase } from '../lib/supabase'
import { useToastStore } from '../stores/toast.store'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8787'

export async function apiClient<T>(path: string, options: RequestInit = {}): Promise<T> {
  const {
    data: { session },
  } = await supabase.auth.getSession()

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(session?.access_token ? { Authorization: `Bearer ${session.access_token}` } : {}),
    ...(options.headers as Record<string, string>),
  }

  const url = `${API_BASE_URL}${path.startsWith('/') ? path : `/${path}`}`

  const response = await fetch(url, {
    ...options,
    headers,
  })

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({
      error: `Terjadi galat jaringan (HTTP ${response.status})`,
    }))

    // Handle Attendance Gate 403
    if (response.status === 403 && errorData.code === 'ATTENDANCE_REQUIRED') {
      useToastStore.getState().addToast({
        title: 'Absensi Diperlukan',
        message: errorData.error || 'Anda harus melakukan absen masuk terlebih dahulu.',
        type: 'warning',
      })

      if (window.location.pathname !== '/attendance') {
        window.location.href = '/attendance'
      }
      throw new Error(errorData.error)
    }

    useToastStore.getState().addToast({
      title: 'Kesalahan',
      message: errorData.error || `HTTP ${response.status}`,
      type: 'error',
    })

    throw new Error(errorData.error || `HTTP ${response.status}`)
  }

  return response.json()
}
