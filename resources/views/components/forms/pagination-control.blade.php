@props(['idPrefix' => ''])

@php
    $prefix = $idPrefix ? $idPrefix . '-' : '';
@endphp

<div class="mt-4 flex justify-between items-center">
    <div id="{{ $prefix }}paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
    <div class="flex space-x-2" id="{{ $prefix }}paginationControls">
        <button id="{{ $prefix }}prevPage"
            class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
            onclick="{{ $prefix }}changePage(-1)">Previous</button>
        <div id="{{ $prefix }}pageNumbers" class="flex space-x-2"></div>
        <button id="{{ $prefix }}nextPage"
            class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
            onclick="{{ $prefix }}changePage(1)">Next</button>
    </div>
</div>

<script>
    let currentPage = 1;
    const rowsPerPage = {{ $perPage }};
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
    window.addEventListener('load', updateTable);
</script>
