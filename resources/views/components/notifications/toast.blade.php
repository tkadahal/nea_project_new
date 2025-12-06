{{-- resources/views/components/toast.blade.php --}}
@props([
    'type' => 'success',
    'duration' => 3000,
    'position' => 'bottom-right',
])

@php
    $positionClasses = [
        'top-right' => 'fixed top-4 right-4',
        'top-left' => 'fixed top-4 left-4',
        'bottom-right' => 'fixed bottom-4 right-4',
        'bottom-left' => 'fixed bottom-4 left-4',
    ];

    $typeClasses = [
        'success' => 'bg-white dark:bg-gray-800 border-green-200 dark:border-green-700',
        'error' => 'bg-white dark:bg-gray-800 border-red-200 dark:border-red-700',
        'warning' => 'bg-white dark:bg-gray-800 border-yellow-200 dark:border-yellow-700',
        'info' => 'bg-white dark:bg-gray-800 border-blue-200 dark:border-blue-700',
    ];

    $iconClasses = [
        'success' => 'text-green-500 dark:text-green-400',
        'error' => 'text-red-500 dark:text-red-400',
        'warning' => 'text-yellow-500 dark:text-yellow-400',
        'info' => 'text-blue-500 dark:text-blue-400',
    ];
@endphp

<div x-data="{
    show: true,
    init() {
        setTimeout(() => this.show = false, {{ $duration }})
    }
}" x-show="show" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="{{ $positionClasses[$position] }} max-w-xs rounded-lg shadow-lg border {{ $typeClasses[$type] }} z-50">
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                @if ($type === 'success')
                    <svg class="h-6 w-6 {{ $iconClasses[$type] }}" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @elseif($type === 'error')
                    <svg class="h-6 w-6 {{ $iconClasses[$type] }}" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $slot }}</p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="show = false"
                    class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
