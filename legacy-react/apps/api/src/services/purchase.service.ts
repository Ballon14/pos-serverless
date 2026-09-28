import { eq, desc, sql } from 'drizzle-orm'
import { purchases, purchaseItems, products, priceChangeLogs } from '../db/schema'
import { StockService } from './stock.service'
import type { Database } from '../db/client'
import type { CreatePurchaseInput } from '@stockku/shared'

export class PurchaseService {
  static async generateInvoiceNumber(tx: any): Promise<string> {
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }).replace(/-/g, '')
    const prefix = `PO-${today}`

    const last = await tx
      .select({ invoiceNumber: purchases.invoiceNumber })
      .from(purchases)
      .where(sql`${purchases.invoiceNumber} LIKE ${prefix + '-%'}`)
      .orderBy(desc(purchases.invoiceNumber))
      .limit(1)

    let nextNumber = 1
    if (last.length > 0 && last[0].invoiceNumber) {
      const parts = last[0].invoiceNumber.split('-')
      const lastSeq = parseInt(parts[parts.length - 1], 10)
      if (!isNaN(lastSeq)) {
        nextNumber = lastSeq + 1
      }
    }

    return `${prefix}-${String(nextNumber).padStart(4, '0')}`
  }

  static async createPurchase(db: Database, input: CreatePurchaseInput, userId: string) {
    return await db.transaction(async (tx) => {
      const invoiceNumber = await this.generateInvoiceNumber(tx)

      let total = 0
      for (const item of input.items) {
        total += item.qty * item.harga
      }

      const [purchase] = await tx
        .insert(purchases)
        .values({
          invoiceNumber,
          supplierId: input.supplierId,
          userId,
          tanggal: input.tanggal,
          total: String(total),
          status: 'received',
          keterangan: input.keterangan || null,
          fotoNota: input.fotoNota || null,
        })
        .returning()

      const insertedItems = []
      for (const item of input.items) {
        const [pItem] = await tx
          .insert(purchaseItems)
          .values({
            purchaseId: purchase.id,
            productId: item.productId,
            qty: item.qty,
            harga: String(item.harga),
            subtotal: String(item.qty * item.harga),
          })
          .returning()

        insertedItems.push(pItem)

        // Increment stock
        await StockService.recordMovement(tx, {
          productId: item.productId,
          type: 'in',
          qty: item.qty,
          referenceType: 'purchase',
          referenceId: purchase.id,
          keterangan: `Pembelian ${purchase.invoiceNumber}`,
          userId,
        })

        // Update purchase price on product if updated
        const [currentProduct] = await tx.select().from(products).where(eq(products.id, item.productId))
        if (currentProduct && Number(currentProduct.hargaBeli) !== item.harga) {
          await tx.insert(priceChangeLogs).values({
            productId: item.productId,
            hargaLama: currentProduct.hargaBeli,
            hargaBaru: String(item.harga),
            sumber: 'purchase',
            referenceType: 'purchase',
            referenceId: purchase.id,
            userId,
          })

          await tx
            .update(products)
            .set({ hargaBeli: String(item.harga), updatedAt: new Date() })
            .where(eq(products.id, item.productId))
        }
      }

      return {
        ...purchase,
        items: insertedItems,
      }
    })
  }
}
