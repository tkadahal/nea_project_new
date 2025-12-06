@props(['title', 'color' => 'gray'])

@php
    $colorStyles = [
        'gray' => 'bg-gray-50 text-gray-600 ring-1 ring-gray-500/10',
        'red' => 'bg-red-50 text-red-700 ring-1 ring-red-600/10',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-600/20',
        'green' => 'bg-green-50 text-green-700 ring-1 ring-green-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-700/10',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10',
        'purple' => 'bg-purple-50 text-purple-700 ring-1 ring-purple-700/10',
        'pink' => 'bg-pink-50 text-pink-700 ring-1 ring-pink-700/10',
    ];

    // Fallback to custom style if color is a hex code
    $selectedStyle = isset($colorStyles[$color]) ? $colorStyles[$color] : "bg-[$color] text-white ring-1 ring-[$color]/20";
@endphp

<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-inset {{ $selectedStyle }}">
    {{ $title }}
</span>