@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl shadow-sm focus:ring-offset-0']) }}>
