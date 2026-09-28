import { createMiddleware } from 'hono/factory'
import { and, eq, sql } from 'drizzle-orm'
import { getDb } from '../db/client'
import { attendances } from '../db/schema'
import type { Bindings, Variables } from '../types'

export const requireAttendance = createMiddleware<{ Bindings: Bindings; Variables: Variables }>(
  async (c, next) => {
    const userRole = c.get('userRole')

    // Admin (owner) is fully exempt — never gate admin accounts
    if (userRole === 'admin') {
      return next()
    }

    // Only gate mutating methods (POST, PUT, PATCH, DELETE)
    const method = c.req.method.toUpperCase()
    if (['GET', 'HEAD', 'OPTIONS'].includes(method)) {
      return next()
    }

    const userId = c.get('userId')
    const db = getDb(c.env.DATABASE_URL)
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })

    // Check if user clocked in today
    const [attendance] = await db
      .select()
      .from(attendances)
      .where(
        and(
          eq(attendances.userId, userId),
          sql`${attendances.tanggal} = ${today}`
        )
      )
      .limit(1)

    if (!attendance || !attendance.clockIn) {
      return c.json(
        {
          error: 'Anda belum absen masuk hari ini. Silakan lakukan absensi terlebih dahulu.',
          code: 'ATTENDANCE_REQUIRED',
        },
        403
      )
    }

    // If user already clocked out today, block mutations as well
    if (attendance.clockOut) {
      return c.json(
        {
          error: 'Anda sudah absen keluar hari ini. Transaksi mutasi data dinonaktifkan.',
          code: 'ATTENDANCE_REQUIRED',
        },
        403
      )
    }

    await next()
  }
)
