<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Recent Files</h3>
            <p class="text-xs text-gray-500 mt-1">Files for selected projects</p>
        </div>
        <a href="{{ route('admin.schedules.all-files') }}" class="text-xs text-blue-600 hover:underline">View All</a>
    </div>
    <div class="px-4 py-5 sm:p-6 space-y-3">
        @forelse ($recentFiles as $file)
            <div
                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="flex-shrink-0 text-blue-500">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                        </svg>
                    </div>
                    <div class="truncate">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $file->original_name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ $file->project->title }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span
                        class="text-[10px] text-gray-400 whitespace-nowrap">{{ $file->created_at->diffForHumans() }}</span>
                    <a href="{{ route('admin.projects.schedules.download-file', [$file->project_id, $file->id]) }}"
                        class="text-gray-400 hover:text-blue-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-10">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent files found.</p>
            </div>
        @endforelse
    </div>
</div>
