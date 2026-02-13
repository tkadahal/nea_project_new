<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.projectActivity.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.projectActivity.title_singular') }}
        </p>

        <!-- Action Buttons -->
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <button type="button" id="download-template-btn"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                title="Download Excel Template">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                {{ trans('global.projectActivity.excel.download') }}
            </button>
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
        </div>
    </div>
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form id="projectActivity-form" class="w-full" action="{{ route('admin.projectActivity.store') }}" method="POST">
        @csrf

        <!-- Hidden inputs to track initial state (page load) -->
        <input type="hidden" id="initial-capital-ids" name="initial_capital_ids"
            value="{{ collect($capitalActivities)->pluck('id')->implode(',') }}">
        <input type="hidden" id="initial-recurrent-ids" name="initial_recurrent_ids"
            value="{{ collect($recurrentActivities)->pluck('id')->implode(',') }}">

        <!-- Project & Fiscal Year Selection -->
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

        <!-- Error Message -->
        <div id="error-message"
            class="mb-6 {{ $errors->any() ? '' : 'hidden' }} bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-gray-700 dark:text-red-200">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3"
                onclick="this.parentElement.classList.add('hidden')">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20">
                    <path
                        d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                </svg>
            </button>
        </div>

        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">

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
                                <!-- Row 2: Sub-Headers -->
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
                                @forelse($capitalActivities->sortBy('sort_index', SORT_NATURAL)->values() as $activity)
                                    @include('admin.projectActivities.partials.activity-row-full', [
                                        'activity' => $activity,
                                        'type' => 'capital',
                                    ])
                                @empty
                                    <tr class="no-data-row">
                                        <td colspan="17" class="border px-2 py-4 text-center text-gray-500">
                                            No activities yet. Click "Add Row" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-capital-row"
                        class="mt-4 bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                        <span class="text-xl mr-2">+</span>
                        {{ trans('global.projectActivity.fields.add_new_row') }}
                    </button>
                    <div class="mt-4 flex justify-between">
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_budget') }}:
                            <span id="capital-total">0.00</span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_planned_budget') }}:
                            <span id="capital-planned-total">0.00</span>
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
                                <!-- Row 2: Sub-Headers -->
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
                                @forelse($recurrentActivities->sortBy('sort_index', SORT_NATURAL)->values() as $activity)
                                    @include('admin.projectActivities.partials.activity-row-full', [
                                        'activity' => $activity,
                                        'type' => 'recurrent',
                                    ])
                                @empty
                                    <tr class="no-data-row">
                                        <td colspan="17" class="border px-2 py-4 text-center text-gray-500">
                                            No activities yet. Click "Add Row" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-recurrent-row"
                        class="mt-4 bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                        <span class="text-xl mr-2">+</span>
                        {{ trans('global.projectActivity.fields.add_new_row') }}
                    </button>
                    <div class="mt-4 flex justify-between">
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_recurrent_budget') }}:
                            <span id="recurrent-total">0.00</span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_recurrent_planned_budget') }}:
                            <span id="recurrent-planned-total">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Total -->
            <div class="mt-4 flex justify-between">
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_budget') }}:
                    <span id="overall-total">0.00</span>
                </div>
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_planned_budget') }}:
                    <span id="overall-planned-total">0.00</span>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8">
                <x-buttons.primary id="submit-button" type="submit" :disabled="false">
                    {{ trans('global.save') }}
                </x-buttons.primary>
            </div>
        </div>
    </form>

    <div id="structure-change-modal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
        <div class="relative mx-auto p-6 border w-11/12 max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/50 mb-4">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Confirm Structure Change
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6" id="modal-message">
                    The number of activities has changed. This will create a new version of the activity definitions and
                    affect all fiscal years.
                </p>
                <div class="text-xs text-gray-500 dark:text-gray-400 italic mb-6">
                    <strong>Note:</strong> Previous plans in other fiscal years will be deleted. This cannot be undone.
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button type="button" id="cancel-structure-change"
                    class="px-5 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 transition">
                    Cancel
                </button>
                <button type="button" id="confirm-structure-change"
                    class="px-5 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition flex items-center">
                    <span id="confirm-button-text">Confirm & Proceed</span>
                </button>
            </div>
        </div>
    </div>

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

            .activity-row[data-depth="1"] td:nth-child(2) {
                padding-left: 28px;
            }

            .activity-row[data-depth="2"] td:nth-child(2) {
                padding-left: 48px;
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
                const projectIdInput = $('select[name="project_id"], input[name="project_id"]');
                const fiscalYearInput = $('select[name="fiscal_year_id"], input[name="fiscal_year_id"]');

                const $budgetDisplay = $('#budget-display');
                const $capitalTbody = $('#capital-tbody');
                const $recurrentTbody = $('#recurrent-tbody');

                // ============================================================
                // STATE PRESERVATION (AJAX Interceptor Version)
                // ============================================================
                const STORAGE_KEY = 'project_activity_draft';
                let isRestoring = false;
                let saveTimeout = null;

                // ------------------------------------------------------------
                // THE AJAX INTERCEPTOR (Stops the "Blink")
                // ------------------------------------------------------------
                // We wrap jQuery's get function. If it tries to fetch activities
                // for a project we have a draft for, we cancel it.
                (function() {
                    const _get = $.get;
                    $.get = function(url, data, callback) {
                        if (url.includes('getActivities')) {
                            const draft = sessionStorage.getItem(STORAGE_KEY);
                            if (draft) {
                                try {
                                    const state = JSON.parse(draft);
                                    // If request is for the same project ID as the draft, BLOCK IT.
                                    if (state.project_id == data.project_id) {
                                        console.log(
                                            '🛑 AJAX Interceptor: Blocked server fetch to preserve draft for Project ' +
                                            state.project_id);
                                        return false; // Stop the request
                                    }
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                        return _get(url, data, callback);
                    };
                })();

                function debouncedSave() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        saveFormState();
                    }, 500);
                }

                function saveFormState() {
                    if (isRestoring) return;

                    const projectId = projectIdInput.val();
                    const fiscalYearId = fiscalYearInput.val();

                    if (!projectId) {
                        console.log('⚠ Skipping save - no project selected');
                        return;
                    }

                    // Sync DOM Properties to HTML Attributes
                    $('#capital-tbody input, #recurrent-tbody input').each(function() {
                        $(this).attr('value', $(this).val());
                    });
                    $('#capital-tbody select, #recurrent-tbody select').each(function() {
                        const $el = $(this);
                        $el.find('option').prop('selected', false);
                        $el.find('option[value="' + $el.val() + '"]').prop('selected', true);
                    });

                    const state = {
                        project_id: projectId,
                        fiscal_year_id: fiscalYearId,
                        capital_html: $('#capital-tbody').html(),
                        recurrent_html: $('#recurrent-tbody').html(),
                        timestamp: Date.now()
                    };

                    try {
                        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                        updateDraftIndicator();
                        console.log('✓ Draft auto-saved');
                    } catch (e) {
                        console.warn('Failed to save state:', e);
                    }
                }

                function saveImmediately() {
                    if (!isRestoring) {
                        clearTimeout(saveTimeout);
                        saveFormState();
                    }
                }

                function clearFormState() {
                    sessionStorage.removeItem(STORAGE_KEY);
                    console.log('✓ Draft cleared');
                }

                function restoreFormState() {
                    const saved = sessionStorage.getItem(STORAGE_KEY);
                    if (!saved) return false;

                    try {
                        const state = JSON.parse(saved);

                        const hoursSinceLastSave = (Date.now() - state.timestamp) / (1000 * 60 * 60);
                        if (hoursSinceLastSave > 24) {
                            clearFormState();
                            return false;
                        }

                        isRestoring = true;

                        // Restore Values
                        if (state.project_id) projectIdInput.val(state.project_id);
                        if (state.fiscal_year_id) fiscalYearInput.val(state.fiscal_year_id);

                        // Restore Tables
                        if (state.capital_html) $capitalTbody.html(state.capital_html);
                        if (state.recurrent_html) $recurrentTbody.html(state.recurrent_html);

                        // Bind & Calculate
                        bindRowEvents($('.activity-row'));
                        updateTotals();

                        isRestoring = false;

                        // Trigger change events (Interceptor will handle the resulting AJAX)
                        if (state.project_id) projectIdInput.trigger('change');
                        if (state.fiscal_year_id) fiscalYearInput.trigger('change');

                        showSuccessToast('Draft restored from ' + new Date(state.timestamp).toLocaleTimeString());
                        return true;

                    } catch (e) {
                        console.error('❌ Error restoring state:', e);
                        clearFormState();
                        isRestoring = false;
                        return false;
                    }
                }

                function updateDraftIndicator() {
                    const hasDraft = sessionStorage.getItem(STORAGE_KEY);
                    let $indicator = $('#draft-indicator');

                    if (!$indicator.length) {
                        $indicator = $(
                            '<div id="draft-indicator" class="fixed bottom-4 right-4 bg-blue-500 text-white px-3 py-2 rounded-lg text-sm shadow-lg z-50 hidden transition-opacity duration-300"></div>'
                        );
                        $('body').append($indicator);
                    }

                    if (hasDraft) {
                        const state = JSON.parse(hasDraft);
                        const timeAgo = Math.floor((Date.now() - state.timestamp) / 1000);
                        const timeText = timeAgo < 60 ? 'just now' : `${Math.floor(timeAgo / 60)}m ago`;

                        $indicator.html(`
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                    <span>Draft saved ${timeText}</span>
                </div>
            `).removeClass('hidden').css('opacity', 1);

                        setTimeout(() => $indicator.css('opacity', 0).hide(), 3000);
                    }
                }

                // ============================================================
                // SIMPLE OBSERVER (Budget Only)
                // ============================================================
                function observeHiddenInput(input, callback) {
                    input.on('change', function() {
                        if (!isRestoring) callback();
                    });
                    const observer = new MutationObserver(function(mutations) {
                        if (!isRestoring) callback();
                    });
                    observer.observe(input[0], {
                        attributes: true
                    });
                }

                observeHiddenInput(projectIdInput, function() {
                    // If we switch projects, we MUST clear draft and load new data
                    const currentVal = projectIdInput.val();
                    const draft = sessionStorage.getItem(STORAGE_KEY);
                    if (draft) {
                        try {
                            const state = JSON.parse(draft);
                            // If value changed to a different project, clear draft
                            if (state.project_id != currentVal) {
                                clearFormState();
                                loadActivities();
                            }
                            // If value is same as draft, loadActivities() is BLOCKED by the Interceptor
                        } catch (e) {}
                    } else {
                        loadActivities();
                    }
                    loadBudgetData();
                });

                observeHiddenInput(fiscalYearInput, function() {
                    loadBudgetData();
                });


                // ============================================================
                // LOAD BUDGET DATA
                // ============================================================
                function loadBudgetData() {
                    const projectId = projectIdInput.val();
                    const fiscalYearId = fiscalYearInput.val();

                    if (!projectId) {
                        $budgetDisplay.html(
                            '<span class="block text-sm text-gray-500 dark:text-gray-400">Please select a project to view budget details.</span>'
                            );
                        return;
                    }

                    $budgetDisplay.html(
                        '<span class="block text-sm text-gray-500 dark:text-gray-400">Loading budget...</span>');

                    $.ajax({
                        url: '{{ route('admin.projectActivity.budgetData') }}',
                        method: 'GET',
                        data: {
                            project_id: projectId,
                            fiscal_year_id: fiscalYearId || null
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                const d = data.data;
                                const fyNote = fiscalYearId ? '' :
                                    ` (using default FY: ${d.fiscal_year || 'N/A'})`;
                                $budgetDisplay.html(`
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300 font-medium">Total Remaining Budget${fyNote}:</span><span class="font-bold">${Number(d.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Internal:</span><span class="font-bold">${Number(d.internal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Government Share:</span><span class="font-bold">${Number(d.government_share).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Government Loan:</span><span class="font-bold">${Number(d.government_loan).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Foreign Loan:</span><span class="font-bold">${Number(d.foreign_loan).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Foreign Subsidy:</span><span class="font-bold">${Number(d.foreign_subsidy).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-800">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Cumulative (incl. prior years): ${Number(d.cumulative).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                </div>
                            `);
                            } else {
                                $budgetDisplay.html(
                                    `<span class="block text-sm text-red-500 dark:text-red-400">${data.message || 'No budget data available.'}</span>`
                                    );
                            }
                        },
                        error: function() {
                            $budgetDisplay.html(
                                '<span class="block text-sm text-red-500 dark:text-red-400">Error loading budget data.</span>'
                                );
                        }
                    });
                }

                // ============================================================
                // LOAD ACTIVITIES
                // ============================================================
                function loadActivities() {
                    const projectId = projectIdInput.val();
                    if (!projectId) {
                        $capitalTbody.html(
                            '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet. Click "Add Row" to create one.</td></tr>'
                            );
                        $recurrentTbody.html(
                            '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet. Click "Add Row" to create one.</td></tr>'
                            );
                        updateTotals();
                        return;
                    }

                    $.get('{{ route('admin.projectActivity.getActivities') }}', {
                        project_id: projectId,
                        expenditure_id: 1
                    }, function(res) {
                        if (res.success) {
                            $capitalTbody.html(res.html ||
                                '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet.</td></tr>'
                                );
                            bindRowEvents($capitalTbody.find('.activity-row'));
                            updateTotals();
                        }
                    });

                    $.get('{{ route('admin.projectActivity.getActivities') }}', {
                        project_id: projectId,
                        expenditure_id: 2
                    }, function(res) {
                        if (res.success) {
                            $recurrentTbody.html(res.html ||
                                '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet.</td></tr>'
                                );
                            bindRowEvents($recurrentTbody.find('.activity-row'));
                            updateTotals();
                        }
                    });
                }

                // ============================================================
                // INITIALIZATION
                // ============================================================
                if (restoreFormState()) {
                    console.log('Previous draft loaded');
                } else if (projectIdInput.val()) {
                    loadBudgetData();
                    loadActivities();
                } else {
                    $budgetDisplay.html(
                        '<span class="block text-sm text-gray-500 dark:text-gray-400">Please select a project to view budget details.</span>'
                        );
                }

                // ============================================================
                // HELPER FUNCTIONS
                // ============================================================
                function removeNoDataRows() {
                    $('.no-data-row').remove();
                }

                function updateTotals() {
                    let capitalTotal = 0,
                        capitalPlannedTotal = 0;
                    let recurrentTotal = 0,
                        recurrentPlannedTotal = 0;

                    $('#capital-tbody tr.activity-row').each(function() {
                        const $row = $(this);
                        capitalTotal += parseFloat($row.find('.total-budget-input').val()) || 0;
                        capitalPlannedTotal += parseFloat($row.find('.planned-budget-input').val()) || 0;
                    });

                    $('#recurrent-tbody tr.activity-row').each(function() {
                        const $row = $(this);
                        recurrentTotal += parseFloat($row.find('.total-budget-input').val()) || 0;
                        recurrentPlannedTotal += parseFloat($row.find('.planned-budget-input').val()) || 0;
                    });

                    $('#capital-total').text(capitalTotal.toFixed(2));
                    $('#capital-planned-total').text(capitalPlannedTotal.toFixed(2));
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));
                    $('#recurrent-planned-total').text(recurrentPlannedTotal.toFixed(2));
                    $('#overall-total').text((capitalTotal + recurrentTotal).toFixed(2));
                    $('#overall-planned-total').text((capitalPlannedTotal + recurrentPlannedTotal).toFixed(2));
                }

                function findLastDescendant($row, $table) {
                    const rowId = $row.data('id');
                    let $last = $row;
                    $table.find('tr[data-parent-id="' + rowId + '"]').each(function() {
                        $last = findLastDescendant($(this), $table);
                    });
                    return $last;
                }

                function removeRowAndDescendants($row) {
                    const rowId = $row.data('id');
                    $('tr[data-parent-id="' + rowId + '"]').each(function() {
                        removeRowAndDescendants($(this));
                    });
                    $row.remove();

                    const $tbody = $row.closest('tbody');
                    if ($tbody.find('tr.activity-row').length === 0) {
                        $tbody.html(
                            '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet. Click "Add Row" to create one.</td></tr>'
                            );
                    }
                }

                function bindRowEvents($row) {
                    $row.find(
                            '.program-input, .total-budget-input, .total-budget-quantity-input, .total-expense-quantity-input, .expenses-input, .planned-budget-input, .planned-budget-quantity-input, .q1-quantity-input, .quarter-input, .q2-quantity-input, .q3-quantity-input, .q4-quantity-input'
                            )
                        .on('blur', function() {
                            const $input = $(this);
                            if ($input.hasClass('numeric-input')) {
                                let val = parseFloat($input.val()) || 0;
                                $input.val(val.toFixed(2));
                            }
                            updateTotals();
                            saveImmediately();
                        });
                }

                $(document).on('input change',
                    '.program-input, .numeric-input, .total-budget-input, .planned-budget-input, .total-budget-quantity-input, .total-expense-quantity-input, .expenses-input, .planned-budget-quantity-input, .q1-quantity-input, .q2-quantity-input, .q3-quantity-input, .q4-quantity-input, .quarter-input',
                    function() {
                        if (!isRestoring) debouncedSave();
                    });

                // ============================================================
                // ADD SUB-ROW
                // ============================================================
                $(document).on('click', '.add-subrow', function(e) {
                    e.preventDefault();
                    const $btn = $(this);
                    if ($btn.prop('disabled')) return;

                    const parentId = $btn.data('parent-id');
                    const $table = $btn.closest('table');
                    const expenditureId = $table.attr('id').includes('capital') ? 1 : 2;
                    const projectId = projectIdInput.val();

                    if (!projectId) {
                        showError('Please select a project first');
                        return;
                    }
                    if (parseInt($btn.closest('tr').data('depth')) >= 2) {
                        showError('Maximum depth (2 levels) reached');
                        return;
                    }

                    $btn.prop('disabled', true).text('...');

                    $.ajax({
                        url: '{{ route('admin.projectActivity.addRow') }}',
                        type: 'POST',
                        data: {
                            project_id: projectId,
                            expenditure_id: expenditureId,
                            parent_id: parentId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (!response.success) {
                                showError(response.error || 'Failed to add sub-row');
                                return;
                            }
                            removeNoDataRows();
                            const $parentRow = $table.find('tr[data-id="' + parentId + '"]');
                            const $insertAfter = findLastDescendant($parentRow, $table);
                            const $newRow = $(response.row.html).insertAfter($insertAfter);
                            $newRow.attr('data-id', response.row.id).attr('data-depth', response.row
                                    .depth).attr('data-parent-id', response.row.parent_id || '')
                                .attr('data-sort-index', response.row.sort_index);
                            bindRowEvents($newRow);
                            updateTotals();
                            saveImmediately();
                            $('html, body').animate({
                                scrollTop: $newRow.offset().top - 100
                            }, 500);
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON?.error || 'Failed to add sub-row');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('+');
                        }
                    });
                });

                // ============================================================
                // ADD TOP-LEVEL ROW
                // ============================================================
                $('#add-capital-row, #add-recurrent-row').on('click', function(e) {
                    e.preventDefault();
                    const $btn = $(this);
                    const expenditureId = $btn.attr('id').includes('capital') ? 1 : 2;
                    const $tbody = expenditureId === 1 ? $('#capital-tbody') : $('#recurrent-tbody');
                    const projectId = projectIdInput.val();

                    if (!projectId) {
                        showError('Please select a project first');
                        return;
                    }

                    $btn.prop('disabled', true).text('...');

                    $.ajax({
                        url: '{{ route('admin.projectActivity.addRow') }}',
                        method: 'POST',
                        data: {
                            project_id: projectId,
                            expenditure_id: expenditureId,
                            parent_id: null,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (!response.success) {
                                showError(response.error || 'Failed to add row');
                                return;
                            }
                            removeNoDataRows();
                            const $newRow = $(response.row.html).appendTo($tbody);
                            $newRow.attr('data-id', response.row.id).attr('data-depth', response.row
                                    .depth).attr('data-parent-id', response.row.parent_id || '')
                                .attr('data-sort-index', response.row.sort_index);
                            bindRowEvents($newRow);
                            updateTotals();
                            saveImmediately();
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON?.error || 'Failed to add row');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html(
                                '<span class="text-xl mr-2">+</span>{{ trans('global.projectActivity.fields.add_new_row') }}'
                                );
                        }
                    });
                });

                // ============================================================
                // DELETE ROW
                // ============================================================
                $(document).on('click', '.delete-row', function(e) {
                    e.preventDefault();
                    if (!confirm('Delete this row and all sub-rows? Remaining rows will be re-indexed.'))
                return;

                    const activityId = $(this).data('id');
                    const $row = $('tr[data-id="' + activityId + '"]');
                    const $tbody = $row.closest('tbody');
                    const expenditureId = $tbody.attr('id').includes('capital') ? 1 : 2;
                    const projectId = projectIdInput.val();

                    $.ajax({
                        url: '{{ route('admin.projectActivity.deleteRow', ['id' => ':id']) }}'.replace(
                            ':id', activityId),
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (!response.success) {
                                showError(response.error || 'Failed to delete');
                                return;
                            }
                            removeRowAndDescendants($row);
                            reloadActivities(projectId, expenditureId, $tbody);
                            updateTotals();
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON?.error || 'Failed to delete row');
                        }
                    });
                });

                function reloadActivities(projectId, expenditureId, $tbody) {
                    $.ajax({
                        url: '{{ route('admin.projectActivity.getActivities') }}',
                        method: 'GET',
                        data: {
                            project_id: projectId,
                            expenditure_id: expenditureId
                        },
                        success: function(response) {
                            if (response.success) {
                                $tbody.html(response.html);
                                $tbody.find('.activity-row').each(function() {
                                    bindRowEvents($(this));
                                });
                                updateTotals();
                                saveImmediately();
                            }
                        },
                        error: function() {
                            console.error('Failed to reload activities');
                        }
                    });
                }

                // ============================================================
                // VALIDATION & ERRORS
                // ============================================================
                $(document).on('input', '.numeric-input', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                });

                function showError(message) {
                    const $errorText = $('#error-text').length ? $('#error-text') : $('#modal-message');
                    if ($errorText.length) $errorText.html(message);
                    $('#error-message').removeClass('hidden');
                }
                $('#close-error').on('click', function() {
                    $('#error-message').addClass('hidden');
                });

                // ============================================================
                // FORM SUBMISSION
                // ============================================================
                $('#projectActivity-form').on('submit', function(e) {
                    e.preventDefault();
                    const projectId = projectIdInput.val();
                    const fiscalYearId = fiscalYearInput.val();
                    if (!projectId || !fiscalYearId) {
                        showError('Please select both project and fiscal year');
                        return false;
                    }

                    const formData = new FormData(this);
                    const submitButton = $('#submit-button');
                    const originalButtonText = submitButton.html();
                    submitButton.prop('disabled', true).html(
                        '<svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...'
                        );

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            if (response.requires_confirmation) {
                                pendingFormData = formData;
                                showModal(response.message);
                            } else if (response.success) {
                                clearFormState();
                                showSuccessToast(response.message ||
                                    'Project activities saved successfully!');
                                setTimeout(function() {
                                    if (response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        window.location.reload();
                                    }
                                }, 1500);
                            } else {
                                showError(response.message || 'An error occurred while saving');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 409 && xhr.responseJSON?.requires_confirmation) {
                                pendingFormData = formData;
                                showModal(xhr.responseJSON.message);
                                return;
                            }
                            let errorMessage = 'An error occurred while saving';
                            if (xhr.responseJSON?.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON?.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON?.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join(
                                    '<br>');
                            }
                            showError(errorMessage);
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).html(originalButtonText);
                        }
                    });
                });

                // ============================================================
                // DOWNLOAD TEMPLATE
                // ============================================================
                $('#download-template-btn').on('click', function(e) {
                    e.preventDefault();
                    const projectId = $('#project_id').val() || $(
                        '.js-single-select[data-name="project_id"] .js-hidden-input').val();
                    const fiscalYearId = $('#fiscal_year_id').val() || $(
                        '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input').val();
                    if (!projectId || !fiscalYearId) {
                        showError('Please select both project and fiscal year before downloading template');
                        return;
                    }
                    window.location.href = '{{ route('admin.projectActivity.template') }}?project_id=' +
                        projectId + '&fiscal_year_id=' + fiscalYearId;
                });

                $('#download-new-version-template-btn').on('click', function() {
                    const projectId = $('#project_id').val() || $(
                        '.js-single-select[data-name="project_id"] .js-hidden-input').val();
                    const fiscalYearId = $('#fiscal_year_id').val() || $(
                        '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input').val();
                    if (!projectId || !fiscalYearId) {
                        alert('कृपया पहिले परियोजना र आर्थिक वर्ष छान्नुहोस्।');
                        return;
                    }
                    const url = '{{ route('admin.projectActivity.template') }}?project_id=' + projectId +
                        '&fiscal_year_id=' + fiscalYearId + '&new_version=1';
                    window.location.href = url;
                });

                // ============================================================
                // MODAL & TOASTS
                // ============================================================
                const modal = document.getElementById('structure-change-modal');
                const confirmButton = document.getElementById('confirm-structure-change');
                const cancelButton = document.getElementById('cancel-structure-change');
                const confirmButtonText = document.getElementById('confirm-button-text');
                let pendingFormData = null;

                function showModal(message) {
                    const modalMessage = document.getElementById('modal-message');
                    if (message) modalMessage.textContent = message;
                    modal.classList.remove('hidden');
                }

                function hideModal() {
                    modal.classList.add('hidden');
                    pendingFormData = null;
                    confirmButton.disabled = false;
                    confirmButtonText.textContent = 'Confirm & Proceed';
                }

                cancelButton.addEventListener('click', hideModal);
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) hideModal();
                });

                function showSuccessToast(message) {
                    let toastContainer = document.getElementById('toast-container');
                    if (!toastContainer) {
                        toastContainer = document.createElement('div');
                        toastContainer.id = 'toast-container';
                        toastContainer.className = 'fixed top-4 right-4 z-50 space-y-2';
                        document.body.appendChild(toastContainer);
                    }
                    const toast = document.createElement('div');
                    toast.className =
                        'bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-slide-in';
                    toast.innerHTML =
                        `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>${message}</span>`;
                    toastContainer.appendChild(toast);
                    setTimeout(function() {
                        toast.classList.add('animate-slide-out');
                        setTimeout(function() {
                            toast.remove();
                        }, 300);
                    }, 3000);
                }

                confirmButton.addEventListener('click', function() {
                    if (!pendingFormData) return;
                    pendingFormData.append('confirm_structure_change', '1');
                    confirmButton.disabled = true;
                    confirmButtonText.innerHTML =
                        '<span class="inline-block animate-spin mr-2">⌛</span>Processing...';
                    $.ajax({
                        url: $('#projectActivity-form').attr('action'),
                        method: 'POST',
                        data: pendingFormData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            if (response.success) {
                                hideModal();
                                clearFormState();
                                showSuccessToast(response.message ||
                                    'Project activities saved successfully!');
                                setTimeout(function() {
                                    if (response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        window.location.reload();
                                    }
                                }, 1500);
                            } else {
                                showError(response.message || 'An error occurred');
                                confirmButton.disabled = false;
                                confirmButtonText.textContent = 'Confirm & Proceed';
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while saving';
                            if (xhr.responseJSON) {
                                errorMessage = xhr.responseJSON.message || xhr.responseJSON.error ||
                                    errorMessage;
                            }
                            showError(errorMessage);
                            confirmButton.disabled = false;
                            confirmButtonText.textContent = 'Confirm & Proceed';
                        }
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>
