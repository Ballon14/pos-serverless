import { describe, it, expect, vi } from 'vitest'
import { StockService } from '../services/stock.service'

describe('StockService Data Integrity Tests', () => {
  it('throws when qty is 0', async () => {
    const mockTx = {
      select: vi.fn(),
    }

    await expect(
      StockService.recordMovement(mockTx as any, {
        productId: 'prod-1',
        type: 'out',
        qty: 0,
      })
    ).rejects.toThrow('Jumlah mutasi stok tidak boleh 0.')
  })

  it('throws when non-adjustment qty is negative', async () => {
    const mockTx = {
      select: vi.fn(),
    }

    await expect(
      StockService.recordMovement(mockTx as any, {
        productId: 'prod-1',
        type: 'out',
        qty: -5,
      })
    ).rejects.toThrow('Jumlah mutasi stok harus lebih dari 0.')
  })

  it('throws when type = out would drive stock below zero', async () => {
    const mockTx = {
      select: vi.fn().mockReturnValue({
        from: vi.fn().mockReturnValue({
          where: vi.fn().mockReturnValue({
            for: vi.fn().mockResolvedValue([{ id: 'prod-1', name: 'Kopi', stok: 5 }]),
          }),
        }),
      }),
    }

    await expect(
      StockService.recordMovement(mockTx as any, {
        productId: 'prod-1',
        type: 'out',
        qty: 10, // Requesting 10 when only 5 in stock
      })
    ).rejects.toThrow('Stok Kopi tidak mencukupi untuk pengurangan 10 (tersisa 5).')
  })
})
