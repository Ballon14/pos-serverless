import { pgTable, uuid, varchar, numeric, integer, text, boolean, jsonb, timestamp } from 'drizzle-orm/pg-core'
import { categories } from './categories'

export const products = pgTable('products', {
  id: uuid('id').primaryKey().defaultRandom(),
  categoryId: uuid('category_id').notNull().references(() => categories.id, { onDelete: 'restrict' }),
  name: varchar('name', { length: 255 }).notNull(),
  sku: varchar('sku', { length: 100 }).notNull().unique(),
  hargaBeli: numeric('harga_beli', { precision: 15, scale: 2 }).notNull().default('0'),
  hargaJual: numeric('harga_jual', { precision: 15, scale: 2 }).notNull().default('0'),
  grosirTiers: jsonb('grosir_tiers').default([]),
  stok: integer('stok').notNull().default(0),
  minStok: integer('min_stok').notNull().default(5),
  satuan: varchar('satuan', { length: 50 }).notNull().default('pcs'),
  foto: text('foto'),
  deskripsi: text('deskripsi'),
  isActive: boolean('is_active').notNull().default(true),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp('updated_at', { withTimezone: true }).notNull().defaultNow(),
})
