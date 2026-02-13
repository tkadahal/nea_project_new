<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.projectActivity.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.edit') }} {{ trans('global.projectActivity.title_singular') }}
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            <!-- Back button - left side -->
            @can('projectActivity_access')
                <a href="{{ route('admin.projectActivity.index') }}"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                    {{ trans('global.back_to_list') }}
                </a>
            @endcan

            <!-- Action Buttons - right side -->
            <div class="flex flex-wrap items-center gap-4">
                <button type="button" id="download-template-btn"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                    title="Download Excel Template">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z">
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
    </div>

    <form id="projectActivity-form" class="w-full"
        action="{{ route('admin.projectActivity.update', [$projectId, $fiscalYearId]) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Hidden inputs to track initial state (page load) -->
        <input type="hidden" id="initial-capital-ids" name="initial_capital_ids"
            value="{{ collect($capitalRows)->pluck('id')->implode(',') }}">
        <input type="hidden" id="initial-recurrent-ids" name="initial_recurrent_ids"
            value="{{ collect($recurrentRows)->pluck('id')->implode(',') }}">

        <!-- Project & Fiscal Year Selection -->
        <div
            class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.projectActivity.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="$projectId"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.projectActivity.fields.fiscal_year_id') }}"
                        name="fiscal_year_id" id="fiscal_year_id" :options="$fiscalYears" :selected="$fiscalYearId"
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
            class="mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-gray-700 dark:text-red-200">
            <span id="error-text"></span>
            <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20">
                    <path
                        d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                </svg>
            </button>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-200"
                role="alert">
                <strong class="font-bold">{{ trans('global.error') }}!</strong>
                <span class="block sm:inline">{{ trans('global.validation_failed_message') }}:</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                                @forelse($capitalRows as $activity)
                                    @include('admin.projectActivities.partials.activity-row', [
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
                                @forelse($recurrentRows as $activity)
                                    @include('admin.projectActivities.partials.activity-row', [
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
                <a href="{{ route('admin.projectActivity.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 ml-2">
                    {{ trans('global.cancel') }}
                </a>
            </div>
        </div>
    </form>

    <!-- Structural Change Confirmation Modal -->
    @if (session('requires_structural_confirmation'))
        <div id="structuralChangeModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('global.projectActivity.structural_change_title') ?? 'Structural Change Detected' }}
                        </h3>
                        <button type="button" id="close-modal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-6 text-gray-700 dark:text-gray-300">
                        <p class="mb-3">
                            {{ session('structural_warning') }}
                        </p>
                        <p class="font-semibold text-red-600 dark:text-red-400">
                            This will create a <strong>new version</strong> of the activity structure and <strong>reset
                                all quarterly plans</strong>.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" id="cancel-structural-change"
                            class="px-5 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            {{ __('global.cancel') }}
                        </button>

                        <button type="button" id="confirm-structural-change"
                            class="px-5 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                            Yes, Create New Version
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('confirm-structural-change').addEventListener('click', function() {
                const form = document.getElementById('projectActivity-form');

                // Add hidden input to force new version
                let forceInput = document.createElement('input');
                forceInput.type = 'hidden';
                forceInput.name = 'force_structural_change';
                forceInput.value = '1';
                form.appendChild(forceInput);

                form.submit();
            });

            document.getElementById('cancel-structural-change').addEventListener('click', () => {
                document.getElementById('structuralChangeModal').remove();
            });

            document.getElementById('close-modal').addEventListener('click', () => {
                document.getElementById('structuralChangeModal').remove();
            });
        </script>
    @endif

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
                // FIX 1: Inject PHP variables to ensure initial load works
                const initialProjectId = "{{ $projectId }}";
                const initialFiscalYearId = "{{ $fiscalYearId }}";

                const projectIdInput = $('.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalYearInput = $('.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const $budgetDisplay = $('#budget-display');

                // Ensure inputs have values immediately (fixes custom select timing issues)
                if (projectIdInput.val() === '' && initialProjectId) projectIdInput.val(initialProjectId);
                if (fiscalYearInput.val() === '' && initialFiscalYearId) fiscalYearInput.val(initialFiscalYearId);

                // Remove "no data" rows helper
                function removeNoDataRows() {
                    $('.no-data-row').remove();
                }

                // Calculate totals
                function updateTotals() {
                    let capitalTotal = 0,
                        capitalPlannedTotal = 0;
                    let recurrentTotal = 0,
                        recurrentPlannedTotal = 0;

                    // FIX 2: Updated selectors to match Name Attributes.
                    // Using .total-budget-input class was failing because the inputs don't have that class.
                    $('#capital-tbody tr.activity-row').each(function() {
                        const $row = $(this);
                        capitalTotal += parseFloat($row.find('input[name$="[total_budget]"]').val()) || 0;
                        capitalPlannedTotal += parseFloat($row.find('input[name$="[planned_budget]"]').val()) ||
                            0;
                    });

                    $('#recurrent-tbody tr.activity-row').each(function() {
                        const $row = $(this);
                        recurrentTotal += parseFloat($row.find('input[name$="[total_budget]"]').val()) || 0;
                        recurrentPlannedTotal += parseFloat($row.find('input[name$="[planned_budget]"]')
                            .val()) || 0;
                    });

                    $('#capital-total').text(capitalTotal.toFixed(2));
                    $('#capital-planned-total').text(capitalPlannedTotal.toFixed(2));
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));
                    $('#recurrent-planned-total').text(recurrentPlannedTotal.toFixed(2));
                    $('#overall-total').text((capitalTotal + recurrentTotal).toFixed(2));
                    $('#overall-planned-total').text((capitalPlannedTotal + recurrentPlannedTotal).toFixed(2));
                }

                // Find last descendant
                function findLastDescendant($row, $table) {
                    const rowId = $row.data('id');
                    let $last = $row;
                    $table.find('tr[data-parent-id="' + rowId + '"]').each(function() {
                        $last = findLastDescendant($(this), $table);
                    });
                    return $last;
                }

                // Remove row and descendants
                function removeRowAndDescendants($row) {
                    const rowId = $row.data('id');
                    $('tr[data-parent-id="' + rowId + '"]').each(function() {
                        removeRowAndDescendants($(this));
                    });
                    $row.remove();

                    // Re-add no-data if empty
                    const $tbody = $row.closest('tbody');
                    if ($tbody.find('tr.activity-row').length === 0) {
                        $tbody.html(
                            '<tr class="no-data-row"><td colspan="17" class="border px-2 py-4 text-center text-gray-500">No activities yet. Click "Add Row" to create one.</td></tr>'
                        );
                    }
                }

                // Bind events to row
                function bindRowEvents($row) {
                    // FIX 2: Updated selectors to use Name Attributes for reliability
                    $row.find(
                        'input[name$="[program]"], ' +
                        'input[name$="[total_budget]"], ' +
                        'input[name$="[total_budget_quantity]"], ' +
                        'input[name$="[total_expense_quantity]"], ' +
                        'input[name$="[total_expense]"], ' +
                        'input[name$="[planned_budget]"], ' +
                        'input[name$="[planned_budget_quantity]"], ' +
                        'input[name$="[q1_quantity]"], ' +
                        'input[name$="[q1]"], ' +
                        'input[name$="[q2_quantity]"], ' +
                        'input[name$="[q2]"], ' +
                        'input[name$="[q3_quantity]"], ' +
                        'input[name$="[q3]"], ' +
                        'input[name$="[q4_quantity]"], ' +
                        'input[name$="[q4]"]'
                    ).on('blur', function() {
                        const $input = $(this);
                        if ($input.hasClass('numeric-input')) {
                            let val = parseFloat($input.val()) || 0;
                            $input.val(val.toFixed(2));
                        }
                        updateTotals();
                    });
                }

                // Add sub-row handler
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

                    const currentDepth = parseInt($btn.closest('tr').data('depth')) || 0;
                    if (currentDepth >= 2) {
                        showError('Maximum depth (2 levels) reached');
                        return;
                    }

                    $btn.prop('disabled', true).text('...');

                    $.ajax({
                        url: '{{ route('admin.projectActivity.addRow') }}',
                        method: 'POST',
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
                            $newRow.attr('data-id', response.row.id);
                            $newRow.attr('data-depth', response.row.depth);
                            $newRow.attr('data-parent-id', response.row.parent_id || '');
                            $newRow.attr('data-sort-index', response.row.sort_index);
                            bindRowEvents($newRow);
                            updateTotals();
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

                // Top-level add
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
                            $newRow.attr('data-id', response.row.id);
                            $newRow.attr('data-depth', response.row.depth);
                            $newRow.attr('data-parent-id', response.row.parent_id || '');
                            $newRow.attr('data-sort-index', response.row.sort_index);
                            bindRowEvents($newRow);
                            updateTotals();
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

                // Delete handler
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

                            // Remove row and descendants from view
                            removeRowAndDescendants($row);

                            // Reload the activities to show updated indices
                            reloadActivities(projectId, expenditureId, $tbody);

                            updateTotals();
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON?.error || 'Failed to delete row');
                        }
                    });
                });

                // Helper function to reload activities after deletion
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

                                // Re-bind events to new rows
                                $tbody.find('.activity-row').each(function() {
                                    bindRowEvents($(this));
                                });

                                updateTotals();
                            }
                        },
                        error: function() {
                            console.error('Failed to reload activities');
                        }
                    });
                }

                // Numeric input validation
                $(document).on('input', '.numeric-input', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                });

                // Show error message
                function showError(message) {
                    $('#error-text').text(message);
                    $('#error-message').removeClass('hidden');
                }

                // Close error
                $('#close-error').on('click', function() {
                    $('#error-message').addClass('hidden');
                });

                // Form submit validation
                // $('#projectActivity-form').on('submit', function(e) {
                //     const projectId = projectIdInput.val();
                //     const fiscalYearId = fiscalYearInput.val();
                //     if (!projectId || !fiscalYearId) {
                //         // e.preventDefault();
                //         showError('Please select both project and fiscal year');
                //         return false;
                //     }
                // });

                // Load Budget Data
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

                // Listen for changes
                projectIdInput.on('change', loadBudgetData);
                fiscalYearInput.on('change', loadBudgetData);

                // Initial load
                loadBudgetData();
                updateTotals();

                // Initial bind for existing rows
                $('.activity-row').each(function() {
                    bindRowEvents($(this));
                });

                // Download template handler
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
            });
        </script>
    @endpush
</x-layouts.app>
