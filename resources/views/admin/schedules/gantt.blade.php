<x-layouts.app>
    {{-- Full Screen Container Layout --}}
    <div class="flex flex-col h-screen overflow-hidden bg-white dark:bg-gray-900">

        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Schedule Overview
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Overview of all accessible contract schedules
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.contracts.schedules.charts', $contract) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        View Charts
                    </a>
                </div>

            </div>
        </div>

        {{-- Sticky Header --}}
        <header
            class="flex-none bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 shadow-sm z-20">
            <div class="flex items-center justify-between">

                {{-- Left: Back Button & Title --}}
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.contracts.schedules.charts', $contract) }}"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                        title="Back to Charts">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">Critical Path Analysis
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md">{{ $contract->title }}</p>
                    </div>
                </div>

                {{-- Right: View Modes & Refresh --}}
                <div class="flex items-center space-x-3">
                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-md p-1 shadow-inner">
                        <button
                            class="view-btn px-3 py-1.5 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600 shadow-sm"
                            data-mode="Day">Day</button>
                        <button
                            class="view-btn px-3 py-1.5 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600"
                            data-mode="Week">Week</button>
                        <button
                            class="view-btn px-3 py-1.5 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600"
                            data-mode="Month">Month</button>
                    </div>

                    <button onclick="initGantt()"
                        class="p-2 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                        title="Refresh Chart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Main Gantt Container (Fills remaining height) --}}
        <main class="flex-1 overflow-hidden relative bg-white dark:bg-gray-900" id="ganttContainer">

            {{-- Wrapper: clips vertical overflow, allows horizontal scroll --}}
            <div class="absolute inset-0 gantt-scroll-wrapper">
                <div id="gantt" class="min-w-full"></div>
            </div>

            {{-- Loading Overlay --}}
            <div id="ganttLoading"
                class="absolute inset-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm flex items-center justify-center z-30 transition-opacity duration-300">
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-10 w-10 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400 font-medium tracking-wide">Loading Timeline
                        Data...</span>
                </div>
            </div>
        </main>
    </div>

    <style>
        /* ========================================
           SCROLLBAR: HIDE VERTICAL, SHOW HORIZONTAL
           ========================================
           Strategy:
           - .gantt-scroll-wrapper clips overflow — no scroll here
           - .gantt-container (injected by Frappe) is the real scroll surface
           - width: 0  → kills the vertical scrollbar track entirely
           - height: 6px → keeps the horizontal scrollbar visible & styled
           ======================================== */

        /* Our wrapper just clips — no scroll of its own */
        .gantt-scroll-wrapper {
            overflow: hidden;
        }

        /* Frappe's injected container: horizontal scroll only */
        .gantt-container {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            /* Firefox */
            scrollbar-width: thin !important;
            scrollbar-color: #9ca3af transparent !important;
        }

        /* Chrome / Safari / Edge */
        .gantt-container::-webkit-scrollbar {
            width: 0px !important;
            /* hides vertical track */
            height: 6px !important;
            /* shows horizontal track */
        }

        .gantt-container::-webkit-scrollbar-track {
            background: transparent !important;
        }

        .gantt-container::-webkit-scrollbar-thumb {
            background-color: #9ca3af !important;
            border-radius: 3px !important;
        }

        .dark .gantt-container::-webkit-scrollbar-thumb {
            background-color: #4b5563 !important;
        }

        /* #gantt div itself must never scroll */
        #gantt {
            overflow: hidden !important;
        }

        /* ========================================
           GANTT CHART STYLES (Adaptive Light/Dark)
           ======================================== */

        /* Light Mode */
        .gantt .grid-row {
            fill: #ffffff !important;
        }

        .gantt .grid-header {
            fill: #f9fafb !important;
            stroke: #e5e7eb !important;
        }

        .gantt .grid-line {
            stroke: #f3f4f6 !important;
        }

        .gantt text,
        .gantt .lower-text,
        .gantt .upper-text {
            fill: #374151 !important;
            font-size: 12px;
        }

        .gantt .bar-label {
            fill: #1f2937 !important;
            font-weight: 600 !important;
            font-size: 11px;
        }

        /* Dark Mode */
        .dark .gantt .grid-row {
            fill: #1f2937 !important;
            stroke: #1f2937 !important;
            stroke-opacity: 0 !important;
        }

        .dark .gantt .grid-header {
            fill: #111827 !important;
            stroke: #374151 !important;
            stroke-opacity: 0.1 !important;
        }

        .dark .gantt .grid-line {
            stroke: #374151 !important;
            opacity: 0.05 !important;
        }

        .dark .gantt .tick {
            stroke: #374151 !important;
            opacity: 0.05 !important;
        }

        .dark .gantt text,
        .dark .gantt .lower-text,
        .dark .gantt .upper-text {
            fill: #9ca3af !important;
        }

        .dark .gantt .bar-label {
            fill: #e5e7eb !important;
        }

        /* Task Bars - Normal (Blue) */
        .gantt .bar-wrapper.normal .bar {
            fill: #93c5fd !important;
            stroke: #3b82f6 !important;
            stroke-width: 2 !important;
            rx: 4;
        }

        .gantt .bar-wrapper.normal .bar-progress {
            fill: #3b82f6 !important;
            rx: 4;
        }

        /* Task Bars - Critical (Red) */
        .gantt .bar-wrapper.critical .bar {
            fill: #fca5a5 !important;
            stroke: #ef4444 !important;
            stroke-width: 2 !important;
            rx: 4;
        }

        .gantt .bar-wrapper.critical .bar-progress {
            fill: #dc2626 !important;
            rx: 4;
        }

        /* Task Bars - Warning (Yellow) */
        .gantt .bar-wrapper.warning .bar {
            fill: #fde047 !important;
            stroke: #eab308 !important;
            stroke-width: 2 !important;
            rx: 4;
        }

        .gantt .bar-wrapper.warning .bar-progress {
            fill: #ca8a04 !important;
            rx: 4;
        }

        /* Clean Up Highlights */
        .gantt .today-highlight,
        .gantt .weekend-highlight,
        .gantt .holiday-highlight,
        .gantt .holiday-back {
            fill: transparent !important;
            opacity: 0 !important;
        }

        /* Dependency Arrows */
        .gantt .arrow {
            stroke: #6b7280 !important;
            stroke-width: 1.5 !important;
            fill: none !important;
        }

        .dark .gantt .arrow {
            stroke: #9ca3af !important;
        }

        /* Ensure bar visibility */
        .gantt .bar-wrapper,
        .gantt .bar,
        .gantt .bar-progress {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* View Button Active State */
        .view-btn.active {
            background-color: #fff !important;
            color: #4f46e5 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .view-btn.active {
            background-color: #374151 !important;
            color: #818cf8 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
        }
    </style>

    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
        <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>

        <script>
            let ganttInstance = null;
            let currentViewMode = 'Day';

            // ============================================
            // HELPER: Override Frappe's scroll behaviour
            // Frappe hardcodes overflow:auto on .gantt-container
            // after every render, so we patch it via inline styles.
            // Called immediately + after a short tick (Frappe
            // sometimes finishes DOM writes asynchronously).
            // ============================================
            function fixGanttScroll() {
                document.querySelectorAll('.gantt-container').forEach(el => {
                    el.style.setProperty('overflow-x', 'auto', 'important');
                    el.style.setProperty('overflow-y', 'hidden', 'important');
                });
            }

            // ============================================
            // VIEW MODE BUTTONS
            // ============================================
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                    e.target.classList.add('active');
                    currentViewMode = e.target.getAttribute('data-mode');
                    if (ganttInstance) {
                        ganttInstance.change_view_mode(currentViewMode);
                        fixGanttScroll();
                        setTimeout(fixGanttScroll, 50);
                    }
                });
            });

            document.querySelector(`.view-btn[data-mode="${currentViewMode}"]`)?.classList.add('active');

            // ============================================
            // CORE: Load & Render Gantt
            // ============================================
            function initGantt() {
                const loadingEl = document.getElementById('ganttLoading');
                const wrapper = document.getElementById('ganttContainer');
                const ganttEl = document.getElementById('gantt');

                if (loadingEl) loadingEl.classList.remove('hidden');

                fetch('{{ route('admin.contracts.schedules.api.gantt-data', $contract) }}')
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP ${res.status}: Failed to fetch data`);
                        return res.json();
                    })
                    .then(rawTasks => {
                        if (loadingEl) loadingEl.classList.add('hidden');

                        if (!rawTasks || rawTasks.length === 0) {
                            wrapper.innerHTML = `
                                <div class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400 p-8">
                                    <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                                    <h3 class="text-lg font-medium">No Activity Data</h3>
                                    <p class="text-sm">There are no scheduled activities to display for this contract.</p>
                                </div>`;
                            return;
                        }

                        // Normalize tasks
                        const tasks = rawTasks.map((task, index) => ({
                            id: task.id || `task_${index}`,
                            name: task.name || `Task ${index + 1}`,
                            start: task.start || new Date().toISOString().split('T')[0],
                            end: task.end || new Date(Date.now() + 86400000).toISOString().split('T')[0],
                            progress: Math.max(0, Math.min(100, task.progress || 0)),
                            dependencies: task.dependencies || '',
                            custom_class: task.custom_class || 'normal'
                        }));

                        // Strip invalid dependency IDs
                        const taskIds = new Set(tasks.map(t => t.id));
                        tasks.forEach(task => {
                            if (task.dependencies?.trim()) {
                                const deps = task.dependencies.split(',')
                                    .map(d => d.trim())
                                    .filter(d => d && taskIds.has(d));
                                task.dependencies = deps.join(',') || '';
                            }
                        });

                        if (ganttEl) ganttEl.innerHTML = '';

                        try {
                            ganttInstance = new Gantt("#gantt", tasks, {
                                view_mode: currentViewMode,
                                bar_height: 30,
                                padding: 20,
                                date_format: 'YYYY-MM-DD',
                                custom_popup_html: function(task) {
                                    const start = task._start ? task._start.toLocaleDateString() : 'N/A';
                                    const end = task._end ? task._end.toLocaleDateString() : 'N/A';

                                    let badgeClass =
                                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200';
                                    let status = 'Normal';
                                    if (task.custom_class === 'critical') {
                                        badgeClass =
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                        status = 'Critical / Delayed';
                                    } else if (task.custom_class === 'warning') {
                                        badgeClass =
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                        status = 'Warning';
                                    }

                                    return `
                                        <div class="p-4 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-100 dark:border-gray-700" style="min-width: 240px;">
                                            <h5 class="font-bold text-gray-900 dark:text-white mb-2 border-b dark:border-gray-700 pb-2 leading-tight">${task.name}</h5>
                                            <div class="grid grid-cols-2 gap-y-2 text-sm mb-3">
                                                <div class="text-gray-500 dark:text-gray-400">Start:</div>
                                                <div class="text-right font-medium text-gray-800 dark:text-gray-200">${start}</div>
                                                <div class="text-gray-500 dark:text-gray-400">End:</div>
                                                <div class="text-right font-medium text-gray-800 dark:text-gray-200">${end}</div>
                                                <div class="text-gray-500 dark:text-gray-400">Progress:</div>
                                                <div class="text-right font-medium text-gray-800 dark:text-gray-200">${task.progress}%</div>
                                            </div>
                                            <div class="text-center">
                                                <span class="inline-block w-full text-center text-xs px-2 py-1 rounded ${badgeClass} font-semibold tracking-wide uppercase">${status}</span>
                                            </div>
                                        </div>
                                    `;
                                }
                            });

                            // Patch immediately + after async Frappe DOM writes settle
                            fixGanttScroll();
                            setTimeout(fixGanttScroll, 50);

                        } catch (err) {
                            console.error('Gantt Render Error:', err);
                            wrapper.innerHTML = `
                                <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-full mb-4">
                                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <p class="text-red-500 font-medium mb-2 text-lg">Rendering Error</p>
                                    <p class="text-sm text-gray-500 mb-6 max-w-md">${err.message}</p>
                                    <button onclick="initGantt()" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition shadow-sm font-medium">Retry</button>
                                </div>`;
                        }
                    })
                    .catch(err => {
                        if (loadingEl) loadingEl.classList.add('hidden');
                        console.error('Network Error:', err);
                        wrapper.innerHTML = `
                            <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <p class="text-red-500 font-medium mb-2 text-lg">Network Error</p>
                                <p class="text-sm text-gray-500 mb-6 max-w-md">Could not fetch schedule data from the server.</p>
                                <button onclick="initGantt()" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition shadow-sm font-medium">Retry</button>
                            </div>`;
                    });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initGantt();
            });
        </script>
    @endpush
</x-layouts.app>
