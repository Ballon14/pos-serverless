@php
    $flashes = collect();
    foreach (['success', 'error', 'warning'] as $type) {
        if (session($type)) {
            $flashes->push(['type' => $type, 'message' => session($type)]);
        }
    }
@endphp

@if($flashes->isNotEmpty())
<div class="flash-overlay fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50">
    <div class="w-full max-w-sm space-y-3 max-h-[85vh] overflow-y-auto">
        @foreach($flashes as $flash)
        <div class="flash-modal pointer-events-auto bg-white rounded-3xl shadow-2xl shadow-slate-900/30 overflow-hidden">
            <div class="p-6 text-center">
                <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center mb-4 {{ $flash['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : ($flash['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600') }}">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        @if($flash['type'] === 'success')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        @elseif($flash['type'] === 'warning')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        @endif
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $flash['type'] === 'success' ? 'Berhasil' : ($flash['type'] === 'warning' ? 'Perhatian' : 'Gagal') }}</h3>
                <p class="text-sm text-slate-500 leading-relaxed">{{ $flash['message'] }}</p>
            </div>
            <div class="px-6 pb-6">
                <button type="button" class="w-full py-2.5 rounded-xl font-semibold text-white shadow-lg transition-all hover:brightness-110 active:scale-[0.98] {{ $flash['type'] === 'success' ? 'bg-emerald-600 shadow-emerald-500/30' : ($flash['type'] === 'warning' ? 'bg-amber-500 shadow-amber-500/30' : 'bg-red-500 shadow-red-500/30') }}" onclick="closeFlashModal(this)">
                    {{ $flash['type'] === 'warning' ? 'Mengerti' : 'OK' }}
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .flash-modal { animation: flash-pop .25s ease-out; }
    @keyframes flash-pop {
        from { opacity: 0; transform: scale(.92) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>
<script>
    // Hapus kartu modal, lalu bersihkan SEMUA overlay yang sudah tidak berisi kartu
    // agar tidak ada backdrop kosong yang memblokir halaman.
    function closeFlashModal(button) {
        button.closest('.flash-modal')?.remove();
        document.querySelectorAll('.flash-overlay').forEach(function (overlay) {
            if (!overlay.querySelector('.flash-modal')) {
                overlay.remove();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.flash-modal').forEach(function (m) { m.remove(); });
            document.querySelectorAll('.flash-overlay').forEach(function (overlay) {
                if (!overlay.querySelector('.flash-modal')) overlay.remove();
            });
        }
    });
</script>
@endif