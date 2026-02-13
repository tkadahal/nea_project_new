@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.budget.index');
    $allocationRoute = '/admin/budget/';

    // Fetch Role IDs for logic
    $userRoleIds = auth()->user()->roles->pluck('id')->toArray();
    $isSuperAdminOrAdmin =
        auth()->user()->hasRole(\App\Models\Role::SUPERADMIN) || auth()->user()->hasRole(\App\Models\Role::ADMIN);

    // Get Current Fiscal Year
    $currentFiscalYear = \App\Models\FiscalYear::currentFiscalYear();
    $currentFiscalYearId = $currentFiscalYear ? $currentFiscalYear->id : null;

    $directorateColors = config('colors.directorate');
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
                {{ trans('global.budget.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.budget.title') }}
            </p>
        </div>

        @can('budget_create')
            <a href="{{ route('admin.budget.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                {{ trans('global.add') }} {{ trans('global.new') }}
            </a>
        @endcan
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if ($isSuperAdminOrAdmin || auth()->user()->hasRole(\App\Models\Role::DIRECTORATE_USER))
                <div>
                    <label for="directorateFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
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
            @endif

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
                <label for="fiscalYearFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Fiscal Year
                </label>
                <select id="fiscalYearFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Fiscal Years</option>
                    @foreach ($filters['fiscalYears'] as $fiscalYear)
                        <option value="{{ $fiscalYear->id }}"
                            {{ $fiscalYear->id == $currentFiscalYearId ? 'selected' : '' }}>
                            {{ $fiscalYear->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search
                </label>
                <input type="text" id="searchInput" placeholder="Search by project or fiscal year..."
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
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading budgets...</p>
    </div>

    <!-- Error Display -->
    <div id="errorDisplay" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <p id="errorMessage"></p>
    </div>

    <!-- Budgets Table -->
    <div class="overflow-x-auto">
        <table
            class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
            <thead>
                <tr
                    class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                    @foreach ($headers as $header)
                        <th class="py-3 px-6 text-left">{{ $header }}</th>
                    @endforeach
                    <th class="py-3 px-6 text-left">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="text-gray-600 dark:text-gray-300 text-sm font-light"></tbody>
        </table>
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

        // Configuration
        const CONFIG = {
            csrfToken: '{{ $csrfToken }}',
            indexRoute: '{{ $indexRoute }}',
            deleteRoute: '/admin/budget/',
            viewRoute: '/admin/budget/',
            editRoute: '/admin/budget/',
            allocationRoute: '/admin/budgetQuaterAllocation/create',

            // User Roles passed from PHP
            userRoles: {!! json_encode($userRoleIds) !!},

            // Role IDs (VERIFY these IDs in your database/Role model)
            roleId: {
                SUPERADMIN: 1,
                ADMIN: 2
            },

            directorateColors: {!! json_encode($directorateColors) !!},

            currentFiscalYearId: {{ $currentFiscalYearId ? $currentFiscalYearId : 'null' }}
        };

        // Application State
        const state = {
            currentPage: 1,
            perPage: 20,
            directorateFilter: '',
            projectFilter: '',
            fiscalYearFilter: CONFIG.currentFiscalYearId,
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null,
            isLoading: false,
            allProjects: []
        };

        // DOM Elements Cache
        let elements = {};

        // Initialize DOM elements
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
                clearFilters: document.getElementById('clearFilters')
            };
        }

        // Utility Functions
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
                setTimeout(() => {
                    elements.errorDisplay.classList.add('hidden');
                }, 5000);
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

        // --- NEW: Load Projects for Dropdown (Client-Side Strategy) ---
        async function loadProjectsForDropdown() {
            console.log('Loading projects for dropdown...');
            try {
                const url = CONFIG.indexRoute + '?lightweight=1';
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('Failed to load projects list');
                    return;
                }

                const data = await response.json();
                state.allProjects = data.projects || [];

                // Initial population based on current selection (if any)
                const currentDirectorate = elements.directorateFilter ? elements.directorateFilter.value : '';
                populateProjectDropdown(currentDirectorate);

            } catch (error) {
                console.error('Error loading projects for dropdown:', error);
            }
        }

        // --- NEW: Populate Project Dropdown based on stored state ---
        function populateProjectDropdown(directorateId) {
            const projectSelect = elements.projectFilter;
            if (!projectSelect) return;

            // Save current selection to restore if possible
            const currentValue = projectSelect.value;

            // Reset dropdown
            projectSelect.innerHTML = '<option value="">{{ trans('global.allProjects') }}</option>';

            // Filter projects locally
            let filteredProjects = state.allProjects;
            if (directorateId) {
                filteredProjects = state.allProjects.filter(p => p.directorate_id == directorateId);
            }

            // Add options
            filteredProjects.forEach(project => {
                const option = document.createElement('option');
                option.value = project.id;
                option.textContent = project.title;
                if (project.id == currentValue) {
                    option.selected = true;
                }
                projectSelect.appendChild(option);
            });
        }

        // Delete Budget Function
        window.deleteBudget = function(budgetId) {
            if (!confirm('Are you sure you want to delete this budget? This action cannot be undone.')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = CONFIG.deleteRoute + budgetId;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = CONFIG.csrfToken;

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(cssrToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        };

        // Load Budgets from Server
        async function loadBudgets() {
            if (state.isLoading) {
                console.log('Already loading, skipping...');
                return;
            }

            state.isLoading = true;
            showLoading();
            hideError();

            try {
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
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server response:', errorText);
                    throw new Error(`Server error (${response.status}): ${response.statusText}`);
                }

                const data = await response.json();

                if (!data || typeof data !== 'object') {
                    throw new Error('Invalid response format from server');
                }

                renderTable(data.data || []);

                state.totalPages = data.last_page || 1;
                state.totalRecords = data.total || 0;
                updatePagination();

            } catch (error) {
                console.error('Error loading budgets:', error);
                showError('Failed to load budgets: ' + error.message);
                renderEmptyTable('Error loading budgets. Please try again.');
            } finally {
                hideLoading();
                state.isLoading = false;
            }
        }

        // Render Table (Updated: Colors & Totals)
        function renderTable(budgets) {
            if (!elements.tableBody) {
                console.error('Table body element not found');
                return;
            }

            if (!budgets || budgets.length === 0) {
                renderEmptyTable('No budgets found');
                return;
            }

            // 1. Sort by Directorate Title
            const sortedBudgets = budgets.sort((a, b) => {
                const dirA = a.directorate || 'Z';
                const dirB = b.directorate || 'Z';
                return dirA.localeCompare(dirB);
            });

            // 2. Group by Directorate
            const grouped = sortedBudgets.reduce((groups, item) => {
                const dirName = item.directorate || 'Uncategorized';
                if (!groups[dirName]) groups[dirName] = [];
                groups[dirName].push(item);
                return groups;
            }, {});

            const isSuperAdminOrAdmin = CONFIG.userRoles.includes(CONFIG.roleId.SUPERADMIN) || CONFIG.userRoles
                .includes(CONFIG.roleId.ADMIN);
            let html = '';

            for (const [directorateName, items] of Object.entries(grouped)) {

                // --- NEW: Calculate Total & Get Color ---
                const dirId = items[0].directorate_id; // Get ID to find color
                const colorName = CONFIG.directorateColors[dirId] || 'gray'; // Get color from config (default gray)

                // Calculate Total (Parse "1,000.00" to float, sum, format back)
                const dirTotal = items.reduce((sum, item) => {
                    const val = parseFloat((item.total_budget || '0').replace(/,/g, ''));
                    return sum + (isNaN(val) ? 0 : val);
                }, 0);
                const formattedTotal = dirTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // --- Group Header (Styled) ---
                html += `
                    <tr class="bg-${colorName}-100 border-b-2 border-${colorName}-300 dark:bg-${colorName}-900 dark:border-${colorName}-700">
                        <td colspan="11" class="py-3 px-6">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                                <!-- Name (Colored) -->
                                <span class="font-bold text-lg text-${colorName}-800 dark:text-${colorName}-200 uppercase tracking-wider">
                                    ${escapeHtml(directorateName)}
                                </span>
                                <!-- Total Budget -->
                                <span class="text-sm font-semibold text-${colorName}-900 dark:text-${colorName}-300 bg-white dark:bg-gray-800 px-3 py-1 rounded-full shadow-sm border border-${colorName}-200 dark:border-${colorName}-700">
                                    Total: ${formattedTotal}
                                </span>
                            </div>
                        </td>
                    </tr>
                `;

                // --- Item Rows ---
                items.forEach(budget => {
                    const actionsHtml = generateActionsHtml(budget, isSuperAdminOrAdmin);

                    html += `
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="py-3 px-6 font-mono text-xs text-gray-500 dark:text-gray-400">${budget.id}</td>
                        <td class="py-3 px-6">${escapeHtml(budget.fiscal_year)}</td>
                        <td class="py-3 px-6 font-medium">${escapeHtml(budget.project)}</td>
                        <td class="py-3 px-6 text-right">${escapeHtml(budget.government_share)}</td>
                        <td class="py-3 px-6 text-right">${escapeHtml(budget.government_loan)}</td>
                        <td class="py-3 px-6 text-right">${escapeHtml(budget.foreign_loan)}</td>
                        <td class="py-3 px-6 text-right">${escapeHtml(budget.foreign_subsidy)}</td>
                        <td class="py-3 px-6 text-right">${escapeHtml(budget.internal_budget)}</td>
                        <td class="py-3 px-6 text-right font-bold text-gray-800 dark:text-gray-200">${escapeHtml(budget.total_budget)}</td>
                        <td class="py-3 px-6 text-center">${escapeHtml(budget.budget_revision)}</td>
                        <td class="py-3 px-6 flex items-center gap-2">
                            ${actionsHtml}
                        </td>
                    </tr>`;
                });
            }

            elements.tableBody.innerHTML = html;
        }

        // --- NEW: Generate Actions (Cleaner UI + Correct Routes) ---
        function generateActionsHtml(budget, canEdit) {
            // 1. Primary Action: View (Points to Show Page)
            let html = `
            <a href="${CONFIG.viewRoute}${budget.id}"
               class="px-3 py-1 bg-blue-500 text-white rounded text-xs font-medium hover:bg-blue-600 transition-colors shadow-sm">
               View
            </a>
            `;

            // 2. Secondary Actions: Dropdown (⋮)
            html += `
            <details class="relative inline-block text-left">
                <summary class="cursor-pointer list-none p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                </summary>

                <!-- Dropdown Menu -->
                <div class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden">

                    <!-- Edit (SuperAdmin, Admin only) -->
                    ${canEdit ? `
                        <a href="${CONFIG.editRoute}${budget.id}/edit"
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-yellow-50 dark:hover:bg-gray-700">
                           <span class="text-yellow-600 mr-2">✎</span> Edit
                        </a>
                    ` : ''}

                    <!-- Quarter Allocation (All) -->
                    <a href="${CONFIG.allocationRoute}?budget_id=${budget.id}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700">
                       <span class="text-green-600 mr-2">📅</span> Quarter
                    </a>

                    <!-- Delete (SuperAdmin, Admin only) -->
                    ${canEdit ? `
                        <button type="button" onclick="deleteBudget(${budget.id})"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 border-t border-gray-100 dark:border-gray-700 mt-1">
                           <span class="mr-2">🗑</span> Delete
                        </button>
                    ` : ''}

                </div>
            </details>
        `;

            return html;
        }

        // Render Empty Table State
        function renderEmptyTable(message) {
            if (!elements.tableBody) return;

            elements.tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-gray-500 dark:text-gray-400 py-8">
                    ${escapeHtml(message)}
                </td>
            </tr>
        `;
        }

        // Update Pagination Controls
        function updatePagination() {
            if (!elements.paginationInfo) return;

            const start = state.totalRecords === 0 ? 0 : ((state.currentPage - 1) * state.perPage) + 1;
            const end = Math.min(state.currentPage * state.perPage, state.totalRecords);

            elements.paginationInfo.textContent =
                `Showing ${start} to ${end} of ${state.totalRecords} records (Page ${state.currentPage} of ${state.totalPages})`;

            if (elements.prevPage) {
                elements.prevPage.disabled = state.currentPage <= 1;
            }

            if (elements.nextPage) {
                elements.nextPage.disabled = state.currentPage >= state.totalPages;
            }

            renderPageNumbers();
        }

        // Render Pagination Page Numbers
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
                    'px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition-colors';

                button.addEventListener('click', () => goToPage(i));
                elements.pageNumbers.appendChild(button);
            }
        }

        // Navigate to Specific Page
        function goToPage(page) {
            if (page < 1 || page > state.totalPages || page === state.currentPage) {
                return;
            }
            state.currentPage = page;
            loadBudgets();
        }

        // Debounced Search Function
        function debounceSearch(value) {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => {
                state.searchQuery = value;
                state.currentPage = 1;
                loadBudgets();
            }, 300);
        }

        // Clear All Filters
        function clearAllFilters() {
            state.directorateFilter = '';
            state.projectFilter = '';
            state.fiscalYearFilter = '';
            state.searchQuery = '';
            state.currentPage = 1;

            if (elements.directorateFilter) elements.directorateFilter.value = '';
            if (elements.projectFilter) elements.projectFilter.value = '';
            if (elements.fiscalYearFilter) elements.fiscalYearFilter.value = '';
            if (elements.searchInput) elements.searchInput.value = '';

            // Reset dropdown using all projects
            populateProjectDropdown('');

            loadBudgets();
        }

        // Setup Event Listeners
        function setupEventListeners() {
            // Pagination
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

            // Directorate Filter Listener (Client-Side Logic)
            if (elements.directorateFilter) {
                elements.directorateFilter.addEventListener('change', (e) => {
                    state.directorateFilter = e.target.value;
                    state.currentPage = 1;

                    // 1. Update Project Dropdown Options (Locally)
                    populateProjectDropdown(e.target.value);

                    // 2. Reset Project Selection (because it might not exist in new list)
                    state.projectFilter = '';
                    if (elements.projectFilter) elements.projectFilter.value = '';

                    // 3. Load Data
                    loadBudgets();
                });
            }

            if (elements.projectFilter) {
                elements.projectFilter.addEventListener('change', (e) => {
                    state.projectFilter = e.target.value;
                    state.currentPage = 1;
                    loadBudgets();
                });
            }

            if (elements.fiscalYearFilter) {
                elements.fiscalYearFilter.addEventListener('change', (e) => {
                    state.fiscalYearFilter = e.target.value;
                    state.currentPage = 1;
                    loadBudgets();
                });
            }

            if (elements.searchInput) {
                elements.searchInput.addEventListener('input', (e) => {
                    debounceSearch(e.target.value);
                });
            }

            if (elements.perPageSelect) {
                elements.perPageSelect.addEventListener('change', (e) => {
                    state.perPage = parseInt(e.target.value);
                    state.currentPage = 1;
                    loadBudgets();
                });
            }

            if (elements.clearFilters) {
                elements.clearFilters.addEventListener('click', clearAllFilters);
            }
        }

        // Initialize Application
        function init() {
            console.log('Initializing budget page...');

            cacheElements();
            setupEventListeners();

            // 1. Pre-select Fiscal Year Dropdown visually
            if (elements.fiscalYearFilter && CONFIG.currentFiscalYearId) {
                elements.fiscalYearFilter.value = CONFIG.currentFiscalYearId;
            }

            // 1. Load Projects for Dropdown (Client-Side Strategy)
            loadProjectsForDropdown();

            // 2. Load Initial Table Data
            loadBudgets();
        }

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>
