@props([
    'active' => false,
    'href' => '#',
    'icon' => null,
])

<li>
    <a href="{{ $href }}"
        class="js-sidebar-link flex items-center px-3 py-2 text-sm rounded-md transition-colors duration-200 {{ $active ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground text-sidebar-foreground' }}">
        @svg($icon, $active ? 'w-5 h-5 text-white' : 'w-5 h-5 text-gray-500')
        <span class="js-link-label ml-3 transition-opacity duration-300 opacity-100">{{ $slot }}</span>
    </a>
</li>
