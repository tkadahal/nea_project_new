@php
    use App\Models\Role;

    $arrayColumnColorJson = json_encode($arrayColumnColor);
    $userRolesJson = json_encode(Auth::user()->roles->pluck('id')->toArray());
    $csrfToken = csrf_token();
    $indexRoute = route('admin.project.index');

    $userRoleIds = Auth::user()->roles->pluck('id')->toArray();
    $showDirectorateFilter = in_array(Role::SUPERADMIN, $userRoleIds) || in_array(Role::ADMIN, $userRoleIds);
@endphp

<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.project.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.project.title') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-blue-500 text-white border border-gray-200 rounded-l-md hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700"
                    data-view="card">
                    🗂️
                </button>
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
                    data-view="list">
                    📋
                </button>
            </div>

            @can('project_create')
                <a href="{{ route('admin.project.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            @if ($showDirectorateFilter)
                <!-- Directorate Filter (Only for SuperAdmin & Admin) -->
                <div>
                    <label for="directorateFilter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('global.project.fields.directorate_id') }}
                    </label>
                    <select id="directorateFilter"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <option value="">{{ trans('global.allDirectorate') }}</option>
                        @foreach ($directorates as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Project Filter -->
            <div @if ($showDirectorateFilter) class="" @else class="md:col-span-2" @endif>
                <label for="projectFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ trans('global.project.title_singular') }}
                </label>
                <select id="projectFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">{{ trans('global.allProjects') }}</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                </label>
                <select id="statusFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Status</option>
                    <option value="1">To Do</option>
                    <option value="2">In Progress</option>
                    <option value="3">Completed</option>
                </select>
            </div>

            <!-- Search Filter -->
            <div>
                <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ trans('global.search') }}
                </label>
                <input type="text" id="searchInput" placeholder="Search projects..."
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            </div>
        </div>

        <div class="mt-3 flex justify-end">
            <button id="clearFilters"
                class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                Clear Filters
            </button>
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
        <p class="text-gray-600 dark:text-gray-400 mt-2">Loading projects...</p>
    </div>

    <!-- Card View -->
    <div id="card-view">
        <div id="cardContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </div>

    <!-- List View -->
    <div id="list-view" class="mb-6 hidden">
        <div class="overflow-x-auto">
            <table
                class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
                <thead>
                    <tr
                        class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-sm leading-normal">
                        @foreach ($tableHeaders as $header)
                            <th class="py-3 px-6 text-left">{{ $header }}</th>
                        @endforeach
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="text-gray-600 dark:text-gray-300 text-sm font-light"></tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="mt-4 flex justify-between items-center">
        <div id="paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
        <div class="flex space-x-2" id="paginationControls">
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

        console.log('Script starting...');

        const CONFIG = {
            arrayColumnColor: {!! $arrayColumnColorJson !!},
            routePrefix: '{{ $routePrefix }}',
            userRoles: {!! $userRolesJson !!},
            csrfToken: '{{ $csrfToken }}',
            indexRoute: '{{ $indexRoute }}',
            showDirectorateFilter: {{ $showDirectorateFilter ? 'true' : 'false' }}
        };

        const state = {
            currentPage: 1,
            perPage: 12,
            currentView: 'card',
            directorateFilter: '',
            projectFilter: '',
            statusFilter: '',
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null,
            allProjects: []
        };

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function deleteProject(projectId) {
            if (confirm('Are you sure you want to delete this project?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/project/' + projectId;

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
        window.deleteProject = deleteProject;

        function populateProjectDropdown(directorateId) {
            console.log('Populating dropdown, directorate:', directorateId, 'Total projects:', state.allProjects
                .length);

            const projectFilter = document.getElementById('projectFilter');
            if (!projectFilter) {
                console.error('Project filter not found!');
                return;
            }

            const currentValue = projectFilter.value;
            projectFilter.innerHTML = '<option value="">{{ trans('global.allProjects') }}</option>';

            let filteredProjects = state.allProjects;

            // If the user sees the directorate filter, filter the projects based on selection
            // If not, allProjects should already be filtered by the backend (user scope)
            if (CONFIG.showDirectorateFilter && directorateId) {
                filteredProjects = state.allProjects.filter(p => p.directorate_id == directorateId);
            }

            console.log('Filtered projects for dropdown:', filteredProjects.length);

            filteredProjects.forEach(project => {
                const option = document.createElement('option');
                option.value = project.id;
                option.textContent = project.title;
                if (project.id == currentValue) {
                    option.selected = true;
                }
                projectFilter.appendChild(option);
            });

            console.log('Dropdown populated with', filteredProjects.length, 'projects');
        }

        async function loadProjectsForDropdown() {
            console.log('Loading projects for dropdown...');

            try {
                const url = CONFIG.indexRoute + '?lightweight=1';
                console.log('Fetching from:', url);

                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);

                if (!response.ok) {
                    console.error('Failed to load projects:', response.status);
                    return;
                }

                const data = await response.json();
                console.log('Received data:', data);

                state.allProjects = data.projects || [];
                console.log('Total projects loaded:', state.allProjects.length);

                // If directorate filter is visible, try to preserve selection or default to all
                const directorateEl = document.getElementById('directorateFilter');
                const currentDirectorate = directorateEl ? directorateEl.value : '';

                populateProjectDropdown(currentDirectorate);
            } catch (error) {
                console.error('Error loading projects for dropdown:', error);
            }
        }

        async function loadProjects() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const cardContainer = document.getElementById('cardContainer');
            const tableBody = document.getElementById('tableBody');

            loadingIndicator.classList.remove('hidden');

            try {
                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: state.perPage,
                    view: state.currentView,
                    status_id: state.statusFilter,
                    search: state.searchQuery
                });

                // Only add directorate_id if the filter exists
                if (CONFIG.showDirectorateFilter) {
                    const directorateEl = document.getElementById('directorateFilter');
                    if (directorateEl) {
                        params.append('directorate_id', directorateEl.value);
                    }
                }

                const projectEl = document.getElementById('projectFilter');
                if (projectEl) {
                    params.append('project_id', projectEl.value);
                }

                const response = await fetch(CONFIG.indexRoute + '?' + params, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (state.currentView === 'card') {
                    renderCards(data.data);
                } else {
                    renderTable(data.tableData);
                }

                state.totalPages = data.last_page || 1;
                state.totalRecords = data.total || 0;
                updatePagination();
            } catch (error) {
                console.error('Error loading projects:', error);
                if (state.currentView === 'card') {
                    cardContainer.innerHTML =
                        '<div class="col-span-full text-center text-red-500 py-8">Error loading projects</div>';
                } else {
                    tableBody.innerHTML =
                        '<tr><td colspan="100" class="text-center text-red-500 py-4">Error loading projects</td></tr>';
                }
            } finally {
                loadingIndicator.classList.add('hidden');
            }
        }

        function renderCards(projects) {
            const container = document.getElementById('cardContainer');

            if (!projects || projects.length === 0) {
                container.innerHTML =
                    '<div class="col-span-full text-center text-gray-500 py-8">No projects found</div>';
                return;
            }

            container.innerHTML = projects.map(project => createCardHTML(project)).join('');

            setTimeout(() => {
                initializeProjectDropdowns();
                initializeProjectAccordions();
            }, 10);
        }

        function createCardHTML(project) {
            const dropdownId = 'dropdown-' + project.id;
            const accordionId = 'accordion-' + project.id;

            const directorateColor = CONFIG.arrayColumnColor.directorate && CONFIG.arrayColumnColor.directorate[
                project.directorate && project.directorate.id] || 'gray';
            const budgetHeadingColor = project.budget_heading_color || '#6B7280';

            const specialRoles = [1, 2, 3, 4];
            const isSpecialUser = CONFIG.userRoles.some(role => specialRoles.includes(role));

            return '<div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md p-6 border border-gray-300 dark:border-gray-600">' +
                '<div class="flex justify-between items-start">' +
                '<div class="flex-1 min-w-0">' +
                '<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 truncate">' + escapeHtml(project
                    .title) + '</h3>' +
                '<p class="text-gray-600 dark:text-gray-400 mt-1 text-sm truncate">' + escapeHtml(project
                    .description) + '</p>' +
                '</div>' +
                '<div class="relative ml-4">' +
                '<button type="button" class="project-dropdown-toggle text-gray-600 dark:text-gray-400 hover:text-gray-800" data-dropdown="' +
                dropdownId + '">' +
                '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v.01M12 12v.01M12 18v.01"></path></svg>' +
                '</button>' +
                '<div id="' + dropdownId +
                '" class="project-dropdown hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border z-20">' +
                '<a href="/admin/project/' + project.id +
                '" class="block px-4 py-2 text-sm hover:bg-gray-100">View</a>' +
                '<a href="/admin/project/' + project.id +
                '/edit" class="block px-4 py-2 text-sm hover:bg-gray-100">Edit</a>' +
                '<button type="button" onclick="deleteProject(' + project.id +
                ')" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Delete</button>' +
                '<a href="/admin/budget/create?project_id=' + project.id +
                '" class="block px-4 py-2 text-sm hover:bg-gray-100">Add Budget</a>' +
                '</div>' +
                '</div>' +
                '</div>' +
                (project.directorate ?
                    '<div class="mt-4"><span class="text-sm font-medium">Directorate:</span> <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' +
                    directorateColor + '-100 text-' + directorateColor + '-800">' + escapeHtml(project.directorate
                        .title) + '</span></div>' : '') +
                '<div class="mt-6">' +
                '<div class="flex justify-end items-center gap-2 flex-wrap">' +

                // View Details Button
                '<button type="button" class="project-accordion-toggle border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white transition-colors duration-200" data-accordion="' +
                accordionId + '">View Details</button>' +

                // Add Task Button
                '<a href="/admin/task/create?project_id=' + project.id +
                '" class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white transition-colors duration-200">Add Task</a>' +

                // --- CONTRACTS SECTION (CLICKABLE) ---
                (isSpecialUser ? // Wrapped in <a> tag to make it clickable
                    '<a href="/admin/contract?project_id=' + project.id +
                    '" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>' +
                    (project.contracts_count || 0) + ' Contracts' +
                    '</a>' :
                    '<a href="/admin/contract/create?project_id=' + project.id +
                    '" class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white transition-colors duration-200">Add Contract</a>'
                ) +
                // -------------------------------------

                '</div>' +
                '<div id="' + accordionId + '" class="project-accordion hidden mt-4 grid grid-cols-1 gap-2">' +
                (project.fields || []).map(field => '<div><span class="text-sm font-medium">' + field.label +
                    ':</span> <span class="ml-2">' + escapeHtml(field.value) + '</span></div>').join('') +
                '</div>' +
                '</div>' +
                '</div>';
        }

        function renderTable(projects) {
            const tbody = document.getElementById('tableBody');

            if (!projects || projects.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="100" class="text-center text-gray-500 py-8">No projects found</td></tr>';
                return;
            }

            tbody.innerHTML = projects.map(project => {
                const directorateColor = project.directorate && project.directorate[0] && project
                    .directorate[0].color || 'gray';
                return '<tr class="border-b hover:bg-gray-100">' +
                    '<td class="py-3 px-6">' + project.id + '</td>' +
                    '<td class="py-3 px-6">' + escapeHtml(project.title) + '</td>' +
                    '<td class="py-3 px-6">' + (project.directorate && project.directorate[0] ?
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs bg-' +
                        directorateColor + '-100 text-' + directorateColor + '-800">' + escapeHtml(project
                            .directorate[0].title) + '</span>' : 'N/A') + '</td>' +
                    '<td class="py-3 px-6">' + (project.fields || []).map(f => '<div class="text-xs">' + f
                        .title + '</div>').join('') + '</td>' +
                    '<td class="py-3 px-6"><div class="flex space-x-2">' +
                    '<a href="/admin/project/' + project.id +
                    '" class="px-3 py-1 bg-blue-500 text-white rounded text-sm">View</a>' +
                    '<a href="/admin/project/' + project.id +
                    '/edit" class="px-3 py-1 bg-green-500 text-white rounded text-sm">Edit</a>' +
                    '</div></td>' +
                    '</tr>';
            }).join('');
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
            loadProjects();
        }

        function initializeProjectDropdowns() {
            const container = document.getElementById('cardContainer');

            if (container._dropdownListener) {
                container.removeEventListener('click', container._dropdownListener);
            }

            const newListener = function(e) {
                const toggle = e.target.closest('.project-dropdown-toggle');

                if (toggle) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownId = toggle.dataset.dropdown;
                    const dropdown = document.getElementById(dropdownId);

                    document.querySelectorAll('.project-dropdown').forEach(d => {
                        if (d.id !== dropdownId) d.classList.add('hidden');
                    });

                    if (dropdown) dropdown.classList.toggle('hidden');
                }
            };

            container._dropdownListener = newListener;
            container.addEventListener('click', newListener);
        }

        function initializeProjectAccordions() {
            const container = document.getElementById('cardContainer');

            if (container._accordionListener) {
                container.removeEventListener('click', container._accordionListener);
            }

            const newListener = function(e) {
                const toggle = e.target.closest('.project-accordion-toggle');

                if (toggle) {
                    e.preventDefault();
                    e.stopPropagation();

                    const accordionId = toggle.dataset.accordion;
                    const accordion = document.getElementById(accordionId);

                    if (accordion) {
                        const isHidden = accordion.classList.contains('hidden');
                        accordion.classList.toggle('hidden');
                        toggle.textContent = isHidden ? 'Hide Details' : 'View Details';
                    }
                }
            };

            container._accordionListener = newListener;
            container.addEventListener('click', newListener);
        }

        // Event listeners
        document.getElementById('prevPage').addEventListener('click', function() {
            if (state.currentPage > 1) goToPage(state.currentPage - 1);
        });

        document.getElementById('nextPage').addEventListener('click', function() {
            if (state.currentPage < state.totalPages) goToPage(state.currentPage + 1);
        });

        document.querySelectorAll('.view-switch').forEach(function(button) {
            button.addEventListener('click', function() {
                document.querySelectorAll('.view-switch').forEach(function(b) {
                    b.classList.remove('bg-blue-500', 'text-white');
                    b.classList.add('bg-white');
                });
                button.classList.remove('bg-white');
                button.classList.add('bg-blue-500', 'text-white');

                state.currentView = button.dataset.view;
                state.currentPage = 1;

                if (state.currentView === 'card') {
                    document.getElementById('card-view').classList.remove('hidden');
                    document.getElementById('list-view').classList.add('hidden');
                } else {
                    document.getElementById('card-view').classList.add('hidden');
                    document.getElementById('list-view').classList.remove('hidden');
                }

                loadProjects();
            });
        });

        // Directorate Filter Listener (Only exists if user is Admin/SuperAdmin)
        const directorateFilterEl = document.getElementById('directorateFilter');
        if (directorateFilterEl) {
            directorateFilterEl.addEventListener('change', function(e) {
                state.directorateFilter = e.target.value;
                state.currentPage = 1;

                populateProjectDropdown(e.target.value);

                const projectFilter = document.getElementById('projectFilter');
                const projectExists = Array.from(projectFilter.options).some(function(opt) {
                    return opt.value === state.projectFilter;
                });
                if (!projectExists) {
                    state.projectFilter = '';
                    projectFilter.value = '';
                }

                loadProjects();
            });
        }

        document.getElementById('projectFilter').addEventListener('change', function(e) {
            state.projectFilter = e.target.value;
            state.currentPage = 1;
            loadProjects();
        });

        document.getElementById('statusFilter').addEventListener('change', function(e) {
            state.statusFilter = e.target.value;
            state.currentPage = 1;
            loadProjects();
        });

        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(function() {
                state.searchQuery = e.target.value;
                state.currentPage = 1;
                loadProjects();
            }, 300);
        });

        document.getElementById('clearFilters').addEventListener('click', function() {
            state.directorateFilter = '';
            state.projectFilter = '';
            state.statusFilter = '';
            state.searchQuery = '';
            state.currentPage = 1;

            // Reset values safely
            if (directorateFilterEl) directorateFilterEl.value = '';

            const projectFilter = document.getElementById('projectFilter');
            if (projectFilter) projectFilter.value = '';

            document.getElementById('statusFilter').value = '';
            document.getElementById('searchInput').value = '';

            populateProjectDropdown(directorateFilterEl ? directorateFilterEl.value : '');
            loadProjects();
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.project-dropdown-toggle') && !e.target.closest('.project-dropdown')) {
                document.querySelectorAll('.project-dropdown').forEach(function(d) {
                    d.classList.add('hidden');
                });
            }
        });

        console.log('Starting initial load...');
        loadProjectsForDropdown();
        loadProjects();
    })();
</script>
