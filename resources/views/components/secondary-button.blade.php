<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-6 py-2.5 bg-white border-2 border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:border-can-green-600 hover:text-can-green-600 hover:bg-can-green-50 focus:outline-none focus:ring-2 focus:ring-can-green-500 focus:ring-offset-2 disabled:opacity-25 transition-all duration-200']) }}>
    {{ $slot }}
</button>
