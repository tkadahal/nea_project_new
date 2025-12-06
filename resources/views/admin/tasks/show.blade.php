<x-layouts.app>
    @if (session('success'))
        <div
            class="alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.task.title_singular') }} {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }}:
                <span class="font-semibold">
                    {{ $task['title'] ?? 'Unnamed Task' }}
                    @if ($task['project_id'] && is_numeric($task['project_id']))
                        ({{ $task['project_name'] ?? 'No Project' }})
                    @elseif ($task['department_id'])
                        ({{ $task['department_name'] ?? 'No Department' }})
                    @elseif ($task['directorate_id'])
                        ({{ $task['directorate_name'] ?? 'No Directorate' }})
                    @else
                        (No Project/Directorate/Department)
                    @endif
                </span>
            </p>
        </div>

        @can('task_access')
            <a href="{{ route('admin.task.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
            class="md:col-span-2 bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.title') }}
                    </p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $task['title'] ?? 'Unnamed Task' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.status_id') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        @if ($task['status'])
                            <span class="inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                style="background-color: {{ $task['status']['color'] ?? 'gray' }};">
                                {{ ucfirst($task['status']['title']) }}
                            </span>
                        @else
                            <span
                                class="inline-block px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 rounded-full bg-gray-200 dark:bg-gray-600">
                                {{ trans('global.task.no_status') }}
                            </span>
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.priority') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        @if ($task['priority'])
                            <span class="inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                style="background-color: {{ $task['priority']['color'] ?? 'gray' }};">
                                {{ ucfirst($task['priority']['title']) }}
                            </span>
                        @else
                            <span
                                class="inline-block px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 rounded-full bg-gray-200 dark:bg-gray-600">
                                {{ trans('global.task.no_priority') }}
                            </span>
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.progress') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['progress'] ?? '0' }}%
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.directorate') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['directorate_name'] ?? 'N/A' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.department') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['department_name'] ?? 'N/A' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.project') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100 space-y-2">
                        @if (!empty($task['projects']))
                            @foreach ($task['projects'] as $project)
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs w-fit"
                                    title="{{ $project['title'] }}">
                                    {{ $project['title'] }}
                                    @if ($project['id'] == $task['project_id'])
                                        <span class="ml-1 text-xs font-semibold">(Current)</span>
                                    @endif
                                </span>
                            @endforeach
                        @else
                            <span class="text-gray-500 dark:text-gray-400 text-xs w-fit">
                                {{ trans('global.noRecords') }}
                            </span>
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.users') }}
                    </p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @forelse ($task['users'] as $user)
                            <span
                                class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-800 bg-gray-200 rounded-full dark:bg-gray-700 dark:text-gray-200">
                                {{ $user['name'] }}
                            </span>
                        @empty
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ trans('global.task.no_users') }}
                            </span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.start_date') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['start_date'] ? \Carbon\Carbon::parse($task['start_date'])->format('M d, Y') : 'N/A' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.due_date') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['due_date'] ? \Carbon\Carbon::parse($task['due_date'])->format('M d, Y') : 'N/A' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.completion_date') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['completion_date'] ? \Carbon\Carbon::parse($task['completion_date'])->format('M d, Y') : 'N/A' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.created_at') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['created_at']->format('M d, Y H:i A') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans('global.task.fields.updated_at') }}
                    </p>
                    <p class="mt-1 text-lg text-gray-900 dark:text-gray-100">
                        {{ $task['updated_at']->format('M d, Y H:i A') }}
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ trans('global.task.fields.description') }}
                </p>
                <p class="mt-1 text-gray-900 dark:text-gray-100">
                    {!! nl2br(e($task['description'] ?? 'No description provided')) !!}
                </p>
            </div>

            <div class="mt-6 flex space-x-4">
                @can('task_edit')
                    <a href="{{ route('admin.task.edit', [$task['id'], $task['project_id'] && is_numeric($task['project_id']) ? $task['project_id'] : null]) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        {{ trans('global.edit') }}
                    </a>
                @endcan
                @can('task_delete')
                    <form
                        action="{{ route('admin.task.destroy', [$task['id'], $task['project_id'] && is_numeric($task['project_id']) ? $task['project_id'] : null]) }}"
                        method="POST" onsubmit="return confirm('{{ trans('global.are_you_sure') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            {{ trans('global.delete') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div
            class="md:col-span-1 bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 flex flex-col">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">{{ __('Comments') }}</h2>

            <div class="flex-grow overflow-y-auto pr-2" style="max-height: 500px;">
                @if ($comments->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('No comments yet. Be the first to add one!') }}
                    </p>
                @else
                    @foreach ($comments as $comment)
                        @include('admin.comments.comment', [
                            'comment' => $comment,
                            'level' => 0,
                            'commentable' => [
                                $task['id'],
                                $task['project_id'] && is_numeric($task['project_id'])
                                    ? $task['project_id']
                                    : null,
                            ],
                            'routePrefix' => 'admin.tasks',
                        ])
                    @endforeach
                @endif
            </div>

            <div class="mt-6 flex-shrink-0">
                <form method="POST" action="{{ route('admin.tasks.comments.store', $task['id']) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="project_id"
                        value="{{ $task['project_id'] && is_numeric($task['project_id']) ? $task['project_id'] : null }}">
                    <div class="flex items-start space-x-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white font-medium">
                            {{ Auth::user()->initials() }}
                        </span>
                        <div class="flex-1">
                            <textarea name="content"
                                class="w-full p-3 border rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="{{ __('Add a comment...') }}" rows="4" required></textarea>
                            @error('content')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            {{ __('Post Comment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleReplyForm(commentId) {
                const form = document.getElementById(`reply-form-${commentId}`);
                if (form) {
                    form.classList.toggle('hidden');
                } else {
                    console.error(`Reply form with ID reply-form-${commentId} not found.`);
                }
            }
        </script>
    @endpush
</x-layouts.app>
