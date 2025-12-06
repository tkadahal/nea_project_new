<div class="comment mb-4 {{ $level > 0 ? 'ml-' . $level * 4 : '' }}">
    <div class="flex items-start space-x-3">
        <span
            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white font-medium">
            {{ $comment->user->initials() }}
        </span>
        <div class="flex-1">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $comment->user->name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $comment->created_at->format('M d, Y H:i A') }}
                </p>
            </div>
            <p class="mt-1 text-gray-700 dark:text-gray-300">
                {{ $comment->content }}
            </p>
            <div class="mt-2">
                <button onclick="toggleReplyForm({{ $comment->id }})"
                    class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('Reply') }}
                </button>
            </div>

            <!-- Reply Form -->
            <div id="reply-form-{{ $comment->id }}" class="hidden mt-4">
                <form method="POST" action="{{ route($routePrefix . '.comments.store', $commentable) }}"
                    class="space-y-4">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $commentable[1] }}">
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <div class="flex items-start space-x-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white font-medium">
                            {{ Auth::user()->initials() }}
                        </span>
                        <div class="flex-1">
                            <textarea name="content"
                                class="w-full p-3 border rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="{{ __('Add a reply...') }}" rows="3" required></textarea>
                            @error('content')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @error('project_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="toggleReplyForm({{ $comment->id }})"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-offset-gray-900">
                            {{ __('Post Reply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Nested Replies -->
    @if ($comment->replies->isNotEmpty())
        @foreach ($comment->replies as $reply)
            @include('admin.comments.comment', [
                'comment' => $reply,
                'level' => $level + 1,
                'commentable' => $commentable,
                'routePrefix' => $routePrefix,
            ])
        @endforeach
    @endif
</div>
