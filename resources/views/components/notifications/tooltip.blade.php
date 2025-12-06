{{-- resources/views/components/tooltip.blade.php --}}
@props([
    'text',
    'position' => 'top', // top, right, bottom, left
    'delay' => 0,
])

@php
    $positionClasses = [
        'top' => 'bottom-full left-1/2 transform -translate-x-1/2 mb-2',
        'right' => 'left-full top-1/2 transform -translate-y-1/2 ml-2',
        'bottom' => 'top-full left-1/2 transform -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 transform -translate-y-1/2 mr-2',
    ];

    $arrowClasses = [
        'top' =>
            'top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700',
        'right' =>
            'right-full top-1/2 transform -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700',
        'bottom' =>
            'bottom-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-b-gray-900 dark:border-b-gray-700',
        'left' =>
            'left-full top-1/2 transform -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-gray-700',
    ];
@endphp

<div x-data="{ tooltip: false }" class="relative inline-block">
    <div @mouseenter="setTimeout(() => tooltip = true, {{ $delay }})" @mouseleave="tooltip = false"
        {{ $attributes }}>
        {{ $slot }}
    </div>

    <div x-show="tooltip" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute {{ $positionClasses[$position] }} px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-md shadow-lg z-50 whitespace-nowrap">
        {{ $text }}
        <div class="absolute {{ $arrowClasses[$position] }}"></div>
    </div>
</div>
