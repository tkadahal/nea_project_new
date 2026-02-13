<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.project.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Projects
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.project.show', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($project->title, 40) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                Schedules
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span
                                class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Dashboard</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        Progress Dashboard
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <a href="{{ route('admin.projects.schedules.index', $project) }}"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        View All Schedules
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">

            <!-- Overall Progress -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Overall Progress
                                </dt>
                                <dd>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($overallProgress, 2) }}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-green-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="p-3 rounded-md bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Completed
                                </dt>
                                <dd>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ $statistics['completed'] }} <span class="text-sm text-gray-500 font-normal">/
                                            {{ $statistics['total_leaf_schedules'] }}</span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-cyan-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-md bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    In Progress
                                </dt>
                                <dd>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ $statistics['in_progress'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Not Started -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div
                                class="p-3 rounded-md bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Not Started
                                </dt>
                                <dd>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ $statistics['not_started'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Progress Bar -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-5 sm:px-6 bg-blue-600 dark:bg-blue-800">
                <h3 class="text-lg leading-6 font-medium text-white">
                    Overall Project Progress
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 dark:text-blue-300 bg-blue-200 dark:bg-blue-900/50">
                                Summary
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold inline-block text-blue-600 dark:text-blue-300">
                                {{ number_format($overallProgress, 2) }}%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-6 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                        <div style="width:{{ $overallProgress }}%"
                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500 transition-all duration-500">
                            <strong>{{ number_format($overallProgress, 2) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phase Breakdown Grid -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Progress by Phase</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach ($breakdown as $phase)
                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        {{ $phase['code'] }}: {{ $phase['name'] }}
                                    </h4>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300">
                                    Weight: {{ $phase['weightage'] }}%
                                </span>
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Phase
                                        Progress</span>
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ number_format($phase['progress'], 2) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                    <div class="h-4 rounded-full text-xs font-medium text-center leading-4 text-white
                                        @if ($phase['progress'] >= 75) bg-green-500
                                        @elseif ($phase['progress'] >= 50) bg-blue-500
                                        @elseif ($phase['progress'] >= 25) bg-yellow-500
                                        @else bg-gray-500 @endif"
                                        style="width: {{ $phase['progress'] }}%">
                                        {{ number_format($phase['progress'], 1) }}%
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3 border border-blue-100 dark:border-blue-900">
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    Contribution to Overall Progress:
                                    <span
                                        class="float-right bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 py-0.5 px-2 rounded text-xs font-bold">
                                        {{ number_format($phase['weighted_contribution'], 2) }}%
                                    </span>
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    ({{ number_format($phase['progress'], 2) }}% × {{ $phase['weightage'] }}% =
                                    {{ number_format($phase['weighted_contribution'], 2) }}%)
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Completion Chart Section -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Activity Completion Rate</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                    <!-- Chart -->
                    <div class="lg:col-span-2 flex justify-center">
                        <div class="w-full max-w-md">
                            <canvas id="completionChart"></canvas>
                        </div>
                    </div>

                    <!-- Stats List -->
                    <div class="lg:col-span-1">
                        <div class="space-y-4">
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                    </div>
                                    <p class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Completed</p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    {{ $statistics['completed'] }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                    </div>
                                    <p class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">In Progress
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $statistics['in_progress'] }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                                    </div>
                                    <p class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Not Started
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                    {{ $statistics['not_started'] }}
                                </span>
                            </div>

                            <div
                                class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-100 dark:border-blue-900 mt-4">
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Completion Rate:</p>
                                <div class="mt-1 flex items-baseline">
                                    <h3 class="text-2xl font-semibold text-blue-900 dark:text-blue-100">
                                        {{ number_format($statistics['completion_rate'], 2) }}%
                                    </h3>
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    {{ $statistics['completed'] }} of {{ $statistics['total_leaf_schedules'] }}
                                    activities completed
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for dark mode
            const isDarkMode = document.documentElement.classList.contains('dark');

            const ctx = document.getElementById('completionChart').getContext('2d');

            // Set default font colors based on theme
            Chart.defaults.color = isDarkMode ? '#9ca3af' : '#374151';
            Chart.defaults.borderColor = isDarkMode ? '#374151' : '#e5e7eb';

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Not Started'],
                    datasets: [{
                        data: [
                            {{ $statistics['completed'] }},
                            {{ $statistics['in_progress'] }},
                            {{ $statistics['not_started'] }}
                        ],
                        backgroundColor: [
                            '#22c55e', // green-500
                            '#3b82f6', // blue-500
                            '#eab308' // yellow-500
                        ],
                        borderWidth: 2,
                        borderColor: isDarkMode ? '#1f2937' : '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        title: {
                            display: true,
                            text: 'Activity Status Distribution',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-layouts.app>
