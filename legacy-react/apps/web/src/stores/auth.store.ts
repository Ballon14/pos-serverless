import { create } from 'zustand'
import { supabase } from '../lib/supabase'
import { apiClient } from '../api/client'
import type { User, Role } from '@stockku/shared'

interface AttendanceStatus {
  hasClockedIn: boolean
  hasClockedOut: boolean
  clockInTime: string | null
  clockOutTime: string | null
  status: string
}

interface AuthState {
  user: User | null
  role: Role | null
  attendance: AttendanceStatus | null
  isLoading: boolean
  isAuthenticated: boolean
  checkSession: () => Promise<void>
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  refreshAttendance: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  role: null,
  attendance: null,
  isLoading: true,
  isAuthenticated: false,

  checkSession: async () => {
    try {
      set({ isLoading: true })
      const {
        data: { session },
      } = await supabase.auth.getSession()

      if (!session) {
        set({ user: null, role: null, attendance: null, isAuthenticated: false, isLoading: false })
        return
      }

      // Fetch user profile & attendance status from /api/auth/me
      const res = await apiClient<{ user: User; attendance: AttendanceStatus }>('/api/auth/me')
      set({
        user: res.user,
        role: res.user.role,
        attendance: res.attendance,
        isAuthenticated: true,
        isLoading: false,
      })
    } catch (err) {
      console.error('Session check error:', err)
      set({ user: null, role: null, attendance: null, isAuthenticated: false, isLoading: false })
    }
  },

  login: async (email: string, password: string) => {
    set({ isLoading: true })
    const { error } = await supabase.auth.signInWithPassword({ email, password })
    if (error) {
      set({ isLoading: false })
      throw error
    }
    await get().checkSession()
  },

  logout: async () => {
    await supabase.auth.signOut()
    set({ user: null, role: null, attendance: null, isAuthenticated: false })
  },

  refreshAttendance: async () => {
    try {
      const res = await apiClient<{ data: AttendanceStatus }>('/api/attendance/status')
      set({ attendance: res.data })
    } catch (err) {
      console.error('Failed to refresh attendance:', err)
    }
  },
}))
