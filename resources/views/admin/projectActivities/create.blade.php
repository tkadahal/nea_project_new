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
            // Refined Project Activity Scripts - Consolidated & Optimized
            $(document).ready(function() {
                let capitalIndex = 2,
                    recurrentIndex = 2;
                const tippyInstances = new WeakMap();

                // ===== UTILITIES =====
                const parse = val => parseFloat((val || '').replace(/,/g, '')) || 0;
                const getField = $el => {
                    const n = $el.attr('name');
                    return n?.match(/\[([\w_]+)\]$/)?.[1] || null;
                };
                const tooltip = ($el, msg) => {
                    const t = tippyInstances.get($el[0]);
                    if (t) {
                        t.setContent(msg);
                        msg ? t.show() : t.hide();
                    }
                };
                const initTippy = $els => $els.each(function() {
                    if (!tippyInstances.has(this))
                        tippyInstances.set(this, tippy(this, {
                            content: '',
                            trigger: 'manual',
                            placement: 'top',
                            arrow: true,
                            duration: [200, 0]
                        }));
                });

                // ===== ROW MANAGEMENT =====
                const updateNumbers = sec => {
                    const $rows = $(`#${sec}-activities tbody tr`);
                    let top = 0,
                        l1 = {},
                        l2 = {};
                    $rows.each(function() {
                        const d = $(this).data('depth'),
                            p = $(this).data('parent'),
                            idx = $(this).data('index');
                        let num = '';
                        if (d === 0) {
                            top++;
                            num = top + '';
                            l1[idx] = {
                                num: num,
                                count: 0
                            };
                        } else if (d === 1) {
                            const pinfo = l1[p];
                            if (pinfo) {
                                pinfo.count++;
                                num = `${pinfo.num}.${pinfo.count}`;
                                l2[idx] = {
                                    num: num,
                                    count: 0
                                };
                            }
                        } else if (d === 2) {
                            const pinfo = l2[p];
                            if (pinfo) {
                                pinfo.count++;
                                num = `${pinfo.num}.${pinfo.count}`;
                            }
                        }
                        $(this).find('td:first').text(num);
                    });
                };

                const updateTotals = () => {
                    const calc = sel => {
                        let s = 0;
                        $(sel).each(function() {
                            s += parse($(this).val())
                        });
                        return s;
                    };
                    const ct = calc('#capital-activities .projectActivity-row[data-depth="0"] .total-budget-input');
                    const cp = calc(
                        '#capital-activities .projectActivity-row[data-depth="0"] .planned-budget-input');
                    const rt = calc(
                        '#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-input');
                    const rp = calc(
                        '#recurrent-activities .projectActivity-row[data-depth="0"] .planned-budget-input');
                    $('#capital-total').text(ct.toFixed(2));
                    $('#capital-planned-total').text(cp.toFixed(2));
                    $('#recurrent-total').text(rt.toFixed(2));
                    $('#recurrent-planned-total').text(rp.toFixed(2));
                    const ot = ct + rt,
                        op = cp + rp;
                    $('#overall-total').text(ot.toFixed(2));
                    $('#hidden-total-budget').val(ot.toFixed(2));
                    $('#overall-planned-total').text(op.toFixed(2));
                    $('#hidden-total-planned-budget').val(op.toFixed(2));
                };

                // ===== VALIDATION =====
                const validateRow = (sec, idx) => {
                    const $r = $(`#${sec}-activities tr[data-index="${idx}"]`);
                    const $pb = $r.find('.planned-budget-input'),
                        $qs = $r.find('.quarter-input');
                    let qSum = 0;
                    $qs.each(function() {
                        qSum += parse($(this).val())
                    });
                    const pb = parse($pb.val()),
                        q4Fill = $r.find('.quarter-input[name*="[q4]"]').val().trim() !== '';
                    if (q4Fill && Math.abs(qSum - pb) > 0.01) {
                        const msg = qSum > pb ?
                            `Quarters sum (${qSum.toFixed(2)}) exceeds planned (${pb.toFixed(2)})` :
                            `Quarters sum (${qSum.toFixed(2)}) < planned (${pb.toFixed(2)}). Must equal.`;
                        $pb.addClass('error-border');
                        $qs.addClass('error-border');
                        tooltip($pb, msg);
                        $qs.each(function() {
                            tooltip($(this), msg)
                        });
                    } else {
                        $pb.removeClass('error-border');
                        $qs.removeClass('error-border');
                        tooltip($pb, '');
                        $qs.each(function() {
                            tooltip($(this), '')
                        });
                    }

                    const $pbq = $r.find('.planned-budget-quantity-input'),
                        $qqs = $r.find(
                            '.q1-quantity-input,.q2-quantity-input,.q3-quantity-input,.q4-quantity-input');
                    let qqSum = 0;
                    $qqs.each(function() {
                        qqSum += parse($(this).val())
                    });
                    const pbq = parse($pbq.val()),
                        q4qFill = $r.find('.q4-quantity-input').val().trim() !== '';
                    if (q4qFill && Math.abs(qqSum - pbq) > 0.01) {
                        const msg = qqSum > pbq ?
                            `Qty sum (${qqSum.toFixed(0)}) exceeds planned (${pbq.toFixed(0)})` :
                            `Qty sum (${qqSum.toFixed(0)}) < planned (${pbq.toFixed(0)}). Must equal.`;
                        $pbq.addClass('error-border');
                        $qqs.addClass('error-border');
                        tooltip($pbq, msg);
                        $qqs.each(function() {
                            tooltip($(this), msg)
                        });
                    } else {
                        $pbq.removeClass('error-border');
                        $qqs.removeClass('error-border');
                        tooltip($pbq, '');
                        $qqs.each(function() {
                            tooltip($(this), '')
                        });
                    }
                };

                const validateParent = (sec, pidx) => {
                    if (!pidx) return;
                    const $pr = $(`#${sec}-activities tr[data-index="${pidx}"]`),
                        $crs = $(`#${sec}-activities tr[data-parent="${pidx}"]`);
                    if (!$pr.length || !$crs.length) return;
                    const sels = {
                        total_budget: '.total-budget-input',
                        total_expense: '.expenses-input',
                        planned_budget: '.planned-budget-input',
                        q1: '.quarter-input[name*="[q1]"]',
                        q2: '.quarter-input[name*="[q2]"]',
                        q3: '.quarter-input[name*="[q3]"]',
                        q4: '.quarter-input[name*="[q4]"]'
                    };
                    for (const [f, s] of Object.entries(sels)) {
                        const $pi = $pr.find(s);
                        if (!$pi.length) continue;
                        let cs = 0;
                        $crs.each(function() {
                            cs += parse($(this).find(s).val())
                        });
                        const pv = parse($pi.val());
                        if (Math.abs(cs - pv) > 0.01) {
                            const msg = cs > pv ? `Children (${cs.toFixed(2)}) > parent ${f} (${pv.toFixed(2)})` :
                                `Children (${cs.toFixed(2)}) < parent ${f} (${pv.toFixed(2)})`;
                            $pi.addClass('error-border');
                            $crs.find(s).addClass('error-border');
                            tooltip($pi, msg);
                            $crs.each(function() {
                                tooltip($(this).find(s), msg)
                            });
                        } else {
                            $pi.removeClass('error-border');
                            $crs.find(s).removeClass('error-border');
                            tooltip($pi, '');
                            $crs.each(function() {
                                tooltip($(this).find(s), '')
                            });
                        }
                    }
                    validateParent(sec, $pr.data('parent'));
                };

                const validateParents = sec => {
                    const ps = new Set();
                    $(`#${sec}-activities tr[data-parent]`).each(function() {
                        ps.add($(this).data('parent'))
                    });
                    ps.forEach(i => validateParent(sec, i));
                };

                const isValid = sec => {
                    $(`#${sec}-activities .projectActivity-row`).each(function() {
                        validateRow(sec, $(this).data('index'))
                    });
                    validateParents(sec);
                    return $(`#${sec}-activities .error-border`).length === 0;
                };

                // ===== ROW CREATION =====
                const mkRow = (sec, idx, d = 0, p = null) => {
                    const t = sec === 'capital' ? 'capital' : 'recurrent';
                    const hp = p !== null ? `<input type="hidden" name="${t}[${idx}][parent_id]" value="${p}">` :
                        '';
                    const fields = ['total_budget_quantity', 'total_budget', 'total_expense_quantity',
                            'total_expense', 'planned_budget_quantity', 'planned_budget'
                        ]
                        .map(f =>
                            `<td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right ${f.includes('quantity')?'w-24':'w-28'}">
                <input name="${t}[${idx}][${f}]" type="text" placeholder="${f.includes('quantity')?'0':'0.00'}"
                    class="w-full border-0 p-1 text-right ${f.replace(/_/g,'-')}-input tooltip-error numeric-input" /></td>`
                            )
                        .join('');
                    const qs = ['q1', 'q2', 'q3', 'q4'].map(q =>
                        `<td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                <input name="${t}[${idx}][${q}_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right ${q}-quantity-input tooltip-error numeric-input" /></td>
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                <input name="${t}[${idx}][${q}]" type="text" placeholder="0.00" class="w-full border-0 p-1 text-right quarter-input tooltip-error numeric-input" /></td>`
                    ).join('');
                    const acts =
                        `<div class="flex space-x-2 justify-center">${d<2?'<span class="add-sub-row cursor-pointer text-2xl text-blue-500">+</span>':''}
            ${(d>0||idx>1)?'<span class="remove-row cursor-pointer text-2xl text-red-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></span>':''}</div>`;
                    return `<tr class="projectActivity-row" data-depth="${d}" data-index="${idx}" ${p!==null?`data-parent="${p}"`:''}><td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky"></td>
            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-80 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">${hp}<input name="${t}[${idx}][program]" type="text" class="w-full border-0 p-1 tooltip-error" /></td>
            ${fields}${qs}<td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-28 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">${acts}</td></tr>`;
                };

                const addRow = (sec, p = null, d = 0) => {
                    // Check validation BEFORE doing anything
                    if (!isValid(sec)) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text("Correct errors first.");
                        return false; // Explicitly return false
                    }
                    if (d > 2) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text("Maximum depth (2 levels) reached.");
                        return false;
                    }
                    const t = sec === 'capital' ? 'capital' : 'recurrent',
                        idx = t === 'capital' ? capitalIndex++ : recurrentIndex++;
                    const $tb = $(`#${sec}-tbody`),
                        h = mkRow(sec, idx, d, p);
                    if (p !== null) {
                        const $pr = $tb.find(`tr[data-index="${p}"]`);
                        if (!$pr.length) {
                            $("#error-message").removeClass("hidden");
                            $("#error-text").text(`Parent row ${p} not found.`);
                            return false;
                        }
                        const sts = [];
                        const cst = i => $tb.find(`tr[data-parent="${i}"]`).each(function() {
                            sts.push($(this));
                            cst($(this).data('index'))
                        });
                        cst(p);
                        (sts.length ? sts[sts.length - 1] : $pr).after(h);
                    } else $tb.append(h);
                    updateNumbers(sec);
                    updateTotals();
                    if (p) validateParent(sec, p);
                    // Only initialize tooltips on the NEW row, not all rows
                    initTippy($tb.find(`tr[data-index="${idx}"] .tooltip-error`));
                    return true;
                };

                // ===== EVENTS =====
                $('.js-single-select[data-name="project_id"] .js-hidden-input').on('change', () => $(
                    '#projectActivity-form').attr('action', '{{ route('admin.projectActivity.store') }}'));

                // Remove ALL previous handlers completely and use ONE delegated handler
                $(document).off('click.addsubrow').on('click.addsubrow', '.add-sub-row', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Check if button is already processing
                    if ($(this).data('processing')) return false;
                    $(this).data('processing', true);

                    const $row = $(this).closest('tr');
                    const sec = $row.closest('table').attr('id').replace('-activities', '');
                    const parentIdx = parseInt($row.data('index'));
                    const currentDepth = parseInt($row.data('depth'));
                    const newDepth = currentDepth + 1;

                    const success = addRow(sec, parentIdx, newDepth);

                    // Re-enable after short delay
                    setTimeout(() => {
                        $(this).data('processing', false);
                    }, 500);

                    return false;
                });

                // Remove row handler
                $(document).off('click.removerow').on('click.removerow', '.remove-row', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const $r = $(this).closest('tr'),
                        sec = $r.closest('table').attr('id').replace('-activities', ''),
                        idx = $r.data('index'),
                        p = $r.data('parent');
                    $(`tr[data-parent="${idx}"]`).remove();
                    $r.remove();
                    updateNumbers(sec);
                    updateTotals();
                    validateParents(sec);
                    if (p) validateParent(sec, p);

                    return false;
                });

                $('#add-capital-row').off('click').on('click', function(e) {
                    e.preventDefault();
                    addRow('capital');
                });
                $('#add-recurrent-row').off('click').on('click', function(e) {
                    e.preventDefault();
                    addRow('recurrent');
                });


                $(document).on('input', '.numeric-input', function() {
                    const $i = $(this),
                        $r = $i.closest('tr'),
                        sec = $r.closest('table').attr('id').replace('-activities', ''),
                        idx = $r.data('index'),
                        d = $r.data('depth') || 0,
                        f = getField($i);
                    if (d > 0 && f && !f.endsWith('_quantity') && ['total_budget', 'total_expense',
                            'planned_budget', 'q1', 'q2', 'q3', 'q4'
                        ].includes(f)) {
                        const p = $r.data('parent'),
                            $pr = $(`#${sec}-activities tr[data-index="${p}"]`),
                            $sibs = $(`#${sec}-activities tr[data-parent="${p}"]`).not($r);
                        const sm = {
                            total_budget: '.total-budget-input',
                            total_expense: '.expenses-input',
                            planned_budget: '.planned-budget-input',
                            q1: '.quarter-input[name*="[q1]"]',
                            q2: '.quarter-input[name*="[q2]"]',
                            q3: '.quarter-input[name*="[q3]"]',
                            q4: '.quarter-input[name*="[q4]"]'
                        } [f];
                        const pv = parse($pr.find(sm).val());
                        let cv = parse($i.val()),
                            ss = 0;
                        $sibs.each(function() {
                            ss += parse($(this).find(sm).val())
                        });
                        const mx = Math.max(0, pv - ss);
                        if (cv > mx + 0.01) {
                            cv = mx;
                            $i.val(cv.toFixed(cv % 1 === 0 ? 0 : 2));
                            $i.addClass('error-border');
                            tooltip($i, `Capped at ${mx.toFixed(2)}`);
                            $pr.find(sm).addClass('error-border');
                            tooltip($pr.find(sm), 'Exceeded');
                        } else {
                            $i.removeClass('error-border');
                            tooltip($i, '');
                        }
                    }
                    validateParents(sec);
                    validateRow(sec, idx);
                    updateTotals();
                });

                $(document).on('blur', '.numeric-input', function() {
                    const n = parse($(this).val());
                    if (!isNaN(n) && n >= 0) $(this).val(n.toFixed(n % 1 === 0 ? 0 : 2))
                });
                $("#close-error").on('click', () => {
                    $("#error-message").addClass("hidden");
                    $("#error-text").text("");
                    $('.tooltip-error').removeClass('error-border').each(function() {
                        tooltip($(this), '')
                    })
                });

                // ===== FORM SUBMIT =====
                $('#projectActivity-form').on('submit', function(e) {
                    e.preventDefault();
                    const $sb = $('#submit-button');
                    if ($sb.prop('disabled')) return;
                    let err = false;
                    ['capital', 'recurrent'].forEach(sec => {
                        $(`#${sec}-activities .projectActivity-row`).each(function() {
                            const $r = $(this);
                            $r.find('input[name*="[program]"],.numeric-input').each(function() {
                                const $i = $(this),
                                    v = $i.val().trim();
                                if (!$i.is('[name*="[program]"]') && (!v || isNaN(parse(
                                        v)) || parse(v) < 0)) {
                                    $i.addClass('error-border');
                                    tooltip($i, 'Required');
                                    err = true;
                                } else if (!$i.is('[name*="[program]"]') && v && !
                                    /^[0-9]+(\.[0-9]{1,2})?$/.test(v)) {
                                    $i.addClass('error-border');
                                    tooltip($i, 'Invalid format');
                                    err = true;
                                } else {
                                    $i.removeClass('error-border');
                                    tooltip($i, '');
                                }
                            });
                            const origs = {};
                            $r.find(
                                    '.quarter-input,.q1-quantity-input,.q2-quantity-input,.q3-quantity-input,.q4-quantity-input'
                                    )
                                .each(function() {
                                    origs[$(this).attr('name')] = $(this).val();
                                    if ($(this).val().trim() === '') $(this).val('0')
                                });
                            validateRow(sec, $r.data('index'));
                            Object.entries(origs).forEach(([n, v]) => $r.find(
                                `input[name="${n}"]`).val(v));
                            if ($r.find('.error-border').length > 0) err = true;
                        });
                        validateParents(sec);
                        if ($(`#${sec}-activities .error-border`).length > 0) err = true;
                    });
                    if (err) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text("Correct errors.");
                        return;
                    }
                    $sb.prop('disabled', true).addClass('opacity-50 cursor-not-allowed').text(
                        '{{ trans('global.saving') }}...');
                    $('tr[data-parent]').each(function() {
                        const $r = $(this);
                        if ($r.find('input[name$="[parent_id]"]').length === 0) {
                            const t = $r.closest('table').attr('id').replace('-activities', '');
                            $r.find('td:nth-child(2)').append(
                                `<input type="hidden" name="${t}[${$r.data('index')}][parent_id]" value="${$r.data('parent')}">`
                            )
                        }
                    });
                    $.ajax({
                        url: '{{ route('admin.projectActivity.store') }}',
                        method: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        success: () => window.location.href =
                            '{{ route('admin.projectActivity.index') }}',
                        error: function(x) {
                            $sb.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed')
                                .text('{{ trans('global.save') }}');
                            let em = x.responseJSON?.message || "Failed.";
                            if (x.responseJSON?.errors) {
                                const es = x.responseJSON.errors;
                                let et = em + ":<br>";
                                $('.tooltip-error').removeClass('error-border');
                                for (const [idx, msgs] of Object.entries(es)) {
                                    const sec = msgs.some(m => m.includes('capital')) ? 'capital' :
                                        'recurrent';
                                    const $r = $(`#${sec}-activities tr[data-index="${idx}"]`);
                                    msgs.forEach(m => {
                                        et += `Row ${parseInt(idx)}: ${m}<br>`;
                                        const fm = m.match(
                                            /(program|total_budget_quantity|total_budget|total_expense_quantity|total_expense|planned_budget_quantity|planned_budget|q[1-4]_quantity|q[1-4])/i
                                        );
                                        if (fm) {
                                            const $i = $r.find(`input[name*="[${fm[1]}]"]`);
                                            $i.addClass('error-border');
                                            tooltip($i, m)
                                        }
                                    });
                                }
                                $("#error-text").html(et);
                            } else $("#error-text").text(em);
                            $("#error-message").removeClass("hidden");
                        }
                    });
                });

                // ===== LOAD EXISTING =====
                $('#load-existing-program').on('click', function() {
                    const pid = $('.js-single-select[data-name="project_id"] .js-hidden-input').val();
                    if (!pid) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text('Select project first.');
                        return;
                    }
                    $(this).prop('disabled', true).text('Loading...');
                    $.ajax({
                        url: '{{ route('admin.projectActivity.getRows') }}',
                        method: 'GET',
                        data: {
                            project_id: pid
                        },
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        success: function(r) {
                            if (r.success && (r.capital || r.recurrent)) {
                                $('#capital-tbody,#recurrent-tbody').empty();
                                const ld = (sd, sec) => {
                                    if (!sd || sd.length === 0) {
                                        addRow(sec);
                                        return;
                                    }
                                    let maxIdx = 0;
                                    sd.forEach(rd => {
                                        maxIdx = Math.max(maxIdx, rd.index);
                                        const h = mkRow(sec, rd.index, rd.depth || 0, rd
                                                .parent_id || null),
                                            $tb = $(`#${sec}-tbody`);
                                        if (rd.parent_id) {
                                            const $pr = $tb.find(
                                                    `tr[data-index="${rd.parent_id}"]`),
                                                sts = [];
                                            const cst = i => $tb.find(
                                                `tr[data-parent="${i}"]`).each(
                                                function() {
                                                    sts.push($(this));
                                                    cst($(this).data('index'))
                                                });
                                            cst(rd.parent_id);
                                            (sts.length ? sts[sts.length - 1] : $pr)
                                            .after(h);
                                        } else $tb.append(h);
                                        const $nr = $tb.find(
                                                `tr[data-index="${rd.index}"]`),
                                            t = sec === 'capital' ? 'capital' :
                                            'recurrent';
                                        ['program', 'total_budget_quantity',
                                            'total_budget', 'total_expense_quantity',
                                            'total_expense', 'planned_budget_quantity',
                                            'planned_budget', 'q1_quantity', 'q1',
                                            'q2_quantity', 'q2', 'q3_quantity', 'q3',
                                            'q4_quantity', 'q4'
                                        ].forEach(k => {
                                            const $inp = $nr.find(
                                                `input[name="${t}[${rd.index}][${k}]"]`
                                            );
                                            if ($inp.length && rd[k] !==
                                                undefined) $inp.val(k.includes(
                                                    'quantity') || k ===
                                                'program' ? rd[k] :
                                                parseFloat(rd[k] || 0)
                                                .toFixed(2))
                                        });
                                    });
                                    return maxIdx + 1;
                                };
                                const capMax = ld(r.capital, 'capital');
                                const recMax = ld(r.recurrent, 'recurrent');
                                capitalIndex = r.capital_index_next || capMax || capitalIndex;
                                recurrentIndex = r.recurrent_index_next || recMax || recurrentIndex;
                                initTippy($('.tooltip-error'));
                                ['capital', 'recurrent'].forEach(sec => {
                                    $(`#${sec}-activities .projectActivity-row`).each(
                                        function() {
                                            validateRow(sec, $(this).data('index'))
                                        });
                                    validateParents(sec);
                                    updateNumbers(sec)
                                });
                                updateTotals();
                            } else $("#error-message").removeClass("hidden");
                            $("#error-text").text(r.message || 'No data.');
                        },
                        error: () => {
                            $("#error-message").removeClass("hidden");
                            $("#error-text").text('Load failed.');
                        },
                        complete: () => $('#load-existing-program').prop('disabled', false).text(
                            'Load Existing Program')
                    });
                });

                // INIT
                initTippy($('.tooltip-error'));
                ['capital', 'recurrent'].forEach(sec => {
                    $(`#${sec}-activities .projectActivity-row`).each(function() {
                        validateRow(sec, $(this).data('index'))
                    });
                    validateParents(sec)
                });
                updateNumbers('capital');
                updateNumbers('recurrent');
                updateTotals();
            });

            // ===== BUDGET DATA LOADER =====
            document.addEventListener('DOMContentLoaded', function() {
                const ph = document.querySelector('.js-single-select[data-name="project_id"] .js-hidden-input'),
                    fh = document.querySelector('.js-single-select[data-name="fiscal_year_id"] .js-hidden-input'),
                    bd = document.getElementById('budget-display');
                let lp = ph ? ph.value : '',
                    lf = fh ? fh.value : '';
                const ld = () => {
                    const pid = ph.value,
                        fid = fh.value;
                    if (!pid) {
                        bd.innerHTML =
                            '<span class="block text-sm text-gray-500 dark:text-gray-400">Select project.</span>';
                        return;
                    }
                    bd.innerHTML = '<span class="block text-sm text-gray-500 dark:text-gray-400">Loading...</span>';
                    fetch(`{{ route('admin.projectActivity.budgetData') }}?${new URLSearchParams({project_id:pid,fiscal_year_id:fid||null})}`, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        })
                        .then(r => {
                            if (!r.ok) throw new Error(`HTTP ${r.status}`);
                            return r.json()
                        })
                        .then(d => {
                            if (d.success && d.data) {
                                const x = d.data,
                                    fy = !fid && x.fiscal_year ? ` (FY: ${x.fiscal_year})` : '';
                                bd.innerHTML =
                                    `<div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                ${[['Total Remaining'+fy,'total'],['Internal','internal'],['Gov Share','government_share'],['Gov Loan','government_loan'],['Foreign Loan','foreign_loan'],['Foreign Subsidy','foreign_subsidy']].map(([l,k])=>
                `<div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300 font-medium">${l}:</span><span class="font-bold text-gray-800 dark:text-gray-100">${Number(x[k]).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div>`).join('')}
                </div><div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-800"><span class="block text-xs text-gray-500 dark:text-gray-400">Cumulative: ${Number(x.cumulative).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div></div>`;
                            } else bd.innerHTML =
                                `<span class="block text-sm text-red-500 dark:text-red-400">${d.message||'No data.'}</span>`
                        })
                        .catch(() => bd.innerHTML =
                            '<span class="block text-sm text-red-500 dark:text-red-400">Error loading.</span>')
                };
                if (ph) {
                    const po = new MutationObserver(m => m.forEach(x => {
                        if (x.type === 'attributes' && x.attributeName === 'value') {
                            const nv = ph.value;
                            if (nv !== lp) {
                                lp = nv;
                                ld()
                            }
                        }
                    }));
                    po.observe(ph, {
                        attributes: true
                    });
                    ph.addEventListener('change', function() {
                        lp = this.value;
                        ld()
                    });
                }
                if (fh) {
                    const fo = new MutationObserver(m => m.forEach(x => {
                        if (x.type === 'attributes' && x.attributeName === 'value') {
                            const nv = fh.value;
                            if (nv !== lf) {
                                lf = nv;
                                ld()
                            }
                        }
                    }));
                    fo.observe(fh, {
                        attributes: true
                    });
                    fh.addEventListener('change', function() {
                        lf = this.value;
                        ld()
                    });
                }
                ld();
                window.syncDownloadValues = () => {
                    const pid = ph ? ph.value : '',
                        fid = fh ? fh.value : '';
                    document.getElementById('download-project-hidden').value = pid;
                    document.getElementById('download-fiscal-hidden').value = fid;
                    if (!pid || !fid) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text('Select project and fiscal year.');
                        return false
                    }
                    return true
                };
            });
        </script>
    @endpush
</x-layouts.app>
