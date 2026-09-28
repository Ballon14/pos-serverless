import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { createSaleSchema, processReturnSchema } from '@stockku/shared'
import { SaleService } from '../services/sale.service'
import { requireRole } from '../middleware/rbac'
import { requireAttendance } from '../middleware/attendance-gate'
import { getDb } from '../db/client'
import { sales, saleItems } from '../db/schema'
import { desc, eq } from 'drizzle-orm'
import type { Bindings, Variables } from '../types'

const salesRoute = new Hono<{ Bindings: Bindings; Variables: Variables }>()

salesRoute.get('/', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const list = await db
    .select()
    .from(sales)
    .orderBy(desc(sales.createdAt))
    .limit(50)

  return c.json({ data: list })
})

salesRoute.get('/:id', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const id = c.req.param('id')

  const [sale] = await db.select().from(sales).where(eq(sales.id, id)).limit(1)
  if (!sale) {
    return c.json({ error: 'Transaksi tidak ditemukan' }, 404)
  }

  const items = await db.select().from(saleItems).where(eq(saleItems.saleId, id))

  return c.json({ data: { ...sale, items } })
})

salesRoute.post(
  '/',
  requireRole(['admin', 'kasir']),
  requireAttendance,
  zValidator('json', createSaleSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const sale = await SaleService.createSale(db, data, userId)
      return c.json({ data: sale, message: 'Transaksi berhasil disimpan' }, 201)
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

salesRoute.post(
  '/:id/return',
  requireRole(['admin', 'manager']),
  requireAttendance,
  zValidator('json', processReturnSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const id = c.req.param('id')
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const result = await SaleService.processReturn(db, id, data, userId)
      return c.json({ data: result, message: 'Retur transaksi berhasil diproses' })
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

// Offline sync endpoint
salesRoute.post('/sync', requireRole(['admin', 'kasir']), requireAttendance, async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const userId = c.get('userId')
  const body = await c.req.json()
  const salesList = Array.isArray(body) ? body : body.sales

  if (!Array.isArray(salesList)) {
    return c.json({ error: 'Payload harus berupa array penjualan' }, 400)
  }

  const results = []
  const errors = []

  for (const item of salesList) {
    try {
      const validated = createSaleSchema.parse(item)
      const created = await SaleService.createSale(db, validated, userId)
      results.push({ offlineId: item.offlineId, saleId: created.id, status: 'synced' })
    } catch (err: any) {
      errors.push({ offlineId: item.offlineId, error: err.message })
    }
  }

  return c.json({ synced: results, errors })
})

export { salesRoute }
