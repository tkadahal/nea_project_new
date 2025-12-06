@props(['tasks', 'statuses', 'statusColors', 'priorityColors', 'directorates', 'departments'])

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach ($statuses as $status)
        <div class="kanban-column">
            @php
                $columnHeaderColor = $statusColors[$status->id] ?? 'gray';
                $statusTasks = ($tasks[$status->id] ?? collect())
                    ->filter(function ($task) {
                        return is_null($task['parent_id']);
                    })
                    ->values()
                    ->toArray();
                $visibleTasks = array_slice($statusTasks, 0, 5);
            @endphp
            <div class="text-white p-3 rounded-t-md" style="background-color: {{ $columnHeaderColor }};">
                <h4 class="font-bold m-0">{{ $status->title }} (<span class="task-count">{{ count($statusTasks) }}</span>)
                </h4>
            </div>
            <div class="kanban-list bg-gray-50 dark:bg-gray-700 p-3 rounded-b-md" id="status_{{ $status->id }}"
                data-status-id="{{ $status->id }}">
                @foreach ($visibleTasks as $task)
                    @php
                        // Safely get directorate title
                        $directorateTitle =
                            $task['directorate_id'] && $directorates->firstWhere('id', $task['directorate_id'])
                                ? $directorates->firstWhere('id', $task['directorate_id'])->title
                                : $task['directorate_name'] ?? 'N/A';
                        $departmentTitle =
                            $task['department_id'] && $departments->firstWhere('id', $task['department_id'])
                                ? $departments->firstWhere('id', $task['department_id'])->title
                                : $task['department_name'] ?? 'N/A';
                    @endphp
                    <div class="card-item bg-white dark:bg-gray-800 p-4 rounded-md shadow-md mb-3 kanban-item"
                        data-task-id="{{ $task['id'] }}" data-project-id="{{ $task['project_id'] ?? '' }}"
                        data-directorate-id="{{ $task['directorate_id'] ?? '' }}"
                        data-department-id="{{ $task['department_id'] ?? '' }}"
                        data-priority-id="{{ $task['priority_id'] ?? '' }}"
                        data-start-date="{{ $task['start_date'] ?? '' }}"
                        data-due-date="{{ $task['due_date'] ?? '' }}" data-title="{{ $task['title'] }}"
                        data-search="{{ strtolower($task['title'] .' ' .($task['description'] ?? '') .' ' .($task['priority']['title'] ?? '') .' ' .($task['status']['title'] ?? '') .' ' .($task['directorate_id'] ?? '') .' ' .($task['department_id'] ?? '') .' ' .collect($task['sub_tasks'] ?? [])->pluck('title')->implode(' ')) }}"
                        data-status-id="{{ $task['status_id'] ?? $status->id }}"
                        data-has-subtasks="{{ !empty($task['sub_tasks']) ? 'true' : 'false' }}">
                        <div class="flex items-center">
                            <button type="button"
                                class="toggle-subtasks mr-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 {{ empty($task['sub_tasks']) ? 'hidden' : '' }}"
                                data-task-id="{{ $task['id'] }}"
                                onclick="toggleSubtasks(this, '{{ $task['id'] }}')">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 transform transition-transform duration-200" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    @if (!empty($task['sub_tasks']))
                                        <span
                                            class="ml-1 inline-block px-2 py-1 text-xs font-semibold text-white bg-blue-500 rounded-full">
                                            {{ count($task['sub_tasks']) }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex-1">
                                {{ $task['title'] ?? 'Untitled Task' }}
                                @if ($task['priority'])
                                    <span
                                        class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                        style="background-color: {{ $task['priority']['color'] ?? 'gray' }};">
                                        {{ ucfirst($task['priority']['title']) }}
                                    </span>
                                @endif
                            </h5>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{!! $task['description'] ?? 'No description' !!}</p>
                        @if ($task['project_id'])
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Project:
                                {{ $task['project_name'] ?? 'N/A' }}</p>
                        @elseif ($task['directorate_id'] && !$task['department_id'] && !$task['project_id'])
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Directorate:
                                {{ $directorateTitle }}</p>
                        @elseif ($task['directorate_id'] && $task['department_id'] && !$task['project_id'])
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Department:
                                {{ $departmentTitle }}</p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No Project/Directorate/Department
                            </p>
                        @endif
                        <!-- Sub-tasks -->
                        @if (!empty($task['sub_tasks']))
                            <div class="subtasks-container mt-2 hidden" data-task-id="{{ $task['id'] }}">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sub-tasks:</p>
                                <div class="ml-4 border-l-2 border-gray-200 dark:border-gray-600 pl-4">
                                    @foreach ($task['sub_tasks'] as $subTask)
                                        @php
                                            \Log::debug("Rendering sub-task for task {$task['id']}:", [
                                                'sub_task_id' => $subTask['id'],
                                                'sub_task_title' => $subTask['title'],
                                            ]);
                                            // Safely get directorate and department titles for sub-tasks
                                            $subTaskDirectorateTitle =
                                                $subTask['directorate_id'] &&
                                                $directorates->firstWhere('id', $subTask['directorate_id'])
                                                    ? $directorates->firstWhere('id', $subTask['directorate_id'])->title
                                                    : $subTask['directorate_name'] ?? 'N/A';
                                            $subTaskDepartmentTitle =
                                                $subTask['department_id'] &&
                                                $departments->firstWhere('id', $subTask['department_id'])
                                                    ? $departments->firstWhere('id', $subTask['department_id'])->title
                                                    : $subTask['department_name'] ?? 'N/A';
                                        @endphp
                                        <div class="subtask-item bg-gray-100 dark:bg-gray-900 p-3 rounded-md mb-2"
                                            data-task-id="{{ $subTask['id'] }}"
                                            data-project-id="{{ $subTask['project_id'] ?? '' }}"
                                            data-directorate-id="{{ $subTask['directorate_id'] ?? '' }}"
                                            data-department-id="{{ $subTask['department_id'] ?? '' }}"
                                            data-priority-id="{{ $subTask['priority_id'] ?? '' }}"
                                            data-start-date="{{ $subTask['start_date'] ?? '' }}"
                                            data-due-date="{{ $subTask['due_date'] ?? '' }}"
                                            data-title="{{ $subTask['title'] }}"
                                            data-search="{{ strtolower($subTask['title'] . ' ' . ($subTask['description'] ?? '') . ' ' . ($subTask['priority']['title'] ?? '') . ' ' . ($subTask['status']['title'] ?? '') . ' ' . ($subTask['directorate_id'] ?? '') . ' ' . ($subTask['department_id'] ?? '')) }}"
                                            data-status-id="{{ $subTask['status_id'] ?? $status->id }}">
                                            <h6 class="text-md font-medium text-gray-600 dark:text-gray-400">
                                                {{ $subTask['title'] }}
                                                @if ($subTask['priority'])
                                                    <span
                                                        class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                                        style="background-color: {{ $subTask['priority']['color'] ?? 'gray' }};">
                                                        {{ ucfirst($subTask['priority']['title']) }}
                                                    </span>
                                                @endif
                                            </h6>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {!! $subTask['description'] ?? 'No description' !!}</p>
                                            <div class="mt-2 flex space-x-2">
                                                <a href="{{ $subTask['view_url'] }}"
                                                    class="inline-flex items-center gap-1 px-3 py-1 text-white rounded-md hover:opacity-80 text-sm font-medium"
                                                    style="background-color: {{ $subTask['status_color'] ?? 'gray' }}"
                                                    aria-label="View sub-task">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </a>
                                                @can('task_create')
                                                    <a href="{{ route('admin.task.create', ['parent_id' => $subTask['id']]) }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm font-medium"
                                                        aria-label="Add sub-task">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        Add Sub-task
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="mt-2 flex space-x-2">
                            <a href="{{ $task['view_url'] }}"
                                class="inline-flex items-center gap-1 px-3 py-1 text-white rounded-md hover:opacity-80 text-sm font-medium"
                                style="background-color: {{ $task['status_color'] ?? 'gray' }}" aria-label="View task">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </a>
                            @can('task_create')
                                <a href="{{ route('admin.task.create', ['parent_id' => $task['id']]) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm font-medium"
                                    aria-label="Add sub-task">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Sub-task
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
                @if (count($statusTasks) > 5)
                    <button type="button"
                        class="load-more-btn w-full py-2 px-4 bg-blue-600 text-white font-semibold text-sm rounded-md shadow-md hover:bg-blue-700 hover:scale-105 transition-transform duration-200 ease-in-out dark:bg-blue-500 dark:hover:bg-blue-600 dark:shadow-gray-800 relative"
                        data-status-id="{{ $status->id }}" onclick="loadMoreTasks(this)">
                        <span class="load-more-text">{{ trans('global.loadMore') }}</span>
                        <span class="loading-spinner hidden absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </span>
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>

<style>
    .kanban-column {
        display: flex;
        flex-direction: column;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .kanban-list {
        min-height: 150px;
        border: 2px dashed transparent;
        transition: border-color 0.2s ease-in-out;
    }

    .kanban-list.sortable-ghost {
        border-color: #3b82f6;
    }

    .kanban-item {
        cursor: grab;
    }

    .kanban-item.sortable-chosen {
        opacity: 0.5;
        cursor: grabbing;
    }

    .subtask-item {
        border-left: 2px solid #e5e7eb;
        transition: all 0.2s ease-in-out;
    }

    .dark .subtask-item {
        border-left-color: #4b5563;
    }

    .subtasks-container.hidden {
        display: none;
    }

    .toggle-subtasks svg {
        transition: transform 0.2s ease-in-out;
    }

    .toggle-subtasks.expanded svg {
        transform: rotate(90deg);
    }

    .load-more-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
    // Define global variables
    const statusTitles = {};
    @foreach ($statuses as $status)
        statusTitles[{{ $status->id }}] = '{{ $status->title }}';
    @endforeach
    const statusColors = @json($statusColors);

    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            console.error('CSRF token not found.');
            if (typeof toastr !== 'undefined') {
                toastr.error('CSRF token not found. Please refresh the page.');
            }
        }
        return token;
    }

    function toggleSubtasks(button, taskId) {
        console.log(`Attempting to toggle sub-tasks for task ${taskId}`);
        const subtasksContainer = document.querySelector(`.subtasks-container[data-task-id="${taskId}"]`);
        if (subtasksContainer) {
            const isHidden = subtasksContainer.classList.contains('hidden');
            subtasksContainer.classList.toggle('hidden', !isHidden);
            button.classList.toggle('expanded', isHidden);
            console.log(`Toggled sub-tasks for task ${taskId}: ${isHidden ? 'Shown' : 'Hidden'}`);
        } else {
            console.warn(`Subtasks container not found for task ${taskId}`);
        }
    }

    window.attachDragAndDropListeners = function() {
        const statusIdMap = {};
        @foreach ($statuses as $status)
            statusIdMap['status_{{ $status->id }}'] = {{ $status->id }};
        @endforeach

        function updateTaskStatus(taskId, projectId, newStatusId, draggableElement, originalList, originalIndex) {
            const token = getCsrfToken();
            if (!token) return;

            const payload = {
                task_id: taskId,
                status_id: newStatusId
            };
            if (projectId) {
                payload.project_id = projectId;
            }

            fetch("{{ route('admin.task.updateStatus') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Unknown error');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Task status updated:', data);
                    window.pendingCalendarUpdates = window.pendingCalendarUpdates || {};
                    window.pendingCalendarUpdates[taskId] = {
                        status: statusTitles[newStatusId] || 'Unknown Status',
                        color: statusColors[newStatusId] || 'gray'
                    };

                    const viewButton = draggableElement.querySelector('a:not([href*="parent_id"])');
                    if (viewButton) {
                        viewButton.style.backgroundColor = statusColors[newStatusId] || 'gray';
                    }

                    if (typeof taskCalendar !== 'undefined' && taskCalendar) {
                        const event = taskCalendar.getEventById(taskId);
                        if (event) {
                            event.setExtendedProp('status', statusTitles[newStatusId] || 'Unknown Status');
                            event.setProp('color', statusColors[newStatusId] || 'gray');
                            taskCalendar.render();
                            delete window.pendingCalendarUpdates[taskId];
                        }
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Task status updated successfully!');
                    }

                    // Update task count in the column headers
                    const dropzone = draggableElement.closest('.kanban-list');
                    const statusId = dropzone.dataset.statusId;
                    const taskCount = dropzone.querySelectorAll('.card-item:not(.subtask-item)').length;
                    const countElement = dropzone.closest('.kanban-column').querySelector('.task-count');
                    if (countElement) {
                        countElement.textContent = taskCount;
                    }
                    const originalCountElement = originalList.closest('.kanban-column').querySelector(
                        '.task-count');
                    if (originalCountElement) {
                        originalCountElement.textContent = originalList.querySelectorAll(
                            '.card-item:not(.subtask-item)').length;
                    }
                })
                .catch(error => {
                    console.error('Error updating task status:', error.message);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(error.message);
                    }
                    // Revert the task to its original position
                    originalList.insertBefore(draggableElement, originalList.children[originalIndex]);
                    // Update task counts for both columns
                    const dropzone = draggableElement.closest('.kanban-list');
                    const taskCount = dropzone.querySelectorAll('.card-item:not(.subtask-item)').length;
                    const countElement = dropzone.closest('.kanban-column').querySelector('.task-count');
                    if (countElement) {
                        countElement.textContent = taskCount;
                    }
                    const originalCountElement = originalList.closest('.kanban-column').querySelector(
                        '.task-count');
                    if (originalCountElement) {
                        originalCountElement.textContent = originalList.querySelectorAll(
                            '.card-item:not(.subtask-item)').length;
                    }
                });
        }

        const kanbanLists = document.querySelectorAll('.kanban-list');
        if (kanbanLists.length === 0) {
            console.warn('No kanban lists found. SortableJS not initialized.');
            return;
        }

        kanbanLists.forEach(list => {
            new Sortable(list, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: '.subtask-item', // Only prevent sub-tasks from being draggable
                onEnd: function(evt) {
                    const draggableElement = evt.item;
                    const dropzone = evt.to;
                    const originalList = evt.from;
                    const originalIndex = evt.oldIndex;

                    if (evt.from !== dropzone) {
                        const taskId = draggableElement.dataset.taskId;
                        const projectId = draggableElement.dataset.projectId || null;
                        const newStatusId = statusIdMap[dropzone.id];

                        if (newStatusId === undefined) {
                            console.error('Invalid dropzone ID:', dropzone.id);
                            return;
                        }

                        draggableElement.dataset.statusId = newStatusId;
                        console.log(
                            `Moved task ${taskId} to status ${newStatusId} ${projectId ? `for project ${projectId}` : 'without project'}`
                        );
                        updateTaskStatus(taskId, projectId, newStatusId, draggableElement,
                            originalList, originalIndex);
                    }
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const boardView = document.getElementById('board-view');
        if (boardView && !boardView.classList.contains('hidden')) {
            window.attachDragAndDropListeners();
        }
    });

    function loadMoreTasks(button) {
        const statusId = button.getAttribute('data-status-id');
        const list = document.getElementById(`status_${statusId}`);
        const offset = list.querySelectorAll('.card-item:not(.subtask-item)').length;

        const directorateId = document.getElementById('directorate_id')?.value || '';
        const projectId = document.getElementById('project_id')?.value || '';
        const priorityId = document.getElementById('priority_id')?.value || '';
        const dateStart = document.getElementById('date_start')?.value || '';
        const dateEnd = document.getElementById('date_end')?.value || '';

        const spinner = button.querySelector('.loading-spinner');
        const text = button.querySelector('.load-more-text');
        button.disabled = true;
        if (spinner && text) {
            spinner.classList.remove('hidden');
            text.classList.add('hidden');
        }

        const token = getCsrfToken();
        if (!token) {
            button.disabled = false;
            if (spinner && text) {
                spinner.classList.add('hidden');
                text.classList.remove('hidden');
            }
            return;
        }

        console.log('Fetching more tasks', {
            statusId,
            offset,
            directorateId,
            projectId,
            priorityId,
            dateStart,
            dateEnd,
            statusTitles
        });

        fetch(`{{ route('admin.task.loadMore') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    status_id: statusId,
                    offset: offset,
                    directorate_id: directorateId,
                    project_id: projectId,
                    priority_id: priorityId,
                    date_start: dateStart,
                    date_end: dateEnd
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(
                            err.message || 'Unknown error'
                        );
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Loaded tasks:', data.tasks);
                data.tasks.forEach(task => {
                    if (task.parent_id) return; // Skip sub-tasks
                    const card = document.createElement('div');
                    card.className =
                        'card-item bg-white dark:bg-gray-800 p-4 rounded-md shadow-md mb-3 kanban-item';
                    card.dataset.taskId = task.id;
                    card.dataset.projectId = task.project_id || '';
                    card.dataset.directorateId = task.directorate_id || '';
                    card.dataset.departmentId = task.department_id || '';
                    card.dataset.priorityId = task.priority_id || '';
                    card.dataset.startDate = task.start_date || '';
                    card.dataset.dueDate = task.due_date || '';
                    card.dataset.title = task.title || 'Untitled Task';
                    card.dataset.search =
                        `${task.title || ''} ${task.description || ''} ${task.priority?.title || ''} ${statusTitles[task.status_id] || ''} ${task.directorate_id || ''} ${task.department_id || ''} ${task.sub_tasks ? task.sub_tasks.map(st => st.title).join(' ') : ''}`
                        .toLowerCase();
                    card.dataset.statusId = task.status_id;
                    card.dataset.hasSubtasks = task.sub_tasks && task.sub_tasks.length ? 'true' : 'false';
                    card.innerHTML = `
                        <div class="flex items-center">
                            <button type="button" class="toggle-subtasks mr-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 ${task.sub_tasks && task.sub_tasks.length ? '' : 'hidden'}"
                                data-task-id="${task.id}"
                                onclick="toggleSubtasks(this, '${task.id}')">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 transform transition-transform duration-200"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    ${task.sub_tasks && task.sub_tasks.length ? `<span class="ml-1 inline-block px-2 py-1 text-xs font-semibold text-white bg-blue-500 rounded-full">${task.sub_tasks.length}</span>` : ''}
                                </div>
                            </button>
                            <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 flex-1">
                                ${task.title || 'Untitled Task'}
                                ${task.priority ? `<span class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full" style="background-color: ${task.priority.color};">${task.priority.title.charAt(0).toUpperCase() + task.priority.title.slice(1)}</span>` : ''}
                            </h5>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">${task.description || 'No description'}</p>
                        ${task.project_id ? `<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Project: ${task.project_name || 'N/A'}</p>` :
                        task.directorate_id && !task.department_id && !task.project_id ? `<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Directorate: ${task.directorate_name || 'N/A'}</p>` :
                        task.directorate_id && task.department_id && !task.project_id ? `<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Department: ${task.department_name || 'N/A'}</p>` :
                        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No Project/Directorate/Department</p>'}
                        ${task.sub_tasks && task.sub_tasks.length ? `
                            <div class="subtasks-container mt-2 hidden" data-task-id="${task.id}">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sub-tasks:</p>
                                <div class="ml-4 border-l-2 border-gray-200 dark:border-gray-600 pl-4">
                                    ${task.sub_tasks.map(st => `
                                        <div class="subtask-item bg-gray-100 dark:bg-gray-900 p-3 rounded-md mb-2"
                                             data-task-id="${st.id}"
                                             data-project-id="${st.project_id || ''}"
                                             data-directorate-id="${st.directorate_id || ''}"
                                             data-department-id="${st.department_id || ''}"
                                             data-priority-id="${st.priority_id || ''}"
                                             data-start-date="${st.start_date || ''}"
                                             data-due-date="${st.due_date || ''}"
                                             data-title="${st.title}"
                                             data-search="${(st.title + ' ' + (st.description || '') + ' ' + (st.priority?.title || '') + ' ' + (st.status?.title || '') + ' ' + (st.directorate_id || '') + ' ' + (st.department_id || '')).toLowerCase()}"
                                             data-status-id="${st.status_id || statusId}">
                                            <h6 class="text-md font-medium text-gray-600 dark:text-gray-400">
                                                ${st.title}
                                                ${st.priority ? `<span class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full" style="background-color: ${st.priority.color};">${st.priority.title.charAt(0).toUpperCase() + st.priority.title.slice(1)}</span>` : ''}
                                            </h6>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${st.description || 'No description'}</p>
                                            <div class="mt-2 flex space-x-2">
                                                <a href="${st.view_url}" class="inline-flex items-center gap-1 px-3 py-1 text-white rounded-md hover:opacity-80 text-sm font-medium" style="background-color: ${st.status_color}" aria-label="View sub-task">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="{{ route('admin.task.create') }}?parent_id=${st.id}" class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm font-medium" aria-label="Add sub-task">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add Sub-task
                                                </a>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        <div class="mt-2 flex space-x-2">
                            <a href="${task.view_url}" class="inline-flex items-center gap-1 px-3 py-1 text-white rounded-md hover:opacity-80 text-sm font-medium" style="background-color: ${task.status_color}" aria-label="View task">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </a>
                            <a href="{{ route('admin.task.create') }}?parent_id=${task.id}" class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm font-medium" aria-label="Add sub-task">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Sub-task
                            </a>
                        </div>
                    `;
                    list.insertBefore(card, button);
                });
                if (!data.has_more) {
                    button.remove();
                }
                window.attachDragAndDropListeners();

                const taskCount = list.querySelectorAll('.card-item:not(.subtask-item)').length;
                const countElement = list.closest('.kanban-column').querySelector('.task-count');
                if (countElement) {
                    countElement.textContent = taskCount;
                }
            })
            .catch(error => {
                console.error('Error loading more tasks:', error.message);
                if (typeof toastr !== 'undefined') {
                    toastr.error(error.message);
                }
            })
            .finally(() => {
                button.disabled = false;
                if (spinner && text) {
                    spinner.classList.add('hidden');
                    text.classList.remove('hidden');
                }
            });
    }

    // Debug: Log when board is rendered
    console.log('Kanban board rendered with tasks:', @json($tasks));
</script>
