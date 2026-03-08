<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        @if ($viewLevel === 'admin')
                            System-Wide Analytics Charts
                        @elseif($viewLevel === 'directorate')
                            Directorate Analytics Charts
                        @else
                            My Projects Analytics Charts
                        @endif
                    </h1>
                    <nav class="flex mt-2" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('admin.schedules.analytics') }}"
                                    class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                    Analytics
                                </a>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m1 9 4-4-4-4" />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Charts</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.schedules.analytics') }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        @if ($projects->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Projects Available</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">You don't have access to any projects yet.</p>
            </div>
        @else
            <!-- Projects Progress Comparison Chart -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Projects Progress Comparison
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Overall progress across all accessible
                        projects</p>
                </div>
                <div class="p-6">
                    <canvas id="projectsComparisonChart" height="80"></canvas>
                </div>
            </div>

            <!-- Phase Progress Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Phase Breakdown Chart -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Phase Progress
                            Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Average progress by phase</p>
                    </div>
                    <div class="p-6">
                        <canvas id="phaseDistributionChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Project Status Pie Chart -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Progress Status
                            Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Projects by completion status</p>
                    </div>
                    <div class="p-6">
                        <canvas id="statusPieChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            @if ($viewLevel === 'admin' || $viewLevel === 'directorate')
                <!-- Directorate Comparison Chart -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            @if ($viewLevel === 'admin')
                                Directorate Performance Comparison
                            @else
                                Directorate Projects Performance
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Average progress by directorate</p>
                    </div>
                    <div class="p-6">
                        <canvas id="directorateComparisonChart" height="80"></canvas>
                    </div>
                </div>
            @endif

            <!-- Top 10 Performing Projects -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Top 10 Performing Projects
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Highest completion rates</p>
                </div>
                <div class="p-6">
                    <canvas id="topProjectsChart" height="100"></canvas>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            @if ($projects->isNotEmpty())
                // Chart.js default configuration
                Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#374151';
                Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB';

                // Projects Comparison Chart
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(response => response.json())
                    .then(data => {
                        const ctx = document.getElementById('projectsComparisonChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.map(p => p.project),
                                datasets: [{
                                    label: 'Progress (%)',
                                    data: data.map(p => p.overall_progress),
                                    backgroundColor: data.map(p => {
                                        if (p.overall_progress >= 90)
                                        return 'rgba(34, 197, 94, 0.7)';
                                        if (p.overall_progress >= 70)
                                        return 'rgba(59, 130, 246, 0.7)';
                                        if (p.overall_progress >= 50)
                                        return 'rgba(251, 146, 60, 0.7)';
                                        return 'rgba(239, 68, 68, 0.7)';
                                    }),
                                    borderColor: data.map(p => {
                                        if (p.overall_progress >= 90) return 'rgb(34, 197, 94)';
                                        if (p.overall_progress >= 70) return 'rgb(59, 130, 246)';
                                        if (p.overall_progress >= 50) return 'rgb(251, 146, 60)';
                                        return 'rgb(239, 68, 68)';
                                    }),
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: value => value + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });

                // Phase Distribution Chart
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(response => response.json())
                    .then(data => {
                        // Aggregate phase data
                        const phases = {};
                        data.forEach(project => {
                            project.phases.forEach(phase => {
                                if (!phases[phase.code]) {
                                    phases[phase.code] = {
                                        name: phase.name,
                                        total: 0,
                                        count: 0
                                    };
                                }
                                phases[phase.code].total += phase.progress;
                                phases[phase.code].count++;
                            });
                        });

                        const phaseData = Object.keys(phases).map(code => ({
                            code: code,
                            name: phases[code].name,
                            average: phases[code].total / phases[code].count
                        }));

                        const ctx = document.getElementById('phaseDistributionChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: phaseData.map(p => p.code + ': ' + p.name),
                                datasets: [{
                                    data: phaseData.map(p => p.average),
                                    backgroundColor: [
                                        'rgba(59, 130, 246, 0.7)',
                                        'rgba(16, 185, 129, 0.7)',
                                        'rgba(139, 92, 246, 0.7)',
                                        'rgba(251, 146, 60, 0.7)',
                                        'rgba(244, 63, 94, 0.7)'
                                    ],
                                    borderColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(139, 92, 246)',
                                        'rgb(251, 146, 60)',
                                        'rgb(244, 63, 94)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: context => context.label + ': ' + context.parsed.toFixed(1) + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });

                // Status Pie Chart
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(response => response.json())
                    .then(data => {
                        const completed = data.filter(p => p.overall_progress >= 100).length;
                        const inProgress = data.filter(p => p.overall_progress >= 50 && p.overall_progress < 100).length;
                        const atRisk = data.filter(p => p.overall_progress >= 25 && p.overall_progress < 50).length;
                        const delayed = data.filter(p => p.overall_progress < 25).length;

                        const ctx = document.getElementById('statusPieChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['Completed (≥100%)', 'On Track (50-99%)', 'At Risk (25-49%)',
                                    'Delayed (<25%)'
                                ],
                                datasets: [{
                                    data: [completed, inProgress, atRisk, delayed],
                                    backgroundColor: [
                                        'rgba(34, 197, 94, 0.7)',
                                        'rgba(59, 130, 246, 0.7)',
                                        'rgba(251, 146, 60, 0.7)',
                                        'rgba(239, 68, 68, 0.7)'
                                    ],
                                    borderColor: [
                                        'rgb(34, 197, 94)',
                                        'rgb(59, 130, 246)',
                                        'rgb(251, 146, 60)',
                                        'rgb(239, 68, 68)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    });

                @if ($viewLevel === 'admin' || $viewLevel === 'directorate') // Directorate Comparison Chart
        fetch('{{ route('admin.schedules.api.directorates-comparison') }}')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('directorateComparisonChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.directorate),
                        datasets: [{
                            label: 'Average Progress (%)',
                            data: data.map(d => d.average_progress),
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 1
                        }, {
                            label: 'Total Projects',
                            data: data.map(d => d.total_projects),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                position: 'left',
                                ticks: {
                                    callback: value => value + '%'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            }); @endif

                // Top Projects Chart
                fetch('{{ route('admin.schedules.api.top-projects') }}')
                    .then(response => response.json())
                    .then(data => {
                        const ctx = document.getElementById('topProjectsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'horizontalBar',
                            data: {
                                labels: data.map(p => p.title),
                                datasets: [{
                                    label: 'Progress (%)',
                                    data: data.map(p => p.progress),
                                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                    borderColor: 'rgb(34, 197, 94)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: value => value + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });
            @endif
        </script>
    @endpush
</x-layouts.app>
