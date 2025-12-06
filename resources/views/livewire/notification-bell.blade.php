<div class="relative" wire:poll.60s>
    <button wire:click="toggleDropdown"
        class="flex items-center p-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
        <x-notifications.notification-badge :count="$unreadCount">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </x-notifications.notification-badge>
    </button>

    @if ($showDropdown)
        <div
            class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
            @if (count($notifications) > 0)
                @foreach ($notifications as $notification)
                    <a href="{{ $notification['url'] }}" wire:click="markAsRead('{{ $notification['id'] }}')"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $notification['read_at'] ? '' : 'font-semibold' }}"
                        wire:navigate>
                        <div class="flex flex-col">
                            <span>{{ $notification['task_name'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $notification['message'] }}</span>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500">{{ $notification['created_at'] }}</span>
                        </div>
                    </a>
                @endforeach
                <a href="{{ route('admin.notifications.index') }}" wire:navigate
                    class="block px-2 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    View All Notifications
                </a>
            @else
                <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                    No notifications available.
                </div>
            @endif
        </div>
    @endif
</div>
