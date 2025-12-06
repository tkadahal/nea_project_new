<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.budget.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.details_for') }} : {{ $budget->project->title }}
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
                <h3
                    class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                    {{ trans('global.budget.title_singular') }} {{ trans('global.information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.project_id') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $budget->project->title }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.fiscal_year_id') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $budget->fiscalYear->title }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.government_loan') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->government_loan, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.government_share') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->government_share, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.foreign_loan_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->foreign_loan_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.foreign_subsidy_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->foreign_subsidy_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.internal_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->internal_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.total_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->total_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.budget_revision') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $budget->budget_revision }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ trans('global.budget.fields.remaining_budget') }}
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-200">
                                {{ number_format($budget->remaining_budget, 2) }}
                            </p>
                        </div>
                        <a href="{{ route('admin.budget.remaining', $budget->id) }}"
                            class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            {{ trans('global.budget.actions.show_remaining_budgets') }}
                        </a>
                    </div>
                </div>

                <h3
                    class="text-xl font-semibold text-gray-800 dark:text-gray-100 mt-8 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                    {{ trans('global.budget.revisions.title') }}
                </h3>

                @if ($revisions->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ trans('global.noRecords') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.revisions.revision') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.government_loan') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.government_share') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.foreign_loan_budget') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.foreign_subsidy_budget') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.internal_budget') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.total_budget') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.revisions.allocated_at') }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ trans('global.budget.fields.decision_date') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($revisions as $index => $revision)
                                    <tr>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $index + 1 }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->government_loan, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->government_share, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->foreign_loan_budget, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->foreign_subsidy_budget, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->internal_budget, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ number_format($revision->total_budget, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $revision->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ $revision->decision_date }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('budget_access')
                    <div class="mt-8 flex">
                        <a href="{{ route('admin.budget.index') }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            {{ trans('global.back_to_list') }}
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-layouts.app>
