import { pgTable, uuid, varchar, numeric, integer, text, timestamp } from 'drizzle-orm/pg-core'
import { users } from './users'
import { products } from './products'

export const sales = pgTable('sales', {
  id: uuid('id').primaryKey().defaultRandom(),
  invoiceNumber: varchar('invoice_number', { length: 50 }).notNull().unique(),
  userId: uuid('user_id').notNull().references(() => users.id, { onDelete: 'restrict' }),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull().default('0'),
  diskon: numeric('diskon', { precision: 15, scale: 2 }).notNull().default('0'),
  grandTotal: numeric('grand_total', { precision: 15, scale: 2 }).notNull().default('0'),
  bayar: numeric('bayar', { precision: 15, scale: 2 }).notNull().default('0'),
  kembalian: numeric('kembalian', { precision: 15, scale: 2 }).notNull().default('0'),
  paymentMethod: varchar('payment_method', { length: 50 }).notNull().default('cash'),
  status: varchar('status', { length: 50 }).notNull().default('completed'),
  catatan: text('catatan'),
  sumber: varchar('sumber', { length: 50 }),
  offlineId: varchar('offline_id', { length: 100 }).unique(),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp('updated_at', { withTimezone: true }).notNull().defaultNow(),
})

export const saleItems = pgTable('sale_items', {
  id: uuid('id').primaryKey().defaultRandom(),
  saleId: uuid('sale_id').notNull().references(() => sales.id, { onDelete: 'cascade' }),
  productId: uuid('product_id').notNull().references(() => products.id, { onDelete: 'restrict' }),
  qty: integer('qty').notNull(),
  returnedQty: integer('returned_qty').notNull().default(0),
  harga: numeric('harga', { precision: 15, scale: 2 }).notNull(),
  hargaBeli: numeric('harga_beli', { precision: 15, scale: 2 }).notNull().default('0'),
  diskon: numeric('diskon', { precision: 15, scale: 2 }).notNull().default('0'),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull(),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})

export const saleReturns = pgTable('sale_returns', {
  id: uuid('id').primaryKey().defaultRandom(),
  saleId: uuid('sale_id').notNull().references(() => sales.id, { onDelete: 'cascade' }),
  returnNumber: varchar('return_number', { length: 50 }).notNull().unique(),
  totalRefund: numeric('total_refund', { precision: 15, scale: 2 }).notNull().default('0'),
  alasan: text('alasan'),
  status: varchar('status', { length: 50 }).notNull().default('approved'),
  processedBy: uuid('processed_by').references(() => users.id, { onDelete: 'set null' }),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp('updated_at', { withTimezone: true }).notNull().defaultNow(),
})

export const saleReturnItems = pgTable('sale_return_items', {
  id: uuid('id').primaryKey().defaultRandom(),
  saleReturnId: uuid('sale_return_id').notNull().references(() => saleReturns.id, { onDelete: 'cascade' }),
  productId: uuid('product_id').notNull().references(() => products.id, { onDelete: 'restrict' }),
  qty: integer('qty').notNull(),
  harga: numeric('harga', { precision: 15, scale: 2 }).notNull(),
  subtotal: numeric('subtotal', { precision: 15, scale: 2 }).notNull(),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
})
