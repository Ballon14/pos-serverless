import { eq } from 'drizzle-orm'
import { products, stockMovements } from '../db/schema'
import type { Database } from '../db/client'
import type { StockMovementType } from '@stockku/shared'

export interface RecordMovementParams {
  productId: string
  type: StockMovementType
  qty: number
  referenceType?: string | null
  referenceId?: string | null
  keterangan?: string | null
  userId?: string | null
}

export class StockService {
  /**
   * Data Integrity Rule:
   * - Qty cannot be 0
   * - Throws when type = 'out' would drive stock below zero
   * - Throws for unknown types
   */
  static async recordMovement(tx: Database | any, params: RecordMovementParams) {
    const { productId, type, qty, referenceType, referenceId, keterangan, userId } = params

    if (qty === 0) {
      throw new Error('Jumlah mutasi stok tidak boleh 0.')
    }
    if (type !== 'adjustment' && qty < 0) {
      throw new Error('Jumlah mutasi stok harus lebih dari 0.')
    }

    const [product] = await tx
      .select()
      .from(products)
      .where(eq(products.id, productId))
      .for('update')

    if (!product) {
      throw new Error(`Produk dengan ID ${productId} tidak ditemukan.`)
    }

    const stokSebelum = product.stok
    let stokSesudah = stokSebelum

    if (type === 'in' || type === 'return') {
      stokSesudah = stokSebelum + qty
    } else if (type === 'adjustment') {
      stokSesudah = Math.max(0, stokSebelum + qty)
    } else if (type === 'out') {
      if (stokSebelum < qty) {
        throw new Error(
          `Stok ${product.name} tidak mencukupi untuk pengurangan ${qty} (tersisa ${stokSebelum}).`
        )
      }
      stokSesudah = stokSebelum - qty
    } else {
      throw new Error(`Tipe mutasi stok tidak dikenal: ${type}`)
    }

    await tx
      .update(products)
      .set({ stok: stokSesudah, updatedAt: new Date() })
      .where(eq(products.id, productId))

    const [movement] = await tx
      .insert(stockMovements)
      .values({
        productId,
        type,
        qty,
        stokSebelum,
        stokSesudah,
        referenceType,
        referenceId,
        keterangan,
        userId,
      })
      .returning()

    return movement
  }
}
