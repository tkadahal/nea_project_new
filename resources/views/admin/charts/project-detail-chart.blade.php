{{-- resources/views/admin/analytics/project-detail-charts.blade.php --}}
<x-layouts.app>
    {{-- Project Header --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $project->title }}
                </h1>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1">
                    {{ $project->directorate?->title }} • {{ $project->status?->title }}
                </p>
            </div>
            <a href="{{ route('admin.analytics.project-charts') }}"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                ← Back to Overview
            </a>
        </div>
    </div>

    {{-- Project KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Budget</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                ${{ number_format($project->total_budget, 2) }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Physical Progress</div>
            <div class="text-2xl font-bold text-blue-600">
                {{ number_format($project->progress, 1) }}%
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Financial Progress</div>
            <div class="text-2xl font-bold text-green-600">
                {{ number_format($project->financial_progress, 1) }}%
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Days Remaining</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                @if ($project->end_date)
                    {{ $project->end_date->isFuture() ? $project->end_date->diffInDays(now()) : 0 }}
                @else
                    N/A
                @endif
            </div>
        </div>
    </div>

    {{-- SECTION 1: S-Curve (Planned vs Actual Cumulative) --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    📈 S-Curve: Planned vs Actual Spending
                </h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Cumulative Budget: ${{ number_format($sCurveData['total_budget'], 2) }}
                </div>
            </div>
            {{-- Changed to div for Google Charts --}}
            <div id="sCurveChart" style="height: 400px;"></div>
        </div>
    </div>

    {{-- SECTION 2: Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 px-4 sm:px-6 lg:px-8">

        {{-- Quarterly Comparison --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                📊 Quarterly Breakdown
            </h3>
            <div id="quarterlyChart" style="height: 300px;"></div>

            {{-- Quarterly Table --}}
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs">Quarter</th>
                            <th class="px-2 py-2 text-right text-xs">Planned</th>
                            <th class="px-2 py-2 text-right text-xs">Actual</th>
                            <th class="px-2 py-2 text-right text-xs">Var %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($quarterlyComparison as $q)
                            <tr>
                                <td class="px-2 py-2 font-medium">{{ $q['quarter'] }}</td>
                                <td class="px-2 py-2 text-right">${{ number_format($q['planned'], 0) }}</td>
                                <td class="px-2 py-2 text-right">${{ number_format($q['actual'], 0) }}</td>
                                <td
                                    class="px-2 py-2 text-right {{ abs($q['variance_percent']) > 10 ? 'text-red-600 font-bold' : '' }}">
                                    {{ number_format($q['variance_percent'], 1) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Progress Tracking --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                🎯 Progress Tracking
            </h3>
            <div id="progressChart" style="height: 300px;"></div>

            {{-- Legend --}}
            <div class="mt-4 flex justify-center gap-6 text-sm">
                <div class="flex items-center">
                    <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                    Physical Progress
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                    Financial Progress
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Monthly Burn Rate --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                🔥 Monthly Burn Rate
            </h3>
            <div id="burnRateChart" style="height: 300px;"></div>
        </div>
    </div>

    {{-- SECTION 4: Activity Heatmap --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                🌡️ Activity Budget Utilization
            </h3>

            @if (count($activityHeatmap) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Budget</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Spent</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Utilization
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($activityHeatmap as $activity)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-200">
                                        {{ $activity['name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $activity['program'] ?: 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400">
                                        ${{ number_format($activity['budget'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400">
                                        ${{ number_format($activity['spent'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-medium"
                                        style="color: {{ $activity['color'] }}">
                                        {{ number_format($activity['utilization'], 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block w-4 h-4 rounded-full"
                                            style="background-color: {{ $activity['color'] }}"></span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No activity data available
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        {{-- Google Charts Loader --}}
        <script src="https://www.gstatic.com/charts/loader.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const sCurveData = @json($sCurveData);
                const quarterlyData = @json($quarterlyComparison);
                const progressData = @json($progressHistory);
                const burnRateData = @json($burnRateData);

                // Load Google Charts
                google.charts.load('current', {
                    'packages': ['corechart']
                });
                google.charts.setOnLoadCallback(drawCharts);

                function drawCharts() {
                    const darkMode = document.documentElement.classList.contains('dark');
                    const textColor = darkMode ? '#E5E7EB' : '#374151';
                    const gridColor = darkMode ? '#374151' : '#E5E7EB';

                    // ========================================
                    // CHART 1: S-Curve (Line)
                    // ========================================
                    const sCurveArray = [
                        ['Quarter', 'Planned Cumulative', 'Actual Cumulative'],
                        ...sCurveData.labels.map((label, i) => {
                            return [
                                label,
                                sCurveData.planned[i],
                                sCurveData.actual[i]
                            ];
                        })
                    ];
                    const sCurveDataTable = google.visualization.arrayToDataTable(sCurveArray);

                    const sCurveOptions = {
                        backgroundColor: 'transparent',
                        curveType: 'function', // Makes lines smooth
                        legend: {
                            position: 'top',
                            textStyle: {
                                color: textColor
                            }
                        },
                        hAxis: {
                            textStyle: {
                                color: textColor
                            }
                        },
                        vAxis: {
                            textStyle: {
                                color: textColor
                            },
                            format: 'currency',
                            minValue: 0,
                            maxValue: sCurveData.total_budget * 1.1, // Add 10% headroom
                            gridlines: {
                                color: gridColor
                            }
                        },
                        colors: ['#10B981', '#3B82F6'],
                        tooltip: {
                            isHtml: true
                        }
                    };

                    new google.visualization.LineChart(document.getElementById('sCurveChart')).draw(sCurveDataTable,
                        sCurveOptions);

                    // ========================================
                    // CHART 2: Quarterly Comparison (Column)
                    // ========================================
                    const quarterlyArray = [
                        ['Quarter', 'Planned', 'Actual'],
                        ...quarterlyData.map(q => [q.quarter, q.planned, q.actual])
                    ];
                    const quarterlyDataTable = google.visualization.arrayToDataTable(quarterlyArray);

                    const quarterlyOptions = {
                        backgroundColor: 'transparent',
                        colors: ['#10B981', '#3B82F6'],
                        hAxis: {
                            textStyle: {
                                color: textColor
                            }
                        },
                        vAxis: {
                            textStyle: {
                                color: textColor
                            },
                            format: 'currency',
                            gridlines: {
                                color: gridColor
                            }
                        },
                        legend: {
                            position: 'none'
                        }
                    };

                    new google.visualization.ColumnChart(document.getElementById('quarterlyChart')).draw(
                        quarterlyDataTable, quarterlyOptions);

                    // ========================================
                    // CHART 3: Progress Tracking (Line)
                    // ========================================
                    const progressArray = [
                        ['Quarter', 'Physical Progress', 'Financial Progress'],
                        ...progressData.map(p => [p.label, p.physical_progress, p.financial_progress])
                    ];
                    const progressDataTable = google.visualization.arrayToDataTable(progressArray);

                    const progressOptions = {
                        backgroundColor: 'transparent',
                        curveType: 'function',
                        colors: ['#3B82F6', '#10B981'],
                        legend: {
                            position: 'none'
                        },
                        hAxis: {
                            textStyle: {
                                color: textColor
                            }
                        },
                        vAxis: {
                            textStyle: {
                                color: textColor
                            },
                            format: 'decimal', // Display as raw numbers (e.g. 50.5)
                            minValue: 0,
                            maxValue: 100,
                            gridlines: {
                                color: gridColor
                            },
                            title: '%' // Add title to indicate percentage
                        },
                        vAxis: {
                            textStyle: {
                                color: textColor
                            }
                        }
                    };

                    new google.visualization.LineChart(document.getElementById('progressChart')).draw(progressDataTable,
                        progressOptions);

                    // ========================================
                    // CHART 4: Monthly Burn Rate (Column)
                    // ========================================
                    if (burnRateData.labels.length > 0) {
                        const burnRateArray = [
                            ['Month', 'Spending'],
                            ...burnRateData.labels.map((label, i) => [label, burnRateData.data[i]])
                        ];
                        const burnRateDataTable = google.visualization.arrayToDataTable(burnRateArray);

                        const burnRateOptions = {
                            backgroundColor: 'transparent',
                            colors: ['#EF4444'],
                            hAxis: {
                                textStyle: {
                                    color: textColor
                                },
                                slantedText: true,
                                slantedTextAngle: 45
                            },
                            vAxis: {
                                textStyle: {
                                    color: textColor
                                },
                                format: 'currency',
                                gridlines: {
                                    color: gridColor
                                }
                            },
                            legend: {
                                position: 'none'
                            }
                        };

                        new google.visualization.ColumnChart(document.getElementById('burnRateChart')).draw(
                            burnRateDataTable, burnRateOptions);
                    }
                }

                // Redraw on resize
                window.onresize = function() {
                    drawCharts();
                };
            });
        </script>
    @endpush
</x-layouts.app>
