import { pgTable, uuid, varchar, numeric, integer, text, date, timestamp } from 'drizzle-orm/pg-core'
import { suppliers } from './suppliers'
import { users } from './users'
import { products } from './products'

export const purchases = pgTable('purchases', {
  id: uuid('id').primaryKey().defaultRandom(),
  invoiceNumber: varchar('invoice_number', { length: 50 }).notNull().unique(),
  supplierId: uuid('supplier_id').notNull().references(() => suppliers.id, { onDelete: 'restrict' }),
  userId: uuid('user_id').notNull().references(() => users.id, { onDelete: 'restrict' }),
  tanggal: date('tanggal').notNull(),
  total: numeric('total', { precision: 15, scale: 2 }).notNull().default('0'),
  status: varchar('status', { length: 50 }).notNull().default('received'),
  keterangan: text('keterangan'),
  fotoNota: text('foto_nota'),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp('updated_at', { withTimezone: true }).notNull().defaultNow(),
})

export const purchaseItems = pgTable('purchase_items', {
  id: uuid('id').primaryKey().defaultRandom(),
  purchaseId: uuid('purchase_id').notNull().references(() => purchases.id, { onDelete: 'cascade' }),
  productId: uuid('product_id').notNull().references(() => products.id, { onDelete: 'restrict' }),
  qty: integer('qty').notNull(),
  harga: numeric('harga', { precision: 15, scale: 2 }).notNull(),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull(),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})
