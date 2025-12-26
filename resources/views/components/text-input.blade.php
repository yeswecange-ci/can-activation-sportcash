@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-can-green-500 focus:ring-can-green-500 rounded-lg shadow-sm text-gray-900 placeholder-gray-400']) }}>
