{{-- resources/views/admin/analytics/project-charts.blade.php --}}
<x-layouts.app>
    {{-- Page Title --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-200">
            Project Charts & Analytics
        </h1>
        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1">
            Visual insights into project timelines, budgets, and performance
        </p>
    </div>

    {{-- SECTION 1: Gantt Chart (Full Width) --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    📅 Project Timeline (Gantt Chart)
                </h3>
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                        On Track
                    </span>
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                        Behind
                    </span>
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                        Delayed
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div style="min-height: 400px;">
                    <div id="ganttChart" style="height: 400px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 2: Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 px-4 sm:px-6 lg:px-8">

        {{-- Progress Summary (Donut) --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                📊 Project Status Distribution
            </h3>
            <div class="relative" style="height: 300px;">
                <div id="progressSummaryChart" style="height: 300px;"></div>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4 text-center text-sm">
                <div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                        {{ $progressSummary['data'][0] }}
                    </div>
                    <div class="text-xs text-gray-500">Not Started</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $progressSummary['data'][1] }}
                    </div>
                    <div class="text-xs text-gray-500">In Progress</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ $progressSummary['data'][2] }}
                    </div>
                    <div class="text-xs text-gray-500">Completed</div>
                </div>
            </div>
        </div>

        {{-- Top Projects by Budget --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                💰 Top 10 Projects by Budget
            </h3>
            <div style="height: 300px;">
                <div id="topProjectsChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Quarterly Comparison --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    📈 Quarterly Performance: Planned vs Actual
                </h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Current Fiscal Year
                </div>
            </div>
            <div style="height: 350px;">
                <div id="quarterlyComparisonChart" style="height: 350px;"></div>
            </div>

            {{-- Quarterly Summary Table --}}
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quarter</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Planned</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actual</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">% Variance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($quarterlyComparison['labels'] as $index => $quarter)
                            @php
                                $planned = $quarterlyComparison['planned'][$index];
                                $actual = $quarterlyComparison['actual'][$index];
                                $variance = $actual - $planned;
                                $variancePercent = $planned > 0 ? ($variance / $planned) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">
                                    {{ $quarter }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400">
                                    ${{ number_format($planned, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400">
                                    ${{ number_format($actual, 2) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right {{ $variance >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ${{ number_format($variance, 2) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right {{ $variancePercent >= 10 ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                    {{ number_format($variancePercent, 1) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECTION 4: Monthly Burn Rate --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                🔥 Monthly Burn Rate (Last 12 Months)
            </h3>
            <div style="height: 300px;">
                <div id="burnRateChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- Google Charts Loader --}}
        <script src="https://www.gstatic.com/charts/loader.js"></script>

        <style>
            /* Make charts look clickable */
            #ganttChart rect,
            #topProjectsChart rect {
                cursor: pointer !important;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // --- 1. Define Data Variables ---
                const ganttData = @json($ganttData);
                const progressSummary = @json($progressSummary);
                const topProjects = @json($topProjects);
                const quarterlyComparison = @json($quarterlyComparison);
                const burnRateData = @json($burnRateData);

                // --- 2. Load Google Charts ---
                google.charts.load('current', {
                    'packages': ['corechart', 'timeline', 'bar']
                });
                google.charts.setOnLoadCallback(drawAllCharts);

                function drawAllCharts() {

                    const darkMode = document.documentElement.classList.contains('dark');
                    const bgColor = darkMode ? '#1F2937' : '#FFFFFF';
                    const textColor = darkMode ? '#E5E7EB' : '#374151';
                    const gridColor = darkMode ? '#374151' : '#E5E7EB';

                    // ========================================
                    // CHART 1: Gantt Chart (Clickable)
                    // ========================================
                    if (ganttData.length > 0) {
                        const ganttRows = ganttData.map(p => {
                            return [
                                p.name,
                                p.status_label,
                                new Date(p.start),
                                new Date(p.end)
                            ];
                        });

                        const ganttArray = [
                            ['Project', 'Status', 'Start Date', 'End Date'], ...ganttRows
                        ];
                        const ganttDataTable = google.visualization.arrayToDataTable(ganttArray);

                        const ganttOptions = {
                            backgroundColor: bgColor,
                            height: 400,
                            timeline: {
                                colorByRowLabel: false,
                                showBarLabels: false
                            },
                            hAxis: {
                                format: 'MMM yyyy',
                                textStyle: {
                                    color: textColor
                                },
                                gridlines: {
                                    color: gridColor
                                }
                            },
                            tooltip: {
                                isHtml: true
                            }
                        };

                        const ganttChart = new google.visualization.Timeline(document.getElementById('ganttChart'));
                        ganttChart.draw(ganttDataTable, ganttOptions);

                        // --- CLICK LISTENER FOR GANTT ---
                        google.visualization.events.addListener(ganttChart, 'select', function() {
                            var selection = ganttChart.getSelection();
                            if (selection.length > 0 && selection[0].row !== null) {
                                var rowIndex = selection[0].row;
                                var projectId = ganttData[rowIndex].id;
                                // Matches Route::get('/project-charts/{project}')
                                window.location.href = '/admin/analytics/project-charts/' + projectId;
                            }
                        });
                    }

                    // ========================================
                    // CHART 2: Progress Summary (Donut)
                    // ========================================
                    if (progressSummary.data) {
                        const progressArray = [
                            ['Status', 'Count'],
                            ...progressSummary.labels.map((label, i) => [label, progressSummary.data[i]])
                        ];
                        const progressDataTable = google.visualization.arrayToDataTable(progressArray);

                        const progressOptions = {
                            pieHole: 0.5,
                            backgroundColor: 'transparent',
                            colors: progressSummary.colors,
                            legend: {
                                position: 'bottom',
                                textStyle: {
                                    color: textColor
                                }
                            },
                            pieSliceTextStyle: {
                                color: textColor
                            }
                        };

                        new google.visualization.PieChart(document.getElementById('progressSummaryChart')).draw(
                            progressDataTable, progressOptions);
                    }

                    // ========================================
                    // CHART 3: Top Projects (Clickable)
                    // ========================================
                    if (topProjects.labels.length > 0) {
                        const topProjectsArray = [
                            ['Project', 'Budget'],
                            ...topProjects.labels.map((label, i) => [label, topProjects.data[i]])
                        ];
                        const topProjectsDataTable = google.visualization.arrayToDataTable(topProjectsArray);

                        const topProjectsOptions = {
                            backgroundColor: 'transparent',
                            hAxis: {
                                format: 'short',
                                textStyle: {
                                    color: textColor
                                },
                                gridlines: {
                                    color: gridColor
                                }
                            },
                            vAxis: {
                                textStyle: {
                                    color: textColor
                                }
                            },
                            legend: {
                                position: 'none'
                            },
                            bars: 'horizontal'
                        };

                        const topProjectsChart = new google.visualization.BarChart(document.getElementById(
                            'topProjectsChart'));
                        topProjectsChart.draw(topProjectsDataTable, topProjectsOptions);

                        // --- CLICK LISTENER FOR TOP PROJECTS ---
                        google.visualization.events.addListener(topProjectsChart, 'select', function() {
                            var selection = topProjectsChart.getSelection();
                            if (selection.length > 0 && selection[0].row !== null) {
                                var rowIndex = selection[0].row;
                                // Uses the 'ids' array we added in Step 1
                                var projectId = topProjects.ids[rowIndex];
                                window.location.href = '/admin/analytics/project-charts/' + projectId;
                            }
                        });
                    }

                    // ========================================
                    // CHART 4: Quarterly Comparison
                    // ========================================
                    if (quarterlyComparison.labels.length > 0) {
                        const quarterlyArray = [
                            ['Quarter', 'Planned', 'Actual'],
                            ...quarterlyComparison.labels.map((label, i) => {
                                return [
                                    label,
                                    quarterlyComparison.planned[i],
                                    quarterlyComparison.actual[i]
                                ];
                            })
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
                                position: 'top',
                                textStyle: {
                                    color: textColor
                                }
                            }
                        };

                        new google.visualization.ColumnChart(document.getElementById('quarterlyComparisonChart')).draw(
                            quarterlyDataTable, quarterlyOptions);
                    }

                    // ========================================
                    // CHART 5: Monthly Burn Rate
                    // ========================================
                    if (burnRateData.labels.length > 0) {
                        const burnRateArray = [
                            ['Month', 'Spending'],
                            ...burnRateData.labels.map((label, i) => [label, burnRateData.data[i]])
                        ];
                        const burnRateDataTable = google.visualization.arrayToDataTable(burnRateArray);

                        const burnRateOptions = {
                            backgroundColor: 'transparent',
                            curveType: 'function',
                            legend: {
                                position: 'top',
                                textStyle: {
                                    color: textColor
                                }
                            },
                            hAxis: {
                                textStyle: {
                                    color: textColor
                                },
                                slantedText: true,
                                slantedTextAngle: 45
                            },
                            vAxis: {
                                format: 'currency',
                                textStyle: {
                                    color: textColor
                                },
                                gridlines: {
                                    color: gridColor
                                }
                            },
                            colors: ['#EF4444']
                        };

                        new google.visualization.LineChart(document.getElementById('burnRateChart')).draw(
                            burnRateDataTable, burnRateOptions);
                    }
                }

                // Redraw on resize
                window.onresize = function() {
                    drawAllCharts();
                };
            });
        </script>
    @endpush
</x-layouts.app>
