<x-layouts.app>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950">

        <div class="px-4 sm:px-6 lg:px-8 py-8 lg:py-10">

            <!-- Header -->
            <div class="mb-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        @if ($viewLevel === 'admin')
                            System-wide Analytics
                        @elseif($viewLevel === 'directorate')
                            Directorate Analytics
                        @else
                            My Projects Analytics
                        @endif
                    </h1>
                    <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <a href="{{ route('admin.schedules.analytics') }}"
                            class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            Analytics Dashboard
                        </a>
                        <svg class="mx-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                        </svg>
                        <span class="font-medium">Charts & Insights</span>
                    </div>
                </div>

                <a href="{{ route('admin.schedules.analytics') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            @if ($projects->isEmpty())
                <div
                    class="rounded-2xl bg-white p-12 text-center shadow dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700">
                    <svg class="mx-auto h-20 w-20 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-6 text-2xl font-semibold text-gray-900 dark:text-white">No Projects Available</h3>
                    <p class="mt-3 text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        No projects or schedule assignments found under your current access rights.
                    </p>
                </div>
            @else
                <div class="space-y-10">

                    <!-- 1. All Projects Progress Comparison -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Project Progress Overview
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completion percentage — all
                                accessible projects</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-80 md:h-[420px]">
                                <canvas id="projectsComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Two smaller charts side by side -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <div
                            class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Phase / Activity Type
                                    Progress</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Average progress by activity
                                    category</p>
                            </div>
                            <div class="p-6 sm:p-8">
                                <div class="h-64 md:h-72">
                                    <canvas id="phaseDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div
                            class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Status Breakdown
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Projects by completion stage
                                </p>
                            </div>
                            <div class="p-6 sm:p-8">
                                <div class="h-64 md:h-72">
                                    <canvas id="statusPieChart"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- 3. New – At Risk & Delayed Projects -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h3 class="text-xl font-semibold text-red-700 dark:text-red-400">Projects Needing Attention
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Delayed and at-risk projects
                                (count)</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-72 md:h-80">
                                <canvas id="atRiskProjectsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- 4. New – Progress Buckets (Histogram style) -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Progress Distribution</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">How many projects fall into each
                                10% completion range</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-72 md:h-80">
                                <canvas id="progressHistogramChart"></canvas>
                            </div>
                        </div>
                    </div>

                    @if ($viewLevel === 'admin' || $viewLevel === 'directorate')
                        <!-- 5. Directorate Comparison -->
                        <div
                            class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    @if ($viewLevel === 'admin')
                                        Directorate Performance
                                    @else
                                        Directorate Summary
                                    @endif
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Average progress & project
                                    count per directorate</p>
                            </div>
                            <div class="p-6 sm:p-8">
                                <div class="h-80 md:h-[420px]">
                                    <canvas id="directorateComparisonChart"></canvas>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 6. New – Most & Least Completed Activities -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Activity Completion Extremes
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Top 5 best & worst performing
                                schedule items (avg %)</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-80 md:h-96">
                                <canvas id="activityExtremesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Top 10 Performing Projects -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Top 10 Best Performing
                                Projects</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Highest overall completion rates
                            </p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-96 md:h-[480px]">
                                <canvas id="topProjectsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- 8. New – Slippages / Delays by Activity -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800/80 ring-1 ring-gray-200 dark:ring-gray-700/50">
                        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700/70">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Schedule Slippages</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Activities with largest average
                                delay (days)</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="h-80 md:h-96">
                                <canvas id="slippageChart"></canvas>
                            </div>
                        </div>
                    </div>

                </div>

            @endif

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#d1d5db' : '#4b5563';
            Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';

            @if ($projects->isNotEmpty())

                // ────────────────────────────────────────────────
                // 1. Projects Comparison (existing)
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(r => r.json())
                    .then(data => {
                        new Chart(document.getElementById('projectsComparisonChart'), {
                            type: 'bar',
                            data: {
                                labels: data.map(p => p.project),
                                datasets: [{
                                    label: 'Progress',
                                    data: data.map(p => p.overall_progress),
                                    backgroundColor: data.map(p => {
                                        let v = p.overall_progress;
                                        if (v >= 90) return 'rgba(34,197,94,0.65)';
                                        if (v >= 70) return 'rgba(59,130,246,0.65)';
                                        if (v >= 50) return 'rgba(251,146,60,0.65)';
                                        return 'rgba(239,68,68,0.65)';
                                    }),
                                    borderColor: data.map(p => {
                                        let v = p.overall_progress;
                                        if (v >= 90) return 'rgb(34,197,94)';
                                        if (v >= 70) return 'rgb(59,130,246)';
                                        if (v >= 50) return 'rgb(251,146,60)';
                                        return 'rgb(239,68,68)';
                                    }),
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
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
                                            callback: v => v + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 2. Phase / Activity Type Average Progress (doughnut)
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(r => r.json())
                    .then(data => {
                        const phases = {};
                        data.forEach(p => {
                            p.phases?.forEach(ph => {
                                if (!phases[ph.code]) phases[ph.code] = {
                                    name: ph.name,
                                    total: 0,
                                    count: 0
                                };
                                phases[ph.code].total += ph.progress;
                                phases[ph.code].count++;
                            });
                        });

                        const phaseData = Object.values(phases).map(v => ({
                            label: v.name,
                            value: v.count ? v.total / v.count : 0
                        }));

                        new Chart(document.getElementById('phaseDistributionChart'), {
                            type: 'doughnut',
                            data: {
                                labels: phaseData.map(p => p.label),
                                datasets: [{
                                    data: phaseData.map(p => p.value),
                                    backgroundColor: ['#3b82f6aa', '#10b981aa', '#8b5cf6aa', '#fb923caa',
                                        '#f43f5eaa', '#4f46e5aa'
                                    ],
                                    borderColor: ['#3b82f6', '#10b981', '#8b5cf6', '#fb923c', '#f43f5e',
                                        '#4f46e5'
                                    ],
                                    borderWidth: 1.5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 16
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: ctx => `${ctx.label}: ${ctx.parsed.toFixed(1)}%`
                                        }
                                    }
                                },
                                cutout: '58%'
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 3. Status Pie (existing + renamed labels)
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.projects-comparison') }}')
                    .then(r => r.json())
                    .then(data => {
                        const completed = data.filter(p => p.overall_progress >= 100).length;
                        const onTrack = data.filter(p => p.overall_progress >= 50 && p.overall_progress < 100).length;
                        const atRisk = data.filter(p => p.overall_progress >= 25 && p.overall_progress < 50).length;
                        const delayed = data.filter(p => p.overall_progress < 25).length;

                        new Chart(document.getElementById('statusPieChart'), {
                            type: 'pie',
                            data: {
                                labels: ['Completed ≥100%', 'On Track 50–99%', 'At Risk 25–49%', 'Delayed <25%'],
                                datasets: [{
                                    data: [completed, onTrack, atRisk, delayed],
                                    backgroundColor: ['rgba(34,197,94,0.65)', 'rgba(59,130,246,0.65)',
                                        'rgba(251,146,60,0.65)', 'rgba(239,68,68,0.65)'
                                    ],
                                    borderColor: ['rgb(34,197,94)', 'rgb(59,130,246)', 'rgb(251,146,60)',
                                        'rgb(239,68,68)'
                                    ],
                                    borderWidth: 1.5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 16
                                        }
                                    }
                                }
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 4. New – At Risk & Delayed (focus chart)
                // endpoint example: admin.schedules.api.project-attention-counts
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.project-attention-counts') }}')
                    .then(r => r.json())
                    .then(data => {
                        new Chart(document.getElementById('atRiskProjectsChart'), {
                            type: 'bar',
                            data: {
                                labels: ['Delayed', 'At Risk', 'On Track', 'Completed'],
                                datasets: [{
                                    label: 'Projects',
                                    data: [data.delayed ?? 0, data.at_risk ?? 0, data.on_track ?? 0, data
                                        .completed ?? 0
                                    ],
                                    backgroundColor: ['#ef4444aa', '#fb923caa', '#3b82f6aa', '#22c55eaa'],
                                    borderColor: ['#ef4444', '#fb923c', '#3b82f6', '#22c55e'],
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 5. New – Progress Histogram (10% buckets)
                // endpoint example: admin.schedules.api.progress-buckets
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.progress-buckets') }}')
                    .then(r => r.json())
                    .then(data => { // expected: { "0-9": 3, "10-19": 5, ..., "90-100": 8 }
                        const labels = Object.keys(data).sort();
                        const values = labels.map(k => data[k]);

                        new Chart(document.getElementById('progressHistogramChart'), {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Project Count',
                                    data: values,
                                    backgroundColor: 'rgba(79,70,229,0.55)',
                                    borderColor: 'rgb(79,70,229)',
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });

                @if ($viewLevel === 'admin' || $viewLevel === 'directorate')
                    // Directorate Comparison (existing)
                    fetch('{{ route('admin.schedules.api.directorates-comparison') }}')
                        .then(r => r.json())
                        .then(data => {
                            new Chart(document.getElementById('directorateComparisonChart'), {
                                type: 'bar',
                                data: {
                                    labels: data.map(d => d.directorate),
                                    datasets: [{
                                            label: 'Avg Progress (%)',
                                            data: data.map(d => d.average_progress),
                                            backgroundColor: 'rgba(139,92,246,0.65)',
                                            borderColor: 'rgb(139,92,246)',
                                            yAxisID: 'y',
                                            borderWidth: 1.5,
                                            borderRadius: 6
                                        },
                                        {
                                            label: 'Projects',
                                            data: data.map(d => d.total_projects),
                                            backgroundColor: 'rgba(59,130,246,0.55)',
                                            borderColor: 'rgb(59,130,246)',
                                            yAxisID: 'y1',
                                            borderWidth: 1.5,
                                            borderRadius: 6
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100,
                                            position: 'left',
                                            ticks: {
                                                callback: v => v + '%'
                                            }
                                        },
                                        y1: {
                                            beginAtZero: true,
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            }
                                        }
                                    }
                                }
                            });
                        });
                @endif

                // ────────────────────────────────────────────────
                // 6. New – Activity Completion Extremes (top 5 best + worst)
                // endpoint example: admin.schedules.api.activity-extremes
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.activity-extremes') }}')
                    .then(r => r.json())
                    .then(data => { // expected: { best: [{name, avg}], worst: [{name, avg}] }
                        const labels = [...data.best.map(i => i.name), ...data.worst.map(i => i.name)];
                        const values = [...data.best.map(i => i.avg), ...data.worst.map(i => i.avg)];
                        const colors = [
                            ...data.best.map(() => 'rgba(34,197,94,0.65)'),
                            ...data.worst.map(() => 'rgba(239,68,68,0.65)')
                        ];

                        new Chart(document.getElementById('activityExtremesChart'), {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{
                                    label: 'Average Progress (%)',
                                    data: values,
                                    backgroundColor: colors,
                                    borderColor: colors.map(c => c.replace('0.65', '1')),
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
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
                                            callback: v => v + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 7. Top 10 Projects (existing)
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.top-projects') }}')
                    .then(r => r.json())
                    .then(data => {
                        new Chart(document.getElementById('topProjectsChart'), {
                            type: 'bar',
                            data: {
                                labels: data.map(p => p.title),
                                datasets: [{
                                    label: 'Progress',
                                    data: data.map(p => p.progress),
                                    backgroundColor: 'rgba(34,197,94,0.65)',
                                    borderColor: 'rgb(34,197,94)',
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
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
                                            callback: v => v + '%'
                                        }
                                    }
                                }
                            }
                        });
                    });

                // ────────────────────────────────────────────────
                // 8. New – Average Slippage by Activity
                // endpoint example: admin.schedules.api.slippages
                // ────────────────────────────────────────────────
                fetch('{{ route('admin.schedules.api.slippages') }}')
                    .then(r => r.json())
                    .then(data => { // expected: [{name: "...", avg_delay_days: 12.4}, ...]
                        const sorted = data.slice(0, 10); // top 10 worst

                        new Chart(document.getElementById('slippageChart'), {
                            type: 'bar',
                            data: {
                                labels: sorted.map(i => i.name),
                                datasets: [{
                                    label: 'Avg Delay (days)',
                                    data: sorted.map(i => i.avg_delay_days),
                                    backgroundColor: 'rgba(239,68,68,0.55)',
                                    borderColor: 'rgb(239,68,68)',
                                    borderWidth: 1.5,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
            @endif
        </script>
    @endpush
</x-layouts.app>
