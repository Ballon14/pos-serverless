<x-app-layout>
@section('title', 'Tambah Supplier')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">Tambah Supplier</h2></x-slot>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form method="POST" action="{{ route('suppliers.store') }}">@csrf
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama <span class="text-red-500">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-200 text-sm" required>@error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Kode <span class="text-red-500">*</span></label><input type="text" name="code" value="{{ old('code') }}" class="w-full rounded-xl border-slate-200 text-sm" required>@error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label><input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Contact Person</label><input type="text" name="contact_person" value="{{ old('contact_person') }}" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label><textarea name="address" rows="3" class="w-full rounded-xl border-slate-200 text-sm">{{ old('address') }}</textarea></div>
            </div>
            <div class="flex gap-3 mt-6">
                <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium">Batal</a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
