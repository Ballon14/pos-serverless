import { z } from 'zod'
import { STOCK_MOVEMENT_TYPES, LEAVE_TYPES } from '../constants'

export const grosirTierSchema = z.object({
  minQty: z.number().int().positive(),
  harga: z.number().nonnegative(),
})

export const createProductSchema = z.object({
  categoryId: z.string().uuid(),
  name: z.string().min(1, 'Nama produk wajib diisi'),
  sku: z.string().min(1, 'SKU wajib diisi'),
  hargaBeli: z.number().nonnegative('Harga beli tidak boleh negatif').default(0),
  hargaJual: z.number().nonnegative('Harga jual tidak boleh negatif').default(0),
  grosirTiers: z.array(grosirTierSchema).optional().nullable(),
  stok: z.number().int().nonnegative('Stok awal tidak boleh negatif').default(0),
  minStok: z.number().int().nonnegative('Stok minimum tidak boleh negatif').default(5),
  satuan: z.string().default('pcs'),
  foto: z.string().optional().nullable(),
  deskripsi: z.string().optional().nullable(),
  isActive: z.boolean().default(true),
})

export const updateProductSchema = createProductSchema.partial()

export const createSaleItemSchema = z.object({
  productId: z.string().uuid(),
  qty: z.number().int().positive('Jumlah harus lebih dari 0'),
  harga: z.number().nonnegative('Harga tidak boleh negatif'),
  hargaBeli: z.number().nonnegative().optional().default(0),
  diskon: z.number().nonnegative('Diskon tidak boleh negatif').default(0),
})

export const createSaleSchema = z.object({
  items: z.array(createSaleItemSchema).min(1, 'Keranjang belanja tidak boleh kosong'),
  diskon: z.number().nonnegative('Diskon tidak boleh negatif').default(0),
  bayar: z.number().nonnegative('Nominal pembayaran wajib diisi'),
  paymentMethod: z.string().default('cash'),
  catatan: z.string().optional().nullable(),
  sumber: z.string().optional().nullable(),
  offlineId: z.string().optional().nullable(),
})

export const processReturnItemSchema = z.object({
  productId: z.string().uuid(),
  qty: z.number().int().positive(),
  harga: z.number().nonnegative(),
})

export const processReturnSchema = z.object({
  items: z.array(processReturnItemSchema).min(1, 'Minimal satu item diretur'),
  alasan: z.string().optional().nullable(),
})

export const recordStockMovementSchema = z.object({
  productId: z.string().uuid(),
  type: z.enum(STOCK_MOVEMENT_TYPES),
  qty: z.number().int().positive('Qty harus lebih besar dari 0'),
  referenceType: z.string().optional().nullable(),
  referenceId: z.string().optional().nullable(),
  keterangan: z.string().optional().nullable(),
})

export const createCategorySchema = z.object({
  name: z.string().min(1, 'Nama kategori wajib diisi'),
  slug: z.string().min(1, 'Slug wajib diisi'),
  description: z.string().optional().nullable(),
  isActive: z.boolean().default(true),
})

export const updateCategorySchema = createCategorySchema.partial()

export const createSupplierSchema = z.object({
  name: z.string().min(1, 'Nama supplier wajib diisi'),
  code: z.string().min(1, 'Kode supplier wajib diisi'),
  phone: z.string().optional().nullable(),
  email: z.string().email().optional().nullable(),
  address: z.string().optional().nullable(),
  contactPerson: z.string().optional().nullable(),
  isActive: z.boolean().default(true),
})

export const updateSupplierSchema = createSupplierSchema.partial()

export const createPurchaseItemSchema = z.object({
  productId: z.string().uuid(),
  qty: z.number().int().positive(),
  harga: z.number().nonnegative(),
})

export const createPurchaseSchema = z.object({
  supplierId: z.string().uuid(),
  tanggal: z.string(),
  items: z.array(createPurchaseItemSchema).min(1, 'Item pembelian wajib diisi'),
  keterangan: z.string().optional().nullable(),
  fotoNota: z.string().optional().nullable(),
})

export const clockInSchema = z.object({
  keterangan: z.string().optional().nullable(),
})

export const clockOutSchema = z.object({
  keterangan: z.string().optional().nullable(),
})

export const createLeaveRequestSchema = z.object({
  tipe: z.enum(LEAVE_TYPES),
  tanggalMulai: z.string(),
  tanggalSelesai: z.string(),
  alasan: z.string().min(1, 'Alasan izin/cuti wajib diisi'),
})

export type CreateSaleInput = z.infer<typeof createSaleSchema>
export type CreateProductInput = z.infer<typeof createProductSchema>
export type UpdateProductInput = z.infer<typeof updateProductSchema>
export type ProcessReturnInput = z.infer<typeof processReturnSchema>
export type RecordStockMovementInput = z.infer<typeof recordStockMovementSchema>
export type CreatePurchaseInput = z.infer<typeof createPurchaseSchema>
