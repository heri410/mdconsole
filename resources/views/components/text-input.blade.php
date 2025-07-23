@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-white/20 bg-black/30 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-blue-400 focus:ring-2 focus:ring-opacity-50 rounded-lg shadow-lg']) }}>
