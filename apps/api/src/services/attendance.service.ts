import { and, eq, sql, desc } from 'drizzle-orm'
import { attendances, leaveRequests } from '../db/schema'
import type { Database } from '../db/client'
import type { LeaveType } from '@stockku/shared'

export class AttendanceService {
  static getTodayDateStr(): string {
    return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })
  }

  static async getTodayStatus(db: Database, userId: string) {
    const today = this.getTodayDateStr()
    const [att] = await db
      .select()
      .from(attendances)
      .where(and(eq(attendances.userId, userId), sql`${attendances.tanggal} = ${today}`))
      .limit(1)

    return {
      hasClockedIn: !!att?.clockIn,
      hasClockedOut: !!att?.clockOut,
      clockInTime: att?.clockIn ? att.clockIn.toISOString() : null,
      clockOutTime: att?.clockOut ? att.clockOut.toISOString() : null,
      status: att?.status || 'belum_absen',
    }
  }

  static async clockIn(db: Database, userId: string, keterangan?: string | null) {
    const today = this.getTodayDateStr()
    const now = new Date()

    const [existing] = await db
      .select()
      .from(attendances)
      .where(and(eq(attendances.userId, userId), sql`${attendances.tanggal} = ${today}`))
      .limit(1)

    if (existing && existing.clockIn) {
      throw new Error('Anda sudah melakukan absensi masuk hari ini.')
    }

    if (existing) {
      const [updated] = await db
        .update(attendances)
        .set({ clockIn: now, keterangan: keterangan || existing.keterangan, updatedAt: now })
        .where(eq(attendances.id, existing.id))
        .returning()
      return updated
    }

    const [inserted] = await db
      .insert(attendances)
      .values({
        userId,
        tanggal: today,
        clockIn: now,
        status: 'hadir',
        keterangan: keterangan || null,
      })
      .returning()

    return inserted
  }

  static async clockOut(db: Database, userId: string, keterangan?: string | null) {
    const today = this.getTodayDateStr()
    const now = new Date()

    const [existing] = await db
      .select()
      .from(attendances)
      .where(and(eq(attendances.userId, userId), sql`${attendances.tanggal} = ${today}`))
      .limit(1)

    if (!existing || !existing.clockIn) {
      throw new Error('Anda belum melakukan absensi masuk hari ini.')
    }

    if (existing.clockOut) {
      throw new Error('Anda sudah melakukan absensi keluar hari ini.')
    }

    const [updated] = await db
      .update(attendances)
      .set({
        clockOut: now,
        keterangan: keterangan ? `${existing.keterangan || ''} | ${keterangan}` : existing.keterangan,
        updatedAt: now,
      })
      .where(eq(attendances.id, existing.id))
      .returning()

    return updated
  }

  static async createLeaveRequest(
    db: Database,
    userId: string,
    input: { tipe: LeaveType; tanggalMulai: string; tanggalSelesai: string; alasan: string }
  ) {
    const [request] = await db
      .insert(leaveRequests)
      .values({
        userId,
        tipe: input.tipe,
        tanggalMulai: input.tanggalMulai,
        tanggalSelesai: input.tanggalSelesai,
        alasan: input.alasan,
        status: 'pending',
      })
      .returning()

    return request
  }

  static async approveLeaveRequest(db: Database, requestId: string, approvedBy: string, status: 'approved' | 'rejected') {
    const [request] = await db
      .update(leaveRequests)
      .set({ status, approvedBy, updatedAt: new Date() })
      .where(eq(leaveRequests.id, requestId))
      .returning()

    return request
  }
}
