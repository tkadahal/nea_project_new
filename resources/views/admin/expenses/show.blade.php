<x-layouts.app>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.expense.title_singular') }} {{ trans('global.details') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.details_for') }} :
                <span class="font-semibold">
                    {{ $expense->project->title }}
                </span>
            </p>
        </div>

        @can('expense_access')
            <a href="{{ route('admin.expense.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan

    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
                <h3
                    class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                    {{ trans('global.expense.title_singular') }} {{ trans('global.information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.fiscal_year_id') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $expense->fiscalYear->title ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.budget_type') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ str_replace(['internal', 'foreign_loan', 'foreign_subsidy'], ['Internal', 'Foreign Loan', 'Foreign Subsidy'], $expense->budget_type) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.amount') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ number_format($expense->amount, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.description') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $expense->description ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.quarter') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            Q{{ $expense->quarter }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.expense.fields.date') }}
                        </label>
                        <p class="mt-1 text-gray-900 dark:text-gray-200">
                            {{ $expense->date->format('M d, Y') }}
                        </p>
                    </div>

                    <div class="mt-6 flex space-x-3">

                        @can('expense_edit')
                            <a href="{{ route('admin.expense.edit', $expense) }}"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600
                      focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2
                      dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-offset-gray-900">
                                {{ trans('global.edit') }} {{ trans('global.expense.title_singular') }}
                            </a>
                        @endcan

                        @can('expense_delete')
                            <form action="{{ route('admin.expense.destroy', $expense) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this expense? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600
                               focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2
                               dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-gray-900">
                                    {{ trans('global.delete') }} {{ trans('global.expense.title_singular') }}
                                </button>
                            </form>
                        @endcan

                    </div>

                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
