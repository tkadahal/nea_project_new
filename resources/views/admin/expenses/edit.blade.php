<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.expense.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.expense.title_singular') }}
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

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">

        <form class="w-full" method="POST" action="{{ route('admin.expense.update', $expense) }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div
                    class="col-span-full mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="error-message"
                class="col-span-full mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.expense.title_singular') }} {{ trans('global.information') }}
                        </h3>
                        <div class="space-y-6">
                            <x-forms.select label="{{ trans('global.expense.fields.project_id') }}" name="project_id"
                                id="project_id" :options="collect($projects)
                                    ->map(fn($project) => ['value' => (int) $project->id, 'label' => $project->title])
                                    ->values()
                                    ->all()" :selected="old('project_id', $expense->project_id)"
                                placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')"
                                class="js-single-select" />


                            <div>
                                <x-forms.select label="{{ trans('global.expense.fields.fiscal_year_id') }}"
                                    name="fiscal_year_id" id="fiscal_year_id" :options="collect($fiscalYears)
                                        ->map(fn($year) => ['value' => (int) $year->id, 'label' => $year->title])
                                        ->values()
                                        ->all()" :selected="old('fiscal_year_id', $expense->fiscal_year_id)"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')"
                                    class="js-single-select" />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="available-budget"></p>
                            </div>

                            <x-forms.select label="{{ trans('global.expense.fields.budget_type') }}" name="budget_type"
                                id="budget_type" :options="[
                                    ['value' => 'internal', 'label' => __('Internal Budget')],
                                    ['value' => 'foreign_loan', 'label' => __('Foreign Loan Budget')],
                                    ['value' => 'foreign_subsidy', 'label' => __('Foreign Subsidy Budget')],
                                ]" :selected="old('budget_type', $expense->budget_type)"
                                placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('budget_type')"
                                class="js-single-select" />
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.expense.title') }}
                        </h3>
                        <div class="space-y-6">
                            <x-forms.input label="{{ trans('global.expense.fields.amount') }}" name="amount"
                                id="amount" type="number" step="0.01" min="0" :value="old('amount', $expense->amount)" required
                                class="w-full p-2 border rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :error="$errors->first('amount')" />

                            <x-forms.text-area label="{{ trans('global.expense.fields.description') }}"
                                name="description" :value="old('description', $expense->description)" :error="$errors->first('description')" />

                            <x-forms.select label="{{ trans('global.expense.fields.quarter') }}" name="quarter"
                                id="quarter" :options="[
                                    ['value' => 1, 'label' => 'Q1'],
                                    ['value' => 2, 'label' => 'Q2'],
                                    ['value' => 3, 'label' => 'Q3'],
                                    ['value' => 4, 'label' => 'Q4'],
                                ]" :selected="$expense->quarter"
                                placeholder="{{ trans('global.pleaseSelect') }}" class="js-single-select" required />
                            @error('quarter')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror

                            <x-forms.input label="{{ trans('global.expense.fields.date') }}" name="date"
                                id="date" type="date" :value="old('date', $expense->date->format('Y-m-d'))" required
                                class="w-full p-2 border rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :error="$errors->first('date')" />
                        </div>

                    </div>
                </div>
            </div>

            <div class="mt-8">
                <x-buttons.primary>
                    {{ trans('global.save') }}
                </x-buttons.primary>
                <a href="{{ route('admin.expense.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 ml-2">
                    {{ trans('global.cancel') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (function waitForJQuery() {
                if (window.jQuery && document.readyState === 'complete') {
                    initializeExpenseForm();
                } else {
                    setTimeout(waitForJQuery, 50);
                }

                function initializeExpenseForm() {
                    const $ = window.jQuery;
                    const $dateInput = $('#date');
                    const $projectSelect = $('.js-single-select[data-name="project_id"]');
                    const $fiscalYearSelect = $('.js-single-select[data-name="fiscal_year_id"]');
                    const $budgetTypeSelect = $('.js-single-select[data-name="budget_type"]');
                    const $availableBudget = $('#available-budget');

                    function updateAvailableBudget() {
                        const projectId = JSON.parse($projectSelect.attr('data-selected') || 'null');
                        const fiscalYearId = JSON.parse($fiscalYearSelect.attr('data-selected') || 'null');
                        const budgetType = JSON.parse($budgetTypeSelect.attr('data-selected') || 'null');

                        if (projectId && fiscalYearId && budgetType) {
                            $.ajax({
                                url: '{{ route('admin.budgets.available') }}',
                                data: {
                                    project_id: projectId,
                                    fiscal_year_id: fiscalYearId,
                                    budget_type: budgetType
                                },
                                success: function(response) {
                                    if (response.available) {
                                        $availableBudget.text(
                                            `Available ${budgetType} budget: ${response.available.toFixed(2)}`
                                        );
                                    } else {
                                        $availableBudget.text(
                                            'No budget allocated for this fiscal year and budget type.');
                                    }
                                },
                                error: function(xhr) {
                                    console.error('Error fetching available budget:', xhr.responseText);
                                    $availableBudget.text('Error fetching budget information.');
                                }
                            });
                        } else {
                            $availableBudget.text('');
                        }
                    }

                    $dateInput.on('change', function() {
                        const date = $(this).val();
                        if (!date) return;

                        $.ajax({
                            url: '{{ route('admin.fiscal-years.by-date') }}',
                            data: {
                                date: date
                            },
                            success: function(response) {
                                if (response.fiscal_year_id) {
                                    $fiscalYearSelect.attr('data-selected', JSON.stringify(response
                                        .fiscal_year_id));
                                    $fiscalYearSelect.trigger('options-updated', {
                                        options: JSON.parse($fiscalYearSelect.attr(
                                            'data-options') || '[]'),
                                        selected: response.fiscal_year_id
                                    });
                                    console.log('Pre-selected fiscal year:', response.fiscal_year_id);
                                    updateAvailableBudget();
                                }
                            },
                            error: function(xhr) {
                                console.error('Error fetching fiscal year:', xhr.responseText);
                            }
                        });
                    });

                    $projectSelect.on('change', updateAvailableBudget);
                    $fiscalYearSelect.on('change', updateAvailableBudget);
                    $budgetTypeSelect.on('change', updateAvailableBudget);

                    // Initial update
                    updateAvailableBudget();
                }
            })();
        </script>
    @endpush
</x-layouts.app>
