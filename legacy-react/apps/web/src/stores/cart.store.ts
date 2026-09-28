import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { Product } from '@stockku/shared'

export interface CartItem {
  product: Product
  qty: number
  harga: number
  diskon: number
  subtotal: number
}

interface CartState {
  items: CartItem[]
  headerDiskon: number
  paymentMethod: string
  catatan: string
  addItem: (product: Product, qty?: number) => void
  removeItem: (productId: string) => void
  updateQty: (productId: string, qty: number) => void
  updateItemDiskon: (productId: string, diskon: number) => void
  setHeaderDiskon: (diskon: number) => void
  setPaymentMethod: (method: string) => void
  setCatatan: (catatan: string) => void
  clearCart: () => void
  getSubtotal: () => number
  getGrandTotal: () => number
  getTotalItemCount: () => number
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      items: [],
      headerDiskon: 0,
      paymentMethod: 'cash',
      catatan: '',

      addItem: (product: Product, addQty = 1) => {
        const currentItems = get().items
        const existingIndex = currentItems.findIndex((item) => item.product.id === product.id)

        if (existingIndex > -1) {
          const item = currentItems[existingIndex]
          const newQty = item.qty + addQty

          // Do not exceed available stock
          if (newQty > product.stok) {
            return
          }

          // Check for wholesale/grosir tier price
          let effectivePrice = Number(product.hargaJual)
          if (product.grosirTiers && Array.isArray(product.grosirTiers)) {
            const matchedTier = [...product.grosirTiers]
              .sort((a, b) => b.minQty - a.minQty)
              .find((t) => newQty >= t.minQty)
            if (matchedTier) {
              effectivePrice = matchedTier.harga
            }
          }

          const updatedItems = [...currentItems]
          const subtotal = Math.max(0, effectivePrice * newQty - item.diskon)
          updatedItems[existingIndex] = {
            ...item,
            qty: newQty,
            harga: effectivePrice,
            subtotal,
          }

          set({ items: updatedItems })
        } else {
          if (product.stok < addQty) {
            return
          }

          let effectivePrice = Number(product.hargaJual)
          if (product.grosirTiers && Array.isArray(product.grosirTiers)) {
            const matchedTier = [...product.grosirTiers]
              .sort((a, b) => b.minQty - a.minQty)
              .find((t) => addQty >= t.minQty)
            if (matchedTier) {
              effectivePrice = matchedTier.harga
            }
          }

          const newItem: CartItem = {
            product,
            qty: addQty,
            harga: effectivePrice,
            diskon: 0,
            subtotal: effectivePrice * addQty,
          }

          set({ items: [...currentItems, newItem] })
        }
      },

      removeItem: (productId: string) => {
        set({ items: get().items.filter((item) => item.product.id !== productId) })
      },

      updateQty: (productId: string, qty: number) => {
        if (qty <= 0) {
          get().removeItem(productId)
          return
        }

        const currentItems = get().items
        const target = currentItems.find((i) => i.product.id === productId)
        if (!target) return

        const clampedQty = Math.min(qty, target.product.stok)

        let effectivePrice = Number(target.product.hargaJual)
        if (target.product.grosirTiers && Array.isArray(target.product.grosirTiers)) {
          const matchedTier = [...target.product.grosirTiers]
            .sort((a, b) => b.minQty - a.minQty)
            .find((t) => clampedQty >= t.minQty)
          if (matchedTier) {
            effectivePrice = matchedTier.harga
          }
        }

        const subtotal = Math.max(0, effectivePrice * clampedQty - target.diskon)

        set({
          items: currentItems.map((item) =>
            item.product.id === productId
              ? { ...item, qty: clampedQty, harga: effectivePrice, subtotal }
              : item
          ),
        })
      },

      updateItemDiskon: (productId: string, diskon: number) => {
        const safeDiskon = Math.max(0, diskon)
        set({
          items: get().items.map((item) => {
            if (item.product.id === productId) {
              const subtotal = Math.max(0, item.harga * item.qty - safeDiskon)
              return { ...item, diskon: safeDiskon, subtotal }
            }
            return item
          }),
        })
      },

      setHeaderDiskon: (diskon: number) => {
        const subtotal = get().getSubtotal()
        set({ headerDiskon: Math.min(Math.max(0, diskon), subtotal) })
      },

      setPaymentMethod: (method: string) => set({ paymentMethod: method }),
      setCatatan: (catatan: string) => set({ catatan }),

      clearCart: () => set({ items: [], headerDiskon: 0, catatan: '', paymentMethod: 'cash' }),

      getSubtotal: () => {
        return get().items.reduce((sum, item) => sum + item.subtotal, 0)
      },

      getGrandTotal: () => {
        const subtotal = get().getSubtotal()
        const diskon = Math.min(get().headerDiskon, subtotal)
        return Math.max(0, subtotal - diskon)
      },

      getTotalItemCount: () => {
        return get().items.reduce((sum, item) => sum + item.qty, 0)
      },
    }),
    {
      name: 'stockku_pos_cart',
    }
  )
)
