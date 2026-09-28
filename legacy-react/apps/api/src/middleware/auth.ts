import { createMiddleware } from 'hono/factory'
import { createClient } from '@supabase/supabase-js'
import { eq } from 'drizzle-orm'
import { getDb } from '../db/client'
import { users } from '../db/schema'
import type { Bindings, Variables } from '../types'
import type { Role } from '@stockku/shared'

export const authMiddleware = createMiddleware<{ Bindings: Bindings; Variables: Variables }>(
  async (c, next) => {
    const authHeader = c.req.header('Authorization')
    if (!authHeader?.startsWith('Bearer ')) {
      return c.json({ error: 'Unauthorized: Token tidak ditemukan' }, 401)
    }

    const token = authHeader.slice(7)
    const supabase = createClient(c.env.SUPABASE_URL, c.env.SUPABASE_ANON_KEY)

    const {
      data: { user: authUser },
      error,
    } = await supabase.auth.getUser(token)

    if (error || !authUser) {
      return c.json({ error: 'Unauthorized: Sesi kadaluarsa atau tidak valid' }, 401)
    }

    const db = getDb(c.env.DATABASE_URL)
    const [profile] = await db
      .select()
      .from(users)
      .where(eq(users.id, authUser.id))
      .limit(1)

    if (!profile) {
      return c.json({ error: 'Profil pengguna tidak ditemukan' }, 403)
    }

    if (!profile.isActive) {
      return c.json({ error: 'Akun Anda dinonaktifkan. Hubungi administrator.' }, 403)
    }

    c.set('userId', profile.id)
    c.set('userRole', profile.role as Role)
    c.set('user', {
      id: profile.id,
      name: profile.name,
      email: profile.email,
      role: profile.role as Role,
      isActive: profile.isActive,
      createdAt: profile.createdAt.toISOString(),
      updatedAt: profile.updatedAt.toISOString(),
    })

    await next()
  }
)
