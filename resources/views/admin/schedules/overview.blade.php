<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Schedule Overview
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Overview of all accessible project schedules
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.schedules.analytics') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        View Analytics
                    </a>

                    <a href="{{ route('admin.schedules.analytics-charts') }}"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                        </svg>
                        Charts
                    </a>
                    <a href="{{ route('admin.schedules.all-files') }}"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        All Files
                    </a>
                </div>
            </div>
        </div>

        @if ($allProjects->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Projects Available</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">You don't have access to any projects yet.</p>
            </div>
        @else
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                <!-- Total Projects -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total
                                        Projects</dt>
                                    <dd class="text-3xl font-semibold text-gray-900 dark:text-white">
                                        {{ $statistics['total_projects'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Schedules -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total
                                        Schedules</dt>
                                    <dd class="text-3xl font-semibold text-gray-900 dark:text-white">
                                        {{ $statistics['total_schedules'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Progress -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Average
                                        Progress</dt>
                                    <dd class="text-3xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($statistics['average_progress'], 1) }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div
                    class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Filter Projects</h3>
                    @if (request()->hasAny(['directorate_id', 'project_id', 'status']))
                        <a href="{{ route('admin.schedules.overview') }}"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear Filters
                        </a>
                    @endif
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.schedules.overview') }}" id="filterForm"
                        class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if ($viewLevel === 'admin' || $viewLevel === 'directorate')
                            <!-- Directorate Filter -->
                            <div>
                                <label for="directorate_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Directorate
                                </label>
                                <select name="directorate_id" id="directorate_id"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border">
                                    <option value="">All Directorates ({{ $directorates->count() }})</option>
                                    @foreach ($directorates as $directorate)
                                        <option value="{{ $directorate->id }}"
                                            {{ request('directorate_id') == $directorate->id ? 'selected' : '' }}>
                                            {{ $directorate->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Project Filter -->
                        <div>
                            <label for="project_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Project
                            </label>
                            <select name="project_id" id="project_id"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border">
                                <option value="">All Projects ({{ $allProjects->count() }})</option>
                                @foreach ($allProjects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->title }}
                                        @if ($project->directorate && $viewLevel === 'admin')
                                            ({{ $project->directorate->title }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Progress Status
                            </label>
                            <select name="status" id="status"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border">
                                <option value="">All Statuses</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                    Completed (100%)</option>
                                <option value="in_progress"
                                    {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress (1-99%)
                                </option>
                                <option value="not_started"
                                    {{ request('status') == 'not_started' ? 'selected' : '' }}>Not Started (0%)
                                </option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Count & Quick Actions -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-end gap-4">
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Showing {{ $projects->firstItem() ?? 0 }} to {{ $projects->lastItem() ?? 0 }} of
                        {{ $projects->total() }} projects</span>
                </div>
            </div>

            @if ($projects->isEmpty())
                <!-- Empty State (when filters return no results) -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Projects Found</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Try adjusting your filters to see more results.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('admin.schedules.overview') }}"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Clear All Filters
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    @foreach ($projects as $project)
                        @php
                            $progress = $project->cached_progress ?? 0;
                            $totalSchedules = $project->cached_total_schedules ?? 0;
                            $completed = $project->cached_leaf_completed ?? 0;
                            $total = $project->cached_leaf_total ?? 0;
                        @endphp
                        <div
                            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="px-6 py-5">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $project->title }}
                                        </h3>
                                        @if ($project->directorate)
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $project->directorate->title }}
                                            </p>
                                        @endif
                                    </div>
                                    @php
                                        $status = $project->status;

                                        $bg = match (true) {
                                            $status?->isCompleted() => 'bg-green-100 dark:bg-green-900/30',
                                            $status?->isInProgress() => 'bg-blue-100  dark:bg-blue-900/30',
                                            default => 'bg-gray-100  dark:bg-gray-700',
                                        };

                                        $text = match (true) {
                                            $status?->isCompleted() => 'text-green-800 dark:text-green-300',
                                            $status?->isInProgress() => 'text-blue-800  dark:text-blue-300',
                                            default => 'text-gray-800  dark:text-gray-300',
                                        };
                                    @endphp

                                    <span
                                        class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bg }} {{ $text }}">
                                        {{ $status?->title ?? 'N/A' }}
                                    </span>
                                </div>

                                <!-- Progress -->
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall
                                            Progress</span>
                                        <span
                                            class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($progress, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full @if ($progress >= 100) bg-green-500 @elseif($progress >= 75) bg-blue-500 @elseif($progress >= 50) bg-yellow-500 @else bg-gray-400 @endif"
                                            style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $totalSchedules }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Schedules</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $completed }}/{{ $total }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Completed</div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2">
                                    @if ($totalSchedules === 0)
                                        <a href="{{ route('admin.projects.schedules.assign-form', $project) }}"
                                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                            Assign Schedule
                                        </a>
                                    @else
                                        <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            View Schedules
                                        </a>
                                    @endif

                                    <!-- Dashboard button remains always visible -->
                                    <a href="{{ route('admin.projects.schedules.dashboard', $project) }}"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                        title="Dashboard">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </a>

                                    <!-- NEW CHARTS BUTTON -->
                                    <a href="{{ route('admin.projects.schedules.charts', $project) }}"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                        title="Charts">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                        </svg>
                                    </a>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination - REMOVED DIV IF NO PAGES -->
                @if ($projects->hasPages())
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg px-6 py-4">
                        {{ $projects->links() }}
                    </div>
                @endif
            @endif
        @endif
    </div>

    @push('scripts')
        <script>
            // Auto-submit form on filter change
            const filterForm = document.getElementById('filterForm');
            const directorateSelect = document.getElementById('directorate_id');
            const projectSelect = document.getElementById('project_id');
            const statusSelect = document.getElementById('status');

            // Auto-submit on any filter change
            if (directorateSelect) {
                directorateSelect.addEventListener('change', function() {
                    // Clear project selection when directorate changes
                    if (projectSelect) {
                        projectSelect.value = '';
                    }
                    filterForm.submit();
                });
            }

            if (projectSelect) {
                projectSelect.addEventListener('change', function() {
                    filterForm.submit();
                });
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
        </script>
    @endpush
</x-layouts.app>
