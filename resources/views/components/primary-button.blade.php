<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-2.5 bg-can-green-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-can-green-700 focus:bg-can-green-700 active:bg-can-green-800 focus:outline-none focus:ring-2 focus:ring-can-green-500 focus:ring-offset-2 shadow-sm hover:shadow-md transition-all duration-200']) }}>
    {{ $slot }}
</button>
