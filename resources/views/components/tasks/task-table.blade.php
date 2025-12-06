@props([
    'headers' => [],
    'data' => [],
    'perPage' => 10,
    'routePrefix' => '',
    'actions' => ['view', 'edit', 'delete'],
    'deleteConfirmationMessage' => 'Are you sure you want to delete this item?',
    'arrayColumnColor' => 'gray',
])

<div class="overflow-x-auto">
    <div class="mb-4">
        <input type="text" id="taskTableSearchInput" placeholder="{{ trans('global.search') }}"
            class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
               bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
    </div>

    <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                @foreach ($headers as $index => $header)
                    <th class="py-3 px-6 text-left cursor-pointer" data-sort="{{ $index }}"
                        onclick="sortTaskTable(this)">
                        {{ $header }}
                        <span class="ml-1 inline-block" id="taskTableSortIndicator_{{ $index }}">▼</span>
                    </th>
                @endforeach
                <th class="py-3 px-6 text-left">Actions</th>
            </tr>
        </thead>
        <tbody id="taskTableBody" class="text-gray-600 dark:text-gray-300 text-sm font-light">
            @foreach ($data as $row)
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                    data-task-id="{{ $row['id'] }}"
                    data-directorate-id="{{ isset($row['details']['directorate']['title']) ? $row['directorate_id'] ?? '' : '' }}"
                    data-priority-id="{{ isset($row['details']['priority']['title']) ? $row['priority_id'] ?? '' : '' }}"
                    data-due-date="{{ $row['details']['due_date'] ?? '' }}" data-title="{{ $row['title'] ?? '' }}"
                    data-search="{{ $row['search_data'] ??collect($row)->except(['project_id', 'search_data'])->flatten()->implode(' ') }}">
                    <td class="py-3 px-6 text-left">{{ $row['id'] }}</td>
                    <td class="py-3 px-6 text-left">
                        {{ $row['title'] }}
                        @if ($row['parent_id'])
                            <span class="text-sm text-gray-500 dark:text-gray-400">Sub-task of:
                                {{ $row['parent_id'] }}</span>
                        @endif
                        <!-- Sub-tasks -->
                        @if (!empty($row['sub_tasks']))
                            <div class="mt-2">
                                <p class="text-sm font-semibold">Sub-tasks:</p>
                                <ul class="list-disc pl-5 text-sm">
                                    @foreach ($row['sub_tasks'] as $subTask)
                                        <li>
                                            <a href="{{ $subTask['view_url'] }}"
                                                class="hover:underline">{{ $subTask['title'] }}</a>
                                            <span
                                                class="badge inline-block px-2 py-1 text-xs font-semibold text-white rounded-full"
                                                style="background-color: {{ $subTask['status']['color'] ?? 'gray' }};">
                                                {{ $subTask['status']['title'] ?? 'N/A' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </td>
                    <td class="py-3 px-6 text-left">{{ $row['project'] }}</td>
                    <td class="py-3 px-6 text-left">{{ $row['parent_id'] }}</td>
                    <td class="py-3 px-6 text-left">
                        <div class="flex flex-wrap gap-2">
                            @if (isset($row['details']['status']['title']))
                                <x-forms.badge :title="$row['details']['status']['title']" :color="$row['details']['status']['color'] ?? $arrayColumnColor" />
                            @endif
                            @if (isset($row['details']['priority']['title']))
                                <x-forms.badge :title="$row['details']['priority']['title']" :color="$row['details']['priority']['color'] ?? $arrayColumnColor" />
                            @endif
                            @if (is_array($row['details']['users']))
                                @foreach ($row['details']['users'] as $user)
                                    <x-forms.badge :title="$user" :color="$arrayColumnColor" />
                                @endforeach
                            @endif
                            @if (isset($row['details']['directorate']['title']))
                                <x-forms.badge :title="$row['details']['directorate']['title']" :color="$row['details']['directorate']['color'] ?? $arrayColumnColor" />
                            @endif
                            @if ($row['details']['progress'] !== 'N/A')
                                <span>{{ trans('global.task.fields.progress') }}:
                                    {{ $row['details']['progress'] }}</span>
                            @endif
                            @if ($row['details']['due_date'] !== 'N/A')
                                <span>{{ trans('global.task.fields.due_date') }}:
                                    {{ $row['details']['due_date'] }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 px-6 text-left">
                        <div class="flex space-x-2">
                            @if (in_array('view', $actions))
                                <a href="{{ route($routePrefix . '.show', [$row['id'], $row['project_id']]) }}"
                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-sm">
                                    {{ trans('global.view') }}
                                </a>
                            @endif
                            @if (in_array('edit', $actions))
                                <a href="{{ route($routePrefix . '.edit', [$row['id'], $row['project_id']]) }}"
                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm">
                                    {{ trans('global.edit') }}
                                </a>
                            @endif
                            @if (in_array('delete', $actions))
                                <form action="{{ route($routePrefix . '.destroy', $row['id']) }}" method="POST"
                                    onsubmit="return confirm('{{ $deleteConfirmationMessage }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-sm">
                                        {{ trans('global.delete') }}
                                    </button>
                                </form>
                            @endif
                            @can('task_create')
                                <a href="{{ route($routePrefix . '.create', ['parent_id' => $row['id']]) }}"
                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm">
                                    Add Sub-task
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex justify-between items-center">
        <div id="taskTablePaginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
        <div class="flex space-x-2" id="taskTablePaginationControls">
            <button id="taskTablePrevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changeTaskTablePage(-1)">Previous</button>
            <div id="taskTablePageNumbers" class="flex space-x-2">
                {{-- Page numbers will be rendered here by JavaScript --}}
            </div>
            <button id="taskTableNextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changeTaskTablePage(1)">Next</button>
        </div>
    </div>
</div>

<script>
    let taskTableCurrentPage = 1;
    const taskTableRowsPerPage = {{ $perPage }};
    let taskTableSortIndex = -1;
    let taskTableSortDirection = 1;

    function updateTaskTable() {
        const searchInput = document.getElementById('taskTableSearchInput').value.toLowerCase();
        const rows = Array.from(document.querySelectorAll('#taskTableBody tr'));
        let filteredRows = rows.filter(row => row.getAttribute('data-search').toLowerCase().includes(searchInput));

        if (taskTableSortIndex >= 0) {
            filteredRows.sort((a, b) => {
                let aValue = a.cells[taskTableSortIndex].textContent.trim();
                let bValue = b.cells[taskTableSortIndex].textContent.trim();
                if (a.cells[taskTableSortIndex].querySelector('.flex')) {
                    aValue = a.cells[taskTableSortIndex].textContent.split(',').length.toString();
                    bValue = b.cells[taskTableSortIndex].textContent.split(',').length.toString();
                }
                return taskTableSortDirection * aValue.localeCompare(bValue, undefined, {
                    numeric: true
                });
            });
        }

        const totalPages = Math.ceil(filteredRows.length / taskTableRowsPerPage);

        if (taskTableCurrentPage > totalPages && totalPages > 0) {
            taskTableCurrentPage = totalPages;
        } else if (totalPages === 0) {
            taskTableCurrentPage = 0;
        }

        const start = (taskTableCurrentPage - 1) * taskTableRowsPerPage;
        const end = start + taskTableRowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        rows.forEach(row => row.style.display = 'none');
        paginatedRows.forEach(row => row.style.display = '');

        document.getElementById('taskTablePaginationInfo').textContent =
            `Page ${taskTableCurrentPage > 0 ? taskTableCurrentPage : 0} of ${totalPages} (Total: ${filteredRows.length} records)`;
        document.getElementById('taskTablePrevPage').disabled = taskTableCurrentPage <= 1;
        document.getElementById('taskTableNextPage').disabled = taskTableCurrentPage >= totalPages;

        renderTaskTablePageNumbers(totalPages);
    }

    function changeTaskTablePage(delta) {
        const searchInput = document.getElementById('taskTableSearchInput').value.toLowerCase();
        const rows = Array.from(document.querySelectorAll('#taskTableBody tr'));
        const filteredRows = rows.filter(row => row.getAttribute('data-search').toLowerCase().includes(searchInput));
        const totalPages = Math.ceil(filteredRows.length / taskTableRowsPerPage);

        taskTableCurrentPage += delta;
        if (taskTableCurrentPage < 1) taskTableCurrentPage = 1;
        if (taskTableCurrentPage > totalPages) taskTableCurrentPage = totalPages;
        updateTaskTable();
    }

    function goToTaskTablePage(page) {
        taskTableCurrentPage = page;
        updateTaskTable();
    }

    function renderTaskTablePageNumbers(totalPages) {
        const pageNumbersContainer = document.getElementById('taskTablePageNumbers');
        pageNumbersContainer.innerHTML = '';

        const maxPageButtons = 5;
        let startPage, endPage;

        if (totalPages <= maxPageButtons) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const middle = Math.ceil(maxPageButtons / 2);
            if (taskTableCurrentPage <= middle) {
                startPage = 1;
                endPage = maxPageButtons;
            } else if (taskTableCurrentPage + middle > totalPages) {
                startPage = totalPages - maxPageButtons + 1;
                endPage = totalPages;
            } else {
                startPage = taskTableCurrentPage - middle + 1;
                endPage = taskTableCurrentPage + middle - 1;
            }
        }

        if (startPage > 1) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.className = 'px-3 py-1 text-gray-600 dark:text-gray-300';
            pageNumbersContainer.appendChild(ellipsis);
        }

        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className =
                `px-3 py-1 rounded cursor-pointer ${taskTableCurrentPage === i ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'}`;
            button.onclick = () => goToTaskTablePage(i);
            pageNumbersContainer.appendChild(button);
        }

        if (endPage < totalPages) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.className = 'px-3 py-1 text-gray-600 dark:text-gray-300';
            pageNumbersContainer.appendChild(ellipsis);
        }
    }

    function sortTaskTable(th) {
        const index = parseInt(th.getAttribute('data-sort'));
        document.querySelectorAll('#taskTableBody th span').forEach(span => span.textContent = '▼');

        if (taskTableSortIndex === index) {
            taskTableSortDirection *= -1;
        } else {
            taskTableSortIndex = index;
            taskTableSortDirection = 1;
        }

        th.querySelector('span').textContent = taskTableSortDirection === 1 ? '▲' : '▼';
        taskTableCurrentPage = 1;
        updateTaskTable();
    }

    document.getElementById('taskTableSearchInput').addEventListener('input', updateTaskTable);
    window.addEventListener('load', updateTaskTable);
</script>
