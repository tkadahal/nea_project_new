@props(['type' => 'submit', 'class' => ''])
<button type="{{ $type }}"
    {{ $attributes->merge(['class' => 'bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors ' . $class]) }}>
    {{ $slot }}
</button>
