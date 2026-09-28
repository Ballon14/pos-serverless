import { eq, ilike, or, and, desc, sql } from 'drizzle-orm'
import { products, categories, priceChangeLogs } from '../db/schema'
import type { Database } from '../db/client'
import type { CreateProductInput, UpdateProductInput } from '@stockku/shared'

export class ProductService {
  static async list(db: Database, options: { search?: string; categoryId?: string; isLowStock?: boolean; limit?: number; offset?: number }) {
    const { search, categoryId, isLowStock, limit = 50, offset = 0 } = options

    const conditions = []
    if (search) {
      conditions.push(or(ilike(products.name, `%${search}%`), ilike(products.sku, `%${search}%`)))
    }
    if (categoryId) {
      conditions.push(eq(products.categoryId, categoryId))
    }
    if (isLowStock) {
      conditions.push(sql`${products.stok} <= ${products.minStok}`)
    }

    const whereClause = conditions.length > 0 ? and(...conditions) : undefined

    const items = await db
      .select({
        id: products.id,
        categoryId: products.categoryId,
        categoryName: categories.name,
        name: products.name,
        sku: products.sku,
        hargaBeli: products.hargaBeli,
        hargaJual: products.hargaJual,
        grosirTiers: products.grosirTiers,
        stok: products.stok,
        minStok: products.minStok,
        satuan: products.satuan,
        foto: products.foto,
        deskripsi: products.deskripsi,
        isActive: products.isActive,
        createdAt: products.createdAt,
        updatedAt: products.updatedAt,
      })
      .from(products)
      .leftJoin(categories, eq(products.categoryId, categories.id))
      .where(whereClause)
      .orderBy(desc(products.createdAt))
      .limit(limit)
      .offset(offset)

    return items
  }

  static async getById(db: Database, id: string) {
    const [item] = await db
      .select()
      .from(products)
      .where(eq(products.id, id))
      .limit(1)

    return item || null
  }

  static async getBySku(db: Database, sku: string) {
    const [item] = await db
      .select()
      .from(products)
      .where(sql`LOWER(${products.sku}) = LOWER(${sku})`)
      .limit(1)

    return item || null
  }

  static async create(db: Database, input: CreateProductInput, userId?: string) {
    const [created] = await db
      .insert(products)
      .values({
        categoryId: input.categoryId,
        name: input.name,
        sku: input.sku,
        hargaBeli: String(input.hargaBeli),
        hargaJual: String(input.hargaJual),
        grosirTiers: input.grosirTiers || [],
        stok: input.stok,
        minStok: input.minStok,
        satuan: input.satuan,
        foto: input.foto || null,
        deskripsi: input.deskripsi || null,
        isActive: input.isActive ?? true,
      })
      .returning()

    return created
  }

  static async update(db: Database, id: string, input: UpdateProductInput, userId?: string) {
    return await db.transaction(async (tx) => {
      const [existing] = await tx.select().from(products).where(eq(products.id, id)).for('update')
      if (!existing) {
        throw new Error(`Produk dengan ID ${id} tidak ditemukan.`)
      }

      // Check if price changed -> log price change
      if (input.hargaJual !== undefined && Number(existing.hargaJual) !== input.hargaJual) {
        await tx.insert(priceChangeLogs).values({
          productId: id,
          hargaLama: existing.hargaJual,
          hargaBaru: String(input.hargaJual),
          sumber: 'manual_edit',
          userId: userId || null,
        })
      }

      const updateData: Record<string, any> = { updatedAt: new Date() }
      if (input.name !== undefined) updateData.name = input.name
      if (input.categoryId !== undefined) updateData.categoryId = input.categoryId
      if (input.sku !== undefined) updateData.sku = input.sku
      if (input.hargaBeli !== undefined) updateData.hargaBeli = String(input.hargaBeli)
      if (input.hargaJual !== undefined) updateData.hargaJual = String(input.hargaJual)
      if (input.grosirTiers !== undefined) updateData.grosirTiers = input.grosirTiers
      if (input.minStok !== undefined) updateData.minStok = input.minStok
      if (input.satuan !== undefined) updateData.satuan = input.satuan
      if (input.foto !== undefined) updateData.foto = input.foto
      if (input.deskripsi !== undefined) updateData.deskripsi = input.deskripsi
      if (input.isActive !== undefined) updateData.isActive = input.isActive

      const [updated] = await tx.update(products).set(updateData).where(eq(products.id, id)).returning()
      return updated
    })
  }
}
