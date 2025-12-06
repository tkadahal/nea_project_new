<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.budget.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.budget.remaining_budgets_for') }} : {{ $budget->project->title }}
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
                <h3
                    class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                    {{ trans('global.budget.remaining_budgets') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_internal_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_internal_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_government_loan') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_government_loan, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_government_share') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_government_share, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_foreign_loan_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_foreign_loan_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_foreign_subsidy_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_foreign_subsidy_budget, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.budget.fields.remaining_budget') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($budget->remaining_budget, 2) }}
                        </p>
                    </div>
                </div>

                @can('budget_access')
                    <div class="mt-8 flex">
                        <a href="{{ route('admin.budget.show', $budget->id) }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            {{ trans('global.budget.actions.back_to_details') }}
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-layouts.app>
