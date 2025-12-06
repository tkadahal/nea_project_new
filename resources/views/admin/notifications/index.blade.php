<x-layouts.app>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">Notifications</h1>
        <ul class="space-y-4">
            @foreach ($notifications as $notification)
                <li
                    class="p-4 bg-white dark:bg-gray-800 rounded-md shadow {{ $notification->read_at ? '' : 'border-l-4 border-blue-500' }}">
                    <div class="flex flex-col">
                        <span class="">{{ $notification->data['task_name'] ?? 'Unnamed Task' }}</span>
                        <span
                            class="text-sm text-gray-600 dark:text-gray-400">{{ $notification->data['message'] ?? 'No message' }}</span>
                        <span
                            class="text-sm text-gray-500 dark:text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                        <a href="{{ $notification->data['url'] ?? '#' }}"
                            class="text-blue-600 dark:text-blue-400 hover:underline mt-1">View Task</a>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $notifications->links() }}
    </div>
</x-layouts.app>
