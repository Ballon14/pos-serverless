import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { clockInSchema, clockOutSchema, createLeaveRequestSchema } from '@stockku/shared'
import { AttendanceService } from '../services/attendance.service'
import { requireRole } from '../middleware/rbac'
import { getDb } from '../db/client'
import { leaveRequests, attendances } from '../db/schema'
import { desc, eq } from 'drizzle-orm'
import type { Bindings, Variables } from '../types'

const attendance = new Hono<{ Bindings: Bindings; Variables: Variables }>()

attendance.get('/status', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const status = await AttendanceService.getTodayStatus(db, userId)
  return c.json({ data: status })
})

attendance.get('/history', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const history = await db
    .select()
    .from(attendances)
    .where(eq(attendances.userId, userId))
    .orderBy(desc(attendances.tanggal))
    .limit(30)

  return c.json({ data: history })
})

attendance.post('/clock-in', zValidator('json', clockInSchema), async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const data = c.req.valid('json')

  try {
    const record = await AttendanceService.clockIn(db, userId, data.keterangan)
    return c.json({ data: record, message: 'Absen masuk berhasil dicatat' }, 201)
  } catch (err: any) {
    return c.json({ error: err.message }, 400)
  }
})

attendance.post('/clock-out', zValidator('json', clockOutSchema), async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const data = c.req.valid('json')

  try {
    const record = await AttendanceService.clockOut(db, userId, data.keterangan)
    return c.json({ data: record, message: 'Absen keluar berhasil dicatat' })
  } catch (err: any) {
    return c.json({ error: err.message }, 400)
  }
})

attendance.post('/leave', zValidator('json', createLeaveRequestSchema), async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const data = c.req.valid('json')

  try {
    const request = await AttendanceService.createLeaveRequest(db, userId, data)
    return c.json({ data: request, message: 'Pengajuan izin/cuti berhasil dikirim' }, 201)
  } catch (err: any) {
    return c.json({ error: err.message }, 400)
  }
})

attendance.patch(
  '/leave/:id/approve',
  requireRole(['admin', 'manager']),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const requestId = c.req.param('id')
    const userId = c.get('userId')
    const body = await c.req.json()

    if (!['approved', 'rejected'].includes(body.status)) {
      return c.json({ error: 'Status harus approved atau rejected' }, 400)
    }

    try {
      const updated = await AttendanceService.approveLeaveRequest(db, requestId, userId, body.status)
      return c.json({ data: updated, message: `Pengajuan berhasil di-${body.status}` })
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

export { attendance as attendanceRoutes }
