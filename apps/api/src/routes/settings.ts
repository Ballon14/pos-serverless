import { Hono } from 'hono'
import { requireRole } from '../middleware/rbac'
import { getDb } from '../db/client'
import { settings } from '../db/schema'
import { eq } from 'drizzle-orm'
import type { Bindings, Variables } from '../types'

const settingsRoute = new Hono<{ Bindings: Bindings; Variables: Variables }>()

settingsRoute.get('/', async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const allSettings = await db.select().from(settings)
  const map: Record<string, string | null> = {}
  allSettings.forEach((s) => {
    map[s.key] = s.value
  })
  return c.json({ data: map })
})

settingsRoute.put('/', requireRole(['admin']), async (c) => {
  const db = getDb(c.env.DATABASE_URL)
  const body: Record<string, string> = await c.req.json()

  for (const [key, value] of Object.entries(body)) {
    await db
      .insert(settings)
      .values({ key, value })
      .onConflictDoUpdate({
        target: settings.key,
        set: { value, updatedAt: new Date() },
      })
  }

  return c.json({ message: 'Pengaturan berhasil diperbarui' })
})

export { settingsRoute }
