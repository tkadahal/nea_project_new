<x-layouts.app>
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.expense.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.expense.title') }}
            </p>
        </div>

        @can('expense_create')
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.expense.create') }}"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-sm"
                    aria-label="{{ trans('global.create') }} {{ trans('global.expense.title_singular') }}">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>

                <a href="{{ route('admin.expense.testShow', 0) }}"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-sm"
                    aria-label="{{ trans('global.create') }} {{ trans('global.expense.title_singular') }}">
                    Test View
                </a>
            </div>
        @endcan
    </div>

    <div
        class="bg-white dark:bg-gray-900 p-4 sm:p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 mb-6">
        <form class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-4" action="{{ route('admin.expense.index') }}"
            method="GET">
            <div>
                <x-forms.select label="{{ trans('global.expense.filters.byProject') }}" name="project_id"
                    id="project_id" :options="collect($projects)
                        ->map(fn($project) => ['value' => (int) $project->id, 'label' => $project->title])
                        ->values()
                        ->all()" placeholder="{{ trans('global.pleaseSelect') }}"
                    class="js-single-select" :clearable="true" :selected="request()->input('project_id')" />
            </div>
            <div>
                <x-forms.select label="{{ trans('global.expense.filters.byFiscalYear') }}" name="fiscal_year_id"
                    id="fiscal_year_id" :options="collect($fiscalYears)
                        ->map(fn($fiscalYear) => ['value' => (int) $fiscalYear->id, 'label' => $fiscalYear->title])
                        ->values()
                        ->all()" placeholder="{{ trans('global.pleaseSelect') }}"
                    class="js-single-select" :clearable="true" :selected="request()->input('fiscal_year_id')" />
            </div>
            <div>
                <x-forms.select label="{{ trans('global.expense.filters.byBudgetType') }}" name="budget_type"
                    id="budget_type" :options="$budgetTypes" placeholder="{{ trans('global.pleaseSelect') }}"
                    class="js-single-select" :clearable="true" :selected="request()->input('budget_type')" />
            </div>
        </form>

        <x-table.dataTables.expenses :headers="$headers" :data="$data" :routePrefix="$routePrefix" :actions="$actions"
            :deleteConfirmationMessage="$deleteConfirmationMessage" />
    </div>

    @if (session('success'))
        <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-sm"
            role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm"
            role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="mt-4">
        {{ $expenses->links() }}
    </div>

    @push('scripts')
        <script>
            (function waitForJQuery() {
                if (window.jQuery && document.readyState === 'complete') {
                    initializeFilters();
                } else {
                    setTimeout(waitForJQuery, 50);
                }

                function initializeFilters() {
                    const $ = window.jQuery;
                    const $selects = $('.js-single-select');

                    $selects.on('change', function() {
                        const $form = $(this).closest('form');
                        $form.submit();
                    });
                }
            })();
        </script>
    @endpush
</x-layouts.app>
