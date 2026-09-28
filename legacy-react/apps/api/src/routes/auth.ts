import { Hono } from 'hono'
import { AttendanceService } from '../services/attendance.service'
import { getDb } from '../db/client'
import type { Bindings, Variables } from '../types'

const auth = new Hono<{ Bindings: Bindings; Variables: Variables }>()

auth.get('/me', async (c) => {
  const user = c.get('user')
  const db = getDb(c.env.DATABASE_URL)
  const attendanceStatus = await AttendanceService.getTodayStatus(db, user.id)

  return c.json({
    user,
    attendance: attendanceStatus,
  })
})

export { auth as authRoutes }
