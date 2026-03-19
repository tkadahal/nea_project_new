<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Progress History: {{ $schedule->code }}
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $schedule->name }}
            </p>
        </div>

        <!-- ===========================
             NEW: FILTER SECTION
        ============================ -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <form action="{{ route('admin.projects.schedules.progressHistory', [$project->id]) }}" method="GET"
                class="flex flex-col md:flex-row items-end md:items-center gap-4">

                <!-- Start Date -->
                <div class="w-full md:w-1/4">
                    <label for="start_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Start Date
                    </label>
                    <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2 border">
                </div>

                <!-- End Date -->
                <div class="w-full md:w-1/4">
                    <label for="end_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        End Date
                    </label>
                    <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2 border">
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button type="submit"
                        class="w-full md:w-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        Filter Report
                    </button>

                    <a href="{{ route('admin.projects.schedules.progressHistory', [$project->id]) }}"
                        class="w-full md:w-auto inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                        Reset
                    </a>
                </div>
            </form>
        </div>
        <!-- ===========================
             END FILTER SECTION
        ============================ -->

        <!-- Summary Cards (These will now update based on the filtered dates) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Note: If no snapshots exist in the filter, this might error.
                 Add a null check or empty() check around the first() call. -->

            @if ($snapshots->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Current Progress (End of Range)</div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $snapshots->first()->progress }}%
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ $snapshots->first()->snapshot_date->format('M d') }}
                    </div>
                </div>

                @if ($weeklyVelocity)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Avg. Weekly Velocity</div>
                        <div class="text-2xl font-bold text-green-600">
                            +{{ $weeklyVelocity['velocity_per_week'] }}%
                        </div>
                        <div class="text-xs text-gray-400">
                            Over {{ $weeklyVelocity['weeks'] }} weeks
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Weeks to Complete</div>
                        <div class="text-2xl font-bold text-orange-600">
                            {{ $weeklyVelocity['estimated_completion']['weeks_needed'] ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Est. Completion</div>
                        <div class="text-lg font-bold text-purple-600">
                            {{ $weeklyVelocity['estimated_completion']['estimated_date'] ?? 'N/A' }}
                        </div>
                    </div>
                @else
                    <div class="col-span-3 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                    Need at least 2 snapshots within the selected date range to calculate velocity.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="col-span-4 text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-gray-500">No records found for the selected date range.</p>
                </div>
            @endif
        </div>

        <!-- Progress Chart (Automatically updates because $snapshots is filtered) -->
        @if ($snapshots->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Progress Trend (Filtered Range)</h3>
                <canvas id="progressChart" height="80"></canvas>
            </div>
        @endif

        <!-- Progress History Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- ... (Rest of your table code remains exactly the same) ... -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Detailed History ({{ $snapshots->count() }} records)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- ... Table Header ... -->
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                        </tr>
                    </thead>
                    <!-- ... Table Body ... -->
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($snapshots as $index => $snapshot)
                            @php
                                $previous = $snapshots->get($index + 1);
                                $change = $previous ? $snapshot->progress - $previous->progress : 0;
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $snapshot->snapshot_date->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full"
                                                style="width: {{ $snapshot->progress }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ $snapshot->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if ($snapshot->completed_quantity)
                                        {{ $snapshot->completed_quantity }} / {{ $snapshot->target_quantity }}
                                        {{ $snapshot->unit }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if ($change > 0)
                                        <span class="text-green-600">+{{ number_format($change, 1) }}%</span>
                                    @elseif($change < 0)
                                        <span class="text-red-600">{{ number_format($change, 1) }}%</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full 
                                        {{ $snapshot->snapshot_type === 'weekly' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $snapshot->snapshot_type === 'monthly' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $snapshot->snapshot_type === 'manual' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($snapshot->snapshot_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $snapshot->recordedBy->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $snapshot->remarks ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // We only initialize chart if there is data
            @if ($snapshots->isNotEmpty())
                const ctx = document.getElementById('progressChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        // Reverse for chronological order in chart
                        labels: {!! json_encode($snapshots->reverse()->pluck('snapshot_date')->map(fn($d) => $d->format('M d'))) !!},
                        datasets: [{
                            label: 'Progress (%)',
                            data: {!! json_encode($snapshots->reverse()->pluck('progress')) !!},
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            @endif
        </script>
    @endpush
</x-layouts.app>
