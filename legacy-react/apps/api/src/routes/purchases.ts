import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { createPurchaseSchema } from '@stockku/shared'
import { PurchaseService } from '../services/purchase.service'
import { requireRole } from '../middleware/rbac'
import { requireAttendance } from '../middleware/attendance-gate'
import { getDb } from '../db/client'
import { purchases, purchaseItems, suppliers } from '../db/schema'
import { desc, eq } from 'drizzle-orm'
import type { Bindings, Variables } from '../types'

const purchasesRoute = new Hono<{ Bindings: Bindings; Variables: Variables }>()

purchasesRoute.get('/', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const list = await db
    .select({
      id: purchases.id,
      invoiceNumber: purchases.invoiceNumber,
      supplierName: suppliers.name,
      tanggal: purchases.tanggal,
      total: purchases.total,
      status: purchases.status,
      keterangan: purchases.keterangan,
      createdAt: purchases.createdAt,
    })
    .from(purchases)
    .innerJoin(suppliers, eq(purchases.supplierId, suppliers.id))
    .orderBy(desc(purchases.createdAt))
    .limit(50)

  return c.json({ data: list })
})

purchasesRoute.post(
  '/',
  requireRole(['admin', 'manager']),
  requireAttendance,
  zValidator('json', createPurchaseSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const purchase = await PurchaseService.createPurchase(db, data, userId)
      return c.json({ data: purchase, message: 'Pembelian berhasil dicatat dan stok bertambah' }, 201)
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

export { purchasesRoute }
