import { eq, sql, desc, and } from 'drizzle-orm'
import { sales, saleItems, saleReturns, saleReturnItems, products, activityLogs } from '../db/schema'
import { StockService } from './stock.service'
import type { Database } from '../db/client'
import type { CreateSaleInput, ProcessReturnInput } from '@stockku/shared'

export class SaleService {
  /**
   * Generates sequential invoice number per day: INV-YYYYMMDD-0001
   */
  static async generateInvoiceNumber(tx: any): Promise<string> {
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }).replace(/-/g, '')
    const prefix = `INV-${today}`

    const lastSale = await tx
      .select({ invoiceNumber: sales.invoiceNumber })
      .from(sales)
      .where(sql`${sales.invoiceNumber} LIKE ${prefix + '-%'}`)
      .orderBy(desc(sales.invoiceNumber))
      .limit(1)

    let nextNumber = 1
    if (lastSale.length > 0 && lastSale[0].invoiceNumber) {
      const parts = lastSale[0].invoiceNumber.split('-')
      const lastSeq = parseInt(parts[parts.length - 1], 10)
      if (!isNaN(lastSeq)) {
        nextNumber = lastSeq + 1
      }
    }

    return `${prefix}-${String(nextNumber).padStart(4, '0')}`
  }

  /**
   * Generates return number: RET-YYYYMMDD-0001
   */
  static async generateReturnNumber(tx: any): Promise<string> {
    const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }).replace(/-/g, '')
    const prefix = `RET-${today}`

    const lastReturn = await tx
      .select({ returnNumber: saleReturns.returnNumber })
      .from(saleReturns)
      .where(sql`${saleReturns.returnNumber} LIKE ${prefix + '-%'}`)
      .orderBy(desc(saleReturns.returnNumber))
      .limit(1)

    let nextNumber = 1
    if (lastReturn.length > 0 && lastReturn[0].returnNumber) {
      const parts = lastReturn[0].returnNumber.split('-')
      const lastSeq = parseInt(parts[parts.length - 1], 10)
      if (!isNaN(lastSeq)) {
        nextNumber = lastSeq + 1
      }
    }

    return `${prefix}-${String(nextNumber).padStart(4, '0')}`
  }

  /**
   * Creates sale transaction with strict stock guards & monetary rules
   */
  static async createSale(db: Database, input: CreateSaleInput, userId: string) {
    return await db.transaction(async (tx) => {
      if (!input.items || input.items.length === 0) {
        throw new Error('Keranjang belanja kosong.')
      }

      let subtotal = 0
      const preparedItems = []

      // Step 1: Lock products and validate stock (SELECT ... FOR UPDATE)
      for (const item of input.items) {
        const [product] = await tx
          .select()
          .from(products)
          .where(eq(products.id, item.productId))
          .for('update')

        if (!product || !product.isActive) {
          throw new Error(`Produk dengan ID ${item.productId} tidak ditemukan atau nonaktif.`)
        }

        const qty = Math.max(1, Math.floor(item.qty))
        if (product.stok < qty) {
          throw new Error(`Stok ${product.name} tidak mencukupi (tersisa ${product.stok}, diminta ${qty}).`)
        }

        const hargaJual = Number(product.hargaJual)
        const itemDiskon = Math.max(0, Number(item.diskon || 0))
        const itemSubtotal = Math.max(0, hargaJual * qty - itemDiskon)

        preparedItems.push({
          product,
          qty,
          diskon: itemDiskon,
          harga: hargaJual,
          hargaBeli: Number(product.hargaBeli),
          subtotal: itemSubtotal,
        })

        subtotal += itemSubtotal
      }

      // Step 2: Validate money
      const diskon = Math.min(Math.max(0, Number(input.diskon || 0)), subtotal)
      const grandTotal = subtotal - diskon
      const bayar = Math.max(0, Number(input.bayar || 0))

      if (bayar < grandTotal) {
        throw new Error(`Jumlah bayar (Rp ${bayar.toLocaleString('id-ID')}) kurang dari total belanja (Rp ${grandTotal.toLocaleString('id-ID')}).`)
      }

      const kembalian = bayar - grandTotal
      const invoiceNumber = await this.generateInvoiceNumber(tx)

      // Step 3: Insert sale record
      const [sale] = await tx
        .insert(sales)
        .values({
          invoiceNumber,
          userId,
          subtotal: String(subtotal),
          diskon: String(diskon),
          grandTotal: String(grandTotal),
          bayar: String(bayar),
          kembalian: String(kembalian),
          paymentMethod: input.paymentMethod || 'cash',
          status: 'completed',
          catatan: input.catatan || null,
          sumber: input.sumber || 'pos',
          offlineId: input.offlineId || null,
        })
        .returning()

      // Step 4: Insert sale items & deduct stock via StockService
      const insertedItems = []
      for (const item of preparedItems) {
        const [saleItem] = await tx
          .insert(saleItems)
          .values({
            saleId: sale.id,
            productId: item.product.id,
            qty: item.qty,
            returnedQty: 0,
            harga: String(item.harga),
            hargaBeli: String(item.hargaBeli),
            diskon: String(item.diskon),
            subtotal: String(item.subtotal),
          })
          .returning()

        insertedItems.push(saleItem)

        // Deduct stock
        await StockService.recordMovement(tx, {
          productId: item.product.id,
          type: 'out',
          qty: item.qty,
          referenceType: 'sale',
          referenceId: sale.id,
          keterangan: `Penjualan ${sale.invoiceNumber}`,
          userId,
        })
      }

      // Step 5: Log activity
      await tx.insert(activityLogs).values({
        userId,
        action: 'sale.create',
        description: `Penjualan ${sale.invoiceNumber} dibuat (Total: Rp ${grandTotal.toLocaleString('id-ID')})`,
      })

      return {
        ...sale,
        items: insertedItems,
      }
    })
  }

  /**
   * Process return with capped return limit
   */
  static async processReturn(db: Database, saleId: string, input: ProcessReturnInput, userId: string) {
    return await db.transaction(async (tx) => {
      const [sale] = await tx
        .select()
        .from(sales)
        .where(eq(sales.id, saleId))
        .for('update')

      if (!sale) {
        throw new Error('Transaksi penjualan tidak ditemukan.')
      }

      if (sale.status === 'returned') {
        throw new Error('Penjualan ini sudah diretur penuh.')
      }

      const existingItems = await tx
        .select()
        .from(saleItems)
        .where(eq(saleItems.saleId, saleId))
        .for('update')

      let totalRefund = 0
      const preparedReturnItems = []

      for (const reqItem of input.items) {
        const targetSaleItem = existingItems.find((i) => i.productId === reqItem.productId)
        if (!targetSaleItem) {
          throw new Error('Produk tersebut tidak ada pada transaksi ini.')
        }

        const remainingReturnable = targetSaleItem.qty - targetSaleItem.returnedQty
        const qtyToReturn = Math.max(1, Math.floor(reqItem.qty))

        if (qtyToReturn > remainingReturnable) {
          throw new Error(`Qty retur (${qtyToReturn}) melebihi sisa produk yang dapat diretur (${remainingReturnable}).`)
        }

        const refund = Number(targetSaleItem.harga) * qtyToReturn
        totalRefund += refund

        preparedReturnItems.push({
          saleItem: targetSaleItem,
          qty: qtyToReturn,
          harga: Number(targetSaleItem.harga),
          subtotal: refund,
        })
      }

      const returnNumber = await this.generateReturnNumber(tx)

      // Insert return record
      const [saleReturn] = await tx
        .insert(saleReturns)
        .values({
          saleId: sale.id,
          returnNumber,
          totalRefund: String(totalRefund),
          alasan: input.alasan || null,
          status: 'approved',
          processedBy: userId,
        })
        .returning()

      // Insert return items, update returned_qty and restock
      for (const item of preparedReturnItems) {
        await tx.insert(saleReturnItems).values({
          saleReturnId: saleReturn.id,
          productId: item.saleItem.productId,
          qty: item.qty,
          harga: String(item.harga),
          subtotal: String(item.subtotal),
        })

        await tx
          .update(saleItems)
          .set({ returnedQty: item.saleItem.returnedQty + item.qty })
          .where(eq(saleItems.id, item.saleItem.id))

        // Return stock
        await StockService.recordMovement(tx, {
          productId: item.saleItem.productId,
          type: 'return',
          qty: item.qty,
          referenceType: 'sale_return',
          referenceId: saleReturn.id,
          keterangan: `Retur ${saleReturn.returnNumber}`,
          userId,
        })
      }

      // Check if all items are fully returned
      const updatedSaleItems = await tx
        .select()
        .from(saleItems)
        .where(eq(saleItems.saleId, saleId))

      const isAllReturned = updatedSaleItems.every((i) => i.returnedQty >= i.qty)
      const newStatus = isAllReturned ? 'returned' : 'partial_return'

      await tx
        .update(sales)
        .set({ status: newStatus, updatedAt: new Date() })
        .where(eq(sales.id, saleId))

      return {
        ...saleReturn,
        status: newStatus,
      }
    })
  }
}
