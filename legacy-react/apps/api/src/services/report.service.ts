import { sql, and, gte, lte, eq } from 'drizzle-orm'
import { sales, saleItems, products } from '../db/schema'
import type { Database } from '../db/client'

export class ReportService {
  static async getSalesSummary(db: Database, startDate?: string, endDate?: string) {
    const conditions = [sql`${sales.status} = 'completed'`]
    if (startDate) {
      conditions.push(sql`DATE(${sales.createdAt} AT TIME ZONE 'Asia/Jakarta') >= ${startDate}`)
    }
    if (endDate) {
      conditions.push(sql`DATE(${sales.createdAt} AT TIME ZONE 'Asia/Jakarta') <= ${endDate}`)
    }

    const whereClause = and(...conditions)

    const [summary] = await db
      .select({
        totalTransaksi: sql<number>`count(*)::int`,
        totalSubtotal: sql<number>`coalesce(sum(${sales.subtotal}), 0)::float`,
        totalDiskon: sql<number>`coalesce(sum(${sales.diskon}), 0)::float`,
        totalGrandTotal: sql<number>`coalesce(sum(${sales.grandTotal}), 0)::float`,
      })
      .from(sales)
      .where(whereClause)

    return summary || { totalTransaksi: 0, totalSubtotal: 0, totalDiskon: 0, totalGrandTotal: 0 }
  }

  static async getProfitLoss(db: Database, startDate?: string, endDate?: string) {
    const conditions = [sql`${sales.status} = 'completed'`]
    if (startDate) {
      conditions.push(sql`DATE(${sales.createdAt} AT TIME ZONE 'Asia/Jakarta') >= ${startDate}`)
    }
    if (endDate) {
      conditions.push(sql`DATE(${sales.createdAt} AT TIME ZONE 'Asia/Jakarta') <= ${endDate}`)
    }

    const whereClause = and(...conditions)

    const [result] = await db
      .select({
        totalRevenue: sql<number>`coalesce(sum(${sales.grandTotal}), 0)::float`,
        totalCogs: sql<number>`coalesce(sum(${saleItems.hargaBeli} * (${saleItems.qty} - ${saleItems.returnedQty})), 0)::float`,
      })
      .from(sales)
      .innerJoin(saleItems, eq(sales.id, saleItems.saleId))
      .where(whereClause)

    const revenue = result?.totalRevenue || 0
    const cogs = result?.totalCogs || 0
    const grossProfit = revenue - cogs

    return {
      revenue,
      cogs,
      grossProfit,
      marginPercentage: revenue > 0 ? (grossProfit / revenue) * 100 : 0,
    }
  }

  static async getTopProducts(db: Database, limit = 10) {
    return await db
      .select({
        productId: products.id,
        productName: products.name,
        sku: products.sku,
        totalSold: sql<number>`sum(${saleItems.qty} - ${saleItems.returnedQty})::int`,
        totalSales: sql<number>`sum(${saleItems.subtotal})::float`,
      })
      .from(saleItems)
      .innerJoin(products, eq(saleItems.productId, products.id))
      .innerJoin(sales, eq(saleItems.saleId, sales.id))
      .where(sql`${sales.status} = 'completed'`)
      .groupBy(products.id, products.name, products.sku)
      .orderBy(sql`sum(${saleItems.qty} - ${saleItems.returnedQty}) DESC`)
      .limit(limit)
  }
}
