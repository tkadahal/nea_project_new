<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                @if ($viewLevel === 'admin')
                    System-Wide Schedule Analytics
                @elseif($viewLevel === 'directorate')
                    {{ $userDirectorate?->title ?? 'Directorate' }} - Schedule Analytics
                @else
                    My Projects Schedule Analytics
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if ($viewLevel === 'admin')
                    Overview of all schedules across all directorates
                @elseif($viewLevel === 'directorate')
                    Overview of all schedules in your directorate ({{ $statistics['total_projects'] }} projects)
                @else
                    Overview of all your assigned projects ({{ $statistics['total_projects'] }} projects)
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            @php
                $stats = [
                    [
                        'label' => 'Total Projects',
                        'value' => $statistics['total_projects'],
                        'color' => 'text-blue-600',
                        'icon' =>
                            'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    ],
                    [
                        'label' => 'Total Schedules',
                        'value' => $statistics['total_schedules'],
                        'color' => 'text-purple-600',
                        'icon' =>
                            'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    ],
                    [
                        'label' => 'Average Progress',
                        'value' => number_format($statistics['average_progress'], 1) . '%',
                        'color' => 'text-green-600',
                        'icon' =>
                            'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    ],
                    [
                        'label' => 'Files Uploaded',
                        'value' => $statistics['total_files'],
                        'color' => 'text-orange-600',
                        'icon' =>
                            'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    ],
                ];
            @endphp

            @foreach ($stats as $stat)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 {{ $stat['color'] }}" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $stat['icon'] }}"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        {{ $stat['label'] }}</dt>
                                    <dd class="text-3xl font-semibold text-gray-900 dark:text-white">
                                        {{ $stat['value'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>


        @if ($viewLevel === 'admin' || $viewLevel === 'directorate')
            @if (!empty($directoratePerformance))
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            {{ $viewLevel === 'admin' ? 'Directorate Performance' : 'Your Directorate Overview' }}
                        </h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($directoratePerformance as $directorate)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 truncate">
                                        {{ $directorate['title'] }}
                                    </h4>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $directorate['total_projects'] }} projects
                                        </span>
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                            {{ number_format($directorate['average_progress'], 1) }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full"
                                            style="width: {{ $directorate['average_progress'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div id="phase-breakdown-container">
                @include('admin.schedules.partials._phase_breakdown')
            </div>
            <div id="recent-files-container">
                @include('admin.schedules.partials._recent_files')
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6 relative">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Projects Overview</h3>

                    <div class="flex flex-wrap gap-3">
                        @if ($viewLevel === 'admin')
                            <select id="filter-directorate"
                                class="rounded-md border-gray-300 py-1 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <option value="">All Directorates</option>
                                @foreach ($directorates as $dir)
                                    <option value="{{ $dir->id }}">{{ $dir->title }}</option>
                                @endforeach
                            </select>
                        @endif

                        <input type="text" id="filter-project" placeholder="Search Project Name..."
                            class="rounded-md border-gray-300 py-1 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">

                        <div id="table-loader" class="hidden self-center">
                            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div id="projects-table-container">
                @include('admin.schedules.partials._projects_table')
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const tableContainer = document.getElementById('projects-table-container');
            const phaseContainer = document.getElementById('phase-breakdown-container');
            const filesContainer = document.getElementById('recent-files-container');
            const loader = document.getElementById('table-loader');

            // Function to refresh everything based on filters
            function refreshDashboard(url = null) {
                loader.classList.remove('hidden');

                const params = new URLSearchParams({
                    directorate_id: document.getElementById('filter-directorate')?.value || '',
                    search: document.getElementById('filter-project').value || '',
                    is_full_refresh: '1' // Flag to tell controller to return all 3 partials
                });

                const fetchUrl = url ? url + '&' + params.toString() :
                    `{{ route('admin.schedules.analytics') }}?${params.toString()}`;

                fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        tableContainer.innerHTML = data.table;
                        phaseContainer.innerHTML = data.phases;
                        filesContainer.innerHTML = data.files;
                    })
                    .catch(error => console.error('Dashboard Update Error:', error))
                    .finally(() => loader.classList.add('hidden'));
            }

            // Event Listeners for Filters
            document.getElementById('filter-directorate')?.addEventListener('change', () => refreshDashboard());
            document.getElementById('filter-project').addEventListener('input', debounce(() => refreshDashboard(), 500));

            // Pagination Click Handling
            document.addEventListener('click', function(e) {
                const link = e.target.closest('#projects-table-container .pagination a');
                if (link) {
                    e.preventDefault();
                    refreshDashboard(link.href);
                }
            });

            function debounce(func, timeout = 300) {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        func.apply(this, args);
                    }, timeout);
                };
            }
        </script>
    @endpush
</x-layouts.app>
