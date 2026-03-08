<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Average Progress by Phase</h3>
        <p class="text-xs text-gray-500 mt-1">Based on current filters</p>
    </div>
    <div class="px-4 py-5 sm:p-6 space-y-4">
        @forelse ($phaseBreakdown as $phase)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $phase['code'] }}: {{ $phase['name'] }}
                    </span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ number_format($phase['average_progress'], 1) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                        style="width: {{ $phase['average_progress'] }}%"></div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-gray-500 dark:text-gray-400 italic">
                No phase data available for selection.
            </div>
        @endforelse
    </div>
</div>
