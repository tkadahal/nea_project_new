@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.contract.index');

    $user = auth()->user();
    $userRoles = [];
    $highestRoleType = 'guest';

    if ($user) {
        $userRoles = $user->roles->pluck('id')->toArray();

        if (in_array(\App\Models\Role::SUPERADMIN, $userRoles) || in_array(\App\Models\Role::ADMIN, $userRoles)) {
            $highestRoleType = 'superadmin';
        } elseif (in_array(\App\Models\Role::PROJECT_USER, $userRoles)) {
            $highestRoleType = 'project_user';
        } elseif (in_array(\App\Models\Role::DIRECTORATE_USER, $userRoles)) {
            $highestRoleType = 'directorate_user';
        } elseif (in_array(\App\Models\Role::DEPARTMENT_USER, $userRoles)) {
            $highestRoleType = 'department_user';
        } elseif (in_array(\App\Models\Role::CORPORATE_USER, $userRoles)) {
            $highestRoleType = 'corporate_user';
        } else {
            $highestRoleType = 'standard';
        }
    }
@endphp

<x-layouts.app>
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.contract.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.contract.title') }}
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <button id="listViewButton"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <button id="gridViewButton"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h6v6H4V6zm10 10h6v6H4v-6zm10 0h6v6h-6v-6z"></path>
                    </svg>
                </button>
            </div>

            @can('contract_create')
                <a href="{{ route('admin.contract.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
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
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                </label>
                <select id="statusFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Statuses</option>
                    @foreach ($filters['statuses'] as $status)
                        <option value="{{ $status->id }}">{{ $status->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="priorityFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Priority
                </label>
                <select id="priorityFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Priorities</option>
                    @foreach ($filters['priorities'] as $priority)
                        <option value="{{ $priority->id }}">{{ $priority->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <button id="clearFilters"
                    class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">
                    Clear Filters
                </button>
                <input type="text" id="searchInput" placeholder="Search contracts..."
                    class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            </div>

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
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading contracts...</p>
    </div>

    <!-- Error Display -->
    <div id="errorDisplay" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <p id="errorMessage"></p>
    </div>

    <!-- List View -->
    <div id="listView" class="hidden">
        <div class="overflow-x-auto">
            <table
                class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
                <thead>
                    <tr
                        class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">Title</th>
                        <th class="py-3 px-6 text-left">Project</th>
                        <th class="py-3 px-6 text-left">Directorate</th>
                        <th class="py-3 px-6 text-left">Amount</th>
                        <th class="py-3 px-6 text-left">Progress</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Priority</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="text-gray-600 dark:text-gray-300 text-sm"></tbody>
            </table>
        </div>
    </div>

    <!-- Grid View -->
    <div id="gridView" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>

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
            viewRoute: '/admin/contract/',
            deleteRoute: '/admin/contract/',
            userRoleType: '{{ $highestRoleType }}'
        };

        function canPerformAction(action) {
            if (!CONFIG.userRoleType || CONFIG.userRoleType === 'guest' || CONFIG.userRoleType === 'standard') {
                return action === 'view';
            }
            if (CONFIG.userRoleType === 'project_user' || CONFIG.userRoleType === 'directorate_user') {
                return true;
            }
            if (CONFIG.userRoleType === 'superadmin') {
                return true;
            }
            return false;
        }

        const state = {
            currentPage: 1,
            perPage: 20,
            directorateFilter: '',
            projectFilter: '',
            statusFilter: '',
            priorityFilter: '',
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null,
            isLoading: false,
            currentView: 'list'
        };

        let elements = {};

        function cacheElements() {
            elements = {
                loadingIndicator: document.getElementById('loadingIndicator'),
                errorDisplay: document.getElementById('errorDisplay'),
                errorMessage: document.getElementById('errorMessage'),
                tableBody: document.getElementById('tableBody'),
                gridView: document.getElementById('gridView'),
                listView: document.getElementById('listView'),
                paginationInfo: document.getElementById('paginationInfo'),
                pageNumbers: document.getElementById('pageNumbers'),
                prevPage: document.getElementById('prevPage'),
                nextPage: document.getElementById('nextPage'),
                directorateFilter: document.getElementById('directorateFilter'),
                projectFilter: document.getElementById('projectFilter'),
                statusFilter: document.getElementById('statusFilter'),
                priorityFilter: document.getElementById('priorityFilter'),
                searchInput: document.getElementById('searchInput'),
                perPageSelect: document.getElementById('perPageSelect'),
                clearFilters: document.getElementById('clearFilters'),
                listViewButton: document.getElementById('listViewButton'),
                gridViewButton: document.getElementById('gridViewButton')
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

        window.deleteContract = function(contractId) {
            if (!confirm('Are you sure you want to delete this contract?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = CONFIG.deleteRoute + contractId;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = CONFIG.csrfToken;

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        };

        // --- Helper to generate Actions HTML (Refactored) ---
        function generateActionsHtml(contract) {
            const canEdit = canPerformAction('edit');
            const canExtend = canPerformAction('extend');
            const canDelete = canPerformAction('delete');

            // 1. Primary Action: View
            let html = `
            <a href="${CONFIG.viewRoute}${contract.id}"
               class="px-3 py-1 bg-blue-500 text-white rounded text-xs font-medium hover:bg-blue-600 transition-colors shadow-sm">
               View
            </a>
            `;

            // 2. Secondary Actions: Dropdown (⋮)
            html += `
            <details class="relative inline-block text-left">
                <summary class="cursor-pointer list-none p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors ml-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                </summary>

                <!-- Dropdown Menu -->
                <div class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden">
                    <!-- Edit -->
                    ${canEdit ? `
                        <a href="${CONFIG.viewRoute}${contract.id}/edit"
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-yellow-50 dark:hover:bg-gray-700">
                           <span class="text-yellow-600 mr-2">✎</span> Edit
                        </a>
                    ` : ''}

                    <!-- Extend -->
                    ${canExtend ? `
                        <a href="${CONFIG.viewRoute}${contract.id}/extend"
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700">
                           <span class="text-green-600 mr-2">↗</span> Extend
                        </a>
                    ` : ''}

                    <!-- Delete -->
                    ${canDelete ? `
                        <button type="button" onclick="deleteContract(${contract.id})"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 border-t border-gray-100 dark:border-gray-700 mt-1">
                           <span class="mr-2">🗑</span> Delete
                        </button>
                    ` : ''}

                </div>
            </details>
            `;

            return html;
        }

        async function loadContracts() {
            if (state.isLoading) return;

            state.isLoading = true;
            showLoading();
            hideError();

            try {
                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: state.perPage,
                    directorate_filter: state.directorateFilter || '',
                    project_filter: state.projectFilter || '',
                    status_filter: state.statusFilter || '',
                    priority_filter: state.priorityFilter || '',
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

                if (state.currentView === 'list') {
                    renderTableView(data.data || []);
                } else {
                    renderGridView(data.data || []);
                }

                state.totalPages = data.last_page || 1;
                state.totalRecords = data.total || 0;
                updatePagination();

            } catch (error) {
                console.error('Error loading contracts:', error);
                showError('Failed to load contracts: ' + error.message);
                renderEmpty('Error loading contracts. Please try again.');
            } finally {
                hideLoading();
                state.isLoading = false;
            }
        }

        function renderTableView(contracts) {
            if (!elements.tableBody) return;

            if (!contracts || contracts.length === 0) {
                renderEmpty('No contracts found');
                return;
            }

            const rows = contracts.map(contract => {
                const actionsHtml = generateActionsHtml(contract);
                return `
            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-6">${escapeHtml(contract.id)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.title)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.project)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.directorate)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.contract_amount)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.progress)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.status)}</td>
                <td class="py-3 px-6">${escapeHtml(contract.priority)}</td>
                <td class="py-3 px-6 flex items-center gap-2">
                    ${actionsHtml}
                </td>
            </tr>
        `;
            }).join('');

            elements.tableBody.innerHTML = rows;
        }

        function renderGridView(contracts) {
            if (!elements.gridView) return;

            if (!contracts || contracts.length === 0) {
                elements.gridView.innerHTML =
                    '<p class="col-span-full text-center text-gray-500 py-8">No contracts found</p>';
                return;
            }

            const cards = contracts.map(contract => {
                const actionsHtml = generateActionsHtml(contract);
                return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex flex-col">
                <div class="flex-grow">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">${escapeHtml(contract.title)}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">${escapeHtml(contract.description || '')}</p>
                    <div class="mt-4 space-y-2">
                        <p class="text-sm"><span class="font-semibold">Project:</span> ${escapeHtml(contract.project)}</p>
                        <p class="text-sm"><span class="font-semibold">Directorate:</span> ${escapeHtml(contract.directorate)}</p>
                        <p class="text-sm"><span class="font-semibold">Amount:</span> ${escapeHtml(contract.contract_amount)}</p>
                        <p class="text-sm"><span class="font-semibold">Progress:</span> ${escapeHtml(contract.progress)}</p>
                        <p class="text-sm"><span class="font-semibold">Status:</span> ${escapeHtml(contract.status)}</p>
                        <p class="text-sm"><span class="font-semibold">Priority:</span> ${escapeHtml(contract.priority)}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end">
                    ${actionsHtml}
                </div>
            </div>
        `;
            }).join('');

            elements.gridView.innerHTML = cards;
        }

        function renderEmpty(message) {
            if (state.currentView === 'list' && elements.tableBody) {
                elements.tableBody.innerHTML =
                    `<tr><td colspan="9" class="text-center text-gray-500 py-8">${escapeHtml(message)}</td></tr>`;
            } else if (elements.gridView) {
                elements.gridView.innerHTML =
                    `<p class="col-span-full text-center text-gray-500 py-8">${escapeHtml(message)}</p>`;
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
            loadContracts();
        }

        function switchView(view) {
            state.currentView = view;
            if (view === 'list') {
                elements.listView.classList.remove('hidden');
                elements.gridView.classList.add('hidden');
                elements.listViewButton.classList.add('text-blue-600', 'dark:text-blue-500');
                elements.gridViewButton.classList.remove('text-blue-600', 'dark:text-blue-500');
            } else {
                elements.gridView.classList.remove('hidden');
                elements.listView.classList.add('hidden');
                elements.gridViewButton.classList.add('text-blue-600', 'dark:text-blue-500');
                elements.listViewButton.classList.remove('text-blue-600', 'dark:text-blue-500');
            }
            loadContracts();
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
                    loadContracts();
                });
            }

            if (elements.projectFilter) {
                elements.projectFilter.addEventListener('change', (e) => {
                    state.projectFilter = e.target.value;
                    state.currentPage = 1;
                    loadContracts();
                });
            }

            if (elements.statusFilter) {
                elements.statusFilter.addEventListener('change', (e) => {
                    state.statusFilter = e.target.value;
                    state.currentPage = 1;
                    loadContracts();
                });
            }

            if (elements.priorityFilter) {
                elements.priorityFilter.addEventListener('change', (e) => {
                    state.priorityFilter = e.target.value;
                    state.currentPage = 1;
                    loadContracts();
                });
            }

            if (elements.searchInput) {
                elements.searchInput.addEventListener('input', (e) => {
                    clearTimeout(state.debounceTimer);
                    state.debounceTimer = setTimeout(() => {
                        state.searchQuery = e.target.value;
                        state.currentPage = 1;
                        loadContracts();
                    }, 300);
                });
            }

            if (elements.perPageSelect) {
                elements.perPageSelect.addEventListener('change', (e) => {
                    state.perPage = parseInt(e.target.value);
                    state.currentPage = 1;
                    loadContracts();
                });
            }

            if (elements.clearFilters) {
                elements.clearFilters.addEventListener('click', () => {
                    state.directorateFilter = '';
                    state.projectFilter = '';
                    state.statusFilter = '';
                    state.priorityFilter = '';
                    state.searchQuery = '';
                    state.currentPage = 1;

                    if (elements.directorateFilter) elements.directorateFilter.value = '';
                    if (elements.projectFilter) elements.projectFilter.value = '';
                    if (elements.statusFilter) elements.statusFilter.value = '';
                    if (elements.priorityFilter) elements.priorityFilter.value = '';
                    if (elements.searchInput) elements.searchInput.value = '';

                    loadContracts();
                });
            }

            if (elements.listViewButton) {
                elements.listViewButton.addEventListener('click', () => switchView('list'));
            }

            if (elements.gridViewButton) {
                elements.gridViewButton.addEventListener('click', () => switchView('grid'));
            }
        }

        function init() {
            cacheElements();
            setupEventListeners();

            // ---------------------------------------------------
            // FIX START: Read project_id from URL and apply filter
            // ---------------------------------------------------
            const urlParams = new URLSearchParams(window.location.search);
            const initialProjectId = urlParams.get('project_id');

            if (initialProjectId) {
                // Set the state so the AJAX request sends the correct filter
                state.projectFilter = initialProjectId;

                // Visually select the project in the dropdown
                if (elements.projectFilter) {
                    elements.projectFilter.value = initialProjectId;
                }
            }
            // ---------------------------------------------------
            // FIX END
            // ---------------------------------------------------

            switchView('list'); // This triggers loadContracts()
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>
