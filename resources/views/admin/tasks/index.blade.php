<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.task.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.task.title') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'board' ? 'active-view-button' : '' }}"
                    data-view="board">
                    🗄️
                </button>
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'list' ? 'active-view-button' : '' }}"
                    data-view="list">
                    📋
                </button>
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'calendar' ? 'active-view-button' : '' }}"
                    data-view="calendar">
                    📅
                </button>
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'table' ? 'active-view-button' : '' }}"
                    data-view="table">
                    📊
                </button>
            </div>

            @can('task_create')
                <a href="{{ route('admin.task.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter Section -->
    <div id="task-filters" class="mb-4 flex flex-wrap gap-4">
        @if ($directorates->isNotEmpty())
            <div class="w-full max-w-md">
                <label for="directorate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ trans('global.directorate.title') }}
                </label>
                <select id="directorate_id" name="directorate_id"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">{{ trans('global.pleaseSelect') }}</option>
                    @foreach ($directorates as $directorate)
                        <option value="{{ $directorate->id }}"
                            {{ session('filter_directorate_id') == $directorate->id ? 'selected' : '' }}>
                            {{ $directorate->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="w-full max-w-md">
            <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ trans('global.project.title') }}
            </label>
            <select id="project_id" name="project_id"
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <option value="">{{ trans('global.pleaseSelect') }}</option>
                <option value="none" {{ session('filter_project_id') === 'none' ? 'selected' : '' }}>
                    No Project
                </option>
                @foreach ($projectsForFilter as $project)
                    <option value="{{ $project->id }}"
                        {{ session('filter_project_id') == $project->id ? 'selected' : '' }}>
                        {{ $project->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full max-w-md">
            <label for="priority_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ trans('global.priority.title') }}
            </label>
            <select id="priority_id" name="priority_id"
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <option value="">{{ trans('global.pleaseSelect') }}</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->id }}"
                        {{ session('filter_priority_id') == $priority->id ? 'selected' : '' }}>
                        {{ $priority->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full max-w-md flex flex-col">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ trans('global.task.headers.date_progress') }}
            </label>
            <div class="flex gap-2">
                <input type="date" id="date_start" name="date_start" value="{{ session('filter_date_start') }}"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                    placeholder="{{ trans('global.task.fields.start_date') }}">
                <input type="date" id="date_end" name="date_end" value="{{ session('filter_date_end') }}"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                    placeholder="{{ trans('global.task.fields.completion_date') }}">
            </div>
        </div>
    </div>

    @if ($activeView === 'list')
        <div id="task-search" class="mb-4">
            <input type="text" id="task_search" placeholder="{{ trans('global.search') }}"
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        </div>
    @endif

    @if ($activeView === 'board')
        <x-tasks.task-board :tasks="$tasks" :statuses="$statuses" :status-colors="$statusColors" :priority-colors="$priorityColors" :directorates="$directorates"
            :departments="$departments" />
    @elseif ($activeView === 'list')
        <x-tasks.task-list :tasks-flat="$tasksFlat" :status-colors="$statusColors" :priority-colors="$priorityColors" />
    @elseif ($activeView === 'calendar')
        <x-tasks.task-calendar :events="$calendarData" />
    @elseif ($activeView === 'table')
        <x-tasks.task-table :headers="$tableHeaders" :data="$tableData" :routePrefix="$routePrefix" :deleteConfirmationMessage="$deleteConfirmationMessage"
            :actions="$actions" arrayColumnColor="gray" />
    @endif

    <x-tasks.task-filter-drawer :statuses="$statuses" :priorities="$priorities" :projects-for-filter="$projectsForFilter" />

    <style>
        .view-switch.active-view-button {
            background-color: #e2e8f0;
            color: #1a202c;
            font-weight: bold;
        }

        .dark .view-switch.active-view-button {
            background-color: #4a5568;
            color: #ffffff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viewSwitches = document.querySelectorAll('.view-switch');
            const directorateFilter = document.getElementById('directorate_id');
            const projectFilter = document.getElementById('project_id');
            const priorityFilter = document.getElementById('priority_id');
            const dateStartFilter = document.getElementById('date_start');
            const dateEndFilter = document.getElementById('date_end');
            const taskSearchInput = document.getElementById('task_search');

            // Initialize active view
            @if ($activeView === 'calendar' && isset($calendarData))
                if (typeof window.renderTaskCalendar === 'function') {
                    window.renderTaskCalendar();
                    applyFilters();
                }
            @elseif ($activeView === 'board')
                if (typeof window.attachDragAndDropListeners === 'function') {
                    window.attachDragAndDropListeners();
                    applyFilters();
                }
            @else
                applyFilters();
            @endif

            // View switch handling with filter persistence
            viewSwitches.forEach(button => {
                button.addEventListener('click', () => {
                    const view = button.getAttribute('data-view');
                    const directorateId = directorateFilter ? directorateFilter.value : '';
                    const projectId = projectFilter ? projectFilter.value : '';
                    const priorityId = priorityFilter ? priorityFilter.value : '';
                    const dateStart = dateStartFilter.value;
                    const dateEnd = dateEndFilter.value;
                    fetch('{{ route('admin.tasks.set-view') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            task_view_preference: view,
                            directorate_id: directorateId,
                            project_id: projectId,
                            priority_id: priorityId,
                            date_start: dateStart,
                            date_end: dateEnd
                        })
                    }).then(response => {
                        if (response.ok) {
                            window.location.href = '{{ route('admin.task.index') }}?' +
                                new URLSearchParams({
                                    directorate_id: directorateId,
                                    project_id: projectId,
                                    priority_id: priorityId,
                                    date_start: dateStart,
                                    date_end: dateEnd
                                });
                        } else {
                            console.error('Failed to set view preference:', response
                                .statusText);
                        }
                    }).catch(error => console.error('Error setting view preference:', error));
                });
            });

            // Filter application
            function applyFilters() {
                const directorateId = directorateFilter ? directorateFilter.value : '';
                const projectId = projectFilter ? projectFilter.value : '';
                const priorityId = priorityFilter ? priorityFilter.value : '';
                const dateStart = dateStartFilter.value;
                const dateEnd = dateEndFilter.value;
                const searchQuery = taskSearchInput ? taskSearchInput.value.toLowerCase() : '';

                console.log('Applying filters:', {
                    directorateId,
                    projectId,
                    priorityId,
                    dateStart,
                    dateEnd,
                    searchQuery
                });

                if ('{{ $activeView }}' === 'calendar') {
                    fetch('{{ route('admin.tasks.ganttChart') }}?' + new URLSearchParams({
                        directorate_id: directorateId,
                        project_id: projectId,
                        priority_id: priorityId,
                        date_start: dateStart,
                        date_end: dateEnd
                    }), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }).then(response => response.json()).then(data => {
                        console.log('Calendar events fetched:', data.tasks.length);
                        if (typeof window.renderTaskCalendar === 'function') {
                            window.renderTaskCalendar(data.tasks);
                        }
                    }).catch(error => console.error('Error fetching calendar events:', error));
                    return;
                }

                const taskItems = document.querySelectorAll('[data-task-id]');
                if (taskItems.length === 0) {
                    console.warn('No task elements found with [data-task-id]');
                }

                taskItems.forEach(item => {
                    const taskDirectorateId = item.getAttribute('data-directorate-id') || '';
                    const taskProjectId = item.getAttribute('data-project-id') || '';
                    const taskPriorityId = item.getAttribute('data-priority-id') || '';
                    const taskDepartmentId = item.getAttribute('data-department-id') || '';
                    const taskStartDate = item.getAttribute('data-start-date') || '';
                    const taskDueDate = item.getAttribute('data-due-date') || '';
                    const taskSearchData = item.getAttribute('data-search') || '';

                    const matchesDirectorate = !directorateId || taskDirectorateId === directorateId;
                    const matchesProject = !projectId || (projectId === 'none' && !taskProjectId) ||
                        taskProjectId === projectId;
                    const matchesPriority = !priorityId || taskPriorityId === priorityId;
                    const matchesSearch = !searchQuery || taskSearchData.toLowerCase().includes(
                        searchQuery);

                    let matchesDate = true;
                    if (dateStart && dateEnd) {
                        const start = new Date(dateStart);
                        const end = new Date(dateEnd);
                        const taskStart = taskStartDate ? new Date(taskStartDate) : null;
                        const taskDue = taskDueDate ? new Date(taskDueDate) : null;

                        matchesDate = (taskStart || taskDue) &&
                            ((taskStart && taskStart >= start && taskStart <= end) ||
                                (taskDue && taskDue >= start && taskDue <= end) ||
                                (taskStart && taskDue && taskStart <= start && taskDue >= end));
                    }

                    const isVisible = matchesDirectorate && matchesProject && matchesPriority &&
                        matchesDate && matchesSearch;
                    item.style.display = isVisible ? '' : 'none';

                    console.log('Task filter check:', {
                        taskId: item.getAttribute('data-task-id'),
                        taskDirectorateId,
                        taskProjectId,
                        taskPriorityId,
                        taskDepartmentId,
                        taskStartDate,
                        taskDueDate,
                        matchesDirectorate,
                        matchesProject,
                        matchesPriority,
                        matchesDate,
                        matchesSearch,
                        isVisible
                    });
                });

                if ('{{ $activeView }}' === 'board') {
                    document.querySelectorAll('.kanban-column').forEach(column => {
                        const visibleTasks = column.querySelectorAll(
                            '[data-task-id]:not([style*="display: none"])');
                        const countElement = column.querySelector('.task-count');
                        if (countElement) {
                            countElement.textContent = visibleTasks.length;
                        }
                    });
                }

                if ('{{ $activeView }}' === 'table' && typeof updateTaskTable === 'function') {
                    updateTaskTable();
                }
            }

            if (directorateFilter) directorateFilter.addEventListener('change', applyFilters);
            if (projectFilter) projectFilter.addEventListener('change', applyFilters);
            if (priorityFilter) priorityFilter.addEventListener('change', applyFilters);
            if (dateStartFilter) dateStartFilter.addEventListener('change', applyFilters);
            if (dateEndFilter) dateEndFilter.addEventListener('change', applyFilters);
            if (taskSearchInput) taskSearchInput.addEventListener('input', applyFilters);
        });
    </script>
</x-layouts.app>
