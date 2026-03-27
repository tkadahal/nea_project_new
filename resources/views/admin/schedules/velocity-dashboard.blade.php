<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Velocity Dashboard
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    contract: {{ $contract->name }} <span class="mx-2">|</span>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                        Sorted by: Slowest First
                    </span>
                </p>
            </div>

            <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Back to Schedules
            </a>
        </div>

        <!-- Dashboard Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Performance Analysis</h3>
                <p class="text-xs text-gray-500 mt-1">Showing only activities with 2 or more progress snapshots.</p>
            </div>

            @if ($velocityData->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                                    Activity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Velocity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Timeframe</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Est. Completion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($velocityData as $item)
                                @php
                                    $v = $item['velocity'];
                                    $s = $item['schedule'];

                                    // Color Logic
                                    $velocityColor = 'text-green-600';
                                    $bgColor = 'bg-green-100';
                                    if ($v['velocity_per_week'] <= 0) {
                                        $velocityColor = 'text-red-600';
                                        $bgColor = 'bg-red-100';
                                    } elseif ($v['velocity_per_week'] < 1) {
                                        $velocityColor = 'text-orange-600';
                                        $bgColor = 'bg-orange-100';
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-bold text-gray-900 dark:text-white">{{ $s->code }}</span>
                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $s->name }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white mb-1">
                                            {{ number_format($v['progress_gain'], 1) }}% gained
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full"
                                                style="width: {{ min(100, $s->progress ?? 0) }}%"></div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-lg font-bold {{ $velocityColor }}">
                                                {{ number_format($v['velocity_per_week'], 2) }}%
                                            </span>
                                            <span class="ml-2 text-xs text-gray-500">/ week</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        Over {{ number_format($v['weeks'], 1) }} weeks
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($v['estimated_completion'])
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $v['estimated_completion']['estimated_date'] }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ number_format($v['estimated_completion']['weeks_needed'], 1) }}
                                                    weeks left
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-sm text-red-500 font-medium">Stalled / No Velocity</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Insufficient data to calculate velocity.
                        Activities need at least 2 progress snapshots.</p>
                </div>
            @endif
        </div>

        <!-- Legend -->
        <div class="mt-4 flex justify-end gap-4 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center"><span
                    class="w-3 h-3 rounded-full bg-red-100 mr-2 border border-red-300"></span> Stalled (0%)</div>
            <div class="flex items-center"><span
                    class="w-3 h-3 rounded-full bg-orange-100 mr-2 border border-orange-300"></span> Slow (< 1%)</div>
                    <div class="flex items-center"><span
                            class="w-3 h-3 rounded-full bg-green-100 mr-2 border border-green-300"></span> Healthy (>
                        1%)</div>
            </div>

        </div>
</x-layouts.app>
