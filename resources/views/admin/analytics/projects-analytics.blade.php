<x-layouts.app>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            📊 Project Analytics - Portfolio Dashboard
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Strategic overview of project financials, progress, and portfolio health
        </p>
    </div>

    {{-- SECTION 1: Portfolio Health (Executive Summary) --}}
    <div class="mb-6">
        <div
            class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6 border border-purple-200 dark:border-gray-600 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Overall Portfolio Health Score
                </h2>
                <div class="text-3xl font-bold" id="portfolio-health-score">
                    {{ $portfolioHealth['health_score'] }}%
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                <div class="health-progress-bar h-3 rounded-full transition-all duration-500"
                    style="width: {{ $portfolioHealth['health_score'] }}%; background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);">
                </div>
            </div>
        </div>
    </div>

    {{-- Portfolio Health Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @foreach (['total_projects' => ['label' => 'Total Projects', 'color' => 'blue'], 'on_track' => ['label' => 'On Track', 'color' => 'green'], 'at_risk' => ['label' => 'At Risk', 'color' => 'yellow'], 'delayed' => ['label' => 'Delayed', 'color' => 'red'], 'completed' => ['label' => 'Completed', 'color' => 'indigo']] as $key => $meta)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-{{ $meta['color'] }}-500">
                <div class="flex flex-col items-center justify-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $meta['label'] }}</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100" id="stat-{{ $key }}">
                        {{ $portfolioHealth[$key] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- SECTION 2: Financial Health & SECTION 3: Matrix (Split Layout) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Financial Health --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Financial Overview
                </h3>
                <p class="text-sm text-gray-500 mt-1">Budget Utilization & Allocation</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-end border-b pb-2 dark:border-gray-700">
                    <div>
                        <p class="text-xs text-gray-500">Total Budget</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            ${{ number_format($financialHealth['total_budget']) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Monthly Burn</p>
                        <p class="text-sm font-medium text-gray-700">
                            ${{ number_format($financialHealth['monthly_burn_rate'], 0) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-xs text-red-500 font-semibold">Spent</p>
                        <p class="font-bold text-gray-800">{{ $financialHealth['spent_percentage'] }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-yellow-500 font-semibold">Committed</p>
                        <p class="font-bold text-gray-800">{{ $financialHealth['committed_percentage'] }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-500 font-semibold">Available</p>
                        <p class="font-bold text-gray-800">{{ $financialHealth['available_percentage'] }}%</p>
                    </div>
                </div>

                @if ($financialHealth['critical_projects'] > 0 || $financialHealth['warning_projects'] > 0)
                    <div
                        class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-100 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-300 font-semibold">
                            ⚠️ {{ $financialHealth['critical_projects'] }} Critical /
                            {{ $financialHealth['warning_projects'] }} Warning
                        </p>
                        <p class="text-xs text-red-600 dark:text-red-400">Budget exceeded or high burn rate detected.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Budget vs Progress Matrix --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Efficiency Matrix
                </h3>
                <p class="text-sm text-gray-500 mt-1">Budget Spent vs Physical Progress</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 h-full">
                    {{-- Critical: High Spent / Low Progress --}}
                    <div class="border-2 border-red-300 rounded-lg p-4 bg-red-50 dark:bg-red-900/10 flex flex-col">
                        <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">
                            🔴 CRITICAL (Inefficient)
                        </h4>
                        <div class="space-y-2 overflow-y-auto flex-1 max-h-32">
                            @forelse($budgetProgressMatrix['critical'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded shadow-sm">
                                    <p class="font-medium truncate" title="{{ $project['title'] }}">
                                        {{ Str::limit($project['title'], 25) }}</p>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ $project['budget_spent'] }}% Spent</span>
                                        <span>{{ $project['progress'] }}% Done</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Watch: High Spent / Good Progress --}}
                    <div
                        class="border-2 border-yellow-300 rounded-lg p-4 bg-yellow-50 dark:bg-yellow-900/10 flex flex-col">
                        <h4 class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-2">
                            🟡 WATCH (Expensive)
                        </h4>
                        <div class="space-y-2 overflow-y-auto flex-1 max-h-32">
                            @forelse($budgetProgressMatrix['watch'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded shadow-sm">
                                    <p class="font-medium truncate" title="{{ $project['title'] }}">
                                        {{ Str::limit($project['title'], 25) }}</p>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ $project['budget_spent'] }}% Spent</span>
                                        <span>{{ $project['progress'] }}% Done</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Slow: Low Spent / Low Progress --}}
                    <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/10 flex flex-col">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-400 mb-2">
                            ⚪ SLOW (Stalled)
                        </h4>
                        <div class="space-y-2 overflow-y-auto flex-1 max-h-32">
                            @forelse($budgetProgressMatrix['slow'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded shadow-sm">
                                    <p class="font-medium truncate" title="{{ $project['title'] }}">
                                        {{ Str::limit($project['title'], 25) }}</p>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ $project['budget_spent'] }}% Spent</span>
                                        <span>{{ $project['progress'] }}% Done</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Excellent: Low Spent / High Progress --}}
                    <div
                        class="border-2 border-green-300 rounded-lg p-4 bg-green-50 dark:bg-green-900/10 flex flex-col">
                        <h4 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-2">
                            🟢 EXCELLENT (Efficient)
                        </h4>
                        <div class="space-y-2 overflow-y-auto flex-1 max-h-32">
                            @forelse($budgetProgressMatrix['excellent'] as $project)
                                <div class="text-xs bg-white dark:bg-gray-800 p-2 rounded shadow-sm">
                                    <p class="font-medium truncate" title="{{ $project['title'] }}">
                                        {{ Str::limit($project['title'], 25) }}</p>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>{{ $project['budget_spent'] }}% Spent</span>
                                        <span>{{ $project['progress'] }}% Done</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500">None</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 4: Directorate Performance & SECTION 5: Project Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Directorate Performance --}}
        <div class="lg:col-span-2">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Directorate Performance
                    </h3>
                    <span class="text-sm text-gray-500">Budget & Progress</span>
                </div>
                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    @forelse($directorateProjectPerformance as $dir)
                        <div
                            class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition overflow-hidden">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-100 truncate"
                                        title="{{ $dir['title'] }}">
                                        {{ Str::limit($dir['title'], 45) }}
                                    </h4>
                                    <div
                                        class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="whitespace-nowrap">{{ $dir['total_projects'] }} projects</span>
                                        <span class="whitespace-nowrap">{{ $dir['avg_progress'] }}% avg prog</span>
                                        <span class="whitespace-nowrap">{{ $dir['budget_utilization'] }}% budget</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold text-white whitespace-nowrap"
                                        style="background-color: {{ $dir['budget_color'] }}">
                                        {{ $dir['budget_health'] }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    style="width: {{ min($dir['budget_utilization'], 100) }}%; background-color: {{ $dir['budget_color'] }}">
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $dir['alert_message'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">No data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Project Alerts --}}
        <div class="lg:col-span-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Requires Attention
                    </h3>
                </div>
                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    @if (count($projectAlerts['critical']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-600 rounded-full"></span>
                                CRITICAL ({{ count($projectAlerts['critical']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($projectAlerts['critical'] as $alert)
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

                    @if (count($projectAlerts['warning']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-yellow-600 dark:text-yellow-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-yellow-600 rounded-full"></span>
                                WARNING ({{ count($projectAlerts['warning']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($projectAlerts['warning'] as $alert)
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

                    @if (count($projectAlerts['notable']) > 0)
                        <div>
                            <h4
                                class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                                NOTABLE ({{ count($projectAlerts['notable']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($projectAlerts['notable'] as $alert)
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

                    @if (count($projectAlerts['critical']) == 0 && count($projectAlerts['warning']) == 0)
                        <p class="text-center text-gray-500 py-8">All projects looking healthy! 🎉</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 6: Charts (Progress & Allocation) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Physical vs Financial Progress --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Physical vs Financial Progress
                </h3>
                <p class="text-sm text-gray-500 mt-1">Top 10 Active Projects</p>
            </div>
            <div class="p-6">
                <canvas id="progressChart" height="300"></canvas>
            </div>
        </div>

        {{-- Resource Allocation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Resource Allocation
                </h3>
                <p class="text-sm text-gray-500 mt-1">Tasks vs Contracts</p>
            </div>
            <div class="p-6 flex items-center justify-center">
                <canvas id="allocationChart" height="250"></canvas>
            </div>
        </div>
    </div>

    {{-- SECTION 7: Detailed Project List --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <button id="toggle-details" class="w-full flex items-center justify-between text-left">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Detailed Project List
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Click to expand and view projects</p>
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
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                        <select id="department_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($filterOptions['departments'] as $d)
                                <option value="{{ $d->id }}">{{ $d->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select id="status_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Priority</label>
                        <select id="priority_id"
                            class="filter-select w-full p-2 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All</option>
                            @foreach ($priorities as $pr)
                                <option value="{{ $pr->id }}">{{ $pr->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Project Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Directorate
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risk Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget Util
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                        </tr>
                    </thead>
                    <tbody id="projectTableBody"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($detailedProjects['paginated'] as $project)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('admin.project.show', $project->id) }}"
                                        class="text-blue-600 hover:underline font-medium">
                                        {{ $project->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $project->directorate?->title ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded text-xs text-white"
                                        style="background-color: {{ $project->status?->color ?? 'gray' }}">
                                        {{ $project->status?->title ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold text-white
                                        {{ $project->tableData['risk_score'] >= 8 ? 'bg-red-500' : ($project->tableData['risk_score'] >= 5 ? 'bg-yellow-500' : 'bg-green-500') }}">
                                        {{ $project->tableData['risk_score'] }}/10
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full
                                                {{ $project->tableData['budget_utilization'] > 100 ? 'bg-red-500' : 'bg-blue-600' }}"
                                                style="width: {{ min($project->tableData['budget_utilization'], 100) }}%">
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs">{{ round($project->tableData['budget_utilization']) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ round($project->progress) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:border-gray-900"
                id="pagination-container">
                {{ $detailedProjects['paginated']->links() }}
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

        #projectTableBody {
            transition: opacity 0.2s ease;
        }

        #projectTableBody.updating {
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

                const State = {
                    isLoading: false,
                    charts: {
                        progress: null,
                        allocation: null
                    }
                };

                // Initial Data
                const initialData = {
                    portfolioHealth: @json($portfolioHealth),
                    financialHealth: @json($financialHealth),
                    budgetProgressMatrix: @json($budgetProgressMatrix),
                    projectAlerts: @json($projectAlerts),
                    charts: @json($charts)
                };

                function initCharts() {
                    // Progress Chart (Bar)
                    const progressCtx = document.getElementById('progressChart').getContext('2d');
                    State.charts.progress = new Chart(progressCtx, {
                        type: 'bar',
                        data: {
                            labels: initialData.charts.progress.labels,
                            datasets: [{
                                    label: 'Physical Progress',
                                    data: initialData.charts.progress.physical,
                                    backgroundColor: '#3B82F6',
                                },
                                {
                                    label: 'Financial Progress',
                                    data: initialData.charts.progress.financial,
                                    backgroundColor: '#10B981',
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });

                    // Allocation Chart (Doughnut)
                    const allocationCtx = document.getElementById('allocationChart').getContext('2d');
                    State.charts.allocation = new Chart(allocationCtx, {
                        type: 'doughnut',
                        data: {
                            labels: initialData.charts.task_contract.labels,
                            datasets: [{
                                data: initialData.charts.task_contract.data,
                                backgroundColor: ['#6366F1', '#EC4899']
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

                function initFilters() {
                    const filterSelects = document.querySelectorAll('.filter-select');
                    const spinner = document.getElementById('filter-spinner');
                    let filterTimeout;

                    filterSelects.forEach(select => {
                        select.addEventListener('change', (e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            // Debounce
                            clearTimeout(filterTimeout);
                            filterTimeout = setTimeout(() => {
                                applyFilters();
                            }, 150);
                        });
                    });

                    async function applyFilters() {
                        if (State.isLoading) return;
                        State.isLoading = true;

                        // Show loading
                        spinner?.classList.add('active');
                        filterSelects.forEach(s => {
                            s.classList.add('loading');
                            s.disabled = true;
                        });
                        document.getElementById('projectTableBody')?.classList.add('updating');

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

                            // Update Table & Pagination
                            updateProjectTable(data.detailedProjects.tableData);
                            updatePagination(data.detailedProjects.paginated.links_html);

                            // Update Charts
                            updateCharts(data.charts);

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
                                document.getElementById('projectTableBody')?.classList.remove('updating');
                            }, 100);
                        }
                    }

                    function buildFilterURL() {
                        const url = new URL(window.location.href);
                        ['directorate_id', 'department_id', 'status_id', 'priority_id'].forEach(id => {
                            url.searchParams.delete(id);
                        });

                        ['directorate_id', 'department_id', 'status_id', 'priority_id'].forEach(id => {
                            const value = document.getElementById(id)?.value;
                            if (value) url.searchParams.set(id, value);
                        });

                        url.searchParams.delete('page');
                        return url;
                    }
                }

                function updateCharts(chartsData) {
                    if (!chartsData || !State.charts.progress) return;

                    // Update Progress Chart
                    State.charts.progress.data.labels = chartsData.progress.labels;
                    State.charts.progress.data.datasets[0].data = chartsData.progress.physical;
                    State.charts.progress.data.datasets[1].data = chartsData.progress.financial;
                    State.charts.progress.update();

                    // Update Allocation Chart
                    if (chartsData.task_contract) {
                        State.charts.allocation.data.labels = chartsData.task_contract.labels;
                        State.charts.allocation.data.datasets[0].data = chartsData.task_contract.data;
                        State.charts.allocation.update();
                    }
                }

                function updateProjectTable(rows) {
                    const tbody = document.getElementById('projectTableBody');
                    if (!tbody || !rows) return;

                    if (rows.length === 0) {
                        tbody.innerHTML =
                            '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No projects found</td></tr>';
                        return;
                    }

                    tbody.innerHTML = rows.map(row => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm">
                                <a href="/admin/projects/${row.id}" class="text-blue-600 hover:underline font-medium">
                                    ${row.title}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                ${row.directorate || 'N/A'}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded text-xs text-white" style="background-color: ${row.status_color || 'gray'}">
                                    ${row.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded text-xs font-bold text-white
                                    ${row.risk_score >= 8 ? 'bg-red-500' : (row.risk_score >= 5 ? 'bg-yellow-500' : 'bg-green-500')}">
                                    ${row.risk_score}/10
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full
                                            ${row.budget_utilization > 100 ? 'bg-red-500' : 'bg-blue-600'}"
                                            style="width: ${Math.min(row.budget_utilization, 100)}%"></div>
                                    </div>
                                    <span class="text-xs">${Math.round(row.budget_utilization)}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                ${Math.round(row.progress)}%
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

                document.addEventListener('DOMContentLoaded', () => {
                    initCharts();
                    initToggleDetails();
                    initFilters();
                    console.log('✅ Project Analytics Dashboard initialized');
                });
            })();
        </script>
    @endpush
</x-layouts.app>
