@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.projectActivity.index');
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

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.projectActivity.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Manage project annual programs and activities
            </p>
        </div>

        @can('projectActivity_create')
            <a href="{{ route('admin.projectActivity.create') }}"
                class="inline-flex items-center px-5 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New
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
                            {{ $currentFiscalYearId == $fiscalYear->id ? 'selected' : '' }}>
                            {{ $fiscalYear->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search
                </label>
                <input type="text" id="searchInput" placeholder="Search project or fiscal year..."
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
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading programs...</p>
    </div>

    <!-- Error Display -->
    <div id="errorDisplay" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <p id="errorMessage"></p>
    </div>

    <!-- Activities Table -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Fiscal Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Version</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Total Budget</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Capital</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Recurrent</th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
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

    <!-- Rejection Modal Template -->
    <div id="rejectModalTemplate" class="hidden">
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center reject-modal">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Reject Annual Program</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    This will return the program to <strong>Draft</strong> status.
                </p>
                <form class="reject-form" method="POST">
                    <input type="hidden" name="_token" value="{{ $csrfToken }}">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Reason for Rejection <span class="text-red-500">*</span>
                        </label>
                        <textarea name="rejection_reason" rows="4" required placeholder="Provide a clear reason..."
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button"
                            class="cancel-reject px-5 py-2 text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>

<script>
    (function() {
        'use strict';

        const CONFIG = {
            csrfToken: '{{ $csrfToken }}',
            indexRoute: '{{ route('admin.projectActivity.index') }}',
            showRoute: '{{ url('admin/projectActivity') }}/show/',
            editRoute: '{{ url('admin/projectActivity/edit') }}/',
            sendReviewRoute: '{{ url('admin/projectActivity') }}/',
            reviewRoute: '{{ url('admin/projectActivity') }}/',
            approveRoute: '{{ url('admin/projectActivity') }}/',
            rejectRoute: '{{ url('admin/projectActivity') }}/',
            returnDraftRoute: '{{ url('admin/projectActivity') }}/',
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

        async function loadActivities() {
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
                console.error('Error loading activities:', error);
                showError('Failed to load activities: ' + error.message);
                renderEmpty('Error loading activities');
            } finally {
                hideLoading();
                state.isLoading = false;
            }
        }

        function getStatusBadge(activity) {
            const statusConfig = {
                'draft': {
                    bg: 'bg-gray-100 dark:bg-gray-700',
                    text: 'text-gray-800 dark:text-gray-200',
                    icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    label: 'Draft',
                },
                'under_review': {
                    bg: 'bg-yellow-100 dark:bg-yellow-900/30',
                    text: 'text-yellow-800 dark:text-yellow-300',
                    icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    label: activity.reviewed_at ? 'Reviewed' : 'Under Review',
                },
                'approved': {
                    bg: 'bg-green-100 dark:bg-green-900/30',
                    text: 'text-green-800 dark:text-green-300',
                    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    label: 'Approved',
                },
            };

            const cfg = statusConfig[activity.status] ?? statusConfig['draft'];

            return `
            <span class="${cfg.bg} ${cfg.text} inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${cfg.icon}" />
                </svg>
                ${cfg.label}
            </span>
        `;
        }

        function getActionButtons(activity) {
            const buttons = [];
            const pid = activity.project_id;
            const fyid = activity.fiscal_year_id;

            if (activity.can_edit) {
                buttons.push(`
                    <a href="${CONFIG.editRoute}${pid}/${fyid}"
                       class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded transition">
                        Edit
                    </a>
                `);

                buttons.push(`
                    <button onclick="sendForReview(${pid}, ${fyid})"
                            class="px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded transition">
                        Send for Review
                    </button>
                `);
            }

            if (activity.can_review) {
                buttons.push(`
                    <button onclick="markReviewed(${pid}, ${fyid})"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded transition">
                        Mark Reviewed
                    </button>
                `);
            }

            if (activity.can_approve) {
                buttons.push(`
                    <button onclick="approve(${pid}, ${fyid})"
                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition">
                        Approve
                    </button>
                `);
            }

            if (activity.can_reject) {
                buttons.push(`
                    <button onclick="openRejectModal(${pid}, ${fyid})"
                            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition">
                        Reject
                    </button>
                `);
            }

            if (activity.can_return_to_draft) {
                buttons.push(`
                    <button onclick="returnToDraft(${pid}, ${fyid})"
                            class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs rounded transition">
                        Return to Draft
                    </button>
                `);
            }

            return buttons;
        }

        function renderTable(activities) {
            if (!elements.tableBody) return;

            if (!activities || activities.length === 0) {
                renderEmpty('No Annual Programs Found');
                return;
            }

            const rows = activities.map(activity => {
                const actionButtons = getActionButtons(activity);

                return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">${getStatusBadge(activity)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        ${escapeHtml(activity.fiscal_year_title)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                        ${escapeHtml(activity.project_title)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                        v${escapeHtml(activity.current_version)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                        ${escapeHtml(activity.total_budget)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                        ${escapeHtml(activity.capital_budget)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                        ${escapeHtml(activity.recurrent_budget)}
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-medium">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <a href="${CONFIG.showRoute}${activity.project_id}/${activity.fiscal_year_id}/${activity.current_version}"
                               class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                                View
                            </a>
                            ${actionButtons.join('')}
                        </div>
                    </td>
                </tr>
            `;
            }).join('');

            elements.tableBody.innerHTML = rows;
        }

        function renderEmpty(message) {
            if (elements.tableBody) {
                elements.tableBody.innerHTML =
                    `<tr><td colspan="8" class="text-center text-gray-500 py-8">${escapeHtml(message)}</td></tr>`;
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
            loadActivities();
        }

        window.sendForReview = function(projectId, fiscalYearId) {
            if (!confirm('Send for review? Editing will be locked.')) return;
            submitForm(`${CONFIG.sendReviewRoute}${projectId}/${fiscalYearId}/send-for-review`);
        };

        window.markReviewed = function(projectId, fiscalYearId) {
            if (!confirm('Mark as reviewed?')) return;
            submitForm(`${CONFIG.reviewRoute}${projectId}/${fiscalYearId}/review`);
        };

        window.approve = function(projectId, fiscalYearId) {
            if (!confirm('Approve permanently? This cannot be undone.')) return;
            submitForm(`${CONFIG.approveRoute}${projectId}/${fiscalYearId}/approve`);
        };

        window.returnToDraft = function(projectId, fiscalYearId) {
            if (!confirm(
                    'Return this approved program to draft? The Project User will be able to edit it again.'))
                return;
            submitForm(`${CONFIG.returnDraftRoute}${projectId}/${fiscalYearId}/returnToDraft`);
        };

        window.openRejectModal = function(projectId, fiscalYearId) {
            const template = document.getElementById('rejectModalTemplate');
            const modal = template.firstElementChild.cloneNode(true);
            modal.id = `reject-modal-${projectId}-${fiscalYearId}`;

            const form = modal.querySelector('.reject-form');
            form.action = `${CONFIG.rejectRoute}${projectId}/${fiscalYearId}/reject`;

            const cancelBtn = modal.querySelector('.cancel-reject');
            cancelBtn.onclick = () => modal.remove();

            modal.addEventListener('click', function(e) {
                if (e.target === modal) modal.remove();
            });

            document.body.appendChild(modal);
            modal.classList.remove('hidden');
        };

        function submitForm(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = CONFIG.csrfToken;

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }

        document.addEventListener('click', function(event) {
            // No dropdown cleanup needed anymore
        });

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
                    loadActivities();
                });
            }

            if (elements.projectFilter) {
                elements.projectFilter.addEventListener('change', (e) => {
                    state.projectFilter = e.target.value;
                    state.currentPage = 1;
                    loadActivities();
                });
            }

            if (elements.fiscalYearFilter) {
                elements.fiscalYearFilter.addEventListener('change', (e) => {
                    state.fiscalYearFilter = e.target.value;
                    state.currentPage = 1;
                    loadActivities();
                });
            }

            if (elements.searchInput) {
                elements.searchInput.addEventListener('input', (e) => {
                    clearTimeout(state.debounceTimer);
                    state.debounceTimer = setTimeout(() => {
                        state.searchQuery = e.target.value;
                        state.currentPage = 1;
                        loadActivities();
                    }, 300);
                });
            }

            if (elements.perPageSelect) {
                elements.perPageSelect.addEventListener('change', (e) => {
                    state.perPage = parseInt(e.target.value);
                    state.currentPage = 1;
                    loadActivities();
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

                    loadActivities();
                });
            }
        }

        function init() {
            cacheElements();

            if (elements.fiscalYearFilter) {
                state.fiscalYearFilter = elements.fiscalYearFilter.value;
            }

            setupEventListeners();
            loadActivities();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>
