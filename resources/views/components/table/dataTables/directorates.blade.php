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
    <div class="mb-4 flex justify-between items-center">
        <input type="text" id="searchInput" placeholder="{{ trans('global.search') }}"
            class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
               bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        <div>
            <label for="perPageSelect" class="mr-2 text-gray-600 dark:text-gray-300 text-sm md:text-base">
                Records Per Page
            </label>
            <select id="perPageSelect"
                class="p-2 border border-gray-300 dark:border-gray-700 rounded-md
                bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none
                focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </div>

    <table
        class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                @foreach ($headers as $index => $header)
                    <th class="py-3 px-6 text-left cursor-pointer" data-sort="{{ $index }}"
                        onclick="sortTable(this)">
                        {{ $header }}
                        <span class="ml-1 inline-block" id="sortIndicator_{{ $index }}">▼</span>
                    </th>
                @endforeach
                <th class="py-3 px-6 text-left">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="text-gray-600 dark:text-gray-300 text-sm font-light">
            @foreach ($data as $row)
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                    data-search="{{ collect($row)->flatten()->implode(' ') }}">
                    @foreach ($row as $key => $value)
                        <td class="py-3 px-6 text-left">
                            @if (is_array($value))
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($value as $item)
                                        @if (is_array($item))
                                            <x-forms.badge :title="$item['title']" :color="$item['color'] ?? $arrayColumnColor" />
                                        @else
                                            <x-forms.badge :title="$item" :color="$arrayColumnColor" />
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                    <td class="py-3 px-6 text-left">
                        <div class="flex space-x-2">
                            @can('directorate_show')
                                <a href="{{ route($routePrefix . '.show', $row['id']) }}"
                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-sm">
                                    {{ trans('global.view') }}
                                </a>
                            @endcan
                            @can('directorate_edit')
                                <a href="{{ route($routePrefix . '.edit', $row['id']) }}"
                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm">
                                    {{ trans('global.edit') }}
                                </a>
                            @endcan
                            @can('directorate_delete')
                                <form action="{{ route($routePrefix . '.destroy', $row['id']) }}" method="POST"
                                    onsubmit="return confirm('{{ $deleteConfirmationMessage }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-sm">
                                        {{ trans('global.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex justify-between items-center">
        <div id="paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
        <div class="flex space-x-2" id="paginationControls">
            <button id="prevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changePage(-1)">Previous</button>
            <div id="pageNumbers" class="flex space-x-2">
                {{-- Page numbers will be rendered here by JavaScript --}}
            </div>
            <button id="nextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changePage(1)">Next</button>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let rowsPerPage = {{ $perPage }};
    let sortIndex = -1;
    let sortDirection = 1;

    function updateTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const rows = Array.from(document.querySelectorAll('#tableBody tr'));
        let filteredRows = rows.filter(row => row.getAttribute('data-search').toLowerCase().includes(searchInput));

        if (sortIndex >= 0) {
            filteredRows.sort((a, b) => {
                let aValue = a.cells[sortIndex].textContent.trim();
                let bValue = b.cells[sortIndex].textContent.trim();
                if (a.cells[sortIndex].querySelector('.flex')) {
                    aValue = a.cells[sortIndex].textContent.split(',').length.toString();
                    bValue = b.cells[sortIndex].textContent.split(',').length.toString();
                }
                return sortDirection * aValue.localeCompare(bValue, undefined, {
                    numeric: true
                });
            });
        }

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        } else if (totalPages === 0) {
            currentPage = 0;
        }

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        rows.forEach(row => row.style.display = 'none');
        paginatedRows.forEach(row => row.style.display = '');

        document.getElementById('paginationInfo').textContent =
            `Page ${currentPage > 0 ? currentPage : 0} of ${totalPages} (Total: ${filteredRows.length} records)`;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;

        renderPageNumbers(totalPages);
    }

    function changePage(delta) {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const rows = Array.from(document.querySelectorAll('#tableBody tr'));
        const filteredRows = rows.filter(row => row.getAttribute('data-search').toLowerCase().includes(searchInput));
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

        currentPage += delta;
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        updateTable();
    }

    function goToPage(page) {
        currentPage = page;
        updateTable();
    }

    function renderPageNumbers(totalPages) {
        const pageNumbersContainer = document.getElementById('pageNumbers');
        pageNumbersContainer.innerHTML = '';

        const maxPageButtons = 5;
        let startPage, endPage;

        if (totalPages <= maxPageButtons) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const middle = Math.ceil(maxPageButtons / 2);
            if (currentPage <= middle) {
                startPage = 1;
                endPage = maxPageButtons;
            } else if (currentPage + middle > totalPages) {
                startPage = totalPages - maxPageButtons + 1;
                endPage = totalPages;
            } else {
                startPage = currentPage - middle + 1;
                endPage = currentPage + middle - 1;
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
                `px-3 py-1 rounded cursor-pointer ${currentPage === i ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'}`;
            button.onclick = () => goToPage(i);
            pageNumbersContainer.appendChild(button);
        }

        if (endPage < totalPages) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.className = 'px-3 py-1 text-gray-600 dark:text-gray-300';
            pageNumbersContainer.appendChild(ellipsis);
        }
    }

    function sortTable(th) {
        const index = parseInt(th.getAttribute('data-sort'));
        document.querySelectorAll('th span').forEach(span => span.textContent = '▼');

        if (sortIndex === index) {
            sortDirection *= -1;
        } else {
            sortIndex = index;
            sortDirection = 1;
        }

        th.querySelector('span').textContent = sortDirection === 1 ? '▲' : '▼';
        currentPage = 1;
        updateTable();
    }

    document.getElementById('searchInput').addEventListener('input', updateTable);
    document.getElementById('perPageSelect').addEventListener('change', function() {
        rowsPerPage = parseInt(this.value);
        currentPage = 1;
        updateTable();
    });
    window.addEventListener('load', updateTable);
</script>
