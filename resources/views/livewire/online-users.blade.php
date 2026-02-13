<div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Online Users
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Currently active users (last {{ $thresholdMinutes }} minutes)
            </p>
        </div>

        <div class="text-sm text-gray-500 dark:text-gray-400">
            Auto-refreshing every 3 seconds • {{ count($onlineUsers) }} online
        </div>
    </div>

    @if (empty($onlineUsers))
        <div
            class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6 text-center text-gray-500 dark:text-gray-400">
            No users are currently online.
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Name</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Email / Employee ID</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Last Active</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            IP Address</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Chat</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($onlineUsers as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-2 w-2 rounded-full bg-green-500 mr-3"></span>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $user['name'] }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $user['email'] }}
                                @if ($user['employee_id'])
                                    <span class="text-xs text-gray-400">({{ $user['employee_id'] }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $user['last_active_human'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ $user['ip'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100">
                                    Online
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="openChatWith({{ $user['id'] }})"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none"
                                    title="Send message to {{ $user['name'] }}">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Polling for online users list -->
    <div wire:poll.3000ms="refreshOnlineUsers" class="hidden"></div>

    <!-- Floating 1-on-1 Chat Window -->
    @if ($selectedRecipientId)
        <div class="fixed bottom-6 right-6 z-50" x-data="{ minimized: false }">
            <div x-show="!minimized || true" x-transition
                class="w-96 bg-white dark:bg-gray-800 rounded-xl shadow-2xl flex flex-col border border-gray-200 dark:border-gray-700 overflow-hidden"
                :class="{ 'h-[500px]': !minimized, 'h-96': minimized }">

                <!-- Header -->
                <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center">
                    <div class="font-medium">
                        Chat with {{ $selectedRecipientName ?? 'User' }}
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="minimized = !minimized"
                            class="text-white hover:text-gray-200 focus:outline-none">
                            <svg x-show="!minimized" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <svg x-show="minimized" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                        <button wire:click="closeChat"
                            class="text-white hover:text-gray-200 focus:outline-none text-xl leading-none">×</button>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900">
                    @forelse($chatMessages as $msg)
                        <div class="{{ $msg['sender_id'] === auth()->id() ? 'text-right' : 'text-left' }}">

                            <!-- Show sender name for messages you received -->
                            @if ($msg['sender_id'] !== auth()->id())
                                <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $msg['sender']['name'] ?? 'Unknown' }}
                                </div>
                            @endif

                            <div
                                class="inline-block max-w-[80%] px-4 py-2 rounded-lg shadow-sm
                                {{ $msg['sender_id'] === auth()->id()
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                                {{ $msg['content'] }}
                                <div class="text-xs mt-1 opacity-70 text-right">
                                    {{ \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-10">
                            No messages yet. Start the conversation!
                        </p>
                    @endforelse
                </div>

                <!-- Input Area -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-3">
                    <form wire:submit.prevent="sendMessage" class="flex gap-2">
                        <input wire:model.live.debounce.400ms="newChatMessage" type="text"
                            placeholder="Type your message..."
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            autocomplete="off">
                        <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                            Send
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
