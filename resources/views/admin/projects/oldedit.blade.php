<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Project') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('Edit project') }}</p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6 p-6">
                <form class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8"
                    action="{{ route('admin.project.update', $project) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    @if ($errors->any())
                        <div class="col-span-full mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="error-message"
                        class="col-span-full mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="lg:col-span-1 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ __('Project Information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.select label="Directorate" name="directorate_id" id="directorate_id"
                                    :options="collect($directorates)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('directorate_id', $project->directorate_id)" placeholder="Select directorate"
                                    :error="$errors->first('directorate_id')" class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="Department" name="department_id" id="department_select"
                                    :options="collect($departments)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('department_id', $project->department_id)" placeholder="Select department"
                                    allow-clear="true"
                                    data-selected="{{ old('department_id', $project->department_id) }}"
                                    :error="$errors->first('department_id')" class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="Project Manager" name="project_manager"
                                    id="project_manager_select" :options="collect($users)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('project_manager', $project->project_manager)"
                                    placeholder="Select project manager" allow-clear="true"
                                    data-selected="{{ old('project_manager', $project->project_manager) }}"
                                    :error="$errors->first('project_manager')" class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Title" name="title" type="text" :value="old('title', $project->title)"
                                    :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="Description" name="description" :value="old('description', $project->description)"
                                    :error="$errors->first('description')" />
                            </div>

                            <div>
                                <x-forms.date-input label="Start Date" name="start_date" :value="old('start_date', $project->start_date?->format('Y-m-d'))"
                                    :error="$errors->first('start_date')" />
                            </div>

                            <div>
                                <x-forms.date-input label="End Date" name="end_date" :value="old('end_date', $project->end_date?->format('Y-m-d'))"
                                    :error="$errors->first('end_date')" />
                            </div>

                            <div>
                                <x-forms.select label="Status" name="status_id" id="status_id" :options="collect($statuses)
                                    ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                    ->values()
                                    ->all()"
                                    :selected="old('status_id', $project->status_id)" placeholder="Select status" :error="$errors->first('status_id')"
                                    class="js-single-select" />
                            </div>

                            <div>
                                <x-forms.select label="Priority" name="priority_id" id="priority_id" :options="collect($priorities)
                                    ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                    ->values()
                                    ->all()"
                                    :selected="old('priority_id', $project->priority_id)" placeholder="Select priority" :error="$errors->first('priority_id')"
                                    class="js-single-select" />
                            </div>
                        </div>
                    </div>

                    <div
                        class="lg:col-span-2 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            <div class="text-indigo-600 dark:text-indigo-400">
                                <i class="fa fa-dollar-sign text-xl"></i>
                            </div>
                            <h5 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                                {{ trans('global.project.headers.budget_details') }}
                            </h5>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="budget-entries-table"
                                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-100 dark:bg-gray-600">
                                    <tr>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ trans('global.project.fields.fiscal_year_id') }}
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ trans('global.project.fields.total_budget') }}
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ trans('global.project.fields.internal_budget') }}
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ trans('global.project.fields.foreign_loan_budget') }}
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ trans('global.project.fields.foreign_subsidy_budget') }}
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    @php
                                        $initialBudgets = old(
                                            'budgets',
                                            $project->budgets
                                                ->map(function ($budget) {
                                                    return [
                                                        'fiscal_year_id' => $budget->fiscal_year_id,
                                                        'total_budget' => $budget->total_budget,
                                                        'internal_budget' => $budget->internal_budget,
                                                        'foreign_loan_budget' => $budget->foreign_loan_budget,
                                                        'foreign_subsidy_budget' => $budget->foreign_subsidy_budget,
                                                    ];
                                                })
                                                ->toArray(),
                                        );
                                        if (empty($initialBudgets)) {
                                            $initialBudgets = [[]];
                                        }
                                    @endphp

                                    @foreach ($initialBudgets as $index => $budget)
                                        <tr class="budget-entry-row">
                                            <td class="px-3 py-4 whitespace-nowrap">
                                                <x-forms.select name="budgets[{{ $index }}][fiscal_year_id]"
                                                    id="fiscal_year_id_{{ $index }}" :options="collect($fiscalYears)
                                                        ->map(
                                                            fn($label, $value) => [
                                                                'value' => (string) $value,
                                                                'label' => $label,
                                                            ],
                                                        )
                                                        ->values()
                                                        ->all()"
                                                    :selected="old(
                                                        'budgets.' . $index . '.fiscal_year_id',
                                                        $budget['fiscal_year_id'] ?? '',
                                                    )" placeholder="{{ trans('global.pleaseSelect') }}"
                                                    :error="$errors->first('budgets.' . $index . '.fiscal_year_id')" class="js-single-select"
                                                    data-options="{{ json_encode(collect($fiscalYears)->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all()) }}"
                                                    data-selected="{{ old('budgets.' . $index . '.fiscal_year_id', $budget['fiscal_year_id'] ?? '') }}" />
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <x-forms.input name="budgets[{{ $index }}][total_budget]"
                                                    type="number" step="0.01" min="0" :value="old(
                                                        'budgets.' . $index . '.total_budget',
                                                        $budget['total_budget'] ?? '',
                                                    )"
                                                    :error="$errors->first('budgets.' . $index . '.total_budget')" class="w-full" />
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <x-forms.input name="budgets[{{ $index }}][internal_budget]"
                                                    type="number" step="0.01" min="0" :value="old(
                                                        'budgets.' . $index . '.internal_budget',
                                                        $budget['internal_budget'] ?? '',
                                                    )"
                                                    :error="$errors->first('budgets.' . $index . '.internal_budget')" class="w-full" />
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <x-forms.input
                                                    name="budgets[{{ $index }}][foreign_loan_budget]"
                                                    type="number" step="0.01" min="0" :value="old(
                                                        'budgets.' . $index . '.foreign_loan_budget',
                                                        $budget['foreign_loan_budget'] ?? '',
                                                    )"
                                                    :error="$errors->first(
                                                        'budgets.' . $index . '.foreign_loan_budget',
                                                    )" class="w-full" />
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <x-forms.input
                                                    name="budgets[{{ $index }}][foreign_subsidy_budget]"
                                                    type="number" step="0.01" min="0" :value="old(
                                                        'budgets.' . $index . '.foreign_subsidy_budget',
                                                        $budget['foreign_subsidy_budget'] ?? '',
                                                    )"
                                                    :error="$errors->first(
                                                        'budgets.' . $index . '.foreign_subsidy_budget',
                                                    )" class="w-full" />
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                <button type="button"
                                                    class="remove-budget-btn inline-flex items-center justify-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-red-600
                                                        {{ count($initialBudgets) === 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ count($initialBudgets) === 1 ? 'disabled' : '' }}>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="add-budget-row"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-indigo-700 dark:hover:bg-indigo-800 dark:focus:ring-indigo-600">
                            <i class="fa fa-plus mr-2"></i> {{ trans('global.project.fields.add_budget') }}
                        </button>
                    </div>

                    <div class="col-span-full mt-8">
                        <x-buttons.primary>{{ __('Update') }}</x-buttons.primary>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.fiscalYears = @json(collect($fiscalYears)->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all());
        console.log('Fiscal years available:', window.fiscalYears);
    </script>

    @vite(['resources/js/departmentLoader.js', 'resources/js/projectBudget.js'])
</x-layouts.app>
