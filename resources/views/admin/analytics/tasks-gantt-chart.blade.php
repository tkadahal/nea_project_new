<x-layouts.app>
    {{-- Page Title --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            {{ __('Project Gantt Chart') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ __('Visualize your tasks timeline and progress') }}
        </p>
    </div>

    {{-- Filters and View Mode Buttons --}}
    <div class="flex flex-col gap-4 mb-4">
        {{-- Filters (Directorate and Priority) on one line --}}
        <div class="flex flex-col md:flex-row items-center gap-4">
            <div class="w-full md:w-1/2">
                <x-forms.select label="Directorate" name="directorate_id" :options="collect($availableDirectorates)
                    ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                    ->values()
                    ->all()" :selected="request()->input('directorate_id') ? (string) request()->input('directorate_id') : null"
                    placeholder="Select a Directorate..." class="w-full" />
            </div>
            <div class="w-full md:w-1/2">
                <x-forms.select label="Priority" name="priority" :options="collect($priorities)
                    ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                    ->values()
                    ->all()" :selected="request()->input('priority') ? (string) request()->input('priority') : null"
                    placeholder="Select a Priority..." class="w-full" />
            </div>
        </div>

        {{-- View Mode Buttons (Day, Week, Month, Year) as separate buttons on next line --}}
        <div class="flex space-x-2">
            <button onclick="setViewMode('day')" id="day-btn"
                class="px-3 py-1 text-sm font-medium rounded-md bg-gray-700 text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="md:hidden">D</span><span class="hidden md:inline">Day</span>
            </button>
            <button onclick="setViewMode('week')" id="week-btn"
                class="px-3 py-1 text-sm font-medium rounded-md bg-gray-700 text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="md:hidden">W</span><span class="hidden md:inline">Week</span>
            </button>
            <button onclick="setViewMode('month')" id="month-btn"
                class="px-3 py-1 text-sm font-medium rounded-md bg-gray-700 text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="md:hidden">M</span><span class="hidden md:inline">Month</span>
            </button>
            <button onclick="setViewMode('year')" id="year-btn"
                class="px-3 py-1 text-sm font-medium rounded-md bg-gray-700 text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="md:hidden">Y</span><span class="hidden md:inline">Year</span>
            </button>
        </div>
    </div>

    <div class="flex flex-col md:flex-row w-full">
        <div class="bg-gray-800 p-4 rounded-lg shadow flex-1" style="overflow-x: auto; min-height: 400px;">
            <div id="gantt-container" style="width: 100%; height: 400px;"></div>
        </div>
        <div id="tag-sidebar" class="w-full md:w-1/6 bg-gray-700 p-4 rounded-lg shadow md:ml-4 mt-4 md:mt-0"
            style="min-height: 400px; overflow-y: auto;">
            <h3 class="text-lg font-semibold text-gray-200 mb-2">Tags</h3>
            <div id="tag-filter" class="flex flex-col gap-2"></div>
        </div>
    </div>

    <style>
        #gantt-container .gantt_task_line {
            background-color: #6b46c1;
            border-radius: 4px;
            cursor: pointer;
            padding: 2px;
            min-width: 2px;
        }

        #gantt-container .gantt_task_progress {
            background-color: #8a4af3;
            border-radius: 4px;
        }

        #gantt-container .gantt_link_line {
            stroke: #a0aec0;
            stroke-width: 2;
        }

        #gantt-container .gantt_grid_data .gantt_row {
            background-color: #2d3748;
        }

        #gantt-container .gantt_grid_head_cell {
            background-color: #4a5568;
            color: #e2e8f0;
        }

        #gantt-container .gantt_scale_cell {
            background-color: #4a5568;
            color: #e2e8f0;
        }

        #gantt-container .gantt_task_content {
            color: #e2e8f0;
            font-size: 12px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gantt_tooltip {
            background: #2d3748;
            color: #e2e8f0;
            padding: 8px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 12px;
            z-index: 9999;
            max-width: 300px;
        }

        #tag-sidebar .tag-button {
            background-color: #4a5568;
            color: #e2e8f0;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-align: center;
        }

        #tag-sidebar .tag-button.active {
            background-color: #8a4af3;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .w-full.md\:w-1\/2 {
                width: 100%;
            }

            .w-full.md\:w-1\/4 {
                width: 100%;
            }

            #tag-sidebar {
                width: 100%;
                margin-left: 0;
                margin-top: 1rem;
            }
        }
    </style>

    @push('scripts')
        <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
        <link href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css" rel="stylesheet">
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                console.log('DOMContentLoaded fired at:', new Date().toISOString());
                const initialTasksData = @json($tasks);
                const initialLinksData = @json($links);
                console.log('Initial Tasks Data:', initialTasksData);
                console.log('Initial Links Data:', initialLinksData);

                let currentViewMode = 'week'; // Initial view mode

                let activeTag = null;

                function formatTasksForGantt(tasksToFormat) {
                    return tasksToFormat.map(task => {
                        console.log('Formatting task:', task.id, 'start:', task.start, 'end:', task.end,
                            'status:', task.status);
                        const start = new Date(task.start);
                        const end = new Date(task.end);
                        const duration = Math.round((end - start) / (1000 * 60 * 60 * 24)); // Days
                        return {
                            id: task.id.toString(),
                            text: task.title,
                            start_date: task.start,
                            end_date: task.end,
                            progress: parseFloat(task.progress) || 0,
                            parent: task.parent_id ? task.parent_id.toString() : 0,
                            open: true,
                            project_name: task.project_name || 'N/A',
                            directorate: task.directorate || 'N/A',
                            assigned_user: task.assigned_user || 'N/A',
                            gantt_cal: task.gantt_cal || 'N/A',
                            description: task.description || 'No description',
                            status: task.status || 'N/A',
                            priority: task.priority || 'N/A',
                            tags: task.tags ? (Array.isArray(task.tags) ? task.tags : task.tags.split(',').map(
                                t => t.trim())) : [],
                            duration: isNaN(duration) ? 0 : duration
                        };
                    });
                }

                function initializeGantt(tasksToRender, linksToRender, viewMode = 'week') {
                    console.log('Reinitializing Gantt with viewMode:', viewMode, 'tasks:', tasksToRender.length,
                        'links:', linksToRender.length);

                    const formattedTasks = formatTasksForGantt(tasksToRender);

                    gantt.clearAll();

                    gantt.config.date_format = "%Y-%m-%d";
                    gantt.config.scale_height = 50;
                    gantt.config.row_height = 40;
                    gantt.config.task_height = 20;
                    gantt.config.link_line_width = 2;
                    gantt.config.links = {
                        finish_to_start: 0
                    };
                    gantt.config.columns = [];
                    gantt.config.scale_unit_width = 30;
                    gantt.config.min_column_width = 20;

                    // Enable tooltip plugin
                    gantt.plugins({
                        tooltip: true
                    });

                    // Configure detailed tooltip with only duration
                    gantt.templates.tooltip_text = function(start, end, task) {
                        console.log('Tooltip triggered for task:', task.id, 'text:', task.text, 'status:', task
                            .status, 'timestamp:', new Date().toISOString());
                        let entity = 'N/A';
                        if (task.project_name !== 'N/A') entity = task.project_name;
                        else if (task.assigned_user !== 'N/A') entity = task.assigned_user;
                        else if (task.directorate !== 'N/A') entity = task.directorate;

                        return `
                            <b>Title:</b> ${task.text}<br>
                            <b>Description:</b> ${task.description}<br>
                            <b>Duration:</b> ${task.duration} days<br>
                            <b>Directorate/Department/Project:</b> ${entity}<br>
                            <b>Status:</b> ${task.status}<br>
                            <b>Priority:</b> ${task.priority}
                        `;
                    };

                    // Configure scales based on view mode and current date (August 27, 2025)
                    const now = new Date();
                    const currentYear = now.getFullYear();
                    const currentMonth = now.getMonth(); // 0-based (7 for August)

                    switch (viewMode) {
                        case 'day':
                            const startDay = new Date(currentYear, currentMonth, 1); // First of August 2025
                            const endDay = new Date(currentYear, currentMonth + 1, 0); // Last of August 2025 (max 31st)
                            gantt.config.scales = [{
                                unit: "day",
                                step: 1,
                                format: "%d",
                                min_date: startDay,
                                max_date: endDay
                            }];
                            gantt.config.start_date = startDay;
                            gantt.config.end_date = endDay;
                            break;
                        case 'week':
                            const startWeek = new Date(currentYear, currentMonth - 2, 1); // June 1, 2025
                            const endWeek = new Date(currentYear, currentMonth + 1, 0); // August 31, 2025
                            gantt.config.scales = [{
                                    unit: "week",
                                    step: 1,
                                    format: "Week #%W"
                                },
                                {
                                    unit: "month",
                                    step: 1,
                                    format: "%M"
                                }
                            ];
                            gantt.config.start_date = startWeek;
                            gantt.config.end_date = endWeek;
                            break;
                        case 'month':
                            const startMonth = new Date(currentYear, 0, 1); // January 1, 2025
                            const endMonth = new Date(currentYear, 11, 31); // December 31, 2025
                            gantt.config.scales = [{
                                unit: "month",
                                step: 1,
                                format: "%F"
                            }];
                            gantt.config.start_date = startMonth;
                            gantt.config.end_date = endMonth;
                            break;
                        case 'year':
                            const startYear = new Date(currentYear - 1, 0, 1); // January 1, 2024
                            const endYear = new Date(currentYear + 1, 11, 31); // December 31, 2026
                            gantt.config.scales = [{
                                unit: "year",
                                step: 1,
                                format: "%Y"
                            }];
                            gantt.config.start_date = startYear;
                            gantt.config.end_date = endYear;
                            break;
                    }

                    gantt.config.fit_tasks = true;

                    gantt.init("gantt-container");
                    gantt.parse({
                        data: formattedTasks,
                        links: linksToRender
                    });
                    console.log('Gantt chart initialized at:', new Date().toISOString(), 'start:', gantt.config
                        .start_date, 'end:', gantt.config.end_date);

                    // Update tag sidebar
                    updateTagFilter(formattedTasks);
                }

                function applyFilters(directorateId, priorityId) {
                    console.log('Applying filters:', {
                        directorateId,
                        priorityId,
                        timestamp: new Date().toISOString()
                    });

                    const url = new URL('/admin/tasks/gantt-chart', window.location.origin);
                    if (directorateId) url.searchParams.set('directorate_id', directorateId);
                    if (priorityId) url.searchParams.set('priority', priorityId);
                    console.log('AJAX URL:', url.toString());

                    const browserUrl = new URL(window.location.href);
                    if (directorateId) {
                        browserUrl.searchParams.set('directorate_id', directorateId);
                    } else {
                        browserUrl.searchParams.delete('directorate_id');
                    }
                    if (priorityId) {
                        browserUrl.searchParams.set('priority', priorityId);
                    } else {
                        browserUrl.searchParams.delete('priority');
                    }
                    window.history.pushState({}, '', browserUrl.toString());
                    console.log('Browser URL updated:', browserUrl.toString());

                    fetch(url.toString(), {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            console.log('AJAX response status:', response.status, 'timestamp:', new Date()
                                .toISOString());
                            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                            return response.json();
                        })
                        .then(data => {
                            console.log('AJAX response data:', data, 'timestamp:', new Date().toISOString());
                            if (data.tasks && Array.isArray(data.tasks) && data.links && Array.isArray(data
                                    .links)) {
                                initializeGantt(data.tasks, data.links, currentViewMode);
                            } else {
                                console.warn('No tasks or links in response or invalid format:', data);
                                initializeGantt([], [], currentViewMode);
                                alert('No tasks match the selected filters.');
                            }
                        })
                        .catch(error => {
                            console.error('AJAX request failed:', error, 'timestamp:', new Date().toISOString());
                            alert('Failed to load tasks: ' + error.message);
                        });
                }

                function updateTagFilter(tasks) {
                    const allTags = [...new Set(tasks.flatMap(task => task.tags))].filter(tag => tag);
                    const tagFilter = document.getElementById('tag-filter');
                    tagFilter.innerHTML = '';
                    allTags.forEach(tag => {
                        const button = document.createElement('div');
                        button.className = `tag-button ${activeTag === tag ? 'active' : ''}`;
                        button.textContent = tag;
                        button.onclick = () => filterByTag(tag);
                        tagFilter.appendChild(button);
                    });
                }

                function filterByTag(tag) {
                    activeTag = activeTag === tag ? null : tag;
                    const filteredTasks = initialTasksData.filter(task => !activeTag || (task.tags && task.tags
                        .includes(activeTag)));
                    initializeGantt(filteredTasks, initialLinksData, currentViewMode);
                    updateTagFilter(filteredTasks);
                }

                document.addEventListener('change', function(event) {
                    const target = event.target;
                    console.log('Native change event:', {
                        name: target.name,
                        value: target.value,
                        timestamp: new Date().toISOString()
                    });
                    if (target.matches('input[name="directorate_id"], input[name="priority"]')) {
                        const directorateId = document.querySelector('input[name="directorate_id"]')?.value ||
                            '';
                        const priorityId = document.querySelector('input[name="priority"]')?.value || '';
                        console.log('Applying filters (native):', {
                            directorateId,
                            priorityId,
                            timestamp: new Date().toISOString()
                        });
                        applyFilters(directorateId, priorityId);
                    }
                }, true);

                if (window.jQuery) {
                    jQuery(document).on('change', 'input[name="directorate_id"], input[name="priority"]', function() {
                        console.log('jQuery change event:', {
                            name: this.name,
                            value: this.value,
                            timestamp: new Date().toISOString()
                        });
                        const directorateId = jQuery('input[name="directorate_id"]').val() || '';
                        const priorityId = jQuery('input[name="priority"]').val() || '';
                        console.log('Applying filters (jQuery):', {
                            directorateId,
                            priorityId,
                            timestamp: new Date().toISOString()
                        });
                        applyFilters(directorateId, priorityId);
                    });
                }

                const urlParams = new URLSearchParams(window.location.search);
                const initialDirectorateId = urlParams.get('directorate_id') || '';
                const initialPriorityId = urlParams.get('priority') || '';
                console.log('Initial URL parameters:', {
                    initialDirectorateId,
                    initialPriorityId,
                    timestamp: new Date().toISOString()
                });
                if (initialDirectorateId || initialPriorityId) {
                    const directorateInput = document.querySelector('input[name="directorate_id"]');
                    const priorityInput = document.querySelector('input[name="priority"]');
                    if (directorateInput) directorateInput.value = initialDirectorateId;
                    if (priorityInput) priorityInput.value = initialPriorityId;

                    const $ = window.jQuery;
                    if ($) {
                        $('.js-single-select[data-name="directorate_id"]').trigger('options-updated', {
                            selected: initialDirectorateId
                        });
                        $('.js-single-select[data-name="priority"]').trigger('options-updated', {
                            selected: initialPriorityId
                        });
                    }

                    applyFilters(initialDirectorateId, initialPriorityId);
                }

                window.setViewMode = function(mode) {
                    console.log('Changing view mode to:', mode, 'timestamp:', new Date().toISOString());
                    currentViewMode = mode;
                    document.querySelectorAll('#day-btn, #week-btn, #month-btn, #year-btn').forEach(btn => {
                        btn.classList.remove('bg-blue-500', 'text-white', 'dark:bg-blue-600',
                            'dark:hover:bg-blue-700');
                        btn.classList.add('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-300',
                            'dark:hover:bg-gray-500');
                    });
                    const activeBtn = document.getElementById(mode + '-btn');
                    if (activeBtn) {
                        activeBtn.classList.remove('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-300',
                            'dark:hover:bg-gray-500');
                        activeBtn.classList.add('bg-blue-500', 'text-white', 'dark:bg-blue-600',
                            'dark:hover:bg-blue-700');
                    }
                    initializeGantt(initialTasksData, initialLinksData, mode);
                };

                // Initialize Gantt chart with week view
                setViewMode('week');
            });
        </script>
    @endpush
</x-layouts.app>
