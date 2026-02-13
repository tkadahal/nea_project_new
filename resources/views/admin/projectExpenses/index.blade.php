@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.projectExpense.index');
@endphp

<x-layouts.app>
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.expense.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.expense.title') }}
            </p>
        </div>

        @can('projectExpense_create')
            <a id="createExpenseBtn" href="#"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors duration-200">
                Select Project
            </a>
        @endcan
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="directorateFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Directorate
                </label>
                <select id="directorateFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Directorates</option>
                    @foreach ($filters['directorates'] as $directorate)
                        <option value="{{ $directorate->id }}">{{ $directorate->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="projectFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Project
                </label>
                <select id="projectFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Projects</option>
                    @foreach ($filters['projects'] as $project)
                        <option value="{{ $project->id }}"
                            {{ isset($filters['selectedProjectId']) && $filters['selectedProjectId'] == $project->id ? 'selected' : '' }}>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fiscal Year Section -->
            <div>
                <label for="fiscalYearFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Fiscal Year
                </label>
                <select id="fiscalYearFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Fiscal Years</option>
                    @foreach ($filters['fiscalYears'] as $fiscalYear)
                        <option value="{{ $fiscalYear->id }}"
                            {{ isset($filters['selectedFiscalYearId']) && $filters['selectedFiscalYearId'] == $fiscalYear->id ? 'selected' : '' }}>
                            {{ $fiscalYear->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search
                </label>
                <input type="text" id="searchInput" placeholder="Search project..."
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            </div>
        </div>

        <div class="mt-3 flex justify-between items-center">
            <button id="clearFilters"
                class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">
                Clear Filters
            </button>

            <div>
                <label for="perPageSelect" class="mr-2 text-gray-600 dark:text-gray-300 text-sm">
                    Records Per Page
                </label>
                <select id="perPageSelect"
                    class="p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="hidden text-center py-8">
        <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
            </circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading expenses...</p>
    </div>

    <!-- Error Display -->
    <div id="errorDisplay" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <p id="errorMessage"></p>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Project</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Fiscal Year</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Filled Quarters</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total Expense</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Capital Expense</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Recurrent Expense</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="mt-4 flex justify-between items-center">
        <div id="paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm"></div>
        <div class="flex space-x-2">
            <button id="prevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <div id="pageNumbers" class="flex space-x-2"></div>
            <button id="nextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>
</x-layouts.app>

<script>
    (function() {
        'use strict';

        const CONFIG = {
            csrfToken: '{{ $csrfToken }}',
            indexRoute: '{{ $indexRoute }}',
            createRoute: '{{ route('admin.projectExpense.create') }}',
            showRoute: '/admin/projectExpense/show/'
        };

        const state = {
            currentPage: 1,
            perPage: 20,
            directorateFilter: '',
            projectFilter: '',
            fiscalYearFilter: '',
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null,
            isLoading: false
        };

        let elements = {};

        function cacheElements() {
            elements = {
                loadingIndicator: document.getElementById('loadingIndicator'),
                errorDisplay: document.getElementById('errorDisplay'),
                errorMessage: document.getElementById('errorMessage'),
                tableBody: document.getElementById('tableBody'),
                paginationInfo: document.getElementById('paginationInfo'),
                pageNumbers: document.getElementById('pageNumbers'),
                prevPage: document.getElementById('prevPage'),
                nextPage: document.getElementById('nextPage'),
                directorateFilter: document.getElementById('directorateFilter'),
                projectFilter: document.getElementById('projectFilter'),
                fiscalYearFilter: document.getElementById('fiscalYearFilter'),
                searchInput: document.getElementById('searchInput'),
                perPageSelect: document.getElementById('perPageSelect'),
                clearFilters: document.getElementById('clearFilters'),
                createExpenseBtn: document.getElementById('createExpenseBtn'), // Cache the button
            };
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }

        function showError(message) {
            console.error('Error:', message);
            if (elements.errorDisplay && elements.errorMessage) {
                elements.errorMessage.textContent = message;
                elements.errorDisplay.classList.remove('hidden');
                setTimeout(() => elements.errorDisplay.classList.add('hidden'), 5000);
            }
        }

        function hideError() {
            if (elements.errorDisplay) {
                elements.errorDisplay.classList.add('hidden');
            }
        }

        function showLoading() {
            if (elements.loadingIndicator) {
                elements.loadingIndicator.classList.remove('hidden');
            }
        }

        function hideLoading() {
            if (elements.loadingIndicator) {
                elements.loadingIndicator.classList.add('hidden');
            }
        }

        // New function to handle the Add New button state
        function updateCreateButton() {
            if (!elements.createExpenseBtn || !elements.projectFilter) return;

            const projectId = elements.projectFilter.value;
            const fiscalYearId = elements.fiscalYearFilter.value;
            const btn = elements.createExpenseBtn;

            // If no project is selected, disable the button
            if (!projectId) {
                btn.href = '#';
                btn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-500');
                btn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                btn.innerText = 'Select Project';
                return;
            }

            // If a project is selected, enable and set URL
            let url = `${CONFIG.createRoute}?project_id=${projectId}`;
            if (fiscalYearId) {
                url += `&fiscal_year_id=${fiscalYearId}`;
            }

            btn.href = url;
            btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-500');
            btn.classList.add('bg-blue-500', 'hover:bg-blue-600');
            btn.innerText = 'Add New'; // Or use trans: {{ trans('global.add') }} {{ trans('global.new') }}
        }

        async function loadExpenses() {
            if (state.isLoading) return;

            state.isLoading = true;
            showLoading();
            hideError();

            try {
                // Update state from DOM elements to ensure we have latest values
                if (elements.directorateFilter) state.directorateFilter = elements.directorateFilter.value;
                if (elements.projectFilter) state.projectFilter = elements.projectFilter.value;
                if (elements.fiscalYearFilter) state.fiscalYearFilter = elements.fiscalYearFilter.value;
                if (elements.searchInput) state.searchQuery = elements.searchInput.value;

                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: state.perPage,
                    directorate_filter: state.directorateFilter || '',
                    project_filter: state.projectFilter || '',
                    fiscal_year_filter: state.fiscalYearFilter || '',
                    search: state.searchQuery || ''
                });

                const response = await fetch(CONFIG.indexRoute + '?' + params, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server error (${response.status}): ${response.statusText}`);
                }

                const data = await response.json();
                renderTable(data.data || []);

                state.totalPages = data.last_page || 1;
                state.totalRecords = data.total || 0;
                updatePagination();

            } catch (error) {
                console.error('Error loading expenses:', error);
                showError('Failed to load expenses: ' + error.message);
                renderEmpty('Error loading expenses. Please try again.');
            } finally {
                hideLoading();
                state.isLoading = false;
            }
        }

        function renderTable(expenses) {
            if (!elements.tableBody) return;

            if (!expenses || expenses.length === 0) {
                renderEmpty('No expenses found');
                return;
            }

            const rows = expenses.map(expense => {
                // --- FIX START: Handle String vs Array ---
                let quarters = [];
                if (expense.filled_quarters) {
                    if (Array.isArray(expense.filled_quarters)) {
                        quarters = expense.filled_quarters;
                    } else {
                        // If it's a string from SQL "1, 2", convert to array [1, 2]
                        quarters = String(expense.filled_quarters)
                            .split(',')
                            .map(q => parseInt(q.trim(), 10)) // Convert to number
                            .filter(n => !isNaN(n)); // Remove any invalid numbers
                        // Optional: Sort and unique (since SQL order might vary)
                        quarters = [...new Set(quarters)].sort((a, b) => a - b);
                    }
                }
                // --- FIX END ---

                const quartersHtml = quarters.length > 0 ?
                    quarters.map(q => `
                    <span class="inline-flex items-center px-3 py-1 mr-2 mb-1 text-xs font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full shadow-sm">
                        Q${q}
                    </span>
                  `).join('') :
                    '<span class="text-gray-400 dark:text-gray-500 text-sm italic">No quarters filled</span>';

                return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                        ${escapeHtml(expense.project_title)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        ${escapeHtml(expense.fiscal_year_title)}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        ${quartersHtml}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                        ${escapeHtml(expense.total_expense)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                        ${escapeHtml(expense.capital_expense)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                        ${escapeHtml(expense.recurrent_expense)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="${CONFIG.showRoute}${expense.project_id}/${expense.fiscal_year_id}"
                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View
                        </a>
                    </td>
                </tr>
            `;
            }).join('');

            elements.tableBody.innerHTML = rows;
        }

        function renderEmpty(message) {
            if (elements.tableBody) {
                elements.tableBody.innerHTML =
                    `<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">${escapeHtml(message)}</td></tr>`;
            }
        }

        function updatePagination() {
            if (!elements.paginationInfo) return;

            const start = state.totalRecords === 0 ? 0 : ((state.currentPage - 1) * state.perPage) + 1;
            const end = Math.min(state.currentPage * state.perPage, state.totalRecords);

            elements.paginationInfo.textContent =
                `Showing ${start} to ${end} of ${state.totalRecords} records (Page ${state.currentPage} of ${state.totalPages})`;

            if (elements.prevPage) elements.prevPage.disabled = state.currentPage <= 1;
            if (elements.nextPage) elements.nextPage.disabled = state.currentPage >= state.totalPages;

            renderPageNumbers();
        }

        function renderPageNumbers() {
            if (!elements.pageNumbers) return;

            elements.pageNumbers.innerHTML = '';

            const maxButtons = 5;
            let startPage = Math.max(1, state.currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(state.totalPages, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = i === state.currentPage ?
                    'px-3 py-1 rounded bg-blue-500 text-white' :
                    'px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200';
                button.addEventListener('click', () => goToPage(i));
                elements.pageNumbers.appendChild(button);
            }
        }

        function goToPage(page) {
            if (page < 1 || page > state.totalPages || page === state.currentPage) return;
            state.currentPage = page;
            loadExpenses();
        }

        function setupEventListeners() {
            if (elements.prevPage) {
                elements.prevPage.addEventListener('click', () => {
                    if (state.currentPage > 1) goToPage(state.currentPage - 1);
                });
            }

            if (elements.nextPage) {
                elements.nextPage.addEventListener('click', () => {
                    if (state.currentPage < state.totalPages) goToPage(state.currentPage + 1);
                });
            }

            if (elements.directorateFilter) {
                elements.directorateFilter.addEventListener('change', (e) => {
                    state.directorateFilter = e.target.value;
                    state.currentPage = 1;
                    loadExpenses();
                });
            }

            if (elements.projectFilter) {
                elements.projectFilter.addEventListener('change', (e) => {
                    state.projectFilter = e.target.value;
                    state.currentPage = 1;
                    loadExpenses();
                    updateCreateButton(); // Update button state when project changes
                });
            }

            if (elements.fiscalYearFilter) {
                elements.fiscalYearFilter.addEventListener('change', (e) => {
                    state.fiscalYearFilter = e.target.value;
                    state.currentPage = 1;
                    loadExpenses();
                    updateCreateButton(); // Update button state (to pass fiscal year) when it changes
                });
            }

            if (elements.searchInput) {
                elements.searchInput.addEventListener('input', (e) => {
                    clearTimeout(state.debounceTimer);
                    state.debounceTimer = setTimeout(() => {
                        state.searchQuery = e.target.value;
                        state.currentPage = 1;
                        loadExpenses();
                    }, 300);
                });
            }

            if (elements.perPageSelect) {
                elements.perPageSelect.addEventListener('change', (e) => {
                    state.perPage = parseInt(e.target.value);
                    state.currentPage = 1;
                    loadExpenses();
                });
            }

            if (elements.clearFilters) {
                elements.clearFilters.addEventListener('click', () => {
                    state.directorateFilter = '';
                    state.projectFilter = '';
                    state.fiscalYearFilter = '';
                    state.searchQuery = '';
                    state.currentPage = 1;

                    if (elements.directorateFilter) elements.directorateFilter.value = '';
                    if (elements.projectFilter) elements.projectFilter.value = '';
                    if (elements.fiscalYearFilter) elements.fiscalYearFilter.value = '';
                    if (elements.searchInput) elements.searchInput.value = '';

                    loadExpenses();
                    updateCreateButton(); // Reset button state
                });
            }
        }

        function init() {
            cacheElements();
            setupEventListeners();
            updateCreateButton(); // Initialize button state
            loadExpenses();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>
