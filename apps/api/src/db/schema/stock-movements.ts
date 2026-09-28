import { pgTable, uuid, varchar, integer, text, timestamp } from 'drizzle-orm/pg-core'
import { products } from './products'
import { users } from './users'

export const stockMovements = pgTable('stock_movements', {
  id: uuid('id').primaryKey().defaultRandom(),
  productId: uuid('product_id').notNull().references(() => products.id, { onDelete: 'restrict' }),
  type: varchar('type', { length: 50 }).notNull(), // 'in', 'out', 'return', 'adjustment'
  qty: integer('qty').notNull(),
  stokSebelum: integer('stok_sebelum').notNull(),
  stokSesudah: integer('stok_sesudah').notNull(),
  referenceType: varchar('reference_type', { length: 100 }),
  referenceId: varchar('reference_id', { length: 100 }),
  keterangan: text('keterangan'),
  userId: uuid('user_id').references(() => users.id, { onDelete: 'set null' }),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})
