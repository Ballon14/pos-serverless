import { Hono } from 'hono'
import { cors } from 'hono/cors'
import { logger } from 'hono/logger'
import { secureHeaders } from 'hono/secure-headers'

import { authMiddleware } from './middleware/auth'
import { authRoutes } from './routes/auth'
import { productRoutes } from './routes/products'
import { salesRoute } from './routes/sales'
import { stockRoutes } from './routes/stock'
import { purchasesRoute } from './routes/purchases'
import { attendanceRoutes } from './routes/attendance'
import { reportRoutes } from './routes/reports'
import { settingsRoute } from './routes/settings'
import type { Bindings, Variables } from './types'

const app = new Hono<{ Bindings: Bindings; Variables: Variables }>()

// Global middlewares
app.use('*', logger())
app.use('*', secureHeaders())
app.use(
  '*',
  cors({
    origin: (origin) => {
      // Allow localhost and production pages.dev domains
      if (!origin || origin.includes('localhost') || origin.endsWith('.pages.dev')) {
        return origin || '*'
      }
      return 'https://stockku.pages.dev'
    },
    credentials: true,
    allowMethods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowHeaders: ['Content-Type', 'Authorization'],
  })
)

// Global Error Handler
app.onError((err, c) => {
  console.error('Unhandled API Error:', err)
  return c.json(
    {
      error: err.message || 'Terjadi kesalahan internal pada server',
    },
    500
  )
})

// Public Health Check
app.get('/health', (c) =>
  c.json({
    status: 'ok',
    service: 'stockku-api',
    environment: c.env.ENVIRONMENT || 'development',
    timestamp: new Date().toISOString(),
  })
)

// Protected API routes
app.use('/api/*', authMiddleware)

app.route('/api/auth', authRoutes)
app.route('/api/products', productRoutes)
app.route('/api/sales', salesRoute)
app.route('/api/stock', stockRoutes)
app.route('/api/purchases', purchasesRoute)
app.route('/api/attendance', attendanceRoutes)
app.route('/api/reports', reportRoutes)
app.route('/api/settings', settingsRoute)

export default app
