<div class="relative" wire:poll.60s>
    <button wire:click="toggleDropdown"
        class="flex items-center p-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
        <x-notifications.notification-badge :count="$unreadCount">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
        </x-notifications.notification-badge>
    </button>

    @if ($showDropdown)
        <div
            class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
            @if (count($comments) > 0)
                @foreach ($comments as $comment)
                    <a href="{{ $comment['url'] }}" wire:click="markAsRead('{{ $comment['id'] }}')"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $comment['read_at'] ? '' : 'font-semibold' }}"
                        wire:navigate>
                        <div class="flex flex-col">
                            <span>{{ $comment['commentable_type'] }}: {{ $comment['commentable_name'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment['message'] }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $comment['created_at'] }}</span>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ __('No new comments.') }}
                </div>
            @endif
        </div>
    @endif
</div>
