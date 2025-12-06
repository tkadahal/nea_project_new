<div>
    <!-- Project Status and Tasks Section -->
    <div class="grid grid-cols-12 gap-4 sm:gap-6 mb-4 sm:mb-6">
        <!-- Project Status -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 col-span-12 lg:col-span-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">Project Status</h2>
                <div class="relative">
                    <button wire:click="$toggle('showProjectStatusFilter')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </button>
                    @if ($showProjectStatusFilter)
                        <div
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10 p-4">
                            <div class="mb-2">
                                <label for="project-status-filter"
                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select id="project-status-filter" wire:model.live="projectStatusFilter.status"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                    <option value="">All Statuses</option>
                                    <option value="{{ \App\Models\Status::STATUS_TODO }}">To Do</option>
                                    <option value="{{ \App\Models\Status::STATUS_IN_PROGRESS }}">In Progress</option>
                                    <option value="{{ \App\Models\Status::STATUS_COMPLETED }}">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="project-date-range"
                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date
                                    Range</label>
                                <input type="text" id="project-date-range"
                                    wire:model.live="projectStatusFilter.dateRange"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200"
                                    placeholder="YYYY-MM-DD to YYYY-MM-DD">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-center mb-4">
                <div class="relative w-full max-w-[140px] sm:max-w-[160px] aspect-square">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100">
                                {{ $project_status['completed'] }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Completed</p>
                        </div>
                    </div>
                    <canvas id="projectStatusChart" class="w-full h-full"></canvas>
                </div>
            </div>
            <div class="flex justify-around text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                <div class="text-center">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-green-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                    <span>Completed</span>
                    <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $project_status['completed'] }}%</p>
                </div>
                <div class="text-center">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-purple-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                    <span>In-progress</span>
                    <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $project_status['in_progress'] }}%</p>
                </div>
                <div class="text-center">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-red-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                    <span>Behind</span>
                    <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $project_status['behind'] }}%</p>
                </div>
            </div>
        </div>

        <!-- Tasks -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 col-span-12 lg:col-span-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">Tasks</h2>
                <div class="relative">
                    <button wire:click="$toggle('showTasksFilter')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </button>
                    @if ($showTasksFilter)
                        <div
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10 p-4">
                            <div class="mb-2">
                                <label for="tasks-status-filter"
                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select id="tasks-status-filter" wire:model.live="tasksFilter.status"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                    <option value="">All Statuses</option>
                                    <option value="{{ \App\Models\Status::STATUS_TODO }}">To Do</option>
                                    <option value="{{ \App\Models\Status::STATUS_IN_PROGRESS }}">In Progress</option>
                                    <option value="{{ \App\Models\Status::STATUS_COMPLETED }}">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="tasks-date-range"
                                    class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date
                                    Range</label>
                                <input type="text" id="tasks-date-range" wire:model.live="tasksFilter.dateRange"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200"
                                    placeholder="YYYY-MM-DD to YYYY-MM-DD">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ $tasks->where('status.title', 'Completed')->count() }} Tasks completed out of {{ $tasks->count() }}
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="py-2 px-2 sm:px-4 text-left">Task Name</th>
                            <th class="py-2 px-2 sm:px-4 text-left hidden sm:table-cell">Status</th>
                            <th class="py-2 px-2 sm:px-4 text-left hidden md:table-cell">Assigned to</th>
                            <th class="py-2 px-2 sm:px-4 text-left hidden lg:table-cell">Total time</th>
                            <th class="py-2 px-2 sm:px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="border-t dark:border-gray-600">
                                <td class="py-2 px-2 sm:px-4">
                                    {{ $task->name }}
                                    <div class="sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <span
                                            class="{{ $task->status->title == 'Completed' ? 'text-green-500' : ($task->status->title == 'In-progress' ? 'text-purple-500' : 'text-red-500') }}">
                                            {{ $task->status->title }}
                                        </span>
                                        <br>
                                        Assigned: {{ $task->assigned_to }}
                                        <br>
                                        Time: {{ $task->total_time_spent }}
                                    </div>
                                </td>
                                <td class="py-2 px-2 sm:px-4 hidden sm:table-cell">
                                    <span
                                        class="{{ $task->status->title == 'Completed' ? 'text-green-500' : ($task->status->title == 'In-progress' ? 'text-purple-500' : 'text-red-500') }}">
                                        {{ $task->status->title }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 sm:px-4 hidden md:table-cell">{{ $task->assigned_to }}</td>
                                <td class="py-2 px-2 sm:px-4 hidden lg:table-cell">{{ $task->total_time_spent }}</td>
                                <td class="py-2 px-2 sm:px-4">
                                    <div class="flex space-x-1 sm:space-x-2">
                                        <a href="{{ route('admin.task.edit', ['task' => $task->id]) }}"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.task.destroy', ['task' => $task->id]) }}"
                                            method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Task Sprint Overview Section -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">Tasks Overview</h2>
            <div class="space-x-1 sm:space-x-2">
                @foreach (['ALL', '1M', '6M', '1Y'] as $range)
                    <button wire:click="$set('sprintFilterRange', '{{ $range }}')"
                        class="px-1 py-1 sm:px-2 sm:py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs sm:text-sm {{ $sprintFilterRange === $range ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-300' }}">{{ $range }}</button>
                @endforeach
            </div>
        </div>
        <div class="relative w-full h-48 sm:h-64">
            <canvas id="taskSprintChart" class="w-full h-full"></canvas>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:initialized', function() {
                const projectStatusCtx = document.getElementById('projectStatusChart');
                if (projectStatusCtx) {
                    const context = projectStatusCtx.getContext('2d');
                    const data = {
                        datasets: [{
                            data: [{{ $project_status['completed'] }},
                                {{ $project_status['in_progress'] }}, {{ $project_status['behind'] }}
                            ],
                            backgroundColor: ['#10b981', '#8b5cf6', '#ef4444'],
                            borderWidth: 0,
                        }],
                        labels: ['Completed', 'In-progress', 'Behind']
                    };
                    new Chart(context, {
                        type: 'doughnut',
                        data: data,
                        options: {
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: true
                                }
                            },
                            maintainAspectRatio: true,
                            responsive: true
                        }
                    });
                }

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
                        }, {
                            label: 'In Progress',
                            data: @json(array_column($sprint_data, 'in_progress')).reverse(),
                            backgroundColor: 'rgba(139, 92, 246, 0.6)',
                            borderColor: 'rgba(139, 92, 246, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Completed',
                            data: @json(array_column($sprint_data, 'completed')).reverse(),
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        }]
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
    @endpush
</div>
