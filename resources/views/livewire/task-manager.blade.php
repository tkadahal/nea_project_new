<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ __('Tasks') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('Manage your tasks') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <button type="button" wire:click="setView('board')"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'board' ? 'active-view-button' : '' }}">
                    🗄️
                </button>
                <button type="button" wire:click="setView('list')"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'list' ? 'active-view-button' : '' }}">
                    📋
                </button>
                <button type="button" wire:click="setView('calendar')"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'calendar' ? 'active-view-button' : '' }}">
                    📅
                </button>
                <button type="button" wire:click="setView('table')"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 {{ $activeView === 'table' ? 'active-view-button' : '' }}">
                    📊
                </button>
            </div>
            <a href="{{ route('admin.task.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                {{ __('Add New') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($activeView === 'list')
        <div class="mb-4">
            <input type="text" wire:model.debounce.500ms="search" placeholder="{{ trans('global.search') }}..."
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        </div>
    @endif

    @if ($activeView === 'board')
        <div id="board-view">
            <x-tasks.task-board :tasks="$tasks" :statuses="$statuses" :status-colors="$statusColors" :priority-colors="$priorityColors" />
        </div>
    @elseif ($activeView === 'list')
        <div id="list-view">
            <x-tasks.task-list :tasks-flat="$tasksFlat" />
        </div>
    @elseif ($activeView === 'calendar')
        <div id="calendar-view">
            <x-tasks.task-calendar :events="$calendarData" />
        </div>
    @elseif ($activeView === 'table')
        <div id="table-view">
            <x-table.dataTable :headers="$tableHeaders" :data="$tableData" :routePrefix="$routePrefix" :deleteConfirmationMessage="$deleteConfirmationMessage"
                :actions="$actions" arrayColumnColor="gray" />
        </div>
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
            @if ($activeView === 'calendar')
                if (typeof window.renderTaskCalendar === 'function') {
                    window.renderTaskCalendar();
                }
            @elseif ($activeView === 'board')
                if (typeof window.attachDragAndDropListeners === 'function') {
                    window.attachDragAndDropListeners();
                }
            @endif

            // Listen for task updates to refresh client-side scripts
            window.livewire.on('taskUpdated', () => {
                @if ($activeView === 'calendar')
                    if (typeof window.renderTaskCalendar === 'function') {
                        window.renderTaskCalendar();
                    }
                @elseif ($activeView === 'board')
                    if (typeof window.attachDragAndDropListeners === 'function') {
                        window.attachDragAndDropListeners();
                    }
                @endif
            });
        });
    </script>
</div>
