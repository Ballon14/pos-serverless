import { Hono } from 'hono'
import { ReportService } from '../services/report.service'
import { requireRole } from '../middleware/rbac'
import { getDb } from '../db/client'
import type { Bindings, Variables } from '../types'

const reports = new Hono<{ Bindings: Bindings; Variables: Variables }>()

// Reports accessible by Admin & Manager
reports.use('*', requireRole(['admin', 'manager']))

reports.get('/sales', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const { startDate, endDate } = c.req.query()
  const summary = await ReportService.getSalesSummary(db, startDate, endDate)
  return c.json({ data: summary })
})

reports.get('/profit-loss', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const { startDate, endDate } = c.req.query()
  const profitLoss = await ReportService.getProfitLoss(db, startDate, endDate)
  return c.json({ data: profitLoss })
})

reports.get('/top-products', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const { limit } = c.req.query()
  const items = await ReportService.getTopProducts(db, limit ? parseInt(limit, 10) : 10)
  return c.json({ data: items })
})

export { reports as reportRoutes }
