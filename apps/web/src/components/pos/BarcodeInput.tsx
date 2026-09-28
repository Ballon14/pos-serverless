import React, { useState, useRef, useEffect } from 'react'
import { ScanBarcode } from 'lucide-react'

interface BarcodeInputProps {
  onScan: (code: string) => void
  disabled?: boolean
}

export const BarcodeInput: React.FC<BarcodeInputProps> = ({ onScan, disabled }) => {
  const [value, setValue] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      const trimmed = value.trim()
      if (trimmed) {
        onScan(trimmed)
        setValue('')
      }
    }
  }

  // Auto focus input on mount and on clicks
  useEffect(() => {
    inputRef.current?.focus()
  }, [])

  return (
    <div className="relative">
      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
        <ScanBarcode className="w-5 h-5 text-indigo-400" />
      </div>
      <input
        ref={inputRef}
        type="text"
        value={value}
        onChange={(e) => setValue(e.target.value)}
        onKeyDown={handleKeyDown}
        disabled={disabled}
        placeholder="Scan Barcode atau ketik SKU lalu tekan Enter..."
        className="w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner font-mono text-sm"
      />
    </div>
  )
}
