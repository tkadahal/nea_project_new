<div class="task-filter-drawer fixed top-0 right-0 h-full w-80 bg-white dark:bg-gray-800 shadow-lg transform translate-x-full transition-transform duration-300 ease-in-out z-50"
    id="taskFilterDrawer">
    <div class="p-4 border-b border-gray-300 dark:border-gray-600">
        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ trans('global.filter') }}
            {{ trans('global.task.title') }}</h5>
        <button type="button"
            class="absolute top-4 right-4 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
            id="closeTaskFilterDrawer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <div class="p-4">
        <form id="taskFilterForm">
            <div class="mb-4">
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans('global.task.fields.status_id') }}</label>
                <div class="mt-1">
                    @foreach ($statuses as $status)
                        <div class="flex items-center">
                            <input
                                class="form-check-input h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                type="checkbox" value="{{ $status->id }}" id="filterStatus{{ $status->id }}"
                                name="filter_status[]">
                            <label class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                for="filterStatus{{ $status->id }}">
                                {{ $status->title }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-4">
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans('global.task.fields.priority_id') }}</label>
                <div class="mt-1">
                    @foreach ($priorities as $priority)
                        <div class="flex items-center">
                            <input
                                class="form-check-input h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                type="checkbox" value="{{ $priority->id }}" id="filterPriority{{ $priority->id }}"
                                name="filter_priority[]">
                            <label class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                for="filterPriority{{ $priority->id }}">
                                {{ $priority->title }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-4">
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans('global.date_filters') }}</label>
                <div class="mt-1">
                    <input type="date"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                        id="filterStartDate" name="filter_start_date"
                        placeholder="{{ trans('global.task.fields.start_date') }}">
                </div>
                <div class="mt-2">
                    <input type="date"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                        id="filterDueDate" name="filter_due_date"
                        placeholder="{{ trans('global.task.fields.due_date') }}">
                </div>
                <div class="mt-2">
                    <input type="date"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                        id="filterCompletedDate" name="filter_completed_date"
                        placeholder="{{ trans('global.task.fields.completion_date') }}">
                </div>
            </div>

            <div class="mb-4">
                <label for="filterProject"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans('global.task.fields.project_id') }}</label>
                <select
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 select2"
                    id="filterProject" name="filter_project">
                    <option value="">{{ trans('global.pleaseSelect') }}</option>
                    @if (
                        (isset($projectsForFilter) && is_array($projectsForFilter)) ||
                            $projectsForFilter instanceof \Illuminate\Support\Collection)
                        @foreach ($projectsForFilter as $project)
                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <button type="submit"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">{{ trans('global.apply_filter') }}</button>
            <button type="button"
                class="w-full mt-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                id="resetFilter">{{ trans('global.reset') }}</button>
        </form>
    </div>
</div>
<div class="drawer-backdrop fixed inset-0 bg-black bg-opacity-50 hidden" id="taskFilterBackdrop"></div>
