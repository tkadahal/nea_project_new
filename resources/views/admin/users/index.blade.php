@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.user.index');
    $isSuperAdminOrAdmin = $isSuperAdminOrAdmin ?? false;
@endphp

<x-layouts.app>
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.user.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.user.title') }}
            </p>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
            @if (auth()->user()->hasRole(\App\Models\Role::PROJECT_USER) ? $projectManager : true)
                @can('user_create')
                    <a href="{{ route('admin.user.create') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        {{ trans('global.add') }} {{ trans('global.new') }}
                    </a>
                @endcan
            @endif

            @if (auth()->user()->hasRole(\App\Models\Role::SUPERADMIN) || auth()->user()->hasRole(\App\Models\Role::ADMIN))
                <a href="{{ route('admin.online-users.index') }}"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition">
                    Online Users
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <div
            class="grid grid-cols-1 {{ auth()->user()->hasRole(\App\Models\Role::SUPERADMIN) ? 'md:grid-cols-3' : 'md:grid-cols-1' }} gap-4">
            @if (auth()->user()->hasRole(\App\Models\Role::SUPERADMIN))
                <div>
                    <label for="roleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Role
                    </label>
                    <select id="roleFilter"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <option value="">All Roles</option>
                        @foreach (\App\Models\Role::pluck('title', 'id') as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="directorateFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Directorate
                    </label>
                    <select id="directorateFilter"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <option value="">All Directorates</option>
                        @foreach (\App\Models\Directorate::pluck('title', 'id') as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search
                </label>
                <input type="text" id="searchInput" placeholder="Search by name or email..."
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            </div>
        </div>

        <div class="mt-3 flex justify-between items-center">
            @if (auth()->user()->hasRole(\App\Models\Role::SUPERADMIN))
                <button id="clearFilters"
                    class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300">
                    Clear Filters
                </button>
            @else
                <div></div>
            @endif

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
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading users...</p>
    </div>

    <!-- Users Table -->
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
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Previous</button>
            <div id="pageNumbers" class="flex space-x-2"></div>
            <button id="nextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Next</button>
        </div>
    </div>
</x-layouts.app>

<script>
    (function() {
        'use strict';

        const CONFIG = {
            csrfToken: '{{ $csrfToken }}',
            indexRoute: '{{ $indexRoute }}',
            routePrefix: '{{ $routePrefix }}',
            isSuperAdminOrAdmin: {{ $isSuperAdminOrAdmin ? 'true' : 'false' }}
        };

        const state = {
            currentPage: 1,
            perPage: 20,
            roleFilter: '',
            directorateFilter: '',
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null
        };

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function deleteUser(userId) {
            const message = CONFIG.isSuperAdminOrAdmin ?
                'Are you sure you want to delete this user?' :
                'Are you sure you want to remove this user from projects?';

            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/user/' + userId;

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
            }
        }
        window.deleteUser = deleteUser;

        async function loadUsers() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const tableBody = document.getElementById('tableBody');

            loadingIndicator.classList.remove('hidden');

            try {
                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: state.perPage,
                    role_filter: state.roleFilter,
                    directorate_filter: state.directorateFilter,
                    search: state.searchQuery
                });

                const response = await fetch(CONFIG.indexRoute + '?' + params, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                renderTable(data.data);

                state.totalPages = data.last_page || 1;
                state.totalRecords = data.total || 0;
                updatePagination();
            } catch (error) {
                console.error('Error loading users:', error);
                tableBody.innerHTML =
                    '<tr><td colspan="100" class="text-center text-red-500 py-4">Error loading users</td></tr>';
            } finally {
                loadingIndicator.classList.add('hidden');
            }
        }

        function renderTable(users) {
            const tbody = document.getElementById('tableBody');

            if (!users || users.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="100" class="text-center text-gray-500 py-8">No users found</td></tr>';
                return;
            }

            const deleteButtonText = CONFIG.isSuperAdminOrAdmin ? 'Delete' : 'Remove';

            let html = '';
            let separatorAdded = false;

            users.forEach((user, index) => {
                const roles = Array.isArray(user.roles) ? user.roles.join(', ') : user.roles;
                const canAccess = user.can_access;
                const disabledClass = !canAccess ? 'opacity-50 pointer-events-none' : '';
                const rowClass = !canAccess ? 'bg-gray-100 dark:bg-gray-800' :
                    'hover:bg-gray-100 dark:hover:bg-gray-800';

                // Add separator before first disabled user
                if (!canAccess && !separatorAdded) {
                    html +=
                        '<tr class="bg-gray-300 dark:bg-gray-700"><td colspan="6" class="py-2 px-6 text-center text-sm font-semibold text-gray-600 dark:text-gray-300">Users Without Access</td></tr>';
                    separatorAdded = true;
                }

                html += '<tr class="border-b ' + rowClass + ' ' + disabledClass + '">' +
                    '<td class="py-3 px-6">' + user.id + '</td>' +
                    '<td class="py-3 px-6">' + escapeHtml(user.name) +
                    (!canAccess ? ' <span class="text-xs text-gray-500">(No Access)</span>' : '') +
                    '</td>' +
                    '<td class="py-3 px-6">' + escapeHtml(user.email) + '</td>' +
                    '<td class="py-3 px-6">' +
                    '<div class="flex flex-wrap gap-1">' +
                    (Array.isArray(user.roles) ? user.roles.map(role =>
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">' +
                        escapeHtml(role) + '</span>'
                    ).join('') : escapeHtml(roles)) +
                    '</div>' +
                    '</td>' +
                    '<td class="py-3 px-6">' + escapeHtml(user.directorate_id) + '</td>' +
                    '<td class="py-3 px-6">' +
                    '<div class="flex space-x-2">' +
                    (canAccess ?
                        '<a href="/admin/user/' + user.id +
                        '" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">View</a>' +
                        '<a href="/admin/user/' + user.id +
                        '/edit" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm">Edit</a>' +
                        '<button type="button" onclick="deleteUser(' + user.id +
                        ')" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">' +
                        deleteButtonText + '</button>' :
                        '<span class="text-gray-400 text-sm">No Actions Available</span>'
                    ) +
                    '</div>' +
                    '</td>' +
                    '</tr>';
            });

            tbody.innerHTML = html;
        }

        function updatePagination() {
            document.getElementById('paginationInfo').textContent =
                'Page ' + state.currentPage + ' of ' + state.totalPages + ' (Total: ' + state.totalRecords +
                ' records)';

            document.getElementById('prevPage').disabled = state.currentPage <= 1;
            document.getElementById('nextPage').disabled = state.currentPage >= state.totalPages;

            renderPageNumbers();
        }

        function renderPageNumbers() {
            const container = document.getElementById('pageNumbers');
            container.innerHTML = '';

            const maxButtons = 5;
            let startPage = Math.max(1, state.currentPage - 2);
            let endPage = Math.min(state.totalPages, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = 'px-3 py-1 rounded ' + (i === state.currentPage ? 'bg-blue-500 text-white' :
                    'bg-gray-200 hover:bg-gray-300');
                button.onclick = function() {
                    goToPage(i);
                };
                container.appendChild(button);
            }
        }

        function goToPage(page) {
            state.currentPage = page;
            loadUsers();
        }

        // Event listeners
        document.getElementById('prevPage').addEventListener('click', function() {
            if (state.currentPage > 1) goToPage(state.currentPage - 1);
        });

        document.getElementById('nextPage').addEventListener('click', function() {
            if (state.currentPage < state.totalPages) goToPage(state.currentPage + 1);
        });

        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) {
            roleFilter.addEventListener('change', function(e) {
                state.roleFilter = e.target.value;
                state.currentPage = 1;
                loadUsers();
            });
        }

        const directorateFilter = document.getElementById('directorateFilter');
        if (directorateFilter) {
            directorateFilter.addEventListener('change', function(e) {
                state.directorateFilter = e.target.value;
                state.currentPage = 1;
                loadUsers();
            });
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(function() {
                state.searchQuery = e.target.value;
                state.currentPage = 1;
                loadUsers();
            }, 300);
        });

        document.getElementById('perPageSelect').addEventListener('change', function(e) {
            state.perPage = parseInt(e.target.value);
            state.currentPage = 1;
            loadUsers();
        });

        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                state.roleFilter = '';
                state.directorateFilter = '';
                state.searchQuery = '';
                state.currentPage = 1;

                document.getElementById('roleFilter').value = '';
                document.getElementById('directorateFilter').value = '';
                document.getElementById('searchInput').value = '';

                loadUsers();
            });
        }

        // Initial load
        console.log('Loading users...');
        loadUsers();
    })();
</script>
