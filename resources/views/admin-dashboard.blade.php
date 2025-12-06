<x-layouts.app>
    <div class="mb-4 sm:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.dashboard') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm sm:text-base">
            {{ trans('global.welcomeToDashboard') }}
        </p>
    </div>

    <!-- Number Blocks Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
        @foreach ($number_blocks as $block)
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $block['title'] }}
                        </p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">
                            {{ $block['number'] }}</p>
                        <a href="{{ $block['url'] }}"
                            class="text-xs text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 flex items-center mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                            {{ trans('global.view_details') }}
                        </a>
                    </div>
                    <div
                        class="p-2 sm:p-3 rounded-full {{ $block['title'] == trans('global.user.title') ? 'bg-blue-100 dark:bg-blue-900' : ($block['title'] == trans('global.project.title') ? 'bg-green-100 dark:bg-green-900' : ($block['title'] == trans('global.contract.title') ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-purple-100 dark:bg-purple-900')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 sm:h-6 sm:w-6 {{ $block['title'] == trans('global.user.title') ? 'text-blue-600 dark:text-blue-300' : ($block['title'] == trans('global.project.title') ? 'text-green-600 dark:text-green-300' : ($block['title'] == trans('global.contract.title') ? 'text-yellow-600 dark:text-yellow-300' : 'text-purple-600 dark:text-purple-300')) }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if ($block['title'] == trans('global.user.title'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            @elseif ($block['title'] == trans('global.project.title'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2-5h12M5 7h12m-12 4h12" />
                            @elseif ($block['title'] == trans('global.contract.title'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            @elseif ($block['title'] == trans('global.task.title'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" />
                            @endif
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Tasks Table -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">
                All Tasks (Completed: {{ $tasks_for_admin['completed_tasks_count'] }})
            </h2>
            <a href="{{ route('admin.task.index') }}"
                class="text-xs text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
                View All
            </a>
        </div>
        <table class="w-full text-sm text-gray-600 dark:text-gray-400">
            <thead>
                <tr>
                    <th class="text-left pb-2">Task Title</th>
                    <th class="text-left pb-2">Assigned Users</th>
                    <th class="text-left pb-2">Projects</th>
                    <th class="text-left pb-2">Comments</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks_for_admin['tasks'] as $task)
                    <tr>
                        <td class="py-2">{{ $task->title }}</td>
                        <td class="py-2">{{ $task->assigned_users ?: 'None' }}</td>
                        <td class="py-2">{{ $task->assigned_projects ?: 'None' }}</td>
                        <td class="py-2">
                            <a href="{{ route('admin.task.show', $task->id) }}"
                                class="flex items-center text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                {{ $task->comment_count }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Upcoming Tasks -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                Upcoming Tasks (by Due Date)
            </h2>
            <table class="w-full text-sm text-gray-600 dark:text-gray-400">
                <thead>
                    <tr>
                        <th class="text-left">Task</th>
                        <th class="text-left">Due Date</th>
                        <th class="text-left">Days Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks_by_due_date as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->due_date }}</td>
                            <td>{{ $task->days_remaining }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- In Progress Tasks -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                In Progress Tasks
            </h2>
            <ul class="list-disc pl-5 text-sm text-gray-600 dark:text-gray-400">
                @foreach ($in_progress_tasks as $task)
                    <li>{{ $task->title }} (Due: {{ $task->due_date ?? 'N/A' }})</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Recent Comments -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
            Recent Task Comments
        </h2>
        <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
            @foreach ($recent_comments as $comment)
                <div class="border-b pb-2">
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $comment->user_name }} on
                        {{ $comment->task_title }}</p>
                    <p>{{ $comment->content }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Activity Logs -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">
                {{ trans('global.activityLog.title') }}
            </h2>
            <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
            </button>
        </div>
        <div class="space-y-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
            @forelse ($activity_logs as $log)
                <div class="flex items-start space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 mt-0.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100 text-xs sm:text-sm">
                            {{ $log->description }}
                        </p>
                        @if ($log->subject_type && $log->subject_id)
                            <p class="text-xs">
                                {{ class_basename($log->subject_type) }} ID: {{ $log->subject_id }}
                            </p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->created_at }}
                        </p>
                    </div>
                </div>
            @empty
                <p>
                    {{ trans('global.noRecords') }}
                </p>
            @endforelse
        </div>
    </div>

    <!-- Directorate Task Chart -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
            Directorate-wise Tasks
        </h2>
        <canvas id="directorateChart" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('directorateChart').getContext('2d');
            const data = {
                labels: [
                    @foreach ($directorate_task_data as $data)
                        '{{ $data->directorate_name }}',
                    @endforeach
                ],
                datasets: [{
                    label: 'Task Count',
                    data: [
                        @foreach ($directorate_task_data as $data)
                            {{ $data->task_count }},
                        @endforeach
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            };
            new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</x-layouts.app>
