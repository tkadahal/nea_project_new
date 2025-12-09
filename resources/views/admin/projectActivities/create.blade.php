<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.projectActivity.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.projectActivity.title_singular') }}
        </p>
        <!-- Excel Actions -->
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <form method="GET" action="{{ route('admin.projectActivity.template') }}" class="inline-flex">
                <input type="hidden" name="project_id" id="download-project-hidden" value="">
                <input type="hidden" name="fiscal_year_id" id="download-fiscal-hidden" value="">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                    title="Download Excel Template" onclick="return syncDownloadValues()">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ trans('global.projectActivity.excel.download') }}
                </button>
            </form>
            <a href="{{ route('admin.projectActivity.uploadForm') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                title="Upload Excel">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                    </path>
                </svg>
                {{ trans('global.projectActivity.excel.upload') }}
            </a>
            <!-- New Button: Load Existing Program -->
            <button type="button" id="load-existing-program"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                title="Load Existing Program">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Load Existing Program
            </button>
        </div>
    </div>
    <form id="projectActivity-form" class="w-full" action="{{ route('admin.projectActivity.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="total_budget" id="hidden-total-budget" value="0.00">
        <input type="hidden" name="total_planned_budget" id="hidden-total-planned-budget" value="0.00">
        <div
            class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.projectActivity.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="$selectedProjectId ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.projectActivity.fields.fiscal_year_id') }}"
                        name="fiscal_year_id" id="fiscal_year_id" :options="$fiscalYears" :selected="collect($fiscalYears)->firstWhere('selected', true)['value'] ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>
            </div>
            <div id="budget-display" class="mt-2">
                <span class="block text-sm text-gray-500 dark:text-gray-400">
                    {{ trans('global.projectActivity.info.budgetInfo') }}
                </span>
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
            <!-- Capital Budget Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.projectActivity.headers.capital') }}
                    </h3>
                    <div class="overflow-x-auto relative">
                        <table id="capital-activities"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600 table-fixed w-[1800px]">
                            <thead>
                                <!-- Row 1: Group Headers -->
                                <tr class="bg-gray-200 dark:bg-gray-600 header-row-1">
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-40 bg-gray-200 dark:bg-gray-600 left-sticky"
                                        rowspan="2">#</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-80 sticky left-12 z-40 bg-gray-200 dark:bg-gray-600 left-sticky"
                                        rowspan="2">{{ trans('global.projectActivity.fields.program') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.total_budget') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.total_expense') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.planned_budget') }}
                                    </th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q1') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q2') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q3') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q4') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 sticky right-0 z-40 bg-gray-200 dark:bg-gray-600 right-sticky"
                                        rowspan="2">{{ trans('global.action') }}</th>
                                </tr>
                                <!-- Row 2: Sub-Headers (Exactly 14 <th>s to match 7 colspans × 2) -->
                                <tr class="bg-gray-200 dark:bg-gray-600 header-row-2">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                                <tr class="projectActivity-row" data-depth="0" data-index="1">
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
                                        1
                                    </td>
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                                        <input name="capital[1][program]" type="text"
                                            value="{{ old('capital.1.program') }}"
                                            class="w-full border-0 p-1 tooltip-error" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][total_budget_quantity]" type="text" min="0"
                                            step="1" value="{{ old('capital.1.total_budget_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="capital[1][total_budget]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.total_budget') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][total_expense_quantity]" type="text"
                                            value="{{ old('capital.1.total_expense_quantity') }}" placeholder="0"
                                            class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="capital[1][total_expense]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.total_expense') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][planned_budget_quantity]" type="text"
                                            value="{{ old('capital.1.planned_budget_quantity') }}" placeholder="0"
                                            class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="capital[1][planned_budget]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.planned_budget') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="capital[1][q1_quantity]" type="text" min="0"
                                            step="1" value="{{ old('capital.1.q1_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.q1') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="capital[1][q2_quantity]" type="text" min="0"
                                            step="1" value="{{ old('capital.1.q2_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.q2') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="capital[1][q3_quantity]" type="text" min="0"
                                            step="1" value="{{ old('capital.1.q3_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.q3') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="capital[1][q4_quantity]" type="text" min="0"
                                            step="1" value="{{ old('capital.1.q4_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="capital[1][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('capital.1.q4') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
                                        <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-capital-row"
                        class="mt-4 bg-purple-500 text-white px-4 py-2 rounded">
                        <span class="add-sub-row cursor-pointer text-2xl text-white-400">+</span>
                        {{ trans('global.projectActivity.fields.add_new_row') }}
                    </button>
                    <div class="mt-4 flex justify-between">
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_budget') }}:
                            <span id="capital-total">
                                0.00
                            </span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_planned_budget') }}:
                            <span id="capital-planned-total">
                                0.00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recurrent Budget Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.projectActivity.headers.recurrent') }}
                    </h3>
                    <div class="overflow-x-auto relative">
                        <table id="recurrent-activities"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600 table-fixed w-[1800px]">
                            <thead>
                                <!-- Row 1: Group Headers -->
                                <tr class="bg-gray-200 dark:bg-gray-600 header-row-1">
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-40 bg-gray-200 dark:bg-gray-600 left-sticky"
                                        rowspan="2">#</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-80 sticky left-12 z-40 bg-gray-200 dark:bg-gray-600 left-sticky"
                                        rowspan="2">{{ trans('global.projectActivity.fields.program') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.total_budget') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.total_expense') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.planned_budget') }}
                                    </th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q1') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q2') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q3') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center"
                                        colspan="2">{{ trans('global.projectActivity.fields.q4') }}</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 sticky right-0 z-40 bg-gray-200 dark:bg-gray-600 right-sticky"
                                        rowspan="2">{{ trans('global.action') }}</th>
                                </tr>
                                <!-- Row 2: Sub-Headers (Exactly 14 <th>s to match 7 colspans × 2) -->
                                <tr class="bg-gray-200 dark:bg-gray-600 header-row-2">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-28 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
                                <tr class="projectActivity-row" data-depth="0" data-index="1">
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">
                                        1
                                    </td>
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                                        <input name="recurrent[1][program]" type="text"
                                            value="{{ old('recurrent.1.program') }}"
                                            class="w-full border-0 p-1 tooltip-error" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][total_budget_quantity]" type="text"
                                            value="{{ old('recurrent.1.total_budget_quantity') }}" placeholder="0"
                                            class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="recurrent[1][total_budget]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.total_budget') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][total_expense_quantity]" type="text"
                                            value="{{ old('recurrent.1.total_expense_quantity') }}" placeholder="0"
                                            class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="recurrent[1][total_expense]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.total_expense') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][planned_budget_quantity]" type="text"
                                            value="{{ old('recurrent.1.planned_budget_quantity') }}" placeholder="0"
                                            class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                        <input name="recurrent[1][planned_budget]" type="text"
                                            pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.planned_budget') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="recurrent[1][q1_quantity]" type="text" min="0"
                                            step="1" value="{{ old('recurrent.1.q1_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.q1') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="recurrent[1][q2_quantity]" type="text" min="0"
                                            step="1" value="{{ old('recurrent.1.q2_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.q2') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="recurrent[1][q3_quantity]" type="text" min="0"
                                            step="1" value="{{ old('recurrent.1.q3_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.q3') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                        <input name="recurrent[1][q4_quantity]" type="text" min="0"
                                            step="1" value="{{ old('recurrent.1.q4_quantity') }}"
                                            placeholder="0"
                                            class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                        <input name="recurrent[1][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?"
                                            value="{{ old('recurrent.1.q4') }}" placeholder="0.00"
                                            class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                    </td>
                                    <td
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
                                        <span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-recurrent-row"
                        class="mt-4 bg-purple-500 text-white px-4 py-2 rounded">
                        <span class="add-sub-row cursor-pointer text-2xl text-white-400">+</span>
                        {{ trans('global.projectActivity.fields.add_new_row') }}
                    </button>
                    <div class="mt-4 flex justify-between">
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_recurrent_budget') }}:
                            <span id="recurrent-total">
                                0.00
                            </span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_recurrent_planned_budget') }}:
                            <span id="recurrent-planned-total">
                                0.00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-between">
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_budget') }}:
                    <span id="overall-total">
                        0.00
                    </span>
                </div>
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_planned_budget') }}:
                    <span id="overall-planned-total">
                        0.00
                    </span>
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

            #capital-activities,
            #recurrent-activities {
                table-layout: fixed;
                min-width: 1800px;
            }

            #capital-activities thead th:first-child,
            #recurrent-activities thead th:first-child,
            #capital-activities tbody td:first-child,
            #recurrent-activities tbody td:first-child {
                position: sticky;
                left: 0;
                z-index: 30;
                background-color: #f9fafb;
            }

            #capital-activities thead th:nth-child(2),
            #recurrent-activities thead th:nth-child(2),
            #capital-activities tbody td:nth-child(2),
            #recurrent-activities tbody td:nth-child(2) {
                position: sticky;
                left: 3rem;
                z-index: 30;
                background-color: #f9fafb;
            }

            #capital-activities thead th:last-child,
            #recurrent-activities thead th:last-child,
            #capital-activities tbody td:last-child,
            #recurrent-activities tbody td:last-child {
                position: sticky;
                right: 0;
                z-index: 30;
                background-color: #f9fafb;
            }

            /* Sticky header rows for vertical scrolling */
            #capital-activities thead th,
            #recurrent-activities thead th {
                position: sticky;
                z-index: 50;
            }

            #capital-activities .header-row-1 th,
            #recurrent-activities .header-row-1 th {
                top: 0;
            }

            #capital-activities .header-row-2 th,
            #recurrent-activities .header-row-2 th {
                top: 2rem;
                /* Adjust based on the height of the first header row (py-1 + text-sm ≈ 32px, so 2rem) */
            }

            /* Ensure sticky left/right headers have higher z-index when overlapping */
            #capital-activities thead th.sticky,
            #recurrent-activities thead th.sticky {
                z-index: 60;
            }

            .left-sticky {
                box-shadow: 4px 0 4px -2px #e5e7eb;
            }

            .dark .left-sticky {
                box-shadow: 4px 0 4px -2px #374151;
            }

            .right-sticky {
                box-shadow: -4px 0 4px -2px #e5e7eb;
            }

            .dark .right-sticky {
                box-shadow: -4px 0 4px -2px #374151;
            }

            @media (prefers-color-scheme: dark) {

                #capital-activities thead th:first-child,
                #recurrent-activities thead th:first-child,
                #capital-activities tbody td:first-child,
                #recurrent-activities tbody td:first-child,
                #capital-activities thead th:nth-child(2),
                #recurrent-activities thead th:nth-child(2),
                #capital-activities tbody td:nth-child(2),
                #recurrent-activities tbody td:nth-child(2),
                #capital-activities thead th:last-child,
                #recurrent-activities thead th:last-child,
                #capital-activities tbody td:last-child,
                #recurrent-activities tbody td:last-child {
                    background-color: #374151;
                }

                /* Dark mode for sticky headers */
                #capital-activities .header-row-1,
                #recurrent-activities .header-row-1,
                #capital-activities .header-row-2,
                #recurrent-activities .header-row-2 {
                    background-color: #374151 !important;
                }
            }
        </style>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>
        <script>
            $(document).ready(function() {
                const $ = jQuery;
                let capitalIndex = 2;
                let recurrentIndex = 2;
                const tippyInstances = new WeakMap();
                // Helper to parse numeric input safely (handle integers/decimals)
                function parseNumeric(val) {
                    return parseFloat(val.replace(/,/g, '')) || 0;
                }
                // Updated selector for project_id change
                $('.js-single-select[data-name="project_id"] .js-hidden-input').on('change', function() {
                    $('#projectActivity-form').attr('action', '{{ route('admin.projectActivity.store') }}');
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
                    if (parentIndex !== null) {
                        hiddenParentInput =
                            `<input type="hidden" name="${type}[${index}][parent_id]" value="${parentIndex}">`;
                    }
                    const html = `
                    <tr class="projectActivity-row" data-depth="${depth}" data-index="${index}" ${parentIndex !== null ? `data-parent="${parentIndex}"` : ''}>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky"></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                            ${hiddenParentInput}
                            <input name="${type}[${index}][program]" type="text" class="w-full border-0 p-1 tooltip-error" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][total_budget_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                            <input name="${type}[${index}][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][total_expense_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                            <input name="${type}[${index}][total_expense]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][planned_budget_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                            <input name="${type}[${index}][planned_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                            <input name="${type}[${index}][q1_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                            <input name="${type}[${index}][q2_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                            <input name="${type}[${index}][q3_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                            <input name="${type}[${index}][q4_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                            <input name="${type}[${index}][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
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
                    if (parentIndex !== null) {
                        const $parentRow = $tbody.find(`tr[data-index="${parentIndex}"]`);
                        if (!$parentRow.length) {
                            console.error(`Parent row ${parentIndex} not found for insertion in ${section}`);
                            return;
                        }
                        const subTreeRows = [];
                        const collectSubTree = (idx) => {
                            const $children = $tbody.find(`tr[data-parent="${idx}"]`);
                            $children.each(function() {
                                const childIdx = $(this).data('index');
                                subTreeRows.push($(this));
                                collectSubTree(childIdx);
                            });
                        };
                        collectSubTree(parentIndex);
                        const $lastRow = subTreeRows.length ? subTreeRows[subTreeRows.length - 1] : $parentRow;
                        console.log(
                            `Inserting row ${index} in ${section} after ${$lastRow.data('index')} (depth: ${depth})`
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
                    if (parentIndex !== null) {
                        validateParentRow(section, parentIndex);
                    }
                    initializeTooltips($newRow.find('.tooltip-error'));
                }

                function addSubRow($row) {
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const parentIndex = $row.data('index');
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
                $(document).on('click', '.remove-row', function() {
                    const $row = $(this).closest('tr');
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const parentIndex = $row.data('parent');
                    const index = $row.data('index');
                    $(`tr[data-parent="${index}"]`).remove();
                    $row.remove();
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
                        const parentIndex = $row.data('parent');
                        let number = '';
                        if (depth === 0) {
                            topLevelCount++;
                            number = topLevelCount.toString();
                            levelOneCounts[topLevelCount] = 0;
                        } else if (depth === 1) {
                            const parentRow = $rows.filter(`[data-index="${parentIndex}"]`);
                            const parentNumber = parentRow.find('td:first').text();
                            levelOneCounts[parentNumber] = (levelOneCounts[parentNumber] || 0) + 1;
                            number = `${parentNumber}.${levelOneCounts[parentNumber]}`;
                            levelTwoCounts[number] = 0;
                        } else if (depth === 2) {
                            const parentRow = $rows.filter(`[data-index="${parentIndex}"]`);
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
                    let capitalPlannedTotal = 0;
                    $('#capital-activities .projectActivity-row[data-depth="0"] .planned-budget-input').each(
                        function() {
                            capitalPlannedTotal += parseNumeric($(this).val());
                        });
                    $('#capital-planned-total').text(capitalPlannedTotal.toFixed(2));
                    let recurrentTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(
                        function() {
                            recurrentTotal += parseNumeric($(this).val());
                        });
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));
                    let recurrentPlannedTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .planned-budget-input').each(
                        function() {
                            recurrentPlannedTotal += parseNumeric($(this).val());
                        });
                    $('#recurrent-planned-total').text(recurrentPlannedTotal.toFixed(2));
                    let overallTotal = capitalTotal + recurrentTotal;
                    $('#overall-total').text(overallTotal.toFixed(2));
                    $('#hidden-total-budget').val(overallTotal.toFixed(2));
                    let overallPlannedTotal = capitalPlannedTotal + recurrentPlannedTotal;
                    $('#overall-planned-total').text(overallPlannedTotal.toFixed(2));
                    $('#hidden-total-planned-budget').val(overallPlannedTotal.toFixed(2));
                }
                // Updated validateRow: Handles both amounts and quantities
                function validateRow(section, index) {
                    const $row = $(`#${section}-activities tr[data-index="${index}"]`);
                    // Amount validation (existing)
                    const $plannedBudget = $row.find('.planned-budget-input');
                    const $quarters = $row.find('.quarter-input');
                    const $q4Amount = $row.find('.quarter-input[name*="[q4]"]');
                    let quarterAmountSum = 0;
                    $quarters.each(function() {
                        quarterAmountSum += parseNumeric($(this).val());
                    });
                    const plannedBudget = parseNumeric($plannedBudget.val());
                    let amountMessage = '';
                    let amountIsError = false;
                    const q4AmountFilled = $q4Amount.val().trim() !== '';
                    if (q4AmountFilled && Math.abs(quarterAmountSum - plannedBudget) > 0.01) {
                        amountIsError = true;
                        if (quarterAmountSum > plannedBudget) {
                            amountMessage =
                                `Quarters sum (${quarterAmountSum.toFixed(2)}) exceeds planned budget (${plannedBudget.toFixed(2)})`;
                        } else {
                            amountMessage =
                                `Quarters sum (${quarterAmountSum.toFixed(2)}) is less than planned budget (${plannedBudget.toFixed(2)}). Planned budget must equal sum of quarters.`;
                        }
                    }
                    // Quantity validation (new)
                    const $plannedBudgetQuantity = $row.find('.planned-budget-quantity-input');
                    const $quarterQuantities = $row.find(
                        '.q1-quantity-input, .q2-quantity-input, .q3-quantity-input, .q4-quantity-input');
                    const $q4Quantity = $row.find('.q4-quantity-input');
                    let quarterQuantitySum = 0;
                    $quarterQuantities.each(function() {
                        quarterQuantitySum += parseNumeric($(this).val());
                    });
                    const plannedBudgetQuantity = parseNumeric($plannedBudgetQuantity.val());
                    let quantityMessage = '';
                    let quantityIsError = false;
                    const q4QuantityFilled = $q4Quantity.val().trim() !== '';
                    if (q4QuantityFilled && Math.abs(quarterQuantitySum - plannedBudgetQuantity) > 0.01) {
                        quantityIsError = true;
                        if (quarterQuantitySum > plannedBudgetQuantity) {
                            quantityMessage =
                                `Quarter quantities sum (${quarterQuantitySum.toFixed(0)}) exceeds planned quantity (${plannedBudgetQuantity.toFixed(0)})`;
                        } else {
                            quantityMessage =
                                `Quarter quantities sum (${quarterQuantitySum.toFixed(0)}) is less than planned quantity (${plannedBudgetQuantity.toFixed(0)}). Planned quantity must equal sum of quarter quantities.`;
                        }
                    }
                    // Apply errors for amounts
                    if (amountIsError) {
                        $plannedBudget.addClass('error-border');
                        $quarters.addClass('error-border');
                        updateTooltip($plannedBudget, amountMessage);
                        $quarters.each(function() {
                            updateTooltip($(this), amountMessage);
                        });
                    } else {
                        $plannedBudget.removeClass('error-border');
                        $quarters.removeClass('error-border');
                        updateTooltip($plannedBudget, '');
                        $quarters.each(function() {
                            updateTooltip($(this), '');
                        });
                    }
                    // Apply errors for quantities
                    if (quantityIsError) {
                        $plannedBudgetQuantity.addClass('error-border');
                        $quarterQuantities.addClass('error-border');
                        updateTooltip($plannedBudgetQuantity, quantityMessage);
                        $quarterQuantities.each(function() {
                            updateTooltip($(this), quantityMessage);
                        });
                    } else {
                        $plannedBudgetQuantity.removeClass('error-border');
                        $quarterQuantities.removeClass('error-border');
                        updateTooltip($plannedBudgetQuantity, '');
                        $quarterQuantities.each(function() {
                            updateTooltip($(this), '');
                        });
                    }
                    console.log(
                        `Row ${index} validated: amounts ${quarterAmountSum} vs ${plannedBudget} (Q4 filled: ${q4AmountFilled}, error: ${amountIsError}); quantities ${quarterQuantitySum} vs ${plannedBudgetQuantity} (Q4 filled: ${q4QuantityFilled}, error: ${quantityIsError})`
                    );
                }

                function getFieldFromInput($input) {
                    const name = $input.attr('name');
                    if (name.includes('[total_budget]')) return 'total_budget';
                    if (name.includes('[total_budget_quantity]')) return 'total_budget_quantity';
                    if (name.includes('[total_expense]')) return 'total_expense';
                    if (name.includes('[total_expense_quantity]')) return 'total_expense_quantity';
                    if (name.includes('[planned_budget]')) return 'planned_budget';
                    if (name.includes('[planned_budget_quantity]')) return 'planned_budget_quantity';
                    if (name.includes('[q1]')) return 'q1';
                    if (name.includes('[q1_quantity]')) return 'q1_quantity';
                    if (name.includes('[q2]')) return 'q2';
                    if (name.includes('[q2_quantity]')) return 'q2_quantity';
                    if (name.includes('[q3]')) return 'q3';
                    if (name.includes('[q3_quantity]')) return 'q3_quantity';
                    if (name.includes('[q4]')) return 'q4';
                    if (name.includes('[q4_quantity]')) return 'q4_quantity';
                    return null;
                }

                function validateParentRow(section, parentIndex) {
                    if (!parentIndex) return;
                    const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                    if (!$parentRow.length) {
                        console.error(`Parent ${parentIndex} not found`);
                        return;
                    }
                    const $childRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`);
                    if ($childRows.length === 0) return;
                    // FIXED: Only validate amounts (remove quantity fields)
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
                        // FIXED: Enforce equality (not just >) for amounts, as per "equal to the sum"
                        if (Math.abs(childSum - parentValue) > 0.01) {
                            let message;
                            if (childSum > parentValue) {
                                message =
                                    `Children sum (${childSum.toFixed(2)}) exceeds parent ${field} (${parentValue.toFixed(2)}). Parent must equal sum of children.`;
                            } else {
                                message =
                                    `Children sum (${childSum.toFixed(2)}) is less than parent ${field} (${parentValue.toFixed(2)}). Parent must equal sum of children.`;
                            }
                            $parentInput.addClass('error-border');
                            $childRows.find(selector).addClass('error-border');
                            updateTooltip($parentInput, message);
                            $childRows.each(function() {
                                const $childInput = $(this).find(selector);
                                updateTooltip($childInput, message);
                            });
                        } else {
                            // Clear errors on parent and children for this field
                            $parentInput.removeClass('error-border');
                            $childRows.find(selector).removeClass('error-border');
                            updateTooltip($parentInput, '');
                            $childRows.each(function() {
                                const $childInput = $(this).find(selector);
                                updateTooltip($childInput, '');
                            });
                        }
                    }
                    validateParentRow(section, $parentRow.data('parent')); // Recurse
                }

                function validateParentRows(section) {
                    const $rows = $(`#${section}-activities tr[data-parent]`);
                    const parentIndexes = new Set();
                    $rows.each(function() {
                        parentIndexes.add($(this).data('parent'));
                    });
                    parentIndexes.forEach(idx => validateParentRow(section, idx));
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
                $(document).on('input',
                    '.total-budget-quantity-input, .total-budget-input, .total-expense-quantity-input, .expenses-input, .planned-budget-quantity-input, .planned-budget-input, .q1-quantity-input, .quarter-input[name*="[q1]"], .q2-quantity-input, .quarter-input[name*="[q2]"], .q3-quantity-input, .quarter-input[name*="[q3]"], .q4-quantity-input, .quarter-input[name*="[q4]"]',
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
                        // CAPS FOR CHILDREN ONLY (merged from restrictChildInputs) - FIXED: Skip quantities
                        if (depth > 0 && field && !field.endsWith('_quantity') && ['total_budget',
                                'total_expense', 'planned_budget', 'q1', 'q2', 'q3',
                                'q4'
                            ].includes(field)) {
                            const parentIndex = $row.data('parent');
                            const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                            const $siblingRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`).not(
                                $row);
                            let selector;
                            if (field === 'total_budget') selector = '.total-budget-input';
                            else if (field === 'total_expense') selector = '.expenses-input';
                            else if (field === 'planned_budget') selector = '.planned-budget-input';
                            else if (field === 'q1') selector = '.quarter-input[name*="[q1]"]';
                            else if (field === 'q2') selector = '.quarter-input[name*="[q2]"]';
                            else if (field === 'q3') selector = '.quarter-input[name*="[q3]"]';
                            else if (field === 'q4') selector = '.quarter-input[name*="[q4]"]';
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
                            const $quarterQuantities = $row.find(
                                '.q1-quantity-input, .q2-quantity-input, .q3-quantity-input, .q4-quantity-input'
                            );
                            const originalsAmounts = {};
                            const originalsQuantities = {};
                            $quarters.each(function() {
                                originalsAmounts[$(this).attr('name')] = $(this).val();
                                if ($(this).val().trim() === '') $(this).val('0');
                            });
                            $quarterQuantities.each(function() {
                                originalsQuantities[$(this).attr('name')] = $(this)
                                    .val();
                                if ($(this).val().trim() === '') $(this).val('0');
                            });
                            validateRow(section, index);
                            // Restore originals
                            $quarters.each(function() {
                                const name = $(this).attr('name');
                                $(this).val(originalsAmounts[name] || '');
                            });
                            $quarterQuantities.each(function() {
                                const name = $(this).attr('name');
                                $(this).val(originalsQuantities[name] || '');
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
                    $('tr[data-parent]').each(function() {
                        const $row = $(this);
                        if ($row.find('input[name$="[parent_id]"]').length === 0) {
                            const parentIndex = $row.data('parent');
                            const type = $row.closest('table').attr('id').replace('-activities', '');
                            $row.find('td:nth-child(2)').append(
                                `<input type="hidden" name="${type}[${$row.data('index')}][parent_id]" value="${parentIndex}">`
                            );
                        }
                    });
                    $.ajax({
                        url: '{{ route('admin.projectActivity.store') }}',
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
                                "Failed to create activities.";
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
                                            /(program|total_budget_quantity|total_budget|total_expense_quantity|total_expense|planned_budget_quantity|planned_budget|q[1-4]_quantity|q[1-4])/i
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
                // NEW: Load Existing Program Button Handler
                $('#load-existing-program').on('click', function() {
                    const projectId = $('.js-single-select[data-name="project_id"] .js-hidden-input').val();
                    // Removed fiscalYearId (per your note: only project_id needed)
                    if (!projectId) {
                        alert('Please select a project before loading existing programs.');
                        return;
                    }
                    // Show loading state
                    $(this).prop('disabled', true).text('Loading...');
                    $.ajax({
                        url: '{{ route('admin.projectActivity.getRows') }}',
                        method: 'GET',
                        data: {
                            project_id: projectId // Only project_id
                        },
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(response) {
                            console.log('Load response:', response); // Debug: Check full data
                            if (response.success && (response.capital || response.recurrent)) {
                                // Clear existing rows fully
                                $('#capital-tbody').empty();
                                $('#recurrent-tbody').empty();
                                // Temp reset globals to avoid pollution during load
                                const originalCapitalIndex = capitalIndex;
                                const originalRecurrentIndex = recurrentIndex;
                                capitalIndex = 1;
                                recurrentIndex = 1;
                                // Helper to insert row HTML at correct position (after parent's last child)
                                function insertRowHtml($tbody, html, parentIndex = null) {
                                    if (parentIndex === null) {
                                        // Top-level: Append to end
                                        $tbody.append(html);
                                    } else {
                                        // Find parent row and insert after its last descendant
                                        let $insertAfter = $tbody.find(
                                            `tr[data-index="${parentIndex}"]`);
                                        let lastDescendantIndex = parentIndex;
                                        // Collect all descendants recursively
                                        function collectDescendants(idx) {
                                            const $children = $tbody.find(
                                                `tr[data-parent="${idx}"]`);
                                            $children.each(function() {
                                                const childIdx = $(this).data('index');
                                                lastDescendantIndex = Math.max(
                                                    lastDescendantIndex, childIdx);
                                                collectDescendants(childIdx);
                                            });
                                        }
                                        collectDescendants(parentIndex);
                                        $insertAfter = $tbody.find(
                                            `tr[data-index="${lastDescendantIndex}"]`);
                                        $insertAfter.after(html);
                                    }
                                }
                                // Build and insert Capital Rows (manual HTML for exact indices)
                                if (response.capital && response.capital.length > 0) {
                                    response.capital.forEach(function(rowData) {
                                        const index = rowData.index;
                                        const depth = rowData.depth || 0;
                                        const parentId = rowData.parent_id || null;
                                        const type = 'capital';
                                        // Build hidden parent input
                                        let hiddenParentInput = '';
                                        if (parentId !== null) {
                                            hiddenParentInput =
                                                `<input type="hidden" name="${type}[${index}][parent_id]" value="${parentId}">`;
                                        }
                                        // Build full row HTML (mirrors addRow's template, but uses rowData.index)
                                        const html = `
                                        <tr class="projectActivity-row" data-depth="${depth}" data-index="${index}" ${parentId !== null ? `data-parent="${parentId}"` : ''}>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">${rowData.number || index}</td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                                                ${hiddenParentInput}
                                                <input name="${type}[${index}][program]" type="text" value="${rowData.program || ''}" class="w-full border-0 p-1 tooltip-error" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][total_budget_quantity]" type="text" placeholder="0" value="${rowData.total_budget_quantity || ''}" class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.total_budget || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][total_expense_quantity]" type="text" placeholder="0" value="${rowData.total_expense_quantity || ''}" class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][total_expense]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.total_expense || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][planned_budget_quantity]" type="text" placeholder="0" value="${rowData.planned_budget_quantity || ''}" class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][planned_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.planned_budget || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q1_quantity]" type="text" placeholder="0" value="${rowData.q1_quantity || ''}" class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q1 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q2_quantity]" type="text" placeholder="0" value="${rowData.q2_quantity || ''}" class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q2 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q3_quantity]" type="text" placeholder="0" value="${rowData.q3_quantity || ''}" class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q3 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q4_quantity]" type="text" placeholder="0" value="${rowData.q4_quantity || ''}" class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q4 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
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
                                        insertRowHtml($('#capital-tbody'), html, parentId ?
                                            parseInt(parentId) : null);
                                    });
                                } else {
                                    // Add default empty first row if no data
                                    addRow('capital');
                                }
                                // Build and insert Recurrent Rows (identical to capital, symmetric)
                                if (response.recurrent && response.recurrent.length > 0) {
                                    response.recurrent.forEach(function(rowData) {
                                        const index = rowData.index;
                                        const depth = rowData.depth || 0;
                                        const parentId = rowData.parent_id || null;
                                        const type = 'recurrent';
                                        let hiddenParentInput = '';
                                        if (parentId !== null) {
                                            hiddenParentInput =
                                                `<input type="hidden" name="${type}[${index}][parent_id]" value="${parentId}">`;
                                        }
                                        const html = `
                                        <tr class="projectActivity-row" data-depth="${depth}" data-index="${index}" ${parentId !== null ? `data-parent="${parentId}"` : ''}>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky">${rowData.number || index}</td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                                                ${hiddenParentInput}
                                                <input name="${type}[${index}][program]" type="text" value="${rowData.program || ''}" class="w-full border-0 p-1 tooltip-error" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][total_budget_quantity]" type="text" placeholder="0" value="${rowData.total_budget_quantity || ''}" class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.total_budget || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][total_expense_quantity]" type="text" placeholder="0" value="${rowData.total_expense_quantity || ''}" class="w-full border-0 p-1 text-right total-expense-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][total_expense]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.total_expense || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right expenses-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][planned_budget_quantity]" type="text" placeholder="0" value="${rowData.planned_budget_quantity || ''}" class="w-full border-0 p-1 text-right planned-budget-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-28">
                                                <input name="${type}[${index}][planned_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.planned_budget || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right planned-budget-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q1_quantity]" type="text" placeholder="0" value="${rowData.q1_quantity || ''}" class="w-full border-0 p-1 text-right q1-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q1]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q1 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q2_quantity]" type="text" placeholder="0" value="${rowData.q2_quantity || ''}" class="w-full border-0 p-1 text-right q2-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q2]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q2 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q3_quantity]" type="text" placeholder="0" value="${rowData.q3_quantity || ''}" class="w-full border-0 p-1 text-right q3-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q3]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q3 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                                <input name="${type}[${index}][q4_quantity]" type="text" placeholder="0" value="${rowData.q4_quantity || ''}" class="w-full border-0 p-1 text-right q4-quantity-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                                <input name="${type}[${index}][q4]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" value="${(parseFloat(rowData.q4 || 0)).toFixed(2)}" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" />
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
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
                                        insertRowHtml($('#recurrent-tbody'), html,
                                            parentId ? parseInt(parentId) : null);
                                    });
                                } else {
                                    addRow('recurrent');
                                }
                                // Restore globals for future adds (use response's next index)
                                capitalIndex = response.capital_index_next || originalCapitalIndex;
                                recurrentIndex = response.recurrent_index_next ||
                                    originalRecurrentIndex;
                                // Re-initialize tooltips, validate, update numbers/totals
                                initializeTooltips($('.tooltip-error'));
                                ['capital', 'recurrent'].forEach(section => {
                                    $(`#${section}-activities .projectActivity-row`).each(
                                        function() {
                                            const index = $(this).data('index');
                                            validateRow(section, index);
                                        });
                                    validateParentRows(section);
                                    updateRowNumbers(
                                        section
                                    ); // Overwrites backend 'number' with fresh calc
                                });
                                updateTotals();
                                console.log('Loaded rows: Capital=', response.capital?.length || 0,
                                    'Recurrent=', response.recurrent?.length || 0); // Debug
                                alert('Existing programs loaded successfully!');
                            } else {
                                alert(response.message || 'No existing programs found.');
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading existing programs:', xhr.responseJSON ||
                                xhr);
                            alert(xhr.responseJSON?.error ||
                                'Failed to load existing programs. Please try again.');
                        },
                        complete: function() {
                            // Reset button state
                            $('#load-existing-program').prop('disabled', false).text(
                                'Load Existing Program');
                        }
                    });
                });
                // Helper function to populate a row with data
                function populateRow(selector, rowData, type, index) {
                    const $row = $(selector);
                    if (!$row.length) return;
                    // Set data attributes
                    $row.attr('data-depth', rowData.depth || 0);
                    if (rowData.parent_id) {
                        $row.attr('data-parent', rowData.parent_id);
                        // Add hidden parent_id input if not exists
                        if ($row.find(`input[name="${type}[${index}][parent_id]"]`).length === 0) {
                            $row.find('td:nth-child(2)').append(
                                `<input type="hidden" name="${type}[${index}][parent_id]" value="${rowData.parent_id}">`
                            );
                        }
                    }
                    // Populate inputs
                    $row.find(`input[name="${type}[${index}][program]"]`).val(rowData.program || '');
                    $row.find(`input[name="${type}[${index}][total_budget_quantity]"]`).val(rowData
                        .total_budget_quantity || '');
                    $row.find(`input[name="${type}[${index}][total_budget]"]`).val((rowData.total_budget || 0).toFixed(
                        2));
                    $row.find(`input[name="${type}[${index}][total_expense_quantity]"]`).val(rowData
                        .total_expense_quantity || '');
                    $row.find(`input[name="${type}[${index}][total_expense]"]`).val((rowData.total_expense || 0)
                        .toFixed(2));
                    $row.find(`input[name="${type}[${index}][planned_budget_quantity]"]`).val(rowData
                        .planned_budget_quantity || '');
                    $row.find(`input[name="${type}[${index}][planned_budget]"]`).val((rowData.planned_budget || 0)
                        .toFixed(2));
                    $row.find(`input[name="${type}[${index}][q1_quantity]"]`).val(rowData.q1_quantity || '');
                    $row.find(`input[name="${type}[${index}][q1]"]`).val((rowData.q1 || 0).toFixed(2));
                    $row.find(`input[name="${type}[${index}][q2_quantity]"]`).val(rowData.q2_quantity || '');
                    $row.find(`input[name="${type}[${index}][q2]"]`).val((rowData.q2 || 0).toFixed(2));
                    $row.find(`input[name="${type}[${index}][q3_quantity]"]`).val(rowData.q3_quantity || '');
                    $row.find(`input[name="${type}[${index}][q3]"]`).val((rowData.q3 || 0).toFixed(2));
                    $row.find(`input[name="${type}[${index}][q4_quantity]"]`).val(rowData.q4_quantity || '');
                    $row.find(`input[name="${type}[${index}][q4]"]`).val((rowData.q4 || 0).toFixed(2));
                }
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
        <script>
            // Updated: Use MutationObserver to detect hidden input value changes (bypasses event firing issues)
            document.addEventListener('DOMContentLoaded', function() {
                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const budgetDisplay = document.getElementById('budget-display');
                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';

                function loadBudgetData(trigger = 'unknown') {
                    const projectId = projectHidden.value;
                    const fiscalYearId = fiscalHidden.value;
                    console.log(
                        `loadBudgetData triggered by ${trigger} - Project: ${projectId}, Fiscal: ${fiscalYearId}`
                    ); // Debug
                    if (!projectId) {
                        budgetDisplay.innerHTML =
                            '<span class="block text-sm text-gray-500 dark:text-gray-400">Select a project to view budget details.</span>';
                        return;
                    }
                    // Show loading
                    budgetDisplay.innerHTML =
                        '<span class="block text-sm text-gray-500 dark:text-gray-400">Loading budget...</span>';
                    const params = new URLSearchParams({
                        project_id: projectId,
                        fiscal_year_id: fiscalYearId || null
                    });
                    fetch(`{{ route('admin.projectActivity.budgetData') }}?${params}`, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        })
                        .then(response => {
                            console.log('Budget fetch response status:', response.status); // Debug
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Budget AJAX success:', data); // Debug
                            if (data.success && data.data) {
                                const d = data.data;
                                let fyNote = '';
                                if (!fiscalYearId && d.fiscal_year) {
                                    fyNote = ` (using default FY: ${d.fiscal_year})`;
                                }
                                budgetDisplay.innerHTML = `
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300 font-medium">Total Remaining Budget${fyNote}:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Internal:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.internal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Government Share:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.government_share).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Government Loan:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.government_loan).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Foreign Loan:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.foreign_loan).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Foreign Subsidy:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100">${Number(d.foreign_subsidy).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-800">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            Cumulative (incl. prior years): ${Number(d.cumulative).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                        </span>
                                    </div>
                                </div>
                            `;
                            } else {
                                budgetDisplay.innerHTML =
                                    `<span class="block text-sm text-red-500 dark:text-red-400">${data.message || 'No budget data available.'}</span>`;
                            }
                        })
                        .catch(error => {
                            console.error('Budget fetch error:', error); // Debug
                            budgetDisplay.innerHTML =
                                '<span class="block text-sm text-red-500 dark:text-red-400">Error loading budget data. Check console.</span>';
                        });
                }
                // MutationObserver for project changes
                if (projectHidden) {
                    const projectObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = projectHidden.value;
                                if (newValue !== lastProjectValue) {
                                    console.log('Project value changed via observer - Old:',
                                        lastProjectValue, 'New:', newValue); // Debug
                                    lastProjectValue = newValue;
                                    loadBudgetData('project-observer');
                                }
                            }
                        });
                    });
                    projectObserver.observe(projectHidden, {
                        attributes: true
                    });
                    // Fallback: Also add event listener
                    projectHidden.addEventListener('change', function() {
                        console.log('Project change fired via event - Value:', this.value); // Debug
                        lastProjectValue = this.value;
                        loadBudgetData('project-event');
                    });
                }
                // MutationObserver for fiscal changes
                if (fiscalHidden) {
                    const fiscalObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = fiscalHidden.value;
                                if (newValue !== lastFiscalValue) {
                                    console.log('Fiscal value changed via observer - Old:',
                                        lastFiscalValue, 'New:', newValue); // Debug
                                    lastFiscalValue = newValue;
                                    loadBudgetData('fiscal-observer');
                                }
                            }
                        });
                    });
                    fiscalObserver.observe(fiscalHidden, {
                        attributes: true
                    });
                    // Fallback: Also add event listener
                    fiscalHidden.addEventListener('change', function() {
                        console.log('Fiscal change fired via event - Value:', this.value); // Debug
                        lastFiscalValue = this.value;
                        loadBudgetData('fiscal-event');
                    });
                }
                // Initial load
                loadBudgetData('initial');
                // Your old syncDownloadValues stays the same
                window.syncDownloadValues = function() {
                    const projectId = projectHidden ? projectHidden.value : '';
                    const fiscalYearId = fiscalHidden ? fiscalHidden.value : '';
                    document.getElementById('download-project-hidden').value = projectId;
                    document.getElementById('download-fiscal-hidden').value = fiscalYearId;
                    if (!projectId || !fiscalYearId) {
                        alert('Please select a project and fiscal year before downloading the template.');
                        return false;
                    }
                    return true;
                };
            });
        </script>
    @endpush
</x-layouts.app>
