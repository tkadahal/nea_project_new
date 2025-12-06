<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 col-span-12 lg:col-span-8"
        id="task-status-component" x-data="{ openSubtasks: {} }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-base sm:text-sm font-semibold text-gray-800 dark:text-white">
                {{ trans('global.task.title') }} {{ trans('global.status.title') }}
            </h2>
            <div class="flex items-center space-x-4">
                <a class="inline-flex items-center gap-1 px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm"
                    href="{{ route('admin.tasks.ganttChart') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    {{ trans('global.chart') }}
                </a>
                <a class="inline-flex items-center gap-1 px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm"
                    href="{{ route('admin.task.index') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    {{ trans('global.viewAll') }}
                </a>
                <div class="relative" wire:ignore>
                    <button
                        class="dropdown-toggle-task text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </button>
                    <div
                        class="dropdown-menu-task hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl z-[1000]">
                        <div class="p-3">
                            @if (!empty($availableDirectorates))
                                <div class="mb-3">
                                    <input type="text" id="task-directorate-search"
                                        class="w-full text-sm text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded-md px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                        placeholder="Search directorates...">
                                </div>
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                    {{ trans('global.filterDirectorate') }}
                                </h3>
                                <div class="max-h-40 overflow-y-auto custom-scroll">
                                    <button wire:click="$set('directorateFilter', null)"
                                        class="directorate-option-task block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md"
                                        data-filter="all" data-name="All Directorates">
                                        {{ trans('global.allDirectorate') }}
                                    </button>
                                    @foreach ($availableDirectorates as $id => $name)
                                        <button wire:click="$set('directorateFilter', {{ $id }})"
                                            class="directorate-option-task block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md"
                                            data-filter="{{ $id }}" data-name="{{ $name }}">
                                            {{ $name }}
                                        </button>
                                    @endforeach
                                </div>
                                <div id="task-no-results" class="hidden px-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ trans('global.noRecords') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" id="task-summary">
            {{ $tasks->where('status.title', 'Completed')->count() }} Tasks completed out of {{ $tasks->count() }}
        </p>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300 min-w-[600px]">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="py-2 px-4 text-left">
                            {{ trans('global.task.fields.title') }}
                        </th>
                        <th class="py-2 px-4 text-left hidden sm:table-cell">
                            {{ trans('global.task.fields.status_id') }}
                        </th>
                        <th class="py-2 px-4 text-left hidden md:table-cell">
                            {{ trans('global.task.fields.user_id') }}
                        </th>
                        <th class="py-2 px-4 text-left hidden lg:table-cell">
                            {{ trans('global.task.fields.total_time') }}
                        </th>
                        <th class="py-2 px-4 text-left">
                            {{ trans('global.action') }}
                        </th>
                    </tr>
                </thead>
                <tbody id="task-table">
                    @foreach ($tasks as $task)
                        <tr class="border-t dark:border-gray-600">
                            <td class="py-2 px-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if ($task->sub_tasks && $task->sub_tasks->count() > 0)
                                        <button type="button"
                                            class="toggle-subtasks mr-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                            :class="{ 'expanded': openSubtasks['{{ $task->id }}'] }"
                                            @click="openSubtasks['{{ $task->id }}'] = !openSubtasks['{{ $task->id }}']"
                                            data-task-id="{{ $task->id }}">
                                            <span
                                                class="inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-blue-500 rounded-full">
                                                {{ $task->sub_tasks->count() }}
                                            </span>
                                        </button>
                                    @else
                                        <span class="w-5 h-5 mr-2"></span> <!-- Placeholder for alignment -->
                                    @endif
                                    {{ Str::limit($task->name, 50) }}
                                    (@if ($task->project_id)
                                        Project: {{ $task->project_name ?? 'N/A' }}
                                    @elseif ($task->directorate_id && !$task->department_id)
                                        Directorate: {{ $task->directorate_name ?? 'N/A' }}
                                    @elseif ($task->directorate_id && $task->department_id)
                                        Department: {{ $task->department_name ?? 'N/A' }}
                                    @else
                                        No Project/Directorate/Department
                                    @endif)
                                </div>
                                <div class="sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="inline-block px-2 py-1 rounded-full text-white"
                                        style="background-color: {{ $task->status->color ?? 'gray' }};">
                                        {{ $task->status->title }}
                                    </span>
                                    <br>
                                    Assigned:
                                    <span
                                        class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                        {{ $task->assigned_to }}
                                    </span>
                                    <br>
                                    Time: {{ $task->total_time_spent }}
                                </div>
                            </td>
                            <td class="py-2 px-4 hidden sm:table-cell whitespace-nowrap">
                                <span class="inline-block px-2 py-1 rounded-full text-white text-xs"
                                    style="background-color: {{ $task->status->color ?? 'gray' }};">
                                    {{ $task->status->title }}
                                </span>
                            </td>
                            <td class="py-2 px-4 hidden md:table-cell whitespace-nowrap">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                    {{ $task->assigned_to }}
                                </span>
                            </td>
                            <td class="py-2 px-4 hidden lg:table-cell whitespace-nowrap">
                                {{ $task->total_time_spent }}
                            </td>
                            <td class="py-2 px-4 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.task.show', array_filter([$task->id, $task->project_id])) }}"
                                        class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @if ($task->sub_tasks && $task->sub_tasks->count() > 0)
                            @foreach ($task->sub_tasks as $subTask)
                                <tr class="border-t dark:border-gray-600 subtask-row"
                                    x-show="openSubtasks['{{ $task->id }}']" x-cloak
                                    data-task-id="{{ $task->id }}">
                                    <td class="py-2 px-4 whitespace-nowrap">
                                        <div class="ml-8">
                                            {{ Str::limit($subTask->name, 50) }}
                                            (@if ($subTask->project_id)
                                                Project: {{ $subTask->project_name ?? 'N/A' }}
                                            @elseif ($subTask->directorate_id && !$subTask->department_id)
                                                Directorate: {{ $subTask->directorate_name ?? 'N/A' }}
                                            @elseif ($subTask->directorate_id && $subTask->department_id)
                                                Department: {{ $subTask->department_name ?? 'N/A' }}
                                            @else
                                                No Project/Directorate/Department
                                            @endif)
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 hidden sm:table-cell whitespace-nowrap">
                                        <span class="inline-block px-2 py-1 rounded-full text-white text-xs"
                                            style="background-color: {{ $subTask->status->color ?? 'gray' }};">
                                            {{ $subTask->status->title }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 hidden md:table-cell whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-full bg-gray-200 text-black dark:bg-gray-700 dark:text-white text-xs">
                                            {{ $subTask->assigned_to }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 hidden lg:table-cell whitespace-nowrap">
                                        {{ $subTask->total_time_spent }}
                                    </td>
                                    <td class="py-2 px-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.task.show', array_filter([$task->id, $task->project_id])) }}"
                                                class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #9ca3af;
            border-radius: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        .dark .custom-scroll::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 4px;
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #374151;
        }

        .custom-scroll {
            scrollbar-width: thin;
            scrollbar-color: #9ca3af #e5e7eb;
        }

        .dark .custom-scroll {
            scrollbar-color: #4b5563 #1f2937;
        }

        .subtask-row {
            background-color: #f9fafb;
            transition: all 0.2s ease-in-out;
        }

        .dark .subtask-row {
            background-color: #1f2937;
        }

        .toggle-subtasks svg {
            transition: transform 0.2s ease-in-out;
        }

        .toggle-subtasks.expanded svg {
            transform: rotate(90deg);
        }

        [x-cloak] {
            display: none;
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script>
        function waitForjQuery(callback) {
            if (typeof jQuery !== 'undefined' && jQuery.fn.jquery === '3.7.1') {
                callback(jQuery);
            } else {
                setTimeout(() => waitForjQuery(callback), 100);
            }
        }

        waitForjQuery(function($) {
            const $taskContainer = $('#task-status-component');

            function attachEventListeners() {
                $taskContainer.find('.dropdown-toggle-task').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $dropdown = $taskContainer.find('.dropdown-menu-task');
                    $dropdown.toggleClass('hidden');
                    console.log('Task dropdown toggled, visible:', !$dropdown.hasClass('hidden'),
                        'position:', $dropdown.position());
                    $taskContainer.find('#task-directorate-search').val('');
                    $taskContainer.find('.directorate-option-task').removeClass('hidden');
                    $taskContainer.find('#task-no-results').addClass('hidden');
                });

                $(document).off('click.taskDropdown').on('click.taskDropdown', function(event) {
                    if (!$(event.target).closest('.dropdown-toggle-task, .dropdown-menu-task').length) {
                        $taskContainer.find('.dropdown-menu-task').addClass('hidden');
                        console.log('Task dropdown closed');
                    }
                });

                $taskContainer.find('.directorate-option-task').off('click').on('click', function() {
                    const filterValue = $(this).data('filter');
                    const $dropdown = $taskContainer.find('.dropdown-menu-task');
                    $dropdown.addClass('hidden');
                    console.log('Task filter clicked:', filterValue, 'Dropdown closed');
                });

                $taskContainer.find('#task-directorate-search').off('input').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    console.log('Task search term:', searchTerm);
                    let visibleCount = 0;

                    $taskContainer.find('.directorate-option-task').each(function() {
                        const name = $(this).data('name').toLowerCase();
                        const isAllDirectorates = $(this).data('filter') === 'all';
                        if (isAllDirectorates || name.includes(searchTerm)) {
                            $(this).removeClass('hidden');
                            visibleCount++;
                        } else {
                            $(this).addClass('hidden');
                        }
                    });

                    $taskContainer.find('#task-no-results').toggleClass('hidden', visibleCount > 0);
                });
            }

            attachEventListeners();

            const component = document.getElementById('task-status-component');
            if (component) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            console.log('Task table updated, re-attaching event listeners');
                            attachEventListeners();
                        }
                    });
                });
                observer.observe(component, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }

            // Livewire hook to re-attach Alpine.js after updates
            document.addEventListener('livewire:navigated', function() {
                console.log('Livewire navigated, re-initializing Alpine.js');
                Alpine.initTree(document.getElementById('task-status-component'));
            });
        });
    </script>
</div>
