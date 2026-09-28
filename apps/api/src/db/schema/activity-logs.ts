import { pgTable, uuid, varchar, text, numeric, timestamp } from 'drizzle-orm/pg-core'
import { users } from './users'
import { products } from './products'

export const activityLogs = pgTable('activity_logs', {
  id: uuid('id').primaryKey().defaultRandom(),
  userId: uuid('user_id').references(() => users.id, { onDelete: 'set null' }),
  role: varchar('role', { length: 50 }),
  action: varchar('action', { length: 100 }).notNull(),
  description: text('description').notNull(),
  ipAddress: varchar('ip_address', { length: 45 }),
  userAgent: text('user_agent'),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})

export const priceChangeLogs = pgTable('price_change_logs', {
  id: uuid('id').primaryKey().defaultRandom(),
  productId: uuid('product_id').notNull().references(() => products.id, { onDelete: 'cascade' }),
  hargaLama: numeric('harga_lama', { precision: 15, scale: 2 }).notNull(),
  hargaBaru: numeric('harga_baru', { precision: 15, scale: 2 }).notNull(),
  sumber: varchar('sumber', { length: 50 }).notNull(),
  referenceType: varchar('reference_type', { length: 100 }),
  referenceId: varchar('reference_id', { length: 100 }),
  userId: uuid('user_id').references(() => users.id, { onDelete: 'set null' }),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})
