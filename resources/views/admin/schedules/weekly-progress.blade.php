<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Weekly Progress Report
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Project: {{ $project->name }} <span class="mx-2">|</span>
                    <span class="font-medium">{{ now()->startOfWeek()->format('M d') }} -
                        {{ now()->endOfWeek()->format('M d, Y') }}</span>
                </p>
            </div>

            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Back to Schedules
            </a>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Activities Updated -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Activities
                    Updated</div>
                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['activities_updated'] }}
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Unique schedules updated this week
                </div>
            </div>

            <!-- Avg Progress Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-indigo-500">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Progress
                    (This Week)</div>
                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['avg_progress_gain'], 1) }}%
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Average status of recorded snapshots
                </div>
            </div>

            <!-- Completed This Week -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed
                    This Week</div>
                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $summary['completed_this_week'] }}
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Activities reached 100%
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Detailed Snapshots</h3>
                <span
                    class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-full">
                    {{ $snapshots->count() }} Records
                </span>
            </div>

            @if ($snapshots->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Schedule</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Progress</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($snapshots as $snapshot)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $snapshot->schedule->code }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $snapshot->schedule->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span
                                                class="mr-2 text-sm font-bold {{ $snapshot->progress == 100 ? 'text-green-600' : 'text-blue-600' }}">
                                                {{ $snapshot->progress }}%
                                            </span>
                                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full"
                                                    style="width: {{ $snapshot->progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $snapshot->snapshot_date->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $snapshot->recordedBy->name ?? 'System' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No activity this week</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No progress snapshots were recorded between
                        {{ now()->startOfWeek()->format('M d') }} and today.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
