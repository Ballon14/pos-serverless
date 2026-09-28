import type {
  Role,
  StockMovementType,
  SaleStatus,
  PurchaseStatus,
  AttendanceStatus,
  LeaveType,
  LeaveStatus,
} from '../constants'

export interface User {
  id: string
  name: string
  email: string
  role: Role
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface Category {
  id: string
  name: string
  slug: string
  description?: string | null
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface Supplier {
  id: string
  name: string
  code: string
  phone?: string | null
  email?: string | null
  address?: string | null
  contactPerson?: string | null
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface GrosirTier {
  minQty: number
  harga: number
}

export interface Product {
  id: string
  categoryId: string
  name: string
  sku: string
  hargaBeli: number
  hargaJual: number
  grosirTiers?: GrosirTier[] | null
  stok: number
  minStok: number
  satuan: string
  foto?: string | null
  deskripsi?: string | null
  isActive: boolean
  createdAt: string
  updatedAt: string
  category?: Category
}

export interface SaleItem {
  id: string
  saleId: string
  productId: string
  qty: number
  returnedQty: number
  harga: number
  hargaBeli: number
  diskon: number
  subtotal: number
  product?: Product
  createdAt: string
}

export interface Sale {
  id: string
  invoiceNumber: string
  userId: string
  subtotal: number
  diskon: number
  grandTotal: number
  bayar: number
  kembalian: number
  paymentMethod: string
  status: SaleStatus
  catatan?: string | null
  sumber?: string | null
  offlineId?: string | null
  createdAt: string
  updatedAt: string
  user?: User
  items?: SaleItem[]
}

export interface SaleReturnItem {
  id: string
  saleReturnId: string
  productId: string
  qty: number
  harga: number
  subtotal: number
  product?: Product
}

export interface SaleReturn {
  id: string
  saleId: string
  returnNumber: string
  totalRefund: number
  alasan?: string | null
  status: 'pending' | 'approved' | 'rejected'
  processedBy?: string | null
  createdAt: string
  updatedAt: string
  items?: SaleReturnItem[]
}

export interface PurchaseItem {
  id: string
  purchaseId: string
  productId: string
  qty: number
  harga: number
  subtotal: number
  product?: Product
}

export interface Purchase {
  id: string
  invoiceNumber: string
  supplierId: string
  userId: string
  tanggal: string
  total: number
  status: PurchaseStatus
  keterangan?: string | null
  fotoNota?: string | null
  createdAt: string
  updatedAt: string
  supplier?: Supplier
  user?: User
  items?: PurchaseItem[]
}

export interface StockMovement {
  id: string
  productId: string
  type: StockMovementType
  qty: number
  stokSebelum: number
  stokSesudah: number
  referenceType?: string | null
  referenceId?: string | null
  keterangan?: string | null
  userId?: string | null
  createdAt: string
  product?: Product
  user?: User
}

export interface Attendance {
  id: string
  userId: string
  tanggal: string
  clockIn?: string | null
  clockOut?: string | null
  status: AttendanceStatus
  keterangan?: string | null
  createdAt: string
  updatedAt: string
  user?: User
}

export interface LeaveRequest {
  id: string
  userId: string
  tipe: LeaveType
  tanggalMulai: string
  tanggalSelesai: string
  alasan: string
  status: LeaveStatus
  approvedBy?: string | null
  createdAt: string
  updatedAt: string
  user?: User
}

export interface Setting {
  id: string
  key: string
  value?: string | null
  updatedAt: string
}

export interface ActivityLog {
  id: string
  userId?: string | null
  role?: string | null
  action: string
  description: string
  ipAddress?: string | null
  userAgent?: string | null
  createdAt: string
}
