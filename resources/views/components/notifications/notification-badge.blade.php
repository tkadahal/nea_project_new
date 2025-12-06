@props(['count' => 0, 'max' => 99])

@php
    $displayCount = $count > $max ? $max . '+' : (string) $count;
@endphp

<span class="relative inline-block">
    {{ $slot }}
    @if ($count > 0)
        <span
            class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/2 -translate-y-1/2">
            {{ $displayCount }}
        </span>
    @else
        <span
            class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full transform translate-x-1/2 -translate-y-1/2"></span>
    @endif
</span>
