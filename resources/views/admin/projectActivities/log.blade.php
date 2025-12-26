<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Activity Log
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                History of changes for <strong>{{ $project->title }}</strong> —
                Fiscal Year <strong>{{ $fiscalYear->title }}</strong>
            </p>
        </div>

        <a href="{{ route('admin.projectActivity.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>

    @if ($logs->isEmpty())
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">No activity logged yet for this program.</p>
        </div>
    @else
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} entries
                </p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($logs as $log)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg
                                    {{ (($log->description === 'created'
                                                ? 'bg-blue-600'
                                                : $log->description === 'updated')
                                            ? 'bg-yellow-600'
                                            : $log->description === 'deleted')
                                        ? 'bg-red-600'
                                        : 'bg-gray-600' }}">
                                    {{ strtoupper(substr($log->description, 0, 1)) }}
                                </div>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ ($log->description === 'created'
                                                ? 'Plan Created'
                                                : $log->description === 'updated')
                                            ? 'Plan Updated'
                                            : ucwords(str_replace('_', ' ', $log->description)) }}
                                    </h4>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->format('M d, Y \a\t H:i') }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    by <strong>{{ $log->causer?->name ?? 'System' }}</strong>
                                    @if ($log->causer?->email)
                                        ({{ $log->causer->email }})
                                    @endif
                                </p>

                                @if ($log->properties->has('attributes') && $log->properties->has('old'))
                                    <div class="mt-4">
                                        <details class="cursor-pointer group">
                                            <summary
                                                class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                View Changed Fields
                                                ({{ count(array_diff_assoc($log->properties['attributes'], $log->properties['old'])) }})
                                            </summary>
                                            <div class="mt-3 pl-6">
                                                <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
{{ json_encode(array_diff_assoc($log->properties['attributes'], $log->properties['old']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </details>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        </div>
    @endif
</x-layouts.app>
