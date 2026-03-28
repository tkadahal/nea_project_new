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
            currentView: 'grid' // Default is Grid View
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

        // ==================== PROGRESS BAR ====================
        function generateProgressBar(progress) {
            const percent = Math.min(100, Math.max(0, parseFloat(progress) || 0));

            let colorClass = 'bg-red-500';
            if (percent >= 90) colorClass = 'bg-green-500';
            else if (percent >= 70) colorClass = 'bg-blue-500';
            else if (percent >= 40) colorClass = 'bg-yellow-500';

            return `
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="${colorClass} h-3 rounded-full transition-all duration-300 ease-out" 
                         style="width: ${percent}%">
                    </div>
                </div>
                <div class="flex justify-between items-center mt-1 text-xs">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">${percent.toFixed(1)}%</span>
                    <span class="text-gray-500 dark:text-gray-400">Progress</span>
                </div>
            `;
        }

        function getStatusClass(status) {
            const s = String(status || '').toLowerCase();
            if (s.includes('completed') || s.includes('finish')) {
                return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
            }
            if (s.includes('progress') || s.includes('ongoing')) {
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
            }
            if (s.includes('delay') || s.includes('hold')) {
                return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
            }
            return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
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
            if (elements.errorDisplay) elements.errorDisplay.classList.add('hidden');
        }

        function showLoading() {
            if (elements.loadingIndicator) elements.loadingIndicator.classList.remove('hidden');
        }

        function hideLoading() {
            if (elements.loadingIndicator) elements.loadingIndicator.classList.add('hidden');
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

        function generateActionsHtml(contract) {
            const canEdit = canPerformAction('edit');
            const canExtend = canPerformAction('extend');
            const canDelete = canPerformAction('delete');

            let html = `
                <a href="${CONFIG.viewRoute}${contract.id}" 
                   class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                    View
                </a>
            `;

            html += `
                <details class="relative inline-block text-left">
                    <summary class="cursor-pointer list-none p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a2 2 0 11-4 0 2 2 0 014 0zm7 0a2 2 0 11-4 0 2 2 0 014 0zm7 0a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </summary>
                    <div class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                        ${canEdit ? `
                            <a href="${CONFIG.viewRoute}${contract.id}/edit" 
                               class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-yellow-600">
                                ✏️ Edit
                            </a>
                        ` : ''}
                        ${canExtend ? `
                            <a href="${CONFIG.viewRoute}${contract.id}/extend" 
                               class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-green-600">
                                ↗ Extend
                            </a>
                        ` : ''}
                        ${canDelete ? `
                            <button onclick="deleteContract(${contract.id})" 
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 border-t border-gray-100 dark:border-gray-700">
                                🗑 Delete
                            </button>
                        ` : ''}
                    </div>
                </details>
            `;

            return html;
        }

        // ==================== RENDER FUNCTIONS ====================
        function renderTableView(contracts) {
            if (!elements.tableBody) return;

            if (!contracts || contracts.length === 0) {
                elements.tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            No contracts found
                        </td>
                    </tr>`;
                return;
            }

            const rows = contracts.map(contract => {
                const progressHtml = generateProgressBar(contract.progress);
                const actionsHtml = generateActionsHtml(contract);

                return `
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="py-4 px-6 font-mono text-sm">${escapeHtml(contract.id)}</td>
                        <td class="py-4 px-6 font-medium">${escapeHtml(contract.title)}</td>
                        <td class="py-4 px-6">${escapeHtml(contract.project)}</td>
                        <td class="py-4 px-6">${escapeHtml(contract.directorate)}</td>
                        <td class="py-4 px-6 text-right font-medium">${escapeHtml(contract.contract_amount)}</td>
                        <td class="py-4 px-6 w-48">${progressHtml}</td>
                        <td class="py-4 px-6">
                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full ${getStatusClass(contract.status)}">
                                ${escapeHtml(contract.status)}
                            </span>
                        </td>
                        <td class="py-4 px-6">${escapeHtml(contract.priority)}</td>
                        <td class="py-4 px-6">
                            <div class="flex gap-2">
                                ${actionsHtml}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            elements.tableBody.innerHTML = rows;
        }

        function renderGridView(contracts) {
            if (!elements.gridView) return;

            if (!contracts || contracts.length === 0) {
                elements.gridView.innerHTML = `
                    <p class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                        No contracts found
                    </p>`;
                return;
            }

            const cards = contracts.map(contract => {
                const progressHtml = generateProgressBar(contract.progress);
                const actionsHtml = generateActionsHtml(contract);

                return `
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col h-full border border-gray-100 dark:border-gray-700">
                        <div class="p-6 flex-1">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 leading-tight line-clamp-2">
                                ${escapeHtml(contract.title)}
                            </h3>

                            <div class="mt-5 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Project</span>
                                    <span class="font-medium text-right">${escapeHtml(contract.project)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Directorate</span>
                                    <span class="font-medium text-right">${escapeHtml(contract.directorate)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                    <span class="font-medium text-right">${escapeHtml(contract.contract_amount)}</span>
                                </div>
                            </div>

                            <!-- Progress -->
                            <div class="mt-7">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">PROGRESS</div>
                                ${progressHtml}
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700 p-5 flex justify-end gap-2 bg-gray-50 dark:bg-gray-900">
                            ${actionsHtml}
                        </div>
                    </div>
                `;
            }).join('');

            elements.gridView.innerHTML = cards;
        }

        function renderEmpty(message) {
            if (state.currentView === 'list' && elements.tableBody) {
                elements.tableBody.innerHTML = `
                    <tr><td colspan="9" class="text-center py-12 text-gray-500">${escapeHtml(message)}</td></tr>`;
            } else if (elements.gridView) {
                elements.gridView.innerHTML = `
                    <p class="col-span-full text-center py-12 text-gray-500">${escapeHtml(message)}</p>`;
            }
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

                const response = await fetch(CONFIG.indexRoute + '?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

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
                showError('Failed to load contracts. Please try again.');
                renderEmpty('Error loading contracts');
            } finally {
                hideLoading();
                state.isLoading = false;
            }
        }

        function updatePagination() {
            if (!elements.paginationInfo) return;

            const start = state.totalRecords === 0 ? 0 : ((state.currentPage - 1) * state.perPage) + 1;
            const end = Math.min(state.currentPage * state.perPage, state.totalRecords);

            elements.paginationInfo.textContent =
                `Showing ${start} to ${end} of ${state.totalRecords} records`;

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
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = i === state.currentPage ?
                    'px-4 py-2 bg-blue-600 text-white rounded-lg' :
                    'px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg';
                btn.addEventListener('click', () => goToPage(i));
                elements.pageNumbers.appendChild(btn);
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
            // Pagination
            elements.prevPage?.addEventListener('click', () => {
                if (state.currentPage > 1) goToPage(state.currentPage - 1);
            });

            elements.nextPage?.addEventListener('click', () => {
                if (state.currentPage < state.totalPages) goToPage(state.currentPage + 1);
            });

            // Filters
            elements.directorateFilter?.addEventListener('change', (e) => {
                state.directorateFilter = e.target.value;
                state.currentPage = 1;
                loadContracts();
            });

            elements.projectFilter?.addEventListener('change', (e) => {
                state.projectFilter = e.target.value;
                state.currentPage = 1;
                loadContracts();
            });

            elements.statusFilter?.addEventListener('change', (e) => {
                state.statusFilter = e.target.value;
                state.currentPage = 1;
                loadContracts();
            });

            elements.priorityFilter?.addEventListener('change', (e) => {
                state.priorityFilter = e.target.value;
                state.currentPage = 1;
                loadContracts();
            });

            // Search with debounce
            elements.searchInput?.addEventListener('input', (e) => {
                clearTimeout(state.debounceTimer);
                state.debounceTimer = setTimeout(() => {
                    state.searchQuery = e.target.value.trim();
                    state.currentPage = 1;
                    loadContracts();
                }, 400);
            });

            elements.perPageSelect?.addEventListener('change', (e) => {
                state.perPage = parseInt(e.target.value);
                state.currentPage = 1;
                loadContracts();
            });

            elements.clearFilters?.addEventListener('click', () => {
                state.directorateFilter = '';
                state.projectFilter = '';
                state.statusFilter = '';
                state.priorityFilter = '';
                state.searchQuery = '';
                state.currentPage = 1;

                elements.directorateFilter.value = '';
                elements.projectFilter.value = '';
                elements.statusFilter.value = '';
                elements.priorityFilter.value = '';
                elements.searchInput.value = '';

                loadContracts();
            });

            // View Switch
            elements.listViewButton?.addEventListener('click', () => switchView('list'));
            elements.gridViewButton?.addEventListener('click', () => switchView('grid'));
        }

        function init() {
            cacheElements();
            setupEventListeners();

            // Handle pre-selected project from URL
            const urlParams = new URLSearchParams(window.location.search);
            const initialProjectId = urlParams.get('project_id');
            if (initialProjectId) {
                state.projectFilter = initialProjectId;
                if (elements.projectFilter) elements.projectFilter.value = initialProjectId;
            }

            // Start with Grid View (as you wanted)
            switchView('grid');
        }

        // Initialize
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>
