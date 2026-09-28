import { Hono } from 'hono'
import { zValidator } from '@hono/zod-validator'
import { createProductSchema, updateProductSchema } from '@stockku/shared'
import { ProductService } from '../services/product.service'
import { requireRole } from '../middleware/rbac'
import { requireAttendance } from '../middleware/attendance-gate'
import { getDb } from '../db/client'
import type { Bindings, Variables } from '../types'

const products = new Hono<{ Bindings: Bindings; Variables: Variables }>()

products.get('/', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const { search, categoryId, isLowStock, limit, offset } = c.req.query()

  const items = await ProductService.list(db, {
    search,
    categoryId,
    isLowStock: isLowStock === 'true',
    limit: limit ? parseInt(limit, 10) : 100,
    offset: offset ? parseInt(offset, 10) : 0,
  })

  return c.json({ data: items })
})

products.get('/:id', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const id = c.req.param('id')
  const item = await ProductService.getById(db, id)

  if (!item) {
    return c.json({ error: 'Produk tidak ditemukan' }, 404)
  }

  return c.json({ data: item })
})

products.post(
  '/',
  requireRole(['admin', 'manager']),
  requireAttendance,
  zValidator('json', createProductSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const created = await ProductService.create(db, data, userId)
      return c.json({ data: created, message: 'Produk berhasil ditambahkan' }, 201)
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

products.put(
  '/:id',
  requireRole(['admin', 'manager']),
  requireAttendance,
  zValidator('json', updateProductSchema),
  async (c) => {
    const db = getDb(c.env.DATABASE_URL)
    const id = c.req.param('id')
    const data = c.req.valid('json')
    const userId = c.get('userId')

    try {
      const updated = await ProductService.update(db, id, data, userId)
      return c.json({ data: updated, message: 'Produk berhasil diperbarui' })
    } catch (err: any) {
      return c.json({ error: err.message }, 400)
    }
  }
)

export { products as productRoutes }
