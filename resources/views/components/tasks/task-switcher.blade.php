<div class="mb-6 flex items-center justify-between">
    <div class="inline-flex rounded-md shadow-sm" role="group">
        <button type="button"
            class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
            data-view="board">
            🗂️ Board
        </button>
        <button type="button"
            class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
            data-view="list">
            📋 List
        </button>
        <button type="button"
            class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
            data-view="calendar">
            📅 Calendar
        </button>
        <button type="button"
            class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
            data-view="table">
            📊 Table
        </button>
    </div>
    <button id="openTaskFilterDrawer"
        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
        {{ trans('global.filter') }}
    </button>
</div>
