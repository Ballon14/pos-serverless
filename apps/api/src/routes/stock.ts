import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { recordStockMovementSchema } from '@stockku/shared'
import { StockService } from '../services/stock.service'
import { requireRole } from '../middleware/rbac'
import { requireAttendance } from '../middleware/attendance-gate'
import { getDb } from '../db/client'
import { stockMovements, products } from '../db/schema'
import { desc, eq } from 'drizzle-orm'
import type { Bindings, Variables } from '../types'

const stock = new Hono<{ Bindings: Bindings; Variables: Variables }>()

stock.get('/movements', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const { productId, limit = '50' } = c.req.query()

  let query = db
    .select({
      id: stockMovements.id,
      productId: stockMovements.productId,
      productName: products.name,
      sku: products.sku,
      type: stockMovements.type,
      qty: stockMovements.qty,
      stokSebelum: stockMovements.stokSebelum,
      stokSesudah: stockMovements.stokSesudah,
      keterangan: stockMovements.keterangan,
      createdAt: stockMovements.createdAt,
    })
    .from(stockMovements)
    .innerJoin(products, eq(stockMovements.productId, products.id))
    .orderBy(desc(stockMovements.createdAt))
    .limit(parseInt(limit, 10))

  const results = await query
  return c.json({ data: results })
})

stock.post(
  '/adjustment',
  requireRole(['admin', 'manager']),
  requireAttendance,
  zValidator('json', recordStockMovementSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const movement = await db.transaction(async (tx) => {
        return await StockService.recordMovement(tx, {
          productId: data.productId,
          type: data.type,
          qty: data.qty,
          referenceType: data.referenceType || 'manual_adjustment',
          referenceId: data.referenceId || null,
          keterangan: data.keterangan || 'Penyesuaian stok manual',
          userId,
        })
      })

      return c.json({ data: movement, message: 'Mutasi stok berhasil dicatat' }, 201)
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

export { stock as stockRoutes }
