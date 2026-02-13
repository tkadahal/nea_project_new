<x-layouts.app>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            📊 Task Analytics - Management Dashboard
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Strategic overview of task performance across all directorates and projects
        </p>
    </div>

    {{-- SECTION 1: Executive Summary --}}
    <div class="mb-6">
        <div
            class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6 border border-blue-200 dark:border-gray-600 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Overall Health Score
                </h2>
                <div class="text-3xl font-bold" id="health-score">
                    {{ $executiveSummary['health_score'] }}%
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                <div class="health-progress-bar h-3 rounded-full transition-all duration-500"
                    style="width: {{ $executiveSummary['health_score'] }}%; background: linear-gradient(90deg, #10B981 0%, #3B82F6 100%);">
                </div>
            </div>
        </div>
    </div>

    {{-- Executive Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- On Track --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">On Track</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="on-track-count">
                        {{ $executiveSummary['on_track'] }}
                    </p>
                    @if ($executiveSummary['trends']['on_track'] != 0)
                        <p
                            class="text-xs mt-2 {{ $executiveSummary['trends']['on_track'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $executiveSummary['trends']['on_track'] > 0 ? '↑' : '↓' }}
                            {{ abs($executiveSummary['trends']['on_track']) }} from last week
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- At Risk --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">At Risk</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="at-risk-count">
                        {{ $executiveSummary['at_risk'] }}
                    </p>
                    @if ($executiveSummary['trends']['at_risk'] != 0)
                        <p
                            class="text-xs mt-2 {{ $executiveSummary['trends']['at_risk'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $executiveSummary['trends']['at_risk'] > 0 ? '↑' : '↓' }}
                            {{ abs($executiveSummary['trends']['at_risk']) }} from last week
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Delayed --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Delayed</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="delayed-count">
                        {{ $executiveSummary['delayed'] }}
                    </p>
                    @if ($executiveSummary['trends']['delayed'] != 0)
                        <p
                            class="text-xs mt-2 {{ $executiveSummary['trends']['delayed'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $executiveSummary['trends']['delayed'] > 0 ? '↑' : '↓' }}
                            {{ abs($executiveSummary['trends']['delayed']) }} from last week
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Completed</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="completed-count">
                        {{ $executiveSummary['completed'] }}
                    </p>
                    @if ($executiveSummary['trends']['completed'] > 0)
                        <p class="text-xs mt-2 text-blue-600">
                            ↑ {{ $executiveSummary['trends']['completed'] }} this week
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- SECTION 2: Directorate Performance (2/3 width) --}}
        <div class="lg:col-span-2">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Directorate Performance
                    </h3>
                    <span class="text-sm text-gray-500">Ranked by Progress</span>
                </div>
                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    @forelse($directoratePerformance as $dir)
                        <div
                            class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-100">{{ $dir['title'] }}</h4>
                                    <div class="flex gap-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        <span>Active: {{ $dir['total_tasks'] - $dir['completed_count'] }}</span>
                                        <span>Completed: {{ $dir['completed_count'] }}</span>
                                        <span>Avg: {{ $dir['avg_progress'] }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold text-white"
                                        style="background-color: {{ $dir['health_color'] }}">
                                        {{ $dir['health_status'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                    style="width: {{ $dir['avg_progress'] }}%; background-color: {{ $dir['health_color'] }}">
                                </div>
                            </div>

                            {{-- Alert Message --}}
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                {{ $dir['alert_message'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">No directorate data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- SECTION 6: Alerts & Action Items (1/3 width) --}}
        <div class="lg:col-span-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Requires Attention
                    </h3>
                </div>
                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    {{-- Critical Alerts --}}
                    @if (count($alerts['critical']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-600 rounded-full"></span>
                                CRITICAL ({{ count($alerts['critical']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($alerts['critical'] as $alert)
                                    <div
                                        class="p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded text-sm">
                                        <p class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $alert['message'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $alert['directorate'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Warning Alerts --}}
                    @if (count($alerts['warning']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-yellow-600 dark:text-yellow-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-yellow-600 rounded-full"></span>
                                WARNING ({{ count($alerts['warning']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($alerts['warning'] as $alert)
                                    <div
                                        class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded text-sm">
                                        <p class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $alert['message'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $alert['directorate'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notable Items --}}
                    @if (count($alerts['notable']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                                NOTABLE ({{ count($alerts['notable']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($alerts['notable'] as $alert)
                                    <div
                                        class="p-3 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded text-sm">
                                        <p class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $alert['message'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $alert['directorate'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($alerts['critical']) == 0 && count($alerts['warning']) == 0 && count($alerts['notable']) == 0)
                        <p class="text-center text-gray-500 py-8">All tasks are on track! 🎉</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Continue to Part 2... --}}
    {{-- SECTION 3: Project Risk Matrix & SECTION 4: Trend Analysis --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Project Risk Matrix --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Project Risk Matrix
                </h3>
                <p class="text-sm text-gray-500 mt-1">Priority vs Performance</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4">
                    {{-- Critical Quadrant --}}
                    <div class="border-2 border-red-300 rounded-lg p-4 bg-red-50 dark:bg-red-900/10">
                        <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-3">
                            🔴 CRITICAL
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @forelse($projectRisk['critical'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                    <p class="font-medium">{{ Str::limit($project['title'], 30) }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $project['progress'] }}%</p>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Good Quadrant --}}
                    <div class="border-2 border-green-300 rounded-lg p-4 bg-green-50 dark:bg-green-900/10">
                        <h4 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-3">
                            🟢 GOOD
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @forelse($projectRisk['good'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                    <p class="font-medium">{{ Str::limit($project['title'], 30) }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $project['progress'] }}%</p>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Watch Quadrant --}}
                    <div class="border-2 border-yellow-300 rounded-lg p-4 bg-yellow-50 dark:bg-yellow-900/10">
                        <h4 class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-3">
                            🟡 WATCH
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @forelse($projectRisk['watch'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                    <p class="font-medium">{{ Str::limit($project['title'], 30) }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $project['progress'] }}%</p>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- OK Quadrant --}}
                    <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/10">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-400 mb-3">
                            ⚪ OK
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @forelse($projectRisk['ok'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                    <p class="font-medium">{{ Str::limit($project['title'], 30) }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $project['progress'] }}%</p>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trend Analysis --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Completion Trend
                </h3>
                <p class="text-sm text-gray-500 mt-1">Last 6 Months</p>
            </div>
            <div class="p-6">
                <canvas id="trendChart" height="250"></canvas>
            </div>
        </div>
    </div>

    {{-- SECTION 5: Distribution Data --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Priority Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    By Priority
                </h3>
            </div>
            <div class="p-6">
                <canvas id="priorityChart" height="200"></canvas>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    By Status
                </h3>
            </div>
            <div class="p-6">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- SECTION 7: Team Performance --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Team Performance
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top Performers --}}
                <div>
                    <h4 class="text-sm font-semibold text-green-600 dark:text-green-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        TOP PERFORMERS
                    </h4>
                    <div class="space-y-3">
                        @forelse($teamPerformance['top_performers'] as $member)
                            <div
                                class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $member['name'] }}
                                    </p>
                                    <div class="flex gap-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        <span>{{ $member['task_count'] }} tasks</span>
                                        <span>{{ $member['avg_progress'] }}% avg</span>
                                        <span class="text-green-600">0 overdue</span>
                                    </div>
                                </div>
                                <div class="text-2xl">⭐</div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No data available</p>
                        @endforelse
                    </div>
                </div>

                {{-- Needs Support --}}
                <div>
                    <h4
                        class="text-sm font-semibold text-yellow-600 dark:text-yellow-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        NEEDS SUPPORT
                    </h4>
                    <div class="space-y-3">
                        @forelse($teamPerformance['needs_support'] as $member)
                            <div
                                class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/10 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $member['name'] }}
                                    </p>
                                    <div class="flex gap-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        <span>{{ $member['task_count'] }} tasks</span>
                                        <span>{{ $member['avg_progress'] }}% avg</span>
                                        <span class="text-red-600">{{ $member['overdue_count'] }} overdue</span>
                                    </div>
                                </div>
                                <div class="text-xs bg-yellow-500 text-white px-2 py-1 rounded">
                                    Action Needed
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">Everyone is doing great! 🎉</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 8: Detailed Task List (Expandable) --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <button id="toggle-details" class="w-full flex items-center justify-between text-left">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Detailed Task List
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Click to expand and view all tasks</p>
                </div>
                <svg id="toggle-icon" class="w-6 h-6 text-gray-500 transform transition-transform" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <div id="details-section" class="hidden">
            {{-- Filters --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</h4>
                    <div class="filter-loading-spinner" id="filter-spinner"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @php $roleIds = auth()->user()->roles->pluck('id')->toArray(); @endphp

                    @if (in_array(\App\Models\Role::SUPERADMIN, $roleIds) || in_array(\App\Models\Role::ADMIN, $roleIds))
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Directorate</label>
                            <select id="directorate_id"
                                class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                                <option value="">All</option>
                                @foreach ($filterOptions['directorates'] as $d)
                                    <option value="{{ $d->id }}">{{ $d->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Project</label>
                        <select id="project_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($filterOptions['projects'] as $p)
                                <option value="{{ $p->id }}"
                                    data-directorate-id="{{ $p->directorate_id ?? '' }}">
                                    {{ $p->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select id="status_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($lookupData['statuses'] as $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Priority</label>
                        <select id="priority_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($lookupData['priorities'] as $pr)
                                <option value="{{ $pr->id }}">{{ $pr->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Task Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignees</th>
                        </tr>
                    </thead>
                    <tbody id="taskTableBody"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($detailedTasks['tableData'] as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm">
                                    <div class="max-w-xs truncate" title="{{ $row['title'] }}">{{ $row['title'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">{{ $row['entity'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded text-xs text-white"
                                        style="background-color: {{ $row['status']['color'] }}">
                                        {{ $row['status']['title'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded text-xs text-white"
                                        style="background-color: {{ $row['priority']['color'] }}">
                                        {{ $row['priority']['title'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full"
                                                style="width: {{ $row['progress'] }}%"></div>
                                        </div>
                                        <span class="text-xs">{{ $row['progress'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">{{ $row['due_date'] }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex gap-1">
                                        @foreach ($row['users'] as $u)
                                            <span
                                                class="w-7 h-7 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs flex items-center justify-center font-bold"
                                                title="{{ $u['name'] }}">
                                                {{ $u['initials'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No tasks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:border-gray-900"
                id="pagination-container">
                {!! $detailedTasks['tasks']->links() !!}
            </div>
        </div>
    </div>

    <style>
        .filter-select {
            transition: opacity 0.2s ease;
        }

        .filter-select.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        #taskTableBody {
            transition: opacity 0.2s ease;
        }

        #taskTableBody.updating {
            opacity: 0.6;
        }

        .filter-loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #E5E7EB;
            border-top-color: #3B82F6;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .filter-loading-spinner.active {
            display: inline-block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (() => {
                'use strict';

                // ============================================
                // STATE
                // ============================================
                const State = {
                    isLoading: false,
                    charts: {
                        trend: null,
                        priority: null,
                        status: null
                    }
                };

                // ============================================
                // INITIALIZE CHARTS
                // ============================================
                function initCharts() {
                    // Trend Chart
                    const trendCtx = document.getElementById('trendChart').getContext('2d');
                    State.charts.trend = new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: @json($trendData['labels']),
                            datasets: [{
                                    label: 'Completed',
                                    data: @json($trendData['completed']),
                                    borderColor: '#10B981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                },
                                {
                                    label: 'Created',
                                    data: @json($trendData['created']),
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });

                    // Priority Chart
                    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
                    const priorityData = @json($distributionData['priority']);
                    State.charts.priority = new Chart(priorityCtx, {
                        type: 'doughnut',
                        data: {
                            labels: priorityData.map(d => d.label),
                            datasets: [{
                                data: priorityData.map(d => d.count),
                                backgroundColor: priorityData.map(d => d.color)
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });

                    // Status Chart
                    const statusCtx = document.getElementById('statusChart').getContext('2d');
                    const statusData = @json($distributionData['status']);
                    State.charts.status = new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusData.map(d => d.label),
                            datasets: [{
                                data: statusData.map(d => d.count),
                                backgroundColor: statusData.map(d => d.color)
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }

                // ============================================
                // EXPANDABLE SECTION
                // ============================================
                function initToggleDetails() {
                    const toggleBtn = document.getElementById('toggle-details');
                    const detailsSection = document.getElementById('details-section');
                    const toggleIcon = document.getElementById('toggle-icon');

                    toggleBtn?.addEventListener('click', () => {
                        const isHidden = detailsSection.classList.contains('hidden');

                        if (isHidden) {
                            detailsSection.classList.remove('hidden');
                            toggleIcon.style.transform = 'rotate(180deg)';
                        } else {
                            detailsSection.classList.add('hidden');
                            toggleIcon.style.transform = 'rotate(0deg)';
                        }
                    });
                }

                // ============================================
                // FILTERS
                // ============================================
                function initFilters() {
                    const filterSelects = document.querySelectorAll('.filter-select');
                    const spinner = document.getElementById('filter-spinner');
                    let filterTimeout;

                    filterSelects.forEach(select => {
                        select.addEventListener('change', (e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            // Handle cascading filters
                            if (select.id === 'directorate_id') {
                                handleDirectorateChange();
                            }

                            // Debounce
                            clearTimeout(filterTimeout);
                            filterTimeout = setTimeout(() => {
                                applyFilters();
                            }, 150);
                        });
                    });

                    function handleDirectorateChange() {
                        const directorateId = document.getElementById('directorate_id')?.value;
                        const projectSelect = document.getElementById('project_id');

                        if (!projectSelect) return;

                        projectSelect.value = '';

                        Array.from(projectSelect.options).forEach(option => {
                            if (!option.value) return;

                            const projectDirectorateId = option.getAttribute('data-directorate-id');
                            if (directorateId && projectDirectorateId !== directorateId) {
                                option.style.display = 'none';
                            } else {
                                option.style.display = '';
                            }
                        });
                    }

                    async function applyFilters() {
                        if (State.isLoading) return;
                        State.isLoading = true;

                        // Show loading
                        spinner?.classList.add('active');
                        filterSelects.forEach(s => {
                            s.classList.add('loading');
                            s.disabled = true;
                        });
                        document.getElementById('taskTableBody')?.classList.add('updating');

                        try {
                            const url = buildFilterURL();
                            window.history.replaceState({}, '', url.toString());

                            const response = await fetch(url.toString(), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            if (!response.ok) throw new Error('Failed to fetch');

                            const data = await response.json();

                            // Update all sections
                            updateExecutiveSummary(data.executiveSummary);
                            updateDirectoratePerformance(data.directoratePerformance);
                            updateAlerts(data.alerts);
                            updateCharts(data);
                            updateTaskTable(data.detailedTasks.tableData);
                            updatePagination(data.detailedTasks.tasks.links_html);

                        } catch (error) {
                            console.error('Filter Error:', error);
                        } finally {
                            State.isLoading = false;
                            spinner?.classList.remove('active');
                            setTimeout(() => {
                                filterSelects.forEach(s => {
                                    s.classList.remove('loading');
                                    s.disabled = false;
                                });
                                document.getElementById('taskTableBody')?.classList.remove('updating');
                            }, 100);
                        }
                    }

                    function buildFilterURL() {
                        const url = new URL(window.location.href);
                        ['directorate_id', 'project_id', 'status_id', 'priority_id'].forEach(id => {
                            url.searchParams.delete(id);
                        });

                        ['directorate_id', 'project_id', 'status_id', 'priority_id'].forEach(id => {
                            const value = document.getElementById(id)?.value;
                            if (value) url.searchParams.set(id, value);
                        });

                        url.searchParams.delete('page');
                        return url;
                    }
                }

                // ============================================
                // UPDATE FUNCTIONS
                // ============================================
                function updateExecutiveSummary(summary) {
                    if (!summary) return;

                    document.getElementById('health-score').textContent = summary.health_score + '%';
                    document.querySelector('.health-progress-bar').style.width = summary.health_score + '%';

                    animateNumber('on-track-count', summary.on_track);
                    animateNumber('at-risk-count', summary.at_risk);
                    animateNumber('delayed-count', summary.delayed);
                    animateNumber('completed-count', summary.completed);
                }

                function animateNumber(elementId, newValue) {
                    const element = document.getElementById(elementId);
                    if (!element) return;

                    const current = parseInt(element.textContent) || 0;
                    if (current === newValue) return;

                    const duration = 500;
                    const steps = 20;
                    const increment = (newValue - current) / steps;
                    let step = 0;

                    const timer = setInterval(() => {
                        step++;
                        const value = Math.round(current + (increment * step));
                        element.textContent = value;

                        if (step >= steps) {
                            element.textContent = newValue;
                            clearInterval(timer);
                        }
                    }, duration / steps);
                }

                function updateDirectoratePerformance(directorates) {
                    // Would need to rebuild the HTML - simplified for now
                    console.log('Directorate performance updated', directorates);
                }

                function updateAlerts(alerts) {
                    // Would need to rebuild the HTML - simplified for now
                    console.log('Alerts updated', alerts);
                }

                function updateCharts(data) {
                    if (data.distributionData) {
                        // Update Priority Chart
                        const priorityData = data.distributionData.priority;
                        if (State.charts.priority) {
                            State.charts.priority.data.labels = priorityData.map(d => d.label);
                            State.charts.priority.data.datasets[0].data = priorityData.map(d => d.count);
                            State.charts.priority.update('none');
                        }

                        // Update Status Chart
                        const statusData = data.distributionData.status;
                        if (State.charts.status) {
                            State.charts.status.data.labels = statusData.map(d => d.label);
                            State.charts.status.data.datasets[0].data = statusData.map(d => d.count);
                            State.charts.status.update('none');
                        }
                    }
                }

                function updateTaskTable(rows) {
                    const tbody = document.getElementById('taskTableBody');
                    if (!tbody || !rows) return;

                    if (rows.length === 0) {
                        tbody.innerHTML =
                            '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No tasks found</td></tr>';
                        return;
                    }

                    tbody.innerHTML = rows.map(row => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm">
                                <div class="max-w-xs truncate" title="${row.title}">${row.title}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">${row.entity}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded text-xs text-white" style="background-color: ${row.status.color}">
                                    ${row.status.title}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded text-xs text-white" style="background-color: ${row.priority.color}">
                                    ${row.priority.title}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: ${row.progress}%"></div>
                                    </div>
                                    <span class="text-xs">${row.progress}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">${row.due_date}</td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex gap-1">
                                    ${row.users.map(u => `
                                                <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs flex items-center justify-center font-bold" title="${u.name}">
                                                    ${u.initials}
                                                </span>
                                            `).join('')}
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }

                function updatePagination(html) {
                    const container = document.getElementById('pagination-container');
                    if (container && html) {
                        container.innerHTML = html;
                    }
                }

                // ============================================
                // INITIALIZATION
                // ============================================
                document.addEventListener('DOMContentLoaded', () => {
                    initCharts();
                    initToggleDetails();
                    initFilters();
                    console.log('✅ Management Analytics Dashboard initialized');
                });
            })();
        </script>
    @endpush
</x-layouts.app>
