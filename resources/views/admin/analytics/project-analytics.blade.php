<x-layouts.app>
    {{-- Page Title --}}
    <div class="mb-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-lg sm:text-xl lg:text-2xl xl:text-3xl font-bold text-gray-800 dark:text-gray-200">
            {{ trans('global.analytics.project.title') }}
        </h1>
        <p class="text-sm sm:text-base lg:text-lg text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.analytics.project.headerInfo') }}
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 mb-6 px-4 sm:px-6 lg:px-8">
        <div class="w-full" style="z-index: 1001;">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $roleIds = auth()->user()->roles->pluck('id')->toArray();
                @endphp
                @if (in_array(\App\Models\Role::SUPERADMIN, $roleIds) || in_array(\App\Models\Role::ADMIN, $roleIds))
                    <div class="w-full">
                        <x-forms.select label="{{ trans('global.directorate.title') }}" name="directorate_id"
                            :options="collect($directorates)
                                ->map(
                                    fn($directorate) => [
                                        'value' => (string) $directorate->id,
                                        'label' => $directorate->title,
                                    ],
                                )
                                ->values()
                                ->all()" :selected="request()->input('directorate_id')
                                ? (string) request()->input('directorate_id')
                                : null" placeholder="{{ trans('global.pleaseSelect') }}" />
                    </div>
                @endif
                <div class="w-full">
                    <x-forms.select label="{{ trans('global.department.title') }}" name="department_id"
                        :options="collect($departments)
                            ->map(
                                fn($department) => ['value' => (string) $department->id, 'label' => $department->title],
                            )
                            ->values()
                            ->all()" :selected="request()->input('department_id')
                            ? (string) request()->input('department_id')
                            : null" placeholder="{{ trans('global.pleaseSelect') }}" />
                </div>
                <div class="w-full">
                    <x-forms.select label="{{ trans('global.status.title') }}" name="status_id" :options="collect($statuses)
                        ->map(fn($status) => ['value' => (string) $status->id, 'label' => $status->title])
                        ->values()
                        ->all()"
                        :selected="request()->input('status_id') ? (string) request()->input('status_id') : null" placeholder="{{ trans('global.pleaseSelect') }}" />
                </div>
                <div class="w-full">
                    <x-forms.select label="{{ trans('global.priority.title') }}" name="priority_id" :options="collect($priorities)
                        ->map(fn($priority) => ['value' => (string) $priority->id, 'label' => $priority->title])
                        ->values()
                        ->all()"
                        :selected="request()->input('priority_id') ? (string) request()->input('priority_id') : null" placeholder="{{ trans('global.pleaseSelect') }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                {{ trans('global.analytics.project.fields.total_project') }}
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400">{{ $summary['total_projects'] }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                {{ trans('global.analytics.project.fields.completed_project') }}
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400">
                {{ $summary['completed_projects'] }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                {{ trans('global.analytics.project.fields.overdue_project') }}
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400">
                {{ $summary['overdue_projects'] }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                {{ trans('global.analytics.project.fields.avg_physical_progress') }}
            </h3>
            <p class="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400">
                {{ $summary['average_progress'] }}%
            </p>
        </div>
    </div>

    {{-- Charts and Details Layout --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 px-4 sm:px-6 lg:px-8">
        <!-- Charts (col-md-4) -->
        <div class="md:col-span-4 lg:col-span-1">
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        {{ trans('global.analytics.project.fields.progress_comparision') }}
                    </h3>
                    <div class="relative h-64 sm:h-80 lg:h-96">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        {{ trans('global.analytics.project.fields.task_contract_distribution') }}
                    </h3>
                    <div class="relative w-full max-w-[160px] sm:max-w-[200px] aspect-square mx-auto">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white"
                                    id="taskContractPercentage">
                                    {{ round(array_sum($charts['task_contract']['data']) ? ($charts['task_contract']['data'][0] / array_sum($charts['task_contract']['data'])) * 100 : 0) }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Main Category
                                </p>
                            </div>
                        </div>
                        <canvas id="taskContractChart" class="w-full h-full"></canvas>
                    </div>
                    <div class="flex justify-around text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-2">
                        <div class="text-center">
                            <div class="w-2 h-2 sm:w-3 sm:h-3 bg-[#10b981] rounded-full inline-block mr-1 sm:mr-2">
                            </div>
                            <span>
                                {{ trans('global.task.title') }}
                            </span>
                        </div>
                        <div class="text-center">
                            <div class="w-2 h-2 sm:w-3 sm:h-3 bg-[#ef4444] rounded-full inline-block mr-1 sm:mr-2">
                            </div>
                            <span>
                                {{ trans('global.contract.title') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Table (col-md-8) -->
        <div class="md:col-span-4 lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-800 dark:text-gray-200">
                        {{ trans('global.analytics.project.fields.project_details') }}
                    </h3>
                    <a href="{{ route('admin.projects.analytics.export') }}"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-sm sm:text-base">
                        Export to CSV
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-[1200px]">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.title') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.directorate_id') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.status_id') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.priority_id') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.physical_progress') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.project.fields.financial_progress') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.analytics.project.fields.remaining_budget') }}
                                    </th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ trans('global.analytics.project.fields.remaining_days') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($projects as $project)
                                    <?php
                                    $totalBudget = $project->total_budget;
                                    $remainingBudget = $totalBudget - ($project->expenses()->sum('amount') + $project->contracts()->sum('contract_amount'));
                                    $daysRemaining = $project->end_date ? max(0, $project->end_date->diffInDays(now()) * ($project->end_date > now() ? 1 : -1)) : 0;
                                    ?>
                                    <tr>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->title }}</td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->directorate->title ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->status->title ?? 'N/A' }}</td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->priority->title ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->progress }}%
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $project->financial_progress }}%
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            ${{ number_format($remainingBudget, 2) }}
                                        </td>
                                        <td
                                            class="px-4 sm:px-6 py-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                            {{ $daysRemaining }} days
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Progress Chart (Bar)
                const progressCtx = document.getElementById('progressChart').getContext('2d');
                new Chart(progressCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($charts['progress']['labels']),
                        datasets: [{
                            label: 'Physical Progress (%)',
                            data: @json($charts['progress']['physical']),
                            backgroundColor: '#10b981',
                            borderWidth: 1
                        }, {
                            label: 'Financial Progress (%)',
                            data: @json($charts['progress']['financial']),
                            backgroundColor: '#ef4444',
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
                                    text: 'Progress (%)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: window.innerWidth < 640 ? 12 : window.innerWidth < 1024 ? 14 : 16
                                    }
                                }
                            }
                        }
                    }
                });

                // Task/Contract Chart (Donut)
                const taskContractCtx = document.getElementById('taskContractChart').getContext('2d');
                const taskContractData = {
                    datasets: [{
                        data: @json($charts['task_contract']['data']),
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 0,
                    }],
                    labels: @json($charts['task_contract']['labels'])
                };
                new Chart(taskContractCtx, {
                    type: 'doughnut',
                    data: taskContractData,
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

                // Filter handling with AJAX
                document.addEventListener('change', function(event) {
                    const target = event.target;
                    if (target.matches(
                            'input[name="directorate_id"], input[name="department_id"], input[name="status_id"], input[name="priority_id"]'
                        )) {
                        const directorateId = document.querySelector('input[name="directorate_id"]')?.value ||
                            '';
                        const departmentId = document.querySelector('input[name="department_id"]')?.value || '';
                        const statusId = document.querySelector('input[name="status_id"]')?.value || '';
                        const priorityId = document.querySelector('input[name="priority_id"]')?.value || '';

                        const url = new URL('{{ route('admin.analytics.project') }}', window.location.origin);
                        if (directorateId) url.searchParams.set('directorate_id', directorateId);
                        if (departmentId) url.searchParams.set('department_id', departmentId);
                        if (statusId) url.searchParams.set('status_id', statusId);
                        if (priorityId) url.searchParams.set('priority_id', priorityId);

                        window.history.pushState({}, '', url.toString());

                        fetch(url.toString(), {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content')
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.text().then(text => {
                                        throw new Error(
                                            `HTTP error! Status: ${response.status}, Body: ${text.substring(0, 200)}...`
                                        );
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Filter request failed:', error);
                                alert('Failed to apply filters: ' + error.message);
                            });
                    }
                }, true);

                // jQuery fallback for select2 or custom select components
                if (window.jQuery) {
                    jQuery(document).on('change',
                        'input[name="directorate_id"], input[name="department_id"], input[name="status_id"], input[name="priority_id"]',
                        function() {
                            const directorateId = jQuery('input[name="directorate_id"]').val() || '';
                            const departmentId = jQuery('input[name="department_id"]').val() || '';
                            const statusId = jQuery('input[name="status_id"]').val() || '';
                            const priorityId = jQuery('input[name="priority_id"]').val() || '';

                            const url = new URL('{{ route('admin.analytics.project') }}', window.location.origin);
                            if (directorateId) url.searchParams.set('directorate_id', directorateId);
                            if (departmentId) url.searchParams.set('department_id', departmentId);
                            if (statusId) url.searchParams.set('status_id', statusId);
                            if (priorityId) url.searchParams.set('priority_id', priorityId);

                            window.history.pushState({}, '', url.toString());

                            jQuery.ajax({
                                url: url.toString(),
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                                },
                                dataType: 'json',
                                success: function(data) {
                                    window.location.reload();
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    console.error('jQuery filter request failed:', textStatus,
                                        errorThrown);
                                    alert('Failed to apply filters: ' + errorThrown);
                                }
                            });
                        });
                }

                // Initialize select values from URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const initialDirectorateId = urlParams.get('directorate_id') || '';
                const initialDepartmentId = urlParams.get('department_id') || '';
                const initialStatusId = urlParams.get('status_id') || '';
                const initialPriorityId = urlParams.get('priority_id') || '';

                if (initialDirectorateId || initialDepartmentId || initialStatusId || initialPriorityId) {
                    const directorateInput = document.querySelector('input[name="directorate_id"]');
                    const departmentInput = document.querySelector('input[name="department_id"]');
                    const statusInput = document.querySelector('input[name="status_id"]');
                    const priorityInput = document.querySelector('input[name="priority_id"]');

                    if (directorateInput) directorateInput.value = initialDirectorateId;
                    if (departmentInput) departmentInput.value = initialDepartmentId;
                    if (statusInput) statusInput.value = initialStatusId;
                    if (priorityInput) priorityInput.value = initialPriorityId;

                    const $ = window.jQuery;
                    if ($) {
                        $('.js-single-select[data-name="directorate_id"]').trigger('options-updated', {
                            selected: initialDirectorateId
                        });
                        $('.js-single-select[data-name="department_id"]').trigger('options-updated', {
                            selected: initialDepartmentId
                        });
                        $('.js-single-select[data-name="status_id"]').trigger('options-updated', {
                            selected: initialStatusId
                        });
                        $('.js-single-select[data-name="priority_id"]').trigger('options-updated', {
                            selected: initialPriorityId
                        });
                    }
                }
            });
        </script>
    @endpush
</x-layouts.app>
