@php
    $csrfToken = csrf_token();
    $indexRoute = route('admin.preBudget.index');
    $deleteRoute = '/admin/preBudget/';
    $viewRoute = '/admin/preBudget/';
    $editRoute = '/admin/preBudget/';

    $directorateColors = config('colors.directorate');
@endphp

<x-layouts.app>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Pre Budget List
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Manage yearly pre-budget allocations.
            </p>
        </div>

        @can('preBudget_create')
            <a href="{{ route('admin.preBudget.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Create Pre Budget
            </a>
        @endcan
    </div>

    {{-- FILTER SECTION (Grid Layout) --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            @if (!$isProjectUser)
                <div>
                    <label class="block text-sm mb-1 font-medium text-gray-700 dark:text-gray-300">Directorate</label>
                    <select id="directorateFilter"
                        class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md
                        bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        @foreach ($filters['directorates'] as $directorate)
                            <option value="{{ $directorate->id }}">
                                {{ $directorate->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700 dark:text-gray-300">Project</label>
                <select id="projectFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md
                    bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach ($filters['projects'] as $project)
                        <option value="{{ $project->id }}"
                            {{ isset($projectId) && $projectId == $project->id ? 'selected' : '' }}>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700 dark:text-gray-300">Fiscal Year</label>
                <select id="fiscalYearFilter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md
                    bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach ($filters['fiscalYears'] as $fy)
                        <option value="{{ $fy->id }}"
                            {{ isset($fiscalYearId) && $fiscalYearId == $fy->id ? 'selected' : '' }}>
                            {{ $fy->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- This search is now redundant due to the bar below, but kept if user prefers grid layout --}}
        </div>
    </div>

    {{-- TABLE CONTAINER --}}
    <div class="overflow-x-auto">

        {{-- Top Bar: Search & Per Page (Matches Target View) --}}
        <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <input type="text" id="searchInput" placeholder="Search project..."
                class="w-full md:max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">

            <div class="flex items-center">
                <label for="perPageSelect" class="mr-2 text-gray-600 dark:text-gray-300 text-sm font-medium">
                    Records Per Page
                </label>
                <select id="perPageSelect"
                    class="p-2 border border-gray-300 dark:border-gray-700 rounded-md
                    bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none
                    focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        {{-- Table Styling Matches Target View --}}
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

    {{-- Pagination Controls (Matches Target View) --}}
    <div class="mt-4 flex justify-between items-center">
        <div id="paginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base font-medium"></div>
        <div class="flex space-x-2" id="paginationControls">
            <button id="prevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 font-medium">
                Previous
            </button>
            <div id="pageNumbers" class="flex space-x-2"></div>
            <button id="nextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 font-medium">
                Next
            </button>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const CONFIG = {
                    csrfToken: '{{ $csrfToken }}',
                    indexRoute: '{{ $indexRoute }}',
                    deleteRoute: '{{ $deleteRoute }}',
                    viewRoute: '{{ $viewRoute }}',
                    editRoute: '{{ $editRoute }}',
                    directorateColors: {!! json_encode($directorateColors) !!},
                    allProjects: {!! json_encode($projectsForJs) !!},
                    permissions: {
                        canView: @json(auth()->user()->can('preBudget_show')),
                        canEdit: @json(auth()->user()->can('preBudget_edit')),
                        canDelete: @json(auth()->user()->can('preBudget_delete')),
                    },
                    initialState: {
                        project: '{{ $projectId ?? '' }}',
                        fiscalYear: '{{ $fiscalYearId ?? '' }}'
                    }
                };

                let state = {
                    page: 1,
                    perPage: 20,
                    directorate: '',
                    project: CONFIG.initialState.project,
                    fiscalYear: CONFIG.initialState.fiscalYear,
                    search: '',
                    totalPages: 1
                };

                const tableBody = document.getElementById('tableBody');

                // ====================== FORMAT CURRENCY WITH COMMAS ======================
                function formatCurrency(amount) {
                    if (amount == null || isNaN(amount)) return '0.00';
                    return parseFloat(amount).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                async function loadData() {
                    const params = new URLSearchParams({
                        page: state.page,
                        per_page: state.perPage,
                        directorate_filter: state.directorate,
                        project_filter: state.project,
                        fiscal_year_filter: state.fiscalYear,
                        search: state.search
                    });

                    const response = await fetch(CONFIG.indexRoute + '?' + params, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    renderTable(data.data);
                    state.totalPages = data.last_page;
                    updatePagination(data.total);
                }

                function updateProjectOptions(directorateId) {
                    const currentVal = document.getElementById('projectFilter').value;
                    const filtered = CONFIG.allProjects.filter(p => !directorateId || p.directorate_id == directorateId);

                    const projectSelect = document.getElementById('projectFilter');
                    projectSelect.innerHTML = '<option value="">All</option>';

                    filtered.forEach(p => {
                        const option = new Option(p.title, p.id);
                        if (p.id == currentVal) option.selected = true;
                        projectSelect.appendChild(option);
                    });
                }

                function renderTable(rows) {
                    if (!rows || rows.length === 0) {
                        tableBody.innerHTML =
                            `<tr><td colspan="10" class="text-center py-8 text-gray-500">No records found</td></tr>`;
                        return;
                    }

                    const grouped = rows.reduce((acc, row) => {
                        const dir = row.directorate || 'Unknown';
                        acc[dir] = acc[dir] || [];
                        acc[dir].push(row);
                        return acc;
                    }, {});

                    let html = '';

                    for (const [dir, items] of Object.entries(grouped)) {
                        const color = CONFIG.directorateColors[items[0].directorate_id] || 'gray';

                        const groupTotal = items.reduce((sum, item) => sum + parseFloat(item.total_budget || 0), 0);

                        // Group Header
                        html += `
                <tr class="bg-${color}-100 dark:bg-${color}-900 border-b border-gray-200 dark:border-gray-700">
                    <td colspan="10" class="py-3 px-6 font-bold uppercase text-gray-800 dark:text-gray-100">
                        <div class="flex justify-between items-center">
                            <span>${dir}</span>
                            <span class="text-sm font-normal">Total: ${formatCurrency(groupTotal)}</span>
                        </div>
                    </td>
                </tr>`;

                        // Data Rows
                        items.forEach(row => {
                            html += `
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="py-3 px-6 font-medium">${row.project}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.internal_budget)}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.government_share)}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.government_loan)}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.foreign_loan)}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.foreign_subsidy)}</td>
                        <td class="py-3 px-6 text-right">${formatCurrency(row.company_budget)}</td>
                        <td class="py-3 px-6 text-right font-bold text-gray-800 dark:text-gray-100">
                            ${formatCurrency(row.total_budget)}
                        </td>
                        <td class="py-3 px-6">
                            <div class="flex flex-wrap gap-2">
                                ${CONFIG.permissions.canView ? `
                                            <a href="${CONFIG.viewRoute}${row.id}" 
                                               class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">
                                                View
                                            </a>` : ''}
                                ${CONFIG.permissions.canEdit ? `
                                            <a href="${CONFIG.editRoute}${row.id}/edit" 
                                               class="px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded hover:bg-emerald-700">
                                                Edit
                                            </a>` : ''}
                                ${CONFIG.permissions.canDelete ? `
                                            <form action="${CONFIG.deleteRoute}${row.id}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this?')">
                                                <input type="hidden" name="_token" value="${CONFIG.csrfToken}">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit"
                                                    class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700">
                                                    Delete
                                                </button>
                                            </form>` : ''}
                            </div>
                        </td>
                    </tr>`;
                        });
                    }

                    tableBody.innerHTML = html;
                }

                function updatePagination(total) {
                    document.getElementById('paginationInfo').textContent =
                        `Page ${state.page} of ${state.totalPages} (Total: ${total} records)`;

                    const prevBtn = document.getElementById('prevPage');
                    const nextBtn = document.getElementById('nextPage');

                    prevBtn.disabled = state.page <= 1;
                    nextBtn.disabled = state.page >= state.totalPages;

                    // Page numbers logic (simple version)
                    const container = document.getElementById('pageNumbers');
                    container.innerHTML = '';

                    const maxButtons = 5;
                    let start = Math.max(1, state.page - 2);
                    let end = Math.min(state.totalPages, start + maxButtons - 1);

                    if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);

                    for (let i = start; i <= end; i++) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        btn.className = `px-3 py-1 rounded font-medium ${i === state.page 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600'}`;
                        btn.onclick = () => {
                            state.page = i;
                            loadData();
                        };
                        container.appendChild(btn);
                    }
                }

                // Event Listeners
                document.getElementById('directorateFilter')?.addEventListener('change', e => {
                    state.directorate = e.target.value;
                    state.page = 1;
                    updateProjectOptions(state.directorate);
                    loadData();
                });

                document.getElementById('projectFilter').addEventListener('change', e => {
                    state.project = e.target.value;
                    state.page = 1;
                    loadData();
                });

                document.getElementById('fiscalYearFilter').addEventListener('change', e => {
                    state.fiscalYear = e.target.value;
                    state.page = 1;
                    loadData();
                });

                document.getElementById('searchInput').addEventListener('input', e => {
                    state.search = e.target.value;
                    state.page = 1;
                    loadData();
                });

                document.getElementById('perPageSelect').addEventListener('change', e => {
                    state.perPage = parseInt(e.target.value);
                    state.page = 1;
                    loadData();
                });

                document.getElementById('prevPage').addEventListener('click', () => {
                    if (state.page > 1) {
                        state.page--;
                        loadData();
                    }
                });

                document.getElementById('nextPage').addEventListener('click', () => {
                    if (state.page < state.totalPages) {
                        state.page++;
                        loadData();
                    }
                });

                // Initial load
                loadData();

            })();
        </script>
    @endpush

</x-layouts.app>
