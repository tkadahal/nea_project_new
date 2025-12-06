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
                    Download Excel Template
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
                Upload Excel
            </a>
        </div>
    </div>

    <form id="projectActivity-form" class="w-full" action="{{ route('admin.projectActivity.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf

        <div
            class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="$selectedProjectId ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYearOptions" :selected="collect($fiscalYearOptions)->firstWhere('selected', true)['value'] ?? ''"
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
                                @include('admin.project-activities.tables.capital-rows', [
                                    'rows' => $capitalRows ?? [],
                                ])
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-capital-row" class="mt-4 bg-green-500 text-white px-4 py-2 rounded">
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
                                @include('admin.project-activities.tables.recurrent-rows', [
                                    'rows' => $recurrentRows ?? [],
                                ])
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

                // Expose indices globally for cross-script access
                window.capitalIndex = {{ $capitalNextIndex ?? 2 }};
                window.recurrentIndex = {{ $recurrentNextIndex ?? 2 }};
                let capitalIndex = window.capitalIndex;
                let recurrentIndex = window.recurrentIndex;
                const tippyInstances = new WeakMap();

                // Helper to parse numeric input safely (handle integers/decimals)
                function parseNumeric(val) {
                    return parseFloat(val.replace(/,/g, '')) || 0;
                }

                // Check if table is valid: only errors on exceeds (row quarters > planned or child sum > parent)
                function isTableValid(section) {
                    $(`#${section}-activities .projectActivity-row`).each(function() {
                        const index = $(this).data('index');
                        validateRow(section, index);
                    });
                    validateParentRows(section);
                    const hasErrors = $(`#${section}-activities .error-border`).length > 0;
                    console.log(`Table ${section} validation: ${!hasErrors}`);
                    return !hasErrors;
                }

                function addRow(section, parentIndex = null, depth = 0) {
                    if (!isTableValid(section)) {
                        $("#error-message").removeClass("hidden");
                        $("#error-text").text(
                            "Cannot add row: Please correct exceeds (quarters sum > planned or child sum > parent)."
                        );
                        console.warn(`Cannot add row in ${section}: Exceeds present`);
                        return;
                    }
                    if (depth > 2) {
                        console.warn(`Cannot add row in ${section}: Maximum depth (2) reached`);
                        return;
                    }
                    const type = section === 'capital' ? 'capital' : 'recurrent';
                    const index = type === 'capital' ? capitalIndex++ : recurrentIndex++;
                    window.capitalIndex = capitalIndex; // Sync global
                    window.recurrentIndex = recurrentIndex;
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
                                ${(depth > 0 || index > 1) ? `
                                                                                                                                                                    <span class="remove-row cursor-pointer text-2xl text-red-500">
                                                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                                                                                                        </svg>
                                                                                                                                                                    </span>
                                                                                                                                                                ` : ''}
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
                        restrictChildInputs(section, index, parentIndex);
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
                            "Cannot add sub-row: Please correct exceeds (quarters sum > planned or child sum > parent)."
                        );
                        console.warn(`Cannot add sub-row in ${section}: Exceeds present`);
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

                    let recurrentTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(
                        function() {
                            recurrentTotal += parseNumeric($(this).val());
                        });
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));
                }

                function validateRow(section, index) {
                    const $row = $(`#${section}-activities tr[data-index="${index}"]`);
                    const $plannedBudget = $row.find('.planned-budget-input');
                    const $quarters = $row.find('.quarter-input');
                    let quarterSum = 0;
                    $quarters.each(function() {
                        quarterSum += parseNumeric($(this).val());
                    });
                    const plannedBudget = parseNumeric($plannedBudget.val());
                    if (quarterSum > plannedBudget + 0.01) { // Only error on exceed
                        const message =
                            `Quarters sum (${quarterSum.toFixed(2)}) exceeds planned budget (${plannedBudget.toFixed(2)})`;
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
                    console.log(`Row ${index} validated: quarters ${quarterSum}, planned ${plannedBudget}`);
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
                    const $childRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`);
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
                        if (childSum > parentValue + 0.01) { // Only error on exceed
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

                function restrictChildInputs(section, childIndex, parentIndex) {
                    const $childRow = $(`#${section}-activities tr[data-index="${childIndex}"]`);
                    const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                    const $childRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`);
                    const childInputs = {
                        'total_budget': '.total-budget-input',
                        'total_expense': '.expenses-input',
                        'planned_budget': '.planned-budget-input',
                        'q1': '.quarter-input[name*="[q1]"]',
                        'q2': '.quarter-input[name*="[q2]"]',
                        'q3': '.quarter-input[name*="[q3]"]',
                        'q4': '.quarter-input[name*="[q4]"]'
                    };

                    $childRow.off('input', '.numeric-input').on('input', '.numeric-input', function() {
                        const $input = $(this);
                        const field = getFieldFromInput($input);
                        if (!field || !childInputs[field]) return;

                        const selector = childInputs[field];
                        const $parentInput = $parentRow.find(selector);
                        const parentValue = parseNumeric($parentInput.val());
                        let childValue = parseNumeric($input.val());

                        let sumSiblings = 0;
                        $childRows.not($childRow).each(function() {
                            const $sibInput = $(this).find(selector);
                            sumSiblings += parseNumeric($sibInput.val());
                        });

                        const maxAllowed = Math.max(0, parentValue - sumSiblings);
                        if (childValue > maxAllowed + 0.01) {
                            childValue = maxAllowed;
                            $input.val(childValue.toFixed(childValue % 1 === 0 ? 0 :
                                2)); // No forced decimal for integers
                            $input.addClass('error-border');
                            updateTooltip($input,
                                `Capped at remaining (${maxAllowed.toFixed(2)}) for ${field}`);
                            $parentInput.addClass('error-border');
                            updateTooltip($parentInput, `Children sum for ${field} exceeds parent`);
                        } else {
                            $input.removeClass('error-border');
                            updateTooltip($input, '');
                        }

                        validateParentRow(section, parentIndex);
                    });
                }

                $(document).on('input', '.total-budget-input, .expenses-input, .planned-budget-input, .quarter-input',
                    function() {
                        const $input = $(this);
                        const $row = $input.closest('tr');
                        const section = $row.closest('table').attr('id').replace('-activities', '');
                        const index = $row.data('index');
                        const field = getFieldFromInput($input);
                        console.log(`Input in ${section} row ${index}: ${$input.val()}`);
                        validateRow(section, index);
                        validateParentRows(section);
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

                $('.projectActivity-row[data-parent]').each(function() {
                    const $row = $(this);
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const index = $row.data('index');
                    const parentIndex = $row.data('parent');
                    restrictChildInputs(section, index, parentIndex);
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
                            validateRow(section, index);
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
            // TEST: Run on project change to debug URL
            $(document).ready(function() {
                $('.js-single-select[data-name="project_id"] .js-hidden-input').on('change', function() {
                    const projectId = String($(this).val()).trim();
                    const fiscalYearId = String($(
                        '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input').val()).trim();
                    console.log('TEST: projectId =', projectId, 'type:', typeof projectId);

                    let queryParts = ['project_id=' + encodeURIComponent(projectId)];
                    if (fiscalYearId) queryParts.push('fiscal_year_id=' + encodeURIComponent(fiscalYearId));
                    const testQuery = queryParts.join('&');
                    const testUrl = '/admin/projectActivity/rows?' + testQuery;
                    console.log('TEST: Built URL =', testUrl); // Should be ?project_id=131

                    // Quick manual AJAX test with jQuery
                    $.get(testUrl, function(data) {
                        console.log('TEST AJAX SUCCESS:', data);
                    }).fail(function(xhr) {
                        console.log('TEST AJAX FAIL:', xhr.status, xhr.responseText);
                    });
                });
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
