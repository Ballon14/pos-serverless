<x-app-layout>
@section('title', 'Pengaturan Profil')
<x-slot name="header">
    <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Pengaturan Profil</h2>
</x-slot>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Profile Info Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-indigo-500/30">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">{{ auth()->user()->name }}</h3>
                    <p class="text-sm text-slate-500">{{ auth()->user()->email }} · <span class="capitalize text-indigo-600 font-medium">{{ auth()->user()->roles->first()->name ?? 'User' }}</span></p>
                </div>
            </div>
        </div>
        <form method="post" action="{{ route('profile.update') }}" class="p-5 sm:p-6 space-y-5">
            @csrf
            @method('patch')

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required autofocus autocomplete="name"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 transition-colors" />
                @error('name')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="username"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 transition-colors" />
                @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-sm text-amber-700">
                        Email Anda belum diverifikasi.
                        <button form="send-verification" class="underline font-semibold text-amber-800 hover:text-amber-900">Kirim ulang link verifikasi.</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 text-sm font-medium text-emerald-600">Link verifikasi baru telah dikirim ke email Anda.</p>
                    @endif
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">
                    Simpan Perubahan
                </button>
                @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm font-medium text-emerald-600">
                    ✓ Tersimpan!
                </p>
                @endif
            </div>
        </form>
        <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">@csrf</form>
    </div>

    <!-- Change Password Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Ubah Password</h3>
                    <p class="text-xs text-slate-500">Gunakan password minimal 8 karakter untuk keamanan akun Anda</p>
                </div>
            </div>
        </div>
        <form method="post" action="{{ route('password.update') }}" class="p-5 sm:p-6 space-y-5">
            @csrf
            @method('put')

            <div>
                <label for="update_password_current_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Saat Ini</label>
                <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 transition-colors" placeholder="Masukkan password saat ini" />
                @error('current_password', 'updatePassword')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru</label>
                <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 transition-colors" placeholder="Minimal 8 karakter" />
                @error('password', 'updatePassword')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 transition-colors" placeholder="Ketik ulang password baru" />
                @error('password_confirmation', 'updatePassword')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50 transition-all">
                    Perbarui Password
                </button>
                @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm font-medium text-emerald-600">
                    ✓ Password berhasil diubah!
                </p>
                @endif
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-sm font-semibold text-indigo-800">Lupa Password?</p>
                <p class="text-xs text-indigo-600 mt-1">Jika Anda lupa password dan tidak bisa login, hubungi Admin untuk mereset password akun Anda.</p>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
