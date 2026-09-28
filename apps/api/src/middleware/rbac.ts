import { createMiddleware } from 'hono/factory'
import type { Bindings, Variables } from '../types'
import type { Role } from '@stockku/shared'

export function requireRole(allowedRoles: Role[]) {
  return createMiddleware<{ Bindings: Bindings; Variables: Variables }>(async (c, next) => {
    const userRole = c.get('userRole')
    if (!userRole || !allowedRoles.includes(userRole)) {
      return c.json({ error: 'Akses ditolak: Anda tidak memiliki izin untuk tindakan ini' }, 403)
    }
    await next()
  })
}
