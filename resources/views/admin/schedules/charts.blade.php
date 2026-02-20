<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Schedule Analytics & Charts
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Visualize project progress, S-Curves, and activity timelines.
            </p>

            <nav class="flex mt-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.project.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Projects
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.project.show', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($project->title, 30) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                Schedules
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span
                                class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Charts</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    <span class="text-sm font-medium">Analytics</span>
                </div>
                <a href="{{ route('admin.projects.schedules.dashboard', $project) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                    <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Burn Chart -->
        <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6 ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 bg-red-600 border-b border-red-700 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-white">Burn Chart</h3>
                        <p class="text-red-100 text-sm">Compares planned progress vs actual progress day by day</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="relative h-80 w-full">
                    <canvas id="burnChart"></canvas>
                </div>
            </div>
        </div>

        <!-- S-Curve -->
        <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6 ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 bg-emerald-600 border-b border-emerald-700 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-white">S-Curve</h3>
                        <p class="text-emerald-100 text-sm">Shows cumulative progress curve (planned vs actual)</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="relative h-80 w-full">
                    <canvas id="sCurve"></canvas>
                </div>
            </div>
        </div>

        <!-- Activity Chart (Gantt-style) -->
        <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 bg-blue-600 border-b border-blue-700 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-white">Activity Timeline</h3>
                        <p class="text-blue-100 text-sm">Shows all activities with planned and actual dates</p>
                    </div>
                </div>
            </div>
            <div class="p-0 overflow-hidden">
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto" id="activityChart">
                    <div class="flex items-center justify-center h-32 text-gray-500 dark:text-gray-400">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Loading activity data...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Helper to get chart colors based on Dark Mode
            function getChartColors() {
                const isDarkMode = document.documentElement.classList.contains('dark');
                return {
                    textColor: isDarkMode ? '#9ca3af' : '#374151',
                    gridColor: isDarkMode ? '#374151' : '#e5e7eb',
                    tooltipBg: isDarkMode ? 'rgba(31, 41, 55, 0.9)' : 'rgba(0, 0, 0, 0.8)',
                    tooltipText: isDarkMode ? '#f3f4f6' : '#ffffff'
                };
            }

            const chartColors = getChartColors();

            // Global Chart Defaults
            Chart.defaults.color = chartColors.textColor;
            Chart.defaults.borderColor = chartColors.gridColor;
            Chart.defaults.font.family = "'Inter', sans-serif";

            // =======================================================
            // 1. BURN CHART
            // =======================================================
            fetch('{{ route('admin.projects.schedules.api.burn-chart', $project) }}')
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('burnChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.dates,
                            datasets: [{
                                    label: 'Planned Progress',
                                    data: data.planned,
                                    borderColor: '#3b82f6', // blue-500
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.1,
                                    fill: true
                                },
                                {
                                    label: 'Actual Progress',
                                    data: data.actual,
                                    borderColor: '#ef4444', // red-500
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.1,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Planned vs Actual Progress Over Time',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: chartColors.tooltipBg,
                                    titleColor: chartColors.tooltipText,
                                    bodyColor: chartColors.tooltipText,
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) +
                                                '%';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: value => value + '%',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: chartColors.gridColor
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45,
                                        font: {
                                            size: 10
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(err => {
                    console.error('Error loading burn chart:', err);
                    document.getElementById('burnChart').parentElement.innerHTML =
                        '<div class="p-4 text-center text-red-500">Error loading chart data</div>';
                });

            // =======================================================
            // 2. S-CURVE
            // =======================================================
            fetch('{{ route('admin.projects.schedules.api.s-curve', $project) }}')
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('sCurve').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.dates,
                            datasets: [{
                                    label: 'Planned Cumulative',
                                    data: data.planned,
                                    borderColor: '#10b981', // emerald-500
                                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0
                                },
                                {
                                    label: 'Actual Cumulative',
                                    data: data.actual,
                                    borderColor: '#f59e0b', // amber-500
                                    backgroundColor: 'rgba(245, 158, 11, 0.2)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'S-Curve: Cumulative Progress Over Time',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: chartColors.tooltipBg,
                                    titleColor: chartColors.tooltipText,
                                    bodyColor: chartColors.tooltipText,
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) +
                                                '%';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: value => value + '%',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: chartColors.gridColor
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45,
                                        font: {
                                            size: 10
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(err => {
                    console.error('Error loading s-curve:', err);
                    document.getElementById('sCurve').parentElement.innerHTML =
                        '<div class="p-4 text-center text-red-500">Error loading chart data</div>';
                });

            // =======================================================
            // 3. ACTIVITY CHART (Tailwind Table)
            // =======================================================
            fetch('{{ route('admin.projects.schedules.api.activity-chart', $project) }}')
                .then(res => res.json())
                .then(activities => {
                    const container = document.getElementById('activityChart');

                    if (!activities || activities.length === 0) {
                        container.innerHTML =
                            '<div class="p-8 text-center text-gray-500 dark:text-gray-400">No activities found</div>';
                        return;
                    }

                    // Using Tailwind classes for the table
                    let html =
                        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';

                    // Header
                    html += '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[10%]">Code</th>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[25%]">Activity</th>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[10%]">Parent</th>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[20%]">Planned</th>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[25%]">Revisions</th>';
                    html +=
                        '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-[10%]">Progress</th>';
                    html +=
                        '</tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';

                    activities.forEach(activity => {
                        html += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">`;

                        // Code
                        html += `<td class="px-6 py-4 whitespace-nowrap">
                        <code class="bg-gray-100 dark:bg-gray-900 text-blue-600 dark:text-blue-400 rounded px-1 text-sm font-semibold">${activity.code}</code>
                    </td>`;

                        // Name
                        html +=
                            `<td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200 font-medium">${activity.name}</td>`;

                        // Parent
                        html +=
                            `<td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${activity.parent || '-'}</td>`;

                        // Planned Dates
                        html += `<td class="px-6 py-4 text-sm">`;
                        if (activity.planned_start && activity.planned_end) {
                            html += `<div class="flex flex-col gap-1">`;
                            html +=
                                `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Start: ${activity.planned_start}</span>`;
                            html +=
                                `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">End: ${activity.planned_end}</span>`;
                            html += `</div>`;
                        } else {
                            html += `<span class="text-gray-400 dark:text-gray-500 italic">Not set</span>`;
                        }
                        html += `</td>`;

                        // Revisions
                        html += `<td class="px-6 py-4 text-sm">`;
                        if (activity.actual_dates && activity.actual_dates.length > 0) {
                            html += `<div class="flex flex-col gap-2">`;
                            activity.actual_dates.forEach((date, idx) => {
                                html +=
                                    `<div class="p-2 bg-gray-50 dark:bg-gray-700/50 border-l-4 border-yellow-500 rounded shadow-sm">`;
                                html +=
                                    `<div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Revision ${idx + 1}:</div>`;
                                let badges = '';
                                if (date.start) badges +=
                                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 mr-1 mb-1">Start: ${date.start}</span>`;
                                if (date.end) badges +=
                                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">End: ${date.end}</span>`;

                                html += `<div>${badges}</div>`;
                                html +=
                                    `<div class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>${date.reason}</div>`;
                                html += `</div>`;
                            });
                            html += `</div>`;
                        } else {
                            html += `<span class="text-gray-400 dark:text-gray-500 italic">No revisions</span>`;
                        }
                        html += `</td>`;

                        // Progress
                        html += `<td class="px-6 py-4 whitespace-nowrap align-middle">`;
                        let progressClass = activity.progress >= 100 ? 'bg-green-500' :
                            activity.progress >= 50 ? 'bg-blue-500' : 'bg-gray-400';

                        html += `<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">`;
                        html +=
                            `<div class="h-2.5 rounded-full text-xs font-medium text-center leading-2.5 text-white ${progressClass}" style="width: ${activity.progress}%">`;
                        html += `${activity.progress}%`;
                        html += `</div></div>`;
                        html += `</td>`;

                        html += `</tr>`;
                    });

                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                })
                .catch(err => {
                    console.error('Error loading activity chart:', err);
                    document.getElementById('activityChart').innerHTML =
                        '<div class="p-4 text-center text-red-500">Error loading activity data</div>';
                });
        </script>
    @endpush
</x-layouts.app>
