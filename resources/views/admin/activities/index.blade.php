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
            @if (auth()->user()->projects->count() > 0)
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="projectFilter"
                        data-bs-toggle="dropdown">
                        Filter by Project: {{ request('project_id') ? request('project_id') : 'All' }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="projectFilter">
                        <li><a class="dropdown-item" href="{{ route('admin.project-activities.index') }}">All Projects</a>
                        </li>
                        @foreach (auth()->user()->projects as $project)
                            <li><a class="dropdown-item {{ request('project_id') == $project->id ? 'active' : '' }}"
                                    href="{{ route('admin.project-activities.index', ['project_id' => $project->id]) }}">
                                    {{ $project->title }}
                                </a></li>
                        @endforeach
                    </ul>
                </div>
            @else
                <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">Manage Projects</a>
            @endif
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mb-6 p-4 bg-green-100 text-green-800 border border-green-300 rounded-lg dark:bg-green-900 dark:text-green-200 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div
            class="mb-6 p-4 bg-yellow-100 text-yellow-800 border border-yellow-300 rounded-lg dark:bg-yellow-900 dark:text-yellow-200 dark:border-yellow-700">
            {{ session('warning') }}
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
                            {{ trans('global.projectActivity.fields.total_budget') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.total_capital_budget') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ trans('global.projectActivity.fields.total_recurrent_budget') }}
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
                                {{ $activity->definition->project->title ?? 'N/A' }}
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
                                @can('projectActivity_edit')
                                    <a href="{{ route('admin.projectActivity.edit', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                        Edit
                                    </a>
                                @endcan

                                @can('projectActivity_show')
                                    <a href="{{ route('admin.projectActivity.show', [$activity->project_id, $activity->fiscal_year_id]) }}"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        View
                                    </a>
                                @endcan
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
        @if ($activities->hasPages())
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
