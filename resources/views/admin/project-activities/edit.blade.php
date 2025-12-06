{{-- resources/views/admin/project-activities/edit.blade.php --}}
<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.projectActivity.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.edit') }} {{ trans('global.projectActivity.title_singular') }}
        </p>

        <!-- Excel Actions - Optional for edit, or remove if not needed -->
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <a href="{{ route('admin.projectActivity.template') }}"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                title="Download Excel Template">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Download Excel Template
            </a>
            <a href="{{ route('admin.projectActivity.uploadForm') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                title="Upload Excel">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                    </path>
                </svg>
                Upload Excel
            </a>
        </div>
    </div>

    <form id="projectActivity-form" class="w-full"
        action="{{ route('admin.projectActivity.update', [$project->id, $fiscalYear->id]) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT') {{-- For update, if needed; adjust route if PATCH/PUT --}}

        <div
            class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Project Selection - Pre-selected -->
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="$projectId"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>

                <!-- Fiscal Year Selection - Pre-selected -->
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYears" :selected="$fiscalYearId"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>
            </div>
        </div>

        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">

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
                class="col-span-full mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-gray-700 dark:text-red-200">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <!-- Capital Expenditure Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.projectActivity.headers.capital') }}
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="capital-activities"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        {{ trans('global.projectActivity.fields.program') }}
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Total Budget
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Expenses Till Date
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Planned Budget of this F/Y
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q1
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q2
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q3
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q4
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                                @php $capitalFormIndex = 1; @endphp
                                @foreach ($capitalActivities->whereNull('parent_id') as $topActivity)
                                    @php $topFormIndex = $capitalFormIndex; @endphp
                                    <tr class="projectActivity-row" data-depth="0" data-index="{{ $capitalFormIndex }}"
                                        data-id="{{ $topActivity->id }}" data-parent-id="">
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                            {{-- Numbering handled by JS --}}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                            <input type="hidden" name="capital[{{ $capitalFormIndex }}][id]"
                                                value="{{ $topActivity->id }}" />
                                            <input name="capital[{{ $capitalFormIndex }}][program]" type="text"
                                                value="{{ old('capital.' . $capitalFormIndex . '.program', $topActivity->program) }}"
                                                class="w-full border-0 p-1 tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][total_budget]" type="number"
                                                step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.total_budget', $topActivity->total_budget) }}"
                                                class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][total_expense]"
                                                type="number" step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.total_expense', $topActivity->total_expense) }}"
                                                class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][planned_budget]"
                                                type="number" step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.planned_budget', $topActivity->planned_budget) }}"
                                                class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][q1]" type="number"
                                                step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.q1', $topActivity->q1) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][q2]" type="number"
                                                step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.q2', $topActivity->q2) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][q3]" type="number"
                                                step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.q3', $topActivity->q3) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="capital[{{ $capitalFormIndex }}][q4]" type="number"
                                                step="0.01"
                                                value="{{ old('capital.' . $capitalFormIndex . '.q4', $topActivity->q4) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                            <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                        </td>
                                    </tr>
                                    @php $capitalFormIndex++; @endphp

                                    {{-- Level 1 Children --}}
                                    @foreach ($topActivity->children as $level1Activity)
                                        @php $level1FormIndex = $capitalFormIndex; @endphp
                                        <tr class="projectActivity-row" data-depth="1"
                                            data-index="{{ $capitalFormIndex }}" data-id="{{ $level1Activity->id }}"
                                            data-parent-id="{{ $topActivity->id }}">
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                                {{-- Numbering by JS --}}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                                <input type="hidden" name="capital[{{ $capitalFormIndex }}][id]"
                                                    value="{{ $level1Activity->id }}" />
                                                <input type="hidden"
                                                    name="capital[{{ $capitalFormIndex }}][parent_id]"
                                                    value="{{ $topFormIndex }}" />
                                                <input name="capital[{{ $capitalFormIndex }}][program]"
                                                    type="text"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.program', $level1Activity->program) }}"
                                                    class="w-full border-0 p-1 tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][total_budget]"
                                                    type="number" step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.total_budget', $level1Activity->total_budget) }}"
                                                    class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][total_expense]"
                                                    type="number" step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.total_expense', $level1Activity->total_expense) }}"
                                                    class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][planned_budget]"
                                                    type="number" step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.planned_budget', $level1Activity->planned_budget) }}"
                                                    class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][q1]" type="number"
                                                    step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.q1', $level1Activity->q1) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][q2]" type="number"
                                                    step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.q2', $level1Activity->q2) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][q3]" type="number"
                                                    step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.q3', $level1Activity->q3) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="capital[{{ $capitalFormIndex }}][q4]" type="number"
                                                    step="0.01"
                                                    value="{{ old('capital.' . $capitalFormIndex . '.q4', $level1Activity->q4) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                                <div class="flex space-x-2 justify-center">
                                                    <span
                                                        class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                                    <span class="remove-row cursor-pointer text-2xl text-red-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        @php $capitalFormIndex++; @endphp

                                        {{-- Level 2 Children --}}
                                        @foreach ($level1Activity->children as $level2Activity)
                                            <tr class="projectActivity-row" data-depth="2"
                                                data-index="{{ $capitalFormIndex }}"
                                                data-id="{{ $level2Activity->id }}"
                                                data-parent-id="{{ $level1Activity->id }}">
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                                    {{-- Numbering by JS --}}
                                                </td>
                                                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                                    <input type="hidden" name="capital[{{ $capitalFormIndex }}][id]"
                                                        value="{{ $level2Activity->id }}" />
                                                    <input type="hidden"
                                                        name="capital[{{ $capitalFormIndex }}][parent_id]"
                                                        value="{{ $level1FormIndex }}" />
                                                    <input name="capital[{{ $capitalFormIndex }}][program]"
                                                        type="text"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.program', $level2Activity->program) }}"
                                                        class="w-full border-0 p-1 tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][total_budget]"
                                                        type="number" step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.total_budget', $level2Activity->total_budget) }}"
                                                        class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][total_expense]"
                                                        type="number" step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.total_expense', $level2Activity->total_expense) }}"
                                                        class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][planned_budget]"
                                                        type="number" step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.planned_budget', $level2Activity->planned_budget) }}"
                                                        class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][q1]" type="number"
                                                        step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.q1', $level2Activity->q1) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][q2]" type="number"
                                                        step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.q2', $level2Activity->q2) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][q3]" type="number"
                                                        step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.q3', $level2Activity->q3) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="capital[{{ $capitalFormIndex }}][q4]" type="number"
                                                        step="0.01"
                                                        value="{{ old('capital.' . $capitalFormIndex . '.q4', $level2Activity->q4) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                                    <div class="flex space-x-2 justify-center">
                                                        <span class="remove-row cursor-pointer text-2xl text-red-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $capitalFormIndex++; @endphp
                                        @endforeach
                                    @endforeach
                                @endforeach
                                @php $capitalMaxIndex = $capitalFormIndex - 1; @endphp
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-capital-row"
                        class="mt-4 bg-green-500 text-white px-4 py-2 rounded">
                        Add New Row
                    </button>
                    <div class="mt-4 text-lg font-bold">
                        Total Capital Budget: <span id="capital-total">0.00</span>
                    </div>
                </div>
            </div>

            <!-- Recurrent Expenditure Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.projectActivity.headers.recurrent') }}
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="recurrent-activities"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        {{ trans('global.projectActivity.fields.program') }}
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Total Budget
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Expenses Till Date
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Planned Budget of this F/Y
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q1
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q2
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q3
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q4
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
                                @php $recurrentFormIndex = 1; @endphp
                                @foreach ($recurrentActivities->whereNull('parent_id') as $topActivity)
                                    @php $topFormIndex = $recurrentFormIndex; @endphp
                                    <tr class="projectActivity-row" data-depth="0"
                                        data-index="{{ $recurrentFormIndex }}" data-id="{{ $topActivity->id }}"
                                        data-parent-id="">
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                            {{-- Numbering handled by JS --}}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                            <input type="hidden" name="recurrent[{{ $recurrentFormIndex }}][id]"
                                                value="{{ $topActivity->id }}" />
                                            <input name="recurrent[{{ $recurrentFormIndex }}][program]"
                                                type="text"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.program', $topActivity->program) }}"
                                                class="w-full border-0 p-1 tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][total_budget]"
                                                type="number" step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.total_budget', $topActivity->total_budget) }}"
                                                class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][total_expense]"
                                                type="number" step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.total_expense', $topActivity->total_expense) }}"
                                                class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][planned_budget]"
                                                type="number" step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.planned_budget', $topActivity->planned_budget) }}"
                                                class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][q1]" type="number"
                                                step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.q1', $topActivity->q1) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][q2]" type="number"
                                                step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.q2', $topActivity->q2) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][q3]" type="number"
                                                step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.q3', $topActivity->q3) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                            <input name="recurrent[{{ $recurrentFormIndex }}][q4]" type="number"
                                                step="0.01"
                                                value="{{ old('recurrent.' . $recurrentFormIndex . '.q4', $topActivity->q4) }}"
                                                class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                            <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                        </td>
                                    </tr>
                                    @php $recurrentFormIndex++; @endphp

                                    {{-- Level 1 Children --}}
                                    @foreach ($topActivity->children as $level1Activity)
                                        @php $level1FormIndex = $recurrentFormIndex; @endphp
                                        <tr class="projectActivity-row" data-depth="1"
                                            data-index="{{ $recurrentFormIndex }}"
                                            data-id="{{ $level1Activity->id }}"
                                            data-parent-id="{{ $topActivity->id }}">
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                                {{-- Numbering by JS --}}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                                <input type="hidden"
                                                    name="recurrent[{{ $recurrentFormIndex }}][id]"
                                                    value="{{ $level1Activity->id }}" />
                                                <input type="hidden"
                                                    name="recurrent[{{ $recurrentFormIndex }}][parent_id]"
                                                    value="{{ $topFormIndex }}" />
                                                <input name="recurrent[{{ $recurrentFormIndex }}][program]"
                                                    type="text"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.program', $level1Activity->program) }}"
                                                    class="w-full border-0 p-1 tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][total_budget]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.total_budget', $level1Activity->total_budget) }}"
                                                    class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][total_expense]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.total_expense', $level1Activity->total_expense) }}"
                                                    class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][planned_budget]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.planned_budget', $level1Activity->planned_budget) }}"
                                                    class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][q1]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.q1', $level1Activity->q1) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][q2]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.q2', $level1Activity->q2) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][q3]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.q3', $level1Activity->q3) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                <input name="recurrent[{{ $recurrentFormIndex }}][q4]"
                                                    type="number" step="0.01"
                                                    value="{{ old('recurrent.' . $recurrentFormIndex . '.q4', $level1Activity->q4) }}"
                                                    class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                                <div class="flex space-x-2 justify-center">
                                                    <span
                                                        class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                                    <span class="remove-row cursor-pointer text-2xl text-red-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        @php $recurrentFormIndex++; @endphp

                                        {{-- Level 2 Children --}}
                                        @foreach ($level1Activity->children as $level2Activity)
                                            <tr class="projectActivity-row" data-depth="2"
                                                data-index="{{ $recurrentFormIndex }}"
                                                data-id="{{ $level2Activity->id }}"
                                                data-parent-id="{{ $level1Activity->id }}">
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                                    {{-- Numbering by JS --}}
                                                </td>
                                                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                                                    <input type="hidden"
                                                        name="recurrent[{{ $recurrentFormIndex }}][id]"
                                                        value="{{ $level2Activity->id }}" />
                                                    <input type="hidden"
                                                        name="recurrent[{{ $recurrentFormIndex }}][parent_id]"
                                                        value="{{ $level1FormIndex }}" />
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][program]"
                                                        type="text"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.program', $level2Activity->program) }}"
                                                        class="w-full border-0 p-1 tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][total_budget]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.total_budget', $level2Activity->total_budget) }}"
                                                        class="w-full border-0 p-1 text-right total-budget-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][total_expense]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.total_expense', $level2Activity->total_expense) }}"
                                                        class="w-full border-0 p-1 text-right expenses-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input
                                                        name="recurrent[{{ $recurrentFormIndex }}][planned_budget]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.planned_budget', $level2Activity->planned_budget) }}"
                                                        class="w-full border-0 p-1 text-right planned-budget-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][q1]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.q1', $level2Activity->q1) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][q2]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.q2', $level2Activity->q2) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][q3]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.q3', $level2Activity->q3) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                                                    <input name="recurrent[{{ $recurrentFormIndex }}][q4]"
                                                        type="number" step="0.01"
                                                        value="{{ old('recurrent.' . $recurrentFormIndex . '.q4', $level2Activity->q4) }}"
                                                        class="w-full border-0 p-1 text-right quarter-input tooltip-error" />
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                                                    <div class="flex space-x-2 justify-center">
                                                        <span class="remove-row cursor-pointer text-2xl text-red-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $recurrentFormIndex++; @endphp
                                        @endforeach
                                    @endforeach
                                @endforeach
                                @php $recurrentMaxIndex = $recurrentFormIndex - 1; @endphp
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-recurrent-row"
                        class="mt-4 bg-green-500 text-white px-4 py-2 rounded">
                        Add New Row
                    </button>
                    <div class="mt-4 text-lg font-bold">
                        Total Recurrent Budget: <span id="recurrent-total">0.00</span>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <x-buttons.primary id="submit-button" type="submit" :disabled="false">
                    {{ trans('global.save') }}
                </x-buttons.primary>
            </div>
        </div>
    </form>

    @push('scripts')
        <style>
            .error-border {
                border: 2px solid red !important;
            }

            .tooltip-error {
                position: relative;
            }

            .tooltip-error .tippy-box {
                background-color: #ef4444;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
            }

            .tooltip-error .tippy-arrow {
                color: #ef4444;
            }

            .projectActivity-row[data-depth="1"] td:nth-child(2) {
                padding-left: 20px;
            }

            .projectActivity-row[data-depth="2"] td:nth-child(2) {
                padding-left: 40px;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>

        <script>
            $(document).ready(function() {
                const $ = jQuery;
                let capitalIndex = {{ $capitalMaxIndex + 1 }};
                let recurrentIndex = {{ $recurrentMaxIndex + 1 }};
                const tippyInstances = new WeakMap();

                // Helper to parse numeric input safely (handle integers/decimals)
                function parseNumeric(val) {
                    return parseFloat(val.replace(/,/g, '')) || 0;
                }

                // Updated selector for project_id change
                $('.js-single-select[data-name="project_id"] .js-hidden-input').on('change', function() {
                    $('#projectActivity-form').attr('action',
                        '{{ route('admin.projectActivity.update', [$project->id, $fiscalYear->id]) }}');
                });

                // Check for partial exceeds (sum of entered quarters > planned, empty as 0)
                function hasPartialExceed(section) {
                    let hasExceed = false;
                    $(`#${section}-activities .projectActivity-row`).each(function() {
                        const $row = $(this);
                        const $plannedBudget = $row.find('.planned-budget-input');
                        const plannedBudget = parseNumeric($plannedBudget.val());

                        if (plannedBudget === 0) return true; // Skip if planned not entered

                        let quarterSum = 0;
                        $row.find('.quarter-input').each(function() {
                            quarterSum += parseNumeric($(this).val());
                        });

                        if (quarterSum > plannedBudget + 0.01) {
                            hasExceed = true;
                            const message =
                                `Partial quarters sum (${quarterSum.toFixed(2)}) already exceeds planned budget (${plannedBudget.toFixed(2)})`;
                            $plannedBudget.addClass('error-border');
                            $row.find('.quarter-input').addClass('error-border');
                            updateTooltip($plannedBudget, message);
                            $row.find('.quarter-input').each(function() {
                                updateTooltip($(this), message);
                            });
                        }
                    });
                    return hasExceed;
                }

                // Check if table is valid
                function isTableValid(section) {
                    $(`#${section}-activities .projectActivity-row`).each(function() {
                        const index = $(this).data('index');
                        validateRow(section, index);
                    });
                    validateParentRows(section);

                    const hasFullErrors = $(`#${section}-activities .error-border`).length > 0;
                    const hasPartial = hasPartialExceed(section);
                    const hasErrors = hasFullErrors || hasPartial;

                    console.log(`Table ${section} validation: ${!hasErrors}`);
                    return !hasErrors;
                }

                function addRow(section, parentIndex = null, depth = 0) {
                    if (!isTableValid(section)) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text(
                            "Cannot add row: Please correct validation errors (Planned Budget must equal sum of quarters; child sums must not exceed parent)."
                        );
                        console.warn(`Cannot add row in ${section}: Validation errors present`);
                        return;
                    }

                    if (depth > 2) {
                        console.warn(`Cannot add row in ${section}: Maximum depth (2) reached`);
                        return;
                    }

                    const type = section === 'capital' ? 'capital' : 'recurrent';
                    const index = type === 'capital' ? capitalIndex++ : recurrentIndex++;
                    const $tbody = $(`#${section}-tbody`);

                    if (!$tbody.length) {
                        console.error(`tbody for ${section} not found`);
                        return;
                    }

                    let hiddenParentInput = '';
                    if (parentIndex !== null && parentIndex !== '') {
                        hiddenParentInput =
                            `<input type="hidden" name="${type}[${index}][parent_id]" value="${parentIndex}">`;
                    }

                    const html = `
                    <tr class="projectActivity-row" data-depth="${depth}" data-index="${index}" data-id="${index}" ${parentIndex !== null && parentIndex !== '' ? `data-parent-id="${parentIndex}"` : 'data-parent-id=""'}>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200"></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1">
                            ${hiddenParentInput}
                            <input name="${type}[${index}][program]" type="text" class="w-full border-0 p-1 tooltip-error" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][total_expense]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][planned_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right">
                            <input name="${type}[${index}][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center">
                            <div class="flex space-x-2 justify-center">
                                ${depth < 2 ? `<span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>` : ''}
                                ${(depth > 0 || index > 1) ? `<span class="remove-row cursor-pointer text-2xl text-red-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </span>` : ''}
                            </div>
                        </td>
                    </tr>
                `;

                    let subTreeRows = [];
                    if (parentIndex !== null && parentIndex !== '') {
                        const $parentRow = $tbody.find(`tr[data-index="${parentIndex}"]`);
                        if (!$parentRow.length) {
                            console.error(`Parent row ${parentIndex} not found for insertion in ${section}`);
                            return;
                        }

                        const collectSubTree = (pId) => {
                            const $children = $tbody.find(`tr[data-parent-id="${pId}"]`);
                            $children.each(function() {
                                const childId = $(this).data('id') || '';
                                subTreeRows.push($(this));
                                collectSubTree(childId);
                            });
                        };
                        collectSubTree(parentIndex);

                        const $lastRow = subTreeRows.length ? subTreeRows[subTreeRows.length - 1] : $parentRow;
                        console.log(
                            `Inserting row ${index} in ${section} after ${$lastRow.data('id')} (depth: ${depth})`
                        );
                        $lastRow.after(html);
                    } else {
                        console.log(`Appending row ${index} to ${section}`);
                        $tbody.append(html);
                    }

                    const $newRow = $tbody.find(`tr[data-index="${index}"]`);
                    console.log(`New row ${index} added, depth: ${depth}`);

                    updateRowNumbers(section);
                    updateTotals();

                    if (parentIndex !== null && parentIndex !== '') {
                        validateParentRow(section, parentIndex);
                    }

                    initializeTooltips($newRow.find('.tooltip-error'));
                }

                function addSubRow($row) {
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const parentIndex = $row.data('index'); // Use form index for mapping
                    const depth = $row.data('depth') + 1;

                    if (depth > 2) {
                        console.warn(`Max depth reached for ${parentIndex}`);
                        return;
                    }

                    if (!isTableValid(section)) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text(
                            "Cannot add sub-row: Please correct validation errors (Planned Budget must equal sum of quarters; child sums must not exceed parent)."
                        );
                        console.warn(`Cannot add sub-row in ${section}: Validation errors present`);
                        return;
                    }

                    console.log(`Adding sub-row under ${parentIndex} at depth ${depth}`);
                    addRow(section, parentIndex, depth);
                }

                $(document).off('click', '.add-sub-row').on('click', '.add-sub-row', function(e) {
                    e.preventDefault();
                    addSubRow($(this).closest('tr'));
                });

                $('#add-capital-row').on('click', function() {
                    addRow('capital');
                });

                $('#add-recurrent-row').on('click', function() {
                    addRow('recurrent');
                });

                // Recursive remove for subtree
                function removeRowAndSubtree($row, section) {
                    const directChildren = $(`#${section}-tbody tr[data-parent-id="${$row.data('id')}"]`);
                    directChildren.each(function() {
                        removeRowAndSubtree($(this), section);
                    });
                    $row.remove();
                }

                $(document).on('click', '.remove-row', function() {
                    const $row = $(this).closest('tr');
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const parentIndex = $row.data('parent-id');
                    const index = $row.data('index');

                    removeRowAndSubtree($row, section);
                    console.log(`Removed row ${index} in ${section}`);

                    updateRowNumbers(section);
                    updateTotals();
                    validateParentRows(section);

                    if (parentIndex) {
                        validateParentRow(section, parentIndex);
                    }
                });

                function updateRowNumbers(section) {
                    const $rows = $(`#${section}-activities tbody tr`);
                    let topLevelCount = 0;
                    let levelOneCounts = {};
                    let levelTwoCounts = {};

                    $rows.each(function() {
                        const $row = $(this);
                        const depth = $row.data('depth');
                        const parentId = $row.data('parent-id');
                        let number = '';

                        if (depth === 0) {
                            topLevelCount++;
                            number = topLevelCount.toString();
                            levelOneCounts[topLevelCount] = 0;
                        } else if (depth === 1) {
                            const parentRow = $rows.filter(`[data-id="${parentId}"]`);
                            const parentNumber = parentRow.find('td:first').text();
                            levelOneCounts[parentNumber] = (levelOneCounts[parentNumber] || 0) + 1;
                            number = `${parentNumber}.${levelOneCounts[parentNumber]}`;
                            levelTwoCounts[number] = 0;
                        } else if (depth === 2) {
                            const parentRow = $rows.filter(`[data-id="${parentId}"]`);
                            const parentNumber = parentRow.find('td:first').text();
                            levelTwoCounts[parentNumber] = (levelTwoCounts[parentNumber] || 0) + 1;
                            number = `${parentNumber}.${levelTwoCounts[parentNumber]}`;
                        }

                        $row.find('td:first').text(number);
                    });
                }

                function updateTotals() {
                    let capitalTotal = 0;
                    $('#capital-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(function() {
                        capitalTotal += parseNumeric($(this).val());
                    });
                    $('#capital-total').text(capitalTotal.toFixed(2));

                    let recurrentTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(
                        function() {
                            recurrentTotal += parseNumeric($(this).val());
                        });
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));
                }

                // Updated validateRow: Only show error when Q4 is filled (cleaner UX)
                function validateRow(section, index) {
                    const $row = $(`#${section}-activities tr[data-index="${index}"]`);
                    const $plannedBudget = $row.find('.planned-budget-input');
                    const $quarters = $row.find('.quarter-input');
                    const $q4 = $row.find('.quarter-input[name*="[q4]"]');

                    let quarterSum = 0;
                    $quarters.each(function() {
                        quarterSum += parseNumeric($(this).val());
                    });

                    const plannedBudget = parseNumeric($plannedBudget.val());

                    let message = '';
                    let isError = false;

                    // Only validate and show error if Q4 has been filled
                    const q4Filled = $q4.val().trim() !== '';

                    if (q4Filled && Math.abs(quarterSum - plannedBudget) > 0.01) {
                        isError = true;
                        if (quarterSum > plannedBudget) {
                            message =
                                `Quarters sum (${quarterSum.toFixed(2)}) exceeds planned budget (${plannedBudget.toFixed(2)})`;
                        } else {
                            message =
                                `Quarters sum (${quarterSum.toFixed(2)}) is less than planned budget (${plannedBudget.toFixed(2)}). Planned budget must equal sum of quarters.`;
                        }
                    }

                    if (isError) {
                        $plannedBudget.addClass('error-border');
                        $quarters.addClass('error-border');
                        updateTooltip($plannedBudget, message);
                        $quarters.each(function() {
                            updateTooltip($(this), message);
                        });
                    } else {
                        $plannedBudget.removeClass('error-border');
                        $quarters.removeClass('error-border');
                        updateTooltip($plannedBudget, '');
                        $quarters.each(function() {
                            updateTooltip($(this), '');
                        });
                    }

                    console.log(
                        `Row ${index} validated: quarters ${quarterSum}, planned ${plannedBudget}, Q4 filled: ${q4Filled}, error: ${isError}`
                    );
                }

                function getFieldFromInput($input) {
                    const name = $input.attr('name');
                    if (name.includes('[total_budget]')) return 'total_budget';
                    if (name.includes('[total_expense]')) return 'total_expense';
                    if (name.includes('[planned_budget]')) return 'planned_budget';
                    if (name.includes('[q1]')) return 'q1';
                    if (name.includes('[q2]')) return 'q2';
                    if (name.includes('[q3]')) return 'q3';
                    if (name.includes('[q4]')) return 'q4';
                    return null;
                }

                function validateParentRow(section, parentIndex) {
                    if (!parentIndex) return;

                    const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                    if (!$parentRow.length) {
                        console.error(`Parent ${parentIndex} not found`);
                        return;
                    }

                    const $childRows = $(`#${section}-activities tr[data-parent-id="${$parentRow.data('id')}"]`);
                    if ($childRows.length === 0) return;

                    const childInputs = {
                        'total_budget': '.total-budget-input',
                        'total_expense': '.expenses-input',
                        'planned_budget': '.planned-budget-input',
                        'q1': '.quarter-input[name*="[q1]"]',
                        'q2': '.quarter-input[name*="[q2]"]',
                        'q3': '.quarter-input[name*="[q3]"]',
                        'q4': '.quarter-input[name*="[q4]"]'
                    };

                    for (const [field, selector] of Object.entries(childInputs)) {
                        const $parentInput = $parentRow.find(selector);
                        if (!$parentInput.length) continue;

                        let childSum = 0;
                        $childRows.each(function() {
                            const $childInput = $(this).find(selector);
                            childSum += parseNumeric($childInput.val());
                        });

                        const parentValue = parseNumeric($parentInput.val());

                        if (childSum > parentValue + 0.01) {
                            // Only error on exceed for parent-child
                            const message =
                                `Children sum (${childSum.toFixed(2)}) exceeds parent ${field} (${parentValue.toFixed(2)})`;
                            $parentInput.addClass('error-border');
                            $childRows.find(selector).addClass('error-border');
                            updateTooltip($parentInput, message);
                            $childRows.each(function() {
                                const $childInput = $(this).find(selector);
                                updateTooltip($childInput, message);
                            });
                        } else {
                            // Don't clear errors on parent input - it may have its own validation errors
                            // Only clear errors on child inputs if they don't have their own validation errors
                            $childRows.each(function() {
                                const $childRow = $(this);
                                const $childInput = $childRow.find(selector);

                                // Check if this child has its own validation error (quarters != planned)
                                // Only clear if it's specifically a parent-child error
                                const currentTooltip = tippyInstances.get($childInput[0])?.props.content || '';
                                if (currentTooltip.includes('Children sum') || currentTooltip.includes(
                                        'exceeds parent')) {
                                    $childInput.removeClass('error-border');
                                    updateTooltip($childInput, '');
                                }
                            });
                        }
                    }

                    // Recurse for grandparent if needed
                    validateParentRow(section, $parentRow.data('parent-id'));
                }

                function validateParentRows(section) {
                    const $rows = $(`#${section}-activities tr[data-parent-id]`);
                    const parentIndices = new Set();
                    $rows.each(function() {
                        parentIndices.add($(this).data('parent-id'));
                    });
                    parentIndices.forEach(pId => validateParentRow(section, pId));
                }

                function initializeTooltips($elements) {
                    $elements.each(function() {
                        if (!tippyInstances.has(this)) {
                            tippyInstances.set(this, tippy(this, {
                                content: '',
                                trigger: 'manual',
                                placement: 'top',
                                arrow: true,
                                duration: [200, 0]
                            }));
                        }
                    });
                }

                function updateTooltip($element, message) {
                    const tippyInstance = tippyInstances.get($element[0]);
                    if (tippyInstance) {
                        tippyInstance.setContent(message);
                        if (message) {
                            tippyInstance.show();
                        } else {
                            tippyInstance.hide();
                        }
                    }
                }

                // REFACTORED: Single global handler for all validation + capping (no auto-calc)
                $(document).on('input', '.total-budget-input, .expenses-input, .planned-budget-input, .quarter-input',
                    function() {
                        const $input = $(this);
                        const $row = $input.closest('tr');
                        const section = $row.closest('table').attr('id').replace('-activities', '');
                        const index = $row.data('index');
                        const depth = $row.data('depth') || 0;
                        const field = getFieldFromInput($input);

                        console.log(
                            `Global input in ${section} row ${index} (depth ${depth}): ${field} = ${$input.val()}`
                        );

                        // CAPS FOR CHILDREN ONLY (merged from restrictChildInputs)
                        if (depth > 0 && field && ['total_budget', 'total_expense', 'planned_budget', 'q1', 'q2',
                                'q3', 'q4'
                            ].includes(field)) {
                            const parentIndex = $row.data('parent-id'); // Form index
                            const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                            const $siblingRows = $(
                                `#${section}-activities tr[data-parent-id="${$parentRow.data('id')}"]`).not(
                                $row);

                            const selector = field === 'total_budget' ? '.total-budget-input' :
                                field === 'total_expense' ? '.expenses-input' :
                                field === 'planned_budget' ? '.planned-budget-input' :
                                `.quarter-input[name*="[${field}]"]`;

                            const $parentInput = $parentRow.find(selector);
                            const parentValue = parseNumeric($parentInput.val());
                            let childValue = parseNumeric($input.val());
                            let sumSiblings = 0;

                            $siblingRows.each(function() {
                                sumSiblings += parseNumeric($(this).find(selector).val());
                            });

                            const maxAllowed = Math.max(0, parentValue - sumSiblings);

                            if (childValue > maxAllowed + 0.01) {
                                childValue = maxAllowed;
                                $input.val(childValue.toFixed(childValue % 1 === 0 ? 0 : 2));
                                $input.addClass('error-border');
                                updateTooltip($input,
                                    `Capped at remaining (${maxAllowed.toFixed(2)}) for ${field}`);
                                $parentInput.addClass('error-border');
                                updateTooltip($parentInput, `Children sum for ${field} exceeds parent`);
                            } else {
                                $input.removeClass('error-border');
                                updateTooltip($input, '');
                            }

                            console.log(
                                `Child capping applied for ${field}: max ${maxAllowed}, set to ${childValue}`);
                        }

                        // ALWAYS VALIDATE PARENTS FIRST (hierarchy)
                        validateParentRows(section);

                        // THEN VALIDATE ROW (equality: sum == planned, catches < and >)
                        // This ensures row validation happens after parent-child validation
                        validateRow(section, index);

                        // UPDATE TOTALS
                        updateTotals();
                    });

                // Validate numerics on blur (optional, for submission)
                $(document).on('blur', '.numeric-input', function() {
                    const val = $(this).val();
                    const num = parseNumeric(val);
                    if (!isNaN(num) && num >= 0) {
                        $(this).val(num.toFixed(num % 1 === 0 ? 0 : 2)); // Format without forced decimals
                    }
                });

                initializeTooltips($('.tooltip-error'));

                // FORCE VALIDATE ALL ON LOAD (catches existing mismatches)
                ['capital', 'recurrent'].forEach(section => {
                    $(`#${section}-activities .projectActivity-row`).each(function() {
                        const index = $(this).data('index');
                        validateRow(section, index);
                    });
                    validateParentRows(section);
                });

                // Form submission
                const $form = $('#projectActivity-form');
                const $submitButton = $('#submit-button');

                $form.on('submit', function(e) {
                    e.preventDefault();

                    if ($submitButton.prop('disabled')) return;

                    let hasErrors = false;

                    ['capital', 'recurrent'].forEach(section => {
                        $(`#${section}-activities .projectActivity-row`).each(function() {
                            const $row = $(this);
                            const index = $row.data('index');
                            const $inputs = $row.find(
                                'input[name*="[program]"], .numeric-input');

                            $inputs.each(function() {
                                const $input = $(this);
                                const value = $input.val().trim();

                                if (!$input.is('[name*="[program]"]') && (!value ||
                                        isNaN(parseNumeric(value)) || parseNumeric(
                                            value) < 0)) {
                                    $input.addClass('error-border');
                                    updateTooltip($input,
                                        'Valid non-negative number required');
                                    hasErrors = true;
                                } else if (!$input.is('[name*="[program]"]') && value &&
                                    !/^[0-9]+(\.[0-9]{1,2})?$/.test(value)) {
                                    $input.addClass('error-border');
                                    updateTooltip($input,
                                        'Invalid format (up to 2 decimals)');
                                    hasErrors = true;
                                } else {
                                    $input.removeClass('error-border');
                                    updateTooltip($input, '');
                                }
                            });

                            // For submission, force validation treating empty quarters as 0
                            const $quarters = $row.find('.quarter-input');
                            const originals = {};

                            $quarters.each(function() {
                                originals[$(this).attr('name')] = $(this).val();
                                if ($(this).val().trim() === '') $(this).val('0');
                            });

                            validateRow(section, index);

                            // Restore originals
                            $quarters.each(function() {
                                const name = $(this).attr('name');
                                $(this).val(originals[name] || '');
                            });

                            if ($row.find('.error-border').length > 0) hasErrors = true;
                        });

                        validateParentRows(section);

                        if ($(`#${section}-activities .error-border`).length > 0) hasErrors = true;
                    });

                    if (hasErrors) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text("Please correct the validation errors before submitting.");
                        return;
                    }

                    $submitButton.prop('disabled', true).addClass('opacity-50 cursor-not-allowed').text(
                        '{{ trans('global.saving') }}...');

                    // Ensure parent_id hidden for dynamic rows (already set in addRow)

                    $.ajax({
                        url: '{{ route('admin.projectActivity.update', [$project->id, $fiscalYear->id]) }}',
                        method: 'POST',
                        data: new FormData($form[0]),
                        processData: false,
                        contentType: false,
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(response) {
                            window.location.href = '{{ route('admin.projectActivity.index') }}';
                        },
                        error: function(xhr) {
                            $submitButton.prop('disabled', false).removeClass(
                                'opacity-50 cursor-not-allowed').text(
                                '{{ trans('global.save') }}');

                            let errorMessage = xhr.responseJSON?.message ||
                                "Failed to update activities.";

                            if (xhr.responseJSON?.errors) {
                                const errors = xhr.responseJSON.errors;
                                let errorText = errorMessage + ":<br>";

                                $('.tooltip-error').removeClass('error-border');

                                for (const [index, messages] of Object.entries(errors)) {
                                    const section = messages.some(msg => msg.includes('capital')) ?
                                        'capital' : 'recurrent';
                                    const $row = $(
                                        `#${section}-activities tr[data-index="${index}"]`);

                                    messages.forEach(msg => {
                                        errorText += `Row ${parseInt(index)}: ${msg}<br>`;
                                        const fieldMatch = msg.match(
                                            /(program|total_budget|total_expense|planned_budget|q[1-4])/i
                                        );

                                        if (fieldMatch) {
                                            const field = fieldMatch[1];
                                            $row.find(`input[name*="[${field}]"]`).addClass(
                                                'error-border');
                                            updateTooltip($row.find(
                                                `input[name*="[${field}]"]`), msg);
                                        }
                                    });
                                }

                                $("#error-text").html(errorText);
                            } else {
                                $("#error-text").text(errorMessage);
                            }

                            $("#error-message").removeClass("hidden");
                        }
                    });
                });

                $("#close-error").on('click', function() {
                    $("#error-message").addClass("hidden");
                    $("#error-text").text("");
                    $('.tooltip-error').removeClass('error-border');
                    $('.tooltip-error').each(function() {
                        updateTooltip($(this), '');
                    });
                });

                updateRowNumbers('capital');
                updateRowNumbers('recurrent');
                updateTotals();
                validateParentRows('capital');
                validateParentRows('recurrent');
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                if (projectHidden) {
                    projectHidden.addEventListener('change', function() {
                        const url = new URL(window.location);
                        if (this.value) {
                            url.searchParams.set('project_id', this.value);
                        } else {
                            url.searchParams.delete('project_id');
                        }
                        window.location.href = url.toString();
                    });
                }

                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                if (fiscalHidden) {
                    fiscalHidden.addEventListener('change', function() {
                        // Add reload if needed
                    });
                }
            });
        </script>

        <script>
            function syncDownloadValues() {
                const projectId = $('.js-single-select[data-name="project_id"] .js-hidden-input').val() || '';
                const fiscalYearId = $('.js-single-select[data-name="fiscal_year_id"] .js-hidden-input').val() || '';

                $('#download-project-hidden').val(projectId);
                $('#download-fiscal-hidden').val(fiscalYearId);

                if (!projectId || !fiscalYearId) {
                    alert('Please select a project and fiscal year before downloading the template.');
                    return false;
                }

                return true;
            }
        </script>
    @endpush
</x-layouts.app>
