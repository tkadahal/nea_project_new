<x-layouts.app>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.projectActivity.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.projectActivity.info.summaryInfo') }}
            </p>
        </div>

        @can('projectActivity_access')
            <a href="{{ route('admin.projectActivity.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                  dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                {{ trans('global.add') }} {{ trans('global.projectActivity.title_singular') }}
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mb-6 p-4 bg-green-100 text-green-800 border border-green-300 rounded-lg
                    dark:bg-green-900 dark:text-green-200 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.fiscal_year_id') }}
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.project_id') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.total_planned_budget') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.total_capital_planned_budget') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.total_recurrent_planned_budget') }}
                        </th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.action') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                    @forelse ($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $activity->fiscalYear->title ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $activity->project_title ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->total_budget ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->capital_budget ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                {{ number_format($activity->recurrent_budget ?? 0, 2) }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex flex-wrap justify-center gap-2">

                                    <!-- View Button -->
                                    @can('projectActivity_show')
                                        <a href="{{ route('admin.projectActivity.show', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                            class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">
                                            View
                                        </a>
                                    @endcan

                                    <!-- Edit + Send for Review (Project User - Draft only) -->
                                    @if ($activity->status === 'draft' && auth()->user()->roles->pluck('id')->contains(\App\Models\Role::PROJECT_USER))
                                        @can('projectActivity_edit')
                                            <a href="{{ route('admin.projectActivity.edit', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                                class="px-3 py-1 bg-indigo-500 text-white rounded text-xs hover:bg-indigo-600">
                                                Edit
                                            </a>
                                        @endcan

                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.sendForReview', $activity->project_id) }}"
                                            class="inline"
                                            onsubmit="return confirm('Send for review? Editing will be locked.')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700">
                                                Send for Review
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Mark Reviewed (Directorate User - Under Review, not yet reviewed) -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            is_null($activity->reviewed_at) &&
                                            auth()->user()->roles->pluck('id')->contains(\App\Models\Role::DIRECTORATE_USER))
                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.review', $activity->project_id) }}"
                                            class="inline" onsubmit="return confirm('Mark as reviewed?')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
                                                Mark Reviewed
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Approve (Admin/Superadmin - Under Review + Already Reviewed) -->
                                    @if (
                                        $activity->status === 'under_review' &&
                                            $activity->reviewed_at &&
                                            auth()->user()->roles->pluck('id')->intersect([\App\Models\Role::ADMIN, \App\Models\Role::SUPERADMIN])->isNotEmpty())
                                        <form method="POST"
                                            action="{{ route('admin.projectActivity.approve', $activity->project_id) }}"
                                            class="inline"
                                            onsubmit="return confirm('Approve permanently? This cannot be undone.')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Dynamic Status Badge -->
                                    @php
                                        $badgeColors = [
                                            'draft' => 'gray',
                                            'under_review' => 'yellow',
                                            'approved' => 'green',
                                        ];

                                        $color = $badgeColors[$activity->status] ?? 'gray';
                                        $text = ucfirst(str_replace('_', ' ', $activity->status));

                                        if ($activity->status === 'under_review') {
                                            $color = $activity->reviewed_at ? 'purple' : 'yellow';
                                            $text = $activity->reviewed_at ? 'Reviewed' : 'Under Review';
                                        }
                                    @endphp

                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                                 bg-{{ $color }}-100 text-{{ $color }}-800
                                                 dark:bg-{{ $color }}-900/50 dark:text-{{ $color }}-300
                                                 border border-{{ $color }}-300 dark:border-{{ $color }}-700">
                                        {{ $text }}
                                    </span>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ trans('global.noRecords') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
