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
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                      dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
    </div>

    <!-- Filter Section -->
    <div id="card-search" class="mb-4">
        <div class="flex flex-col md:flex-row gap-4">
            <select id="directorateFilter"
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <option value="">{{ trans('global.allDirectorate') }}</option>
                @foreach ($directorates as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                @endforeach
            </select>
            <input type="text" id="searchInput" placeholder="{{ trans('global.search') }}"
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
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
        <div id="cardContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Cards will be loaded here via AJAX -->
        </div>
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
                <tbody id="tableBody" class="text-gray-600 dark:text-gray-300 text-sm font-light">
                    <!-- Table rows will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="mt-4 flex justify-between items-center">
        <div id="paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
        <div class="flex space-x-2" id="paginationControls">
            <button id="prevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <div id="pageNumbers" class="flex space-x-2"></div>
            <button id="nextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>

    <script>
        const state = {
            currentPage: 1,
            perPage: 12,
            currentView: 'card',
            directorateFilter: '',
            searchQuery: '',
            totalPages: 1,
            totalRecords: 0,
            debounceTimer: null
        };

        const arrayColumnColor = @json($arrayColumnColor);
        const routePrefix = '{{ $routePrefix }}';
        const userRoles = @json(Auth::user()->roles->pluck('id')->toArray());

        // Helper functions - DEFINE FIRST
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Delete project function
        function deleteProject(projectId) {
            if (confirm('Are you sure you want to delete this project?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/project/${projectId}`;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

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

        // Use event delegation for dropdowns - works with dynamically added elements
        function initializeProjectDropdowns() {
            const container = document.getElementById('cardContainer');

            // Remove old listener if exists
            if (container._dropdownListener) {
                container.removeEventListener('click', container._dropdownListener);
            }

            // Create new listener
            const newListener = function(e) {
                const toggle = e.target.closest('.project-dropdown-toggle');

                if (toggle) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownId = toggle.dataset.dropdown;
                    const dropdown = document.getElementById(dropdownId);

                    // Close all project dropdowns
                    document.querySelectorAll('.project-dropdown').forEach(d => {
                        if (d.id !== dropdownId) {
                            d.classList.add('hidden');
                        }
                    });

                    // Toggle current
                    if (dropdown) {
                        dropdown.classList.toggle('hidden');
                    }
                }
            };

            // Store reference and add listener
            container._dropdownListener = newListener;
            container.addEventListener('click', newListener);
        }

        // Use event delegation for accordions
        function initializeProjectAccordions() {
            const container = document.getElementById('cardContainer');

            // Remove old listener if exists
            if (container._accordionListener) {
                container.removeEventListener('click', container._accordionListener);
            }

            // Create new listener
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

            // Store reference and add listener
            container._accordionListener = newListener;
            container.addEventListener('click', newListener);
        }

        // Load projects with AJAX
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
                    directorate_id: state.directorateFilter,
                    search: state.searchQuery
                });

                const response = await fetch(`{{ route('admin.project.index') }}?${params}`, {
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
                        '<div class="col-span-full text-center text-red-500 py-8">Error loading projects. Please refresh the page.</div>';
                } else {
                    tableBody.innerHTML =
                        '<tr><td colspan="100" class="text-center text-red-500 py-4">Error loading projects. Please refresh the page.</td></tr>';
                }
            } finally {
                loadingIndicator.classList.add('hidden');
            }
        }

        // Render cards
        function renderCards(projects) {
            const container = document.getElementById('cardContainer');

            if (!projects || projects.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">No projects found</div>';
                return;
            }

            container.innerHTML = projects.map(project => createCardHTML(project)).join('');

            // Use setTimeout to ensure DOM is ready
            setTimeout(() => {
                initializeProjectDropdowns();
                initializeProjectAccordions();
            }, 10);
        }

        // Create card HTML
        function createCardHTML(project) {
            const dropdownId = `dropdown-${project.id}`;
            const accordionId = `accordion-${project.id}`;

            // Get directorate color
            const directorateColor = arrayColumnColor.directorate?.[project.directorate?.id] || 'gray';
            const budgetHeadingColor = project.budget_heading_color || '#6B7280';

            // Check if user has special roles for contract button
            const specialRoles = [1, 2, 3, 4]; // SUPERADMIN, ADMIN, DIRECTORATE_USER, DEPARTMENT_USER
            const isSpecialUser = userRoles.some(role => specialRoles.includes(role));

            return `
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md p-6 border border-gray-300 dark:border-gray-600">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 truncate">
                                ${escapeHtml(project.title)}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm truncate" title="${escapeHtml(project.description)}">
                                ${escapeHtml(project.description)}
                            </p>
                        </div>
                        <div class="relative ml-4">
                            <button type="button"
                                class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 focus:outline-none project-dropdown-toggle"
                                data-dropdown="${dropdownId}"
                                aria-label="Open actions menu">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v.01M12 12v.01M12 18v.01"></path>
                                </svg>
                            </button>
                            <div id="${dropdownId}" class="project-dropdown hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-300 dark:border-gray-600 z-20">
                                <a href="/admin/project/${project.id}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    View
                                </a>
                                <a href="/admin/project/${project.id}/edit" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Edit
                                </a>
                                <button type="button" onclick="deleteProject(${project.id})" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Delete
                                </button>
                                <a href="/admin/budget/create?project_id=${project.id}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Add Budget
                                </a>
                            </div>
                        </div>
                    </div>

                    ${project.directorate ? `
                        <div class="mt-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans('global.project.fields.directorate_id') }}:</span>
                            <span class="ml-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${directorateColor}-100 text-${directorateColor}-800 dark:bg-${directorateColor}-900 dark:text-${directorateColor}-200">
                                    ${escapeHtml(project.directorate.title)}
                                </span>
                            </span>
                        </div>
                        ` : ''}

                    ${project.budget_heading ? `
                        <div class="mt-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Budget Heading:</span>
                            <span class="ml-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ${budgetHeadingColor}20; color: ${budgetHeadingColor};">
                                    ${escapeHtml(project.budget_heading.title)}
                                </span>
                            </span>
                        </div>
                        ` : ''}

                    <div class="mt-6">
                        <div class="flex justify-end items-center gap-2 flex-wrap">
                            <button type="button"
                                class="project-accordion-toggle border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white"
                                data-accordion="${accordionId}">
                                View Details
                            </button>

                            <a href="/admin/task/create?project_id=${project.id}" class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white">
                                Add Task
                            </a>

                            ${isSpecialUser
                                ? `<a href="/admin/contract?project_id=${project.id}" class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white">Show Contracts</a>`
                                : `<a href="/admin/contract/create?project_id=${project.id}" class="border border-blue-500 text-blue-500 px-2 py-1 rounded text-xs hover:bg-blue-500 hover:text-white">Add Contract</a>`
                            }

                            <a href="/admin/project/${project.id}" class="relative text-blue-500 hover:text-blue-700 dark:hover:text-blue-300" title="Messages">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <span class="absolute -top-1 -right-1 ${project.comment_count == 0 ? 'bg-gray-400' : 'bg-red-500'} text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">
                                    ${project.comment_count}
                                </span>
                            </a>
                        </div>

                        <div id="${accordionId}" class="project-accordion hidden mt-4 grid grid-cols-1 gap-2">
                            ${project.fields.map(field => `
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${field.label}:</span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2">${escapeHtml(field.value)}</span>
                                    </div>
                                `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        // Render table
        function renderTable(projects) {
            const tbody = document.getElementById('tableBody');

            if (!projects || projects.length === 0) {
                tbody.innerHTML =
                '<tr><td colspan="100" class="text-center text-gray-500 py-8">No projects found</td></tr>';
                return;
            }

            tbody.innerHTML = projects.map(project => {
                const directorateColor = project.directorate?.[0]?.color || 'gray';
                return `
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <td class="py-3 px-6">${project.id}</td>
                    <td class="py-3 px-6">${escapeHtml(project.title)}</td>
                    <td class="py-3 px-6">
                        ${project.directorate?.[0] ? `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${directorateColor}-100 text-${directorateColor}-800 dark:bg-${directorateColor}-900 dark:text-${directorateColor}-200">
                                ${escapeHtml(project.directorate[0].title)}
                            </span>
                            ` : 'N/A'}
                    </td>
                    <td class="py-3 px-6">
                        ${project.fields?.map(f => `<div class="text-xs text-gray-600 dark:text-gray-400">${f.title}</div>`).join('') || ''}
                    </td>
                    <td class="py-3 px-6">
                        <div class="flex space-x-2">
                            <a href="/admin/project/${project.id}" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">View</a>
                            <a href="/admin/project/${project.id}/edit" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm">Edit</a>
                        </div>
                    </td>
                </tr>
            `
            }).join('');
        }

        // Update pagination
        function updatePagination() {
            document.getElementById('paginationInfo').textContent =
                `Page ${state.currentPage} of ${state.totalPages} (Total: ${state.totalRecords} records)`;

            document.getElementById('prevPage').disabled = state.currentPage <= 1;
            document.getElementById('nextPage').disabled = state.currentPage >= state.totalPages;

            renderPageNumbers();
        }

        // Render page numbers
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
                button.className =
                    `px-3 py-1 rounded ${i === state.currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-800 dark:text-gray-200'}`;
                button.onclick = () => goToPage(i);
                container.appendChild(button);
            }
        }

        // Navigation functions
        function goToPage(page) {
            state.currentPage = page;
            loadProjects();
        }

        document.getElementById('prevPage').addEventListener('click', () => {
            if (state.currentPage > 1) goToPage(state.currentPage - 1);
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (state.currentPage < state.totalPages) goToPage(state.currentPage + 1);
        });

        // View switching
        document.querySelectorAll('.view-switch').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.view-switch').forEach(b => {
                    b.classList.remove('bg-blue-500', 'text-white');
                    b.classList.add('bg-white', 'dark:bg-gray-800', 'dark:text-white');
                });
                button.classList.remove('bg-white', 'dark:bg-gray-800', 'dark:text-white');
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

        // Filters
        document.getElementById('directorateFilter').addEventListener('change', (e) => {
            state.directorateFilter = e.target.value;
            state.currentPage = 1;
            loadProjects();
        });

        document.getElementById('searchInput').addEventListener('input', (e) => {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => {
                state.searchQuery = e.target.value;
                state.currentPage = 1;
                loadProjects();
            }, 300);
        });

        // Helper functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function initializeDropdowns() {
            // Remove any existing event listeners by cloning
            document.querySelectorAll('.dropdown-toggle').forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);

                newButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownId = newButton.dataset.dropdown;
                    const dropdown = document.getElementById(dropdownId);

                    // Close all other dropdowns first
                    document.querySelectorAll('[id^="dropdown-"]').forEach(d => {
                        if (d.id !== dropdownId) {
                            d.classList.add('hidden');
                        }
                    });

                    // Toggle current dropdown
                    if (dropdown) {
                        dropdown.classList.toggle('hidden');
                    }
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.dropdown-toggle') && !e.target.closest('[id^="dropdown-"]')) {
                    document.querySelectorAll('[id^="dropdown-"]').forEach(d => {
                        d.classList.add('hidden');
                    });
                }
            });
        }

        function initializeAccordions() {
            document.querySelectorAll('.accordion-toggle').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const accordionId = button.dataset.accordion;
                    const accordion = document.getElementById(accordionId);

                    if (accordion) {
                        const isHidden = accordion.classList.contains('hidden');
                        accordion.classList.toggle('hidden');
                        button.textContent = isHidden ? 'Hide Details' : 'View Details';
                    }
                });
            });
        }

        // Delete project function
        function deleteProject(projectId) {
            if (confirm('Are you sure you want to delete this project?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/project/${projectId}`;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

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

        // Initial load
        loadProjects();
    </script>
</x-layouts.app>
