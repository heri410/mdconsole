<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-4 focus:ring-blue-400 focus:ring-opacity-50 shadow-xl hover:shadow-2xl transition-all duration-200 transform hover:scale-105']) }}>
    {{ $slot }}
</button>
