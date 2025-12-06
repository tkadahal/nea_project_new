<div>
    {{-- View Mode Buttons and Filters --}}


    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow"
        style="width: 100%; overflow-x: auto; min-height: 400px;">
        <div id="gantt-container" class="dark:bg-gray-900" style="width: 100%; overflow-x: auto; min-height: 400px;">
            <svg id="gantt-target" style="width: 100%; height: auto; min-width: 1000px;"></svg>
        </div>
    </div>

    <style>
        *,
        ::after,
        ::before,
        ::backdrop,
        ::file-selector-button {
            z-index: 1;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                console.log('DOMContentLoaded fired');
                console.log('Gantt library loaded:', typeof Gantt !== 'undefined');
                const initialTasksData = @json($tasks);
                console.log('Initial Tasks Data (from backend):', initialTasksData);

                const directorateTitlesMap = @json($availableDirectorates);
                const priorityTitlesMap = @json($priorities);

                let ganttInstance;

                function formatTasksForGantt(tasksToFormat) {
                    return tasksToFormat.map(task => ({
                        id: task.id.toString(),
                        name: task.title,
                        start: task.start,
                        end: task.end,
                        progress: task.progress ?? 0,
                        dependencies: '',
                        custom_class: 'gantt-task',
                        directorate_id: task.directorate_id,
                        directorate_title: task.directorate,
                        priority_id: task.priority,
                        priority_title: priorityTitlesMap[task.priority] || 'N/A'
                    }));
                }

                function initializeGantt(tasksToRender, viewMode = 'Week') {
                    const formattedTasks = formatTasksForGantt(tasksToRender);
                    console.log('Formatted Tasks for Gantt:', formattedTasks);

                    if (ganttInstance) {
                        ganttInstance.clear();
                        ganttInstance = null;
                    }

                    // Adjust column_width based on view mode to maintain consistent chart width
                    let column_width;
                    switch (viewMode) {
                        case 'Quarter Day':
                            column_width = 45 / 4; // Approx. 11.25px per quarter day
                            break;
                        case 'Half Day':
                            column_width = 45 / 2; // Approx. 22.5px per half day
                            break;
                        case 'Day':
                            column_width = 45;
                            break;
                        case 'Week':
                            column_width = 45 * 7;
                            break;
                        case 'Month':
                            column_width = 45 * 30;
                            break;
                        case 'Year':
                            column_width = 45 * 365; // Approx. 16425px per year (adjust as needed)
                            break;
                        default:
                            column_width = 45;
                    }

                    ganttInstance = new Gantt("#gantt-target", formattedTasks, {
                        view_mode: viewMode,
                        upper_header_height: 45,
                        lower_header_height: 30,
                        date_format: 'YYYY-MM-DD',
                        snap_at: '1d',
                        bar_height: 30,
                        bar_corner_radius: 3,
                        padding: 18,
                        infinite_padding: true,
                        container_height: 'auto',
                        popup_on: 'click',
                        column_width: column_width,
                        today_button: true,
                        scroll_to: 'today',
                        view_mode_select: true,
                        custom_popup_html: function(task) {
                            return `
                                <div class="p-3 rounded-lg bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-200 text-sm shadow">
                                    <div class="font-semibold mb-1">${task.name}</div>
                                    <div><strong>Start:</strong> ${task.start}</div>
                                    <div><strong>End:</strong> ${task.end}</div>
                                    <div><strong>Progress:</strong> ${task.progress}%</div>
                                    <div><strong>Directorate:</strong> ${task.directorate_title || 'N/A'}</div>
                                    <div><strong>Priority:</strong> ${task.priority_title || 'N/A'}</div>
                                </div>
                            `;
                        }
                    });

                    // Update button styles to reflect the active view mode
                    document.querySelectorAll('.flex.space-x-0.items-center button').forEach(button => {
                        if (button.textContent.trim() === viewMode) {
                            button.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600',
                                'dark:bg-blue-600', 'dark:hover:bg-blue-700');
                        } else {
                            button.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600',
                                'dark:bg-blue-600', 'dark:hover:bg-blue-700');
                            button.classList.add('bg-transparent', 'text-gray-700', 'dark:text-gray-300',
                                'hover:bg-gray-300', 'dark:hover:bg-gray-500');
                        }
                    });
                }

                initializeGantt(initialTasksData);

                // Enhanced event listener for filters
                document.querySelectorAll('.flex.items-center.gap-4.ml-auto select').forEach(select => {
                    select.addEventListener('change', function() {
                        const directorateId = document.querySelector(
                            '[data-name="directorate_id"] select')?.value || '';
                        const priorityId = document.querySelector('[data-name="priority"] select')
                            ?.value || '';
                        console.log('Filter change detected:', {
                            directorateId,
                            priorityId
                        });
                        applyFilters(directorateId, priorityId);
                    });
                });

                function applyFilters(directorateId, priorityId) {
                    const currentViewMode = ganttInstance ? ganttInstance.get_view_mode() : 'Week';

                    const filteredTasks = initialTasksData.filter(task => {
                        const matchesDirectorate = !directorateId || (task.directorate_id && task.directorate_id
                            .toString() === directorateId.toString());
                        const matchesPriority = !priorityId || (task.priority && task.priority.toString() ===
                            priorityId.toString());
                        return matchesDirectorate && matchesPriority;
                    });
                    console.log('Tasks filtered for display:', filteredTasks);

                    const url = new URL(window.location.href);
                    url.searchParams.set('directorate_id', directorateId);
                    url.searchParams.set('priority', priorityId);
                    window.history.pushState({}, '', url.toString());

                    initializeGantt(filteredTasks, currentViewMode);
                }

                const urlParams = new URLSearchParams(window.location.search);
                const initialDirectorateId = urlParams.get('directorate_id');
                const initialPriorityId = urlParams.get('priority');

                if (initialDirectorateId || initialPriorityId) {
                    applyFilters(initialDirectorateId, initialPriorityId);
                }

                window.setViewMode = function(mode) {
                    if (ganttInstance) {
                        ganttInstance.change_view_mode(mode);
                        document.querySelectorAll('.flex.space-x-0.items-center button').forEach(button => {
                            if (button.textContent.trim() === mode) {
                                button.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600',
                                    'dark:bg-blue-600', 'dark:hover:bg-blue-700');
                            } else {
                                button.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600',
                                    'dark:bg-blue-600', 'dark:hover:bg-blue-700');
                                button.classList.add('bg-transparent', 'text-gray-700',
                                    'dark:text-gray-300', 'hover:bg-gray-300', 'dark:hover:bg-gray-500');
                            }
                        });
                    }
                };

                window.addEventListener('themeChange', () => {
                    const directorateId = document.querySelector('[data-name="directorate_id"] select')
                        ?.value || '';
                    const priorityId = document.querySelector('[data-name="priority"] select')?.value || '';
                    const currentViewMode = ganttInstance ? ganttInstance.get_view_mode() : 'Week';

                    const filteredTasksForThemeChange = initialTasksData.filter(task => {
                        const matchesDirectorate = !directorateId || (task.directorate_id && task
                            .directorate_id.toString() === directorateId.toString());
                        const matchesPriority = !priorityId || (task.priority && task.priority
                            .toString() === priorityId.toString());
                        return matchesDirectorate && matchesPriority;
                    });
                    initializeGantt(filteredTasksForThemeChange, currentViewMode);
                });
            });
        </script>
    @endpush

</div>
