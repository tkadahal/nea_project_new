@props(['tasksFlat', 'priorityColors', 'statusColors'])

<div id="taskListContainer">
    @if (isset($tasksFlat) && count($tasksFlat) > 0)
        @foreach ($tasksFlat as $task)
            @if (is_null($task['parent_id']))
                <!-- Only show parent tasks -->
                <div class="list-item border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-3"
                    data-task-id="{{ $task['id'] }}" data-directorate-id="{{ $task['directorate_id'] ?? '' }}"
                    data-department-id="{{ $task['department_id'] ?? '' }}"
                    data-priority-id="{{ $task['priority_id'] ?? '' }}" data-project-id="{{ $task['project_id'] ?? '' }}"
                    data-start-date="{{ $task['start_date'] ?? '' }}" data-due-date="{{ $task['due_date'] ?? '' }}"
                    data-title="{{ $task['title'] }}"
                    data-search="{{ strtolower($task['title'] .' ' .($task['description'] ?? '') .' ' .($task['priority'] ?? '') .' ' .($task['status'] ?? '') .' ' .($task['projects'][0] ?? '') .' ' .($task['directorate_id'] ?? '') .' ' .($task['department_id'] ?? '') .' ' .collect($task['sub_tasks'] ?? [])->pluck('title')->implode(' ')) }}"
                    data-has-subtasks="{{ !empty($task['sub_tasks']) ? 'true' : 'false' }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center">
                                <button type="button"
                                    class="toggle-subtasks mr-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 {{ empty($task['sub_tasks']) ? 'hidden' : '' }}"
                                    data-task-id="{{ $task['id'] }}"
                                    onclick="toggleSubtasks(this, '{{ $task['id'] }}')">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 transform transition-transform duration-200" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
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
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $task['title'] }}
                                    @php
                                        $priorityBadgeColor = $priorityColors[$task['priority'] ?? ''] ?? 'gray';
                                    @endphp
                                    @if ($task['priority'])
                                        <span
                                            class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                            style="background-color: {{ $priorityBadgeColor }};">
                                            {{ ucfirst($task['priority']) }}
                                        </span>
                                    @endif
                                </h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{!! $task['description'] !!}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                @if ($task['project_id'])
                                    {{ trans('global.task.fields.project_id') }} :
                                    <span class="font-medium">
                                        {{ $task['projects'][0] ?? 'N/A' }}
                                    </span>
                                @elseif ($task['department_id'])
                                    {{ trans('global.task.fields.department_id') }} :
                                    <span class="font-medium">
                                        {{ $task['department_name'] ?? 'N/A' }}
                                    </span>
                                @elseif ($task['directorate_id'])
                                    {{ trans('global.task.fields.directorate_id') }} :
                                    <span class="font-medium">
                                        {{ $task['directorate'] ?? 'N/A' }}
                                    </span>
                                @else
                                    <span class="font-medium">No Project/Department/Directorate</span>
                                @endif
                                |
                                {{ trans('global.task.fields.status_id') }} :
                                @php
                                    $taskStatusColor = $statusColors[$task['status_id']] ?? 'gray';
                                @endphp
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                    style="background-color: {{ $taskStatusColor }};">
                                    {{ $task['status'] ?? 'N/A' }}
                                </span>
                                |
                                {{ trans('global.task.fields.due_date') }} :
                                <span class="font-medium">
                                    {{ $task['due_date'] ? \Carbon\Carbon::parse($task['due_date'])->format('M d, Y') : 'No Due Date' }}
                                </span>
                            </p>
                            <!-- Sub-tasks container (initially hidden) -->
                            @if (!empty($task['sub_tasks']))
                                <div class="subtasks-container mt-2 hidden" data-task-id="{{ $task['id'] }}">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Sub-tasks:
                                    </p>
                                    <div class="space-y-3">
                                        @foreach ($task['sub_tasks'] as $subTask)
                                            <div class="subtask-item border border-gray-300 dark:border-gray-600 rounded-lg p-4"
                                                data-task-id="{{ $subTask['id'] }}"
                                                data-directorate-id="{{ $subTask['directorate_id'] ?? '' }}"
                                                data-department-id="{{ $subTask['department_id'] ?? '' }}"
                                                data-priority-id="{{ $subTask['priority_id'] ?? '' }}"
                                                data-project-id="{{ $subTask['project_id'] ?? '' }}"
                                                data-start-date="{{ $subTask['start_date'] ?? '' }}"
                                                data-due-date="{{ $subTask['due_date'] ?? '' }}"
                                                data-title="{{ $subTask['title'] }}"
                                                data-search="{{ strtolower($subTask['title'] . ' ' . ($subTask['description'] ?? '') . ' ' . ($subTask['priority']['title'] ?? '') . ' ' . ($subTask['status']['title'] ?? '') . ' ' . ($subTask['project_name'] ?? '') . ' ' . ($subTask['directorate_id'] ?? '') . ' ' . ($subTask['department_id'] ?? '')) }}">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4
                                                            class="text-md font-semibold text-gray-700 dark:text-gray-300">
                                                            {{ $subTask['title'] }}
                                                            @if ($subTask['priority'])
                                                                <span
                                                                    class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                                                    style="background-color: {{ $subTask['priority']['color'] ?? 'gray' }};">
                                                                    {{ ucfirst($subTask['priority']['title']) }}
                                                                </span>
                                                            @endif
                                                        </h4>
                                                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                                                            {!! $subTask['description'] ?? 'No description' !!}</p>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                            @if ($subTask['project_id'])
                                                                {{ trans('global.task.fields.project_id') }} :
                                                                <span class="font-medium">
                                                                    {{ $subTask['project_name'] ?? 'N/A' }}
                                                                </span>
                                                            @elseif ($subTask['department_id'])
                                                                {{ trans('global.task.fields.department_id') }} :
                                                                <span class="font-medium">
                                                                    {{ $subTask['department_name'] ?? 'N/A' }}
                                                                </span>
                                                            @elseif ($subTask['directorate_id'])
                                                                {{ trans('global.task.fields.directorate_id') }} :
                                                                <span class="font-medium">
                                                                    {{ $subTask['directorate_name'] ?? 'N/A' }}
                                                                </span>
                                                            @else
                                                                <span class="font-medium">No
                                                                    Project/Department/Directorate</span>
                                                            @endif
                                                            |
                                                            {{ trans('global.task.fields.status_id') }} :
                                                            <span
                                                                class="inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                                                style="background-color: {{ $subTask['status']['color'] ?? 'gray' }};">
                                                                {{ $subTask['status']['title'] ?? 'N/A' }}
                                                            </span>
                                                            |
                                                            {{ trans('global.task.fields.due_date') }} :
                                                            <span class="font-medium">
                                                                {{ $subTask['due_date'] ? \Carbon\Carbon::parse($subTask['due_date'])->format('M d, Y') : 'No Due Date' }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <a href="{{ $subTask['view_url'] }}"
                                                            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                                            {{ trans('global.view') }}
                                                        </a>
                                                        @can('task_create')
                                                            <a href="{{ route('admin.task.create', ['parent_id' => $subTask['id']]) }}"
                                                                class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                                                Add Sub-task
                                                            </a>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ $task['view_url'] }}"
                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                {{ trans('global.view') }}
                            </a>
                            @can('task_create')
                                <a href="{{ route('admin.task.create', ['parent_id' => $task['id']]) }}"
                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                    Add Sub-task
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <p class="text-center p-3 text-gray-500 dark:text-gray-400">
            {{ trans('global.noRecords') }}
        </p>
    @endif
</div>

<style>
    .subtasks-container.hidden {
        display: none;
    }

    .toggle-subtasks svg {
        transition: transform 0.2s ease-in-out;
    }

    .toggle-subtasks.expanded svg {
        transform: rotate(90deg);
    }

    .subtask-item {
        border-left: 2px solid #e5e7eb;
        transition: all 0.2s ease-in-out;
    }

    .dark .subtask-item {
        border-left-color: #4b5563;
    }
</style>

<script>
    function toggleSubtasks(button, taskId) {
        console.log(`Toggling sub-tasks for task ${taskId}`);
        const subtasksContainer = document.querySelector(`.subtasks-container[data-task-id="${taskId}"]`);
        if (subtasksContainer) {
            const isHidden = subtasksContainer.classList.contains('hidden');
            subtasksContainer.classList.toggle('hidden', !isHidden);
            button.classList.toggle('expanded', isHidden);
            console.log(`Sub-tasks for task ${taskId}: ${isHidden ? 'Shown' : 'Hidden'}`);
        } else {
            console.warn(`Subtasks container not found for task ${taskId}`);
        }
    }
</script>
