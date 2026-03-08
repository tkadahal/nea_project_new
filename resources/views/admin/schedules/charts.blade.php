<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div
                class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-6 py-4 bg-red-600 border-b border-red-700 flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                    <h3 class="text-lg font-medium text-white">Burn Chart</h3>
                </div>
                <div class="p-6">
                    <div class="relative h-72 w-full">
                        <canvas id="burnChart"></canvas>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-6 py-4 bg-emerald-600 border-b border-emerald-700 flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <h3 class="text-lg font-medium text-white">S-Curve</h3>
                </div>
                <div class="p-6">
                    <div class="relative h-72 w-full">
                        <canvas id="sCurve"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6 ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 bg-blue-600 border-b border-blue-700 flex items-center">
                <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="text-lg font-medium text-white">Activity Timeline</h3>
            </div>
            <div class="p-0 overflow-hidden">
                <div class="overflow-x-auto max-h-[400px]" id="activityChart">
                </div>
            </div>
        </div>

        <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 bg-indigo-600 border-b border-indigo-700 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="text-lg font-medium text-white">Critical Path Analysis (Gantt)</h3>
                </div>
                <select id="ganttViewMode" class="rounded-md border-indigo-300 bg-indigo-500 text-white text-sm">
                    <option value="Day">Day</option>
                    <option value="Week">Week</option>
                    <option value="Month">Month</option>
                </select>
            </div>
            <div class="p-6">
                <div id="ganttContainer" class="gantt-container"
                    style="min-height: 500px; width: 100%; overflow-x: auto;">
                    <svg id="gantt"></svg>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ========================================
       GANTT CHART - ADAPTIVE LIGHT/DARK MODE
       Ultra-clean dark mode with minimal grid
       ======================================== */

        /* ===== LIGHT MODE (Default) ===== */
        .gantt-container,
        #gantt {
            background-color: #ffffff !important;
        }

        .gantt .grid-row {
            fill: #ffffff !important;
        }

        .gantt .grid-header {
            fill: #f9fafb !important;
            /* Gray-50 */
            stroke: #e5e7eb !important;
        }

        .gantt .grid-line {
            stroke: #f3f4f6 !important;
            /* Gray-100 */
        }

        .gantt text,
        .gantt .lower-text,
        .gantt .upper-text {
            fill: #374151 !important;
            /* Gray-700 */
        }

        .gantt .bar-label {
            fill: #1f2937 !important;
            /* Gray-800 */
            font-weight: 600 !important;
        }

        /* ===== DARK MODE (When .dark class is present) ===== */
        .dark .gantt-container,
        .dark #gantt {
            background-color: #111827 !important;
            /* Gray-900 */
        }

        .dark .gantt .grid-row {
            fill: #1f2937 !important;
            /* Gray-800 */
            stroke: #1f2937 !important;
            /* Match background */
            stroke-opacity: 0 !important;
            stroke-width: 0 !important;
        }

        .dark .gantt .grid-header {
            fill: #111827 !important;
            /* Gray-900 */
            stroke: #374151 !important;
            /* Gray-700 */
            stroke-opacity: 0.03 !important;
            /* Very faint */
        }

        /* ULTRA-DIM GRID LINES IN DARK MODE */
        .dark .gantt .grid-line {
            stroke: #374151 !important;
            /* Gray-700 */
            opacity: 0.05 !important;
            /* Almost invisible */
        }

        .dark .gantt .tick {
            stroke: #374151 !important;
            opacity: 0.05 !important;
        }

        .dark .gantt line {
            stroke: #374151 !important;
            opacity: 0.05 !important;
        }

        .dark .gantt text,
        .dark .gantt .lower-text,
        .dark .gantt .upper-text {
            fill: #9ca3af !important;
            /* Gray-400 */
        }

        .dark .gantt .bar-label {
            fill: #e5e7eb !important;
            /* Gray-200 */
            font-weight: 600 !important;
        }

        /* ===== TASK BARS (Same in both modes) ===== */

        /* Normal tasks - Blue */
        .gantt .bar-wrapper.normal .bar,
        .gantt .bar-wrapper .bar {
            fill: #93c5fd !important;
            /* Blue-300 */
            stroke: #3b82f6 !important;
            /* Blue-500 */
            stroke-width: 2 !important;
        }

        .gantt .bar-wrapper.normal .bar-progress,
        .gantt .bar-wrapper .bar-progress {
            fill: #3b82f6 !important;
            /* Blue-500 */
        }

        /* Critical tasks - Red */
        .gantt .bar-wrapper.critical .bar {
            fill: #fca5a5 !important;
            /* Red-300 */
            stroke: #ef4444 !important;
            /* Red-500 */
            stroke-width: 2 !important;
        }

        .gantt .bar-wrapper.critical .bar-progress {
            fill: #dc2626 !important;
            /* Red-600 */
        }

        /* Warning tasks - Yellow */
        .gantt .bar-wrapper.warning .bar {
            fill: #fde047 !important;
            /* Yellow-300 */
            stroke: #eab308 !important;
            /* Yellow-500 */
            stroke-width: 2 !important;
        }

        .gantt .bar-wrapper.warning .bar-progress {
            fill: #ca8a04 !important;
            /* Yellow-600 */
        }

        /* ===== HIDE UNWANTED HIGHLIGHTS ===== */
        .gantt .today-highlight,
        .gantt .weekend-highlight,
        .gantt .holiday-highlight,
        .gantt .holiday-back {
            fill: transparent !important;
            opacity: 0 !important;
        }

        /* ===== DEPENDENCY ARROWS ===== */
        .gantt .arrow {
            stroke: #6b7280 !important;
            /* Gray-500 */
            stroke-width: 1.5 !important;
        }

        .dark .gantt .arrow {
            stroke: #9ca3af !important;
            /* Gray-400 */
            stroke-width: 1.5 !important;
        }

        /* ===== ENSURE BARS ARE VISIBLE ===== */
        .gantt .bar-wrapper,
        .gantt .bar,
        .gantt .bar-progress {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* ===== LAYERING ===== */
        .gantt .grid-row,
        .gantt .grid-line {
            z-index: 1 !important;
        }

        .gantt .bar-wrapper {
            z-index: 10 !important;
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
        <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
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


            // =======================================================
            // 4. GANTT CHART WITH ROBUST ERROR HANDLING
            // =======================================================
            let ganttInstance = null;

            function initGantt() {
                const loadingEl = document.getElementById('ganttLoading');
                const wrapper = document.getElementById('ganttContainer');
                const ganttSvg = document.getElementById('gantt');
                const viewModeSelect = document.getElementById('ganttViewMode');
                const viewMode = viewModeSelect ? viewModeSelect.value : 'Day';

                if (loadingEl) loadingEl.classList.remove('hidden');

                console.log('🚀 Fetching Gantt data...');

                fetch('{{ route('admin.projects.schedules.api.gantt-data', $project) }}')
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                        return res.json();
                    })
                    .then(rawTasks => {
                        if (loadingEl) loadingEl.classList.add('hidden');

                        console.log(`📦 Received ${rawTasks.length} raw tasks`);

                        if (!rawTasks || rawTasks.length === 0) {
                            wrapper.innerHTML =
                                '<div class="p-12 text-center text-gray-500 dark:text-gray-400 italic">No activity data found.</div>';
                            return;
                        }

                        // Validate tasks
                        const tasks = rawTasks.map((task, index) => {
                            return {
                                id: task.id || `task_${index}`,
                                name: task.name || `Task ${index + 1}`,
                                start: task.start || new Date().toISOString().split('T')[0],
                                end: task.end || new Date(Date.now() + 86400000).toISOString().split('T')[0],
                                progress: Math.max(0, Math.min(100, task.progress || 0)),
                                dependencies: task.dependencies || '',
                                custom_class: task.custom_class || 'normal'
                            };
                        });

                        // Clean dependencies
                        const taskIds = new Set(tasks.map(t => t.id));
                        tasks.forEach(task => {
                            if (task.dependencies && task.dependencies.trim()) {
                                const deps = task.dependencies.split(',')
                                    .map(d => d.trim())
                                    .filter(d => d && taskIds.has(d));
                                task.dependencies = deps.join(',') || '';
                            }
                        });

                        console.log(`✅ Validated ${tasks.length} tasks`);
                        console.log('📋 Sample task:', tasks[0]);

                        if (ganttSvg) ganttSvg.innerHTML = '';

                        try {
                            // MINIMAL CONFIGURATION - Less likely to crash
                            ganttInstance = new Gantt("#gantt", tasks, {
                                // Don't specify view_modes - use defaults
                                view_mode: viewMode,
                                // Minimal required config
                                bar_height: 25,
                                padding: 18,
                                // Custom popup
                                custom_popup_html: function(task) {
                                    const start = task._start ? task._start.toLocaleDateString() : 'N/A';
                                    const end = task._end ? task._end.toLocaleDateString() : 'N/A';

                                    let badge = 'bg-indigo-100 text-indigo-800';
                                    let status = 'Normal';
                                    if (task.custom_class === 'critical') {
                                        badge = 'bg-red-100 text-red-800';
                                        status = 'Critical';
                                    }

                                    return `
                            <div class="p-3 bg-white dark:bg-gray-800 shadow-xl rounded-lg" style="min-width: 200px;">
                                <p class="font-bold text-sm mb-2">${task.name}</p>
                                <p class="text-xs mb-1"><strong>Start:</strong> ${start}</p>
                                <p class="text-xs mb-1"><strong>End:</strong> ${end}</p>
                                <p class="text-xs mb-2"><strong>Progress:</strong> ${task.progress}%</p>
                                <span class="text-xs px-2 py-1 rounded ${badge}">${status}</span>
                            </div>
                        `;
                                }
                            });

                            console.log('✅ Gantt chart rendered!');
                        } catch (err) {
                            console.error('❌ Render error:', err);
                            console.error('Stack:', err.stack);

                            wrapper.innerHTML = `
                    <div class="p-8 text-center">
                        <p class="text-red-500 font-medium mb-2">Failed to render chart</p>
                        <p class="text-sm text-gray-600 mb-4">${err.message}</p>
                        <button onclick="initGantt()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Retry
                        </button>
                    </div>
                `;
                        }
                    })
                    .catch(err => {
                        if (loadingEl) loadingEl.classList.add('hidden');
                        console.error('❌ Fetch error:', err);
                        wrapper.innerHTML = `
                <div class="p-8 text-center">
                    <p class="text-red-500 font-medium mb-2">Failed to load data</p>
                    <p class="text-sm text-gray-600 mb-4">${err.message}</p>
                    <button onclick="initGantt()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Retry
                    </button>
                </div>
            `;
                    });
            }

            // View mode change - safe version
            document.getElementById('ganttViewMode')?.addEventListener('change', function(e) {
                console.log('🔄 View mode changed to:', e.target.value);

                // Instead of trying to change view mode on existing instance,
                // just re-initialize the whole chart (safer)
                initGantt();
            });

            // Styles
            const style = document.createElement('style');
            style.innerHTML = `
                .gantt .bar-wrapper.critical .bar { fill: #fca5a5 !important; stroke: #ef4444 !important; }
                .gantt .bar-wrapper.warning .bar { fill: #fde047 !important; stroke: #eab308 !important; }
                .gantt .bar-wrapper.normal .bar { fill: #93c5fd !important; stroke: #3b82f6 !important; }
            `;
            document.head.appendChild(style);

            // Init
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🚀 DOM loaded, initializing...');
                setTimeout(initGantt, 100);
            });
        </script>
    @endpush
</x-layouts.app>
