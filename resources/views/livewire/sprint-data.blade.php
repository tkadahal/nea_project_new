<div
    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">
            {{ trans('global.taskOverview') }}
        </h2>
        <div class="space-x-1 sm:space-x-2">
            <button
                class="px-1 py-1 sm:px-2 sm:py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm">ALL</button>
            <button class="px-1 py-1 sm:px-2 sm:py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm">1M</button>
            <button class="px-1 py-1 sm:px-2 sm:py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm">6M</button>
            <button class="px-1 py-1 sm:px-2 sm:py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm">1Y</button>
        </div>
    </div>
    <div class="relative w-full h-48 sm:h-64">
        <canvas id="taskSprintChart" class="w-full h-full"></canvas>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sprintCtx = document.getElementById('taskSprintChart');
            if (sprintCtx) {
                const sprintContext = sprintCtx.getContext('2d');
                const sprintLabels = @json(array_keys($sprint_data));
                const reversedLabels = sprintLabels.reverse();

                const sprintData = {
                    labels: reversedLabels,
                    datasets: [{
                            label: 'To Do',
                            data: @json(array_column($sprint_data, 'todo')).reverse(),
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'In Progress',
                            data: @json(array_column($sprint_data, 'in_progress')).reverse(),
                            backgroundColor: 'rgba(139, 92, 246, 0.6)',
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Completed',
                            data: @json(array_column($sprint_data, 'completed')).reverse(),
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        }
                    ]
                };

                new Chart(sprintContext, {
                    type: 'bar',
                    data: sprintData,
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Tasks',
                                    font: {
                                        size: window.innerWidth < 640 ? 10 : 12
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Sprints',
                                    font: {
                                        size: window.innerWidth < 640 ? 10 : 12
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: window.innerWidth < 640 ? 8 : 10
                                    }
                                }
                            }
                        },
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    font: {
                                        size: window.innerWidth < 640 ? 10 : 12
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</div>
