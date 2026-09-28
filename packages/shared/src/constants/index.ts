export const ROLES = ['admin', 'manager', 'kasir', 'karyawan'] as const
export type Role = (typeof ROLES)[number]

export const STOCK_MOVEMENT_TYPES = ['in', 'out', 'return', 'adjustment'] as const
export type StockMovementType = (typeof STOCK_MOVEMENT_TYPES)[number]

export const SALE_STATUSES = ['completed', 'returned', 'partial_return'] as const
export type SaleStatus = (typeof SALE_STATUSES)[number]

export const PURCHASE_STATUSES = ['pending', 'received', 'cancelled'] as const
export type PurchaseStatus = (typeof PURCHASE_STATUSES)[number]

export const ATTENDANCE_STATUSES = ['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpha'] as const
export type AttendanceStatus = (typeof ATTENDANCE_STATUSES)[number]

export const LEAVE_TYPES = ['izin', 'sakit', 'cuti'] as const
export type LeaveType = (typeof LEAVE_TYPES)[number]

export const LEAVE_STATUSES = ['pending', 'approved', 'rejected'] as const
export type LeaveStatus = (typeof LEAVE_STATUSES)[number]

export const INVOICE_PREFIX = 'INV-'
export const TIMEZONE = 'Asia/Jakarta'
