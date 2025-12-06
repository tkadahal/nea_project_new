<x-layouts.app>
    <nav class="mb-6 flex items-center text-sm text-gray-600 dark:text-gray-400" aria-label="Breadcrumb">
        <a href="{{ route('admin.project.index') }}"
            class="hover:text-blue-500 dark:hover:text-blue-400">{{ __('Projects') }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('admin.project.show', $project) }}"
            class="hover:text-blue-500 dark:hover:text-blue-400">{{ $project->title }}</a>
        <span class="mx-2">/</span>
        <span>{{ __('Progress Chart') }}</span>
    </nav>

    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Progress Chart for:') }}
            {{ $project->title }}</h1>
        <div
            class="mt-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
            <canvas id="progressChart" class="w-full h-64"></canvas>
        </div>
        <div class="mt-4 flex justify-end">
            <a href="{{ route('admin.project.show', $project) }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                aria-label="{{ __('Back to Project') }}">
                {{ __('Back to Project') }}
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Physical Progress', 'Financial Progress'],
                datasets: [{
                    label: 'Progress (%)',
                    data: [{{ $project->progress }}, {{ $project->financial_progress }}],
                    backgroundColor: ['#3B82F6', '#10B981'],
                    borderColor: ['#3B82F6', '#10B981'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Progress Type'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</x-layouts.app>
