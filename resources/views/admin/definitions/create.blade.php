<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Project Programs
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} Project Program
        </p>
    </div>

    <form id="projectActivity-form" class="w-full" action="" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4 relative z-[9999]">
                <div class="w-full md:w-1/2 relative z-[9999]">
                    <x-forms.select label="{{ trans('global.projectActivity.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="collect($projectOptions)->firstWhere('selected', true)['value'] ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
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
            <!-- Capital Budget Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.projectActivity.headers.capital') }}
                    </h3>
                    <div class="overflow-x-auto relative">
                        <table id="capital-activities"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600 table-fixed w-full">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-40 bg-gray-200 dark:bg-gray-600 left-sticky">
                                        #</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64 sticky left-12 z-40 bg-gray-200 dark:bg-gray-600 left-sticky">
                                        {{ trans('global.projectActivity.fields.program') }}</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Total Quantity</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Total Cost</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-center sticky right-0 z-40 bg-gray-200 dark:bg-gray-600 right-sticky">
                                        {{ trans('global.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-capital-row" class="mt-4 bg-purple-500 text-white px-4 py-2 rounded">
                        <span class="add-sub-row cursor-pointer text-2xl text-white-400">+</span>
                        {{ trans('global.projectActivity.fields.add_new_row') }}
                    </button>
                    <div class="mt-4 flex justify-between">
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_budget_quantity') }}:
                            <span id="capital-total-quantity">
                                0
                            </span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_capital_budget') }}:
                            <span id="capital-total">
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
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600 table-fixed w-full">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-40 bg-gray-200 dark:bg-gray-600 left-sticky">
                                        #</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64 sticky left-12 z-40 bg-gray-200 dark:bg-gray-600 left-sticky">
                                        {{ trans('global.projectActivity.fields.program') }}</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-right">
                                        Total Quantity</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24 text-right">
                                        Total Cost</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-20 text-center sticky right-0 z-40 bg-gray-200 dark:bg-gray-600 right-sticky">
                                        {{ trans('global.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
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
                            {{ trans('global.projectActivity.fields.total_recurrent_budget_quantity') }}:
                            <span id="recurrent-total-quantity">
                                0
                            </span>
                        </div>
                        <div class="text-lg font-bold">
                            {{ trans('global.projectActivity.fields.total_recurrent_budget') }}:
                            <span id="recurrent-total">
                                0.00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-between">
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_budget_quantity') }}:
                    <span id="overall-total-quantity">
                        0
                    </span>
                </div>
                <div class="text-lg font-bold">
                    {{ trans('global.projectActivity.fields.total_budget') }}:
                    <span id="overall-total">
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
                min-width: 100%;
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
                #capital-activities thead,
                #recurrent-activities thead {
                    background-color: #374151 !important;
                }
            }

            /* NEW: Loading state for tables */
            .table-loading {
                opacity: 0.6;
                pointer-events: none;
            }

            .table-loading::after {
                content: "Loading definitions...";
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.8);
                padding: 10px;
                border-radius: 4px;
                z-index: 10;
            }
        </style>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>
        <script>
            $(document).ready(function() {
                const $ = jQuery;
                let capitalIndex = 1;
                let recurrentIndex = 1;
                const tippyInstances = new WeakMap();

                // Helper to parse numeric input safely (handle integers/decimals)
                function parseNumeric(val) {
                    return parseFloat(val.replace(/,/g, '')) || 0;
                }

                // Wire up change events for project dropdown (loadDefinitions removed as incomplete/unnecessary)
                $(document).on('change', '.js-single-select[data-name="project_id"] .js-hidden-input', function() {
                    const projectId = $(this).val();
                    if (!projectId) {
                        $('#capital-tbody').html('');
                        $('#recurrent-tbody').html('');
                        capitalIndex = 1;
                        recurrentIndex = 1;
                        updateTotals();
                    }
                });

                // Check if table is valid (simplified)
                function isTableValid(section) {
                    let hasErrors = false;
                    $(`#${section}-activities .projectActivity-row`).each(function() {
                        const index = $(this).data('index');
                        if (!validateRow(section, index)) {
                            hasErrors = true;
                        }
                    });
                    validateParentRows(section);
                    return $(`#${section}-activities .error-border`).length === 0;
                }

                function addRow(section, parentIndex = null, depth = 0) {
                    if (!isTableValid(section) || depth > 2) return;

                    const type = section === 'capital' ? 'capital' : 'recurrent';
                    const index = type === 'capital' ? capitalIndex++ : recurrentIndex++;
                    const $tbody = $(`#${section}-tbody`);

                    let hiddenParentInput = '';
                    if (parentIndex !== null) {
                        hiddenParentInput =
                            `<input type="hidden" name="${type}[${index}][parent_id]" value="${parentIndex}">`;
                    }

                    const html = `
                        <tr class="projectActivity-row" data-depth="${depth}" data-index="${index}" ${parentIndex !== null ? `data-parent="${parentIndex}"` : ''}>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200 w-12 sticky left-0 z-30 bg-white dark:bg-gray-800 left-sticky"></td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 w-64 sticky left-12 z-30 bg-white dark:bg-gray-800 left-sticky">
                                ${hiddenParentInput}
                                <input name="${type}[${index}][program]" type="text" class="w-full border-0 p-1 tooltip-error" />
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-20">
                                <input name="${type}[${index}][total_budget_quantity]" type="text" placeholder="0" class="w-full border-0 p-1 text-right total-budget-quantity-input tooltip-error numeric-input" />
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-right w-24">
                                <input name="${type}[${index}][total_budget]" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" class="w-full border-0 p-1 text-right total-budget-input tooltip-error numeric-input" />
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center w-20 sticky right-0 z-30 bg-white dark:bg-gray-800 right-sticky">
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
                        if (!$parentRow.length) return;
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
                        $lastRow.after(html);
                    } else {
                        $tbody.append(html);
                    }

                    const $newRow = $tbody.find(`tr[data-index="${index}"]`);
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
                    addRow(section, parentIndex, depth);
                }

                $(document).on('click', '.add-sub-row', function(e) {
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
                    let capitalQuantityTotal = 0;
                    $('#capital-activities .projectActivity-row[data-depth="0"] .total-budget-quantity-input').each(
                        function() {
                            capitalQuantityTotal += parseInt($(this).val()) || 0;
                        });
                    $('#capital-total-quantity').text(capitalQuantityTotal);

                    let capitalTotal = 0;
                    $('#capital-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(function() {
                        capitalTotal += parseNumeric($(this).val());
                    });
                    $('#capital-total').text(capitalTotal.toFixed(2));

                    let recurrentQuantityTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-quantity-input').each(
                        function() {
                            recurrentQuantityTotal += parseInt($(this).val()) || 0;
                        });
                    $('#recurrent-total-quantity').text(recurrentQuantityTotal);

                    let recurrentTotal = 0;
                    $('#recurrent-activities .projectActivity-row[data-depth="0"] .total-budget-input').each(
                function() {
                        recurrentTotal += parseNumeric($(this).val());
                    });
                    $('#recurrent-total').text(recurrentTotal.toFixed(2));

                    let overallQuantityTotal = capitalQuantityTotal + recurrentQuantityTotal;
                    $('#overall-total-quantity').text(overallQuantityTotal);
                    let overallTotal = capitalTotal + recurrentTotal;
                    $('#overall-total').text(overallTotal.toFixed(2));
                    $('#hidden-total-budget').val(overallTotal.toFixed(2));
                }

                // Simplified validateRow: Only check if total_budget_quantity and total_budget are valid numeric
                function validateRow(section, index) {
                    const $row = $(`#${section}-activities tr[data-index="${index}"]`);
                    const $totalBudgetQuantity = $row.find('.total-budget-quantity-input');
                    const $totalBudget = $row.find('.total-budget-input');
                    let isValid = true;

                    // Validate quantity
                    const quantityValue = $totalBudgetQuantity.val().trim();
                    let quantityMessage = '';
                    if (quantityValue && (isNaN(parseNumeric(quantityValue)) || parseNumeric(quantityValue) < 0)) {
                        isValid = false;
                        quantityMessage = 'Valid non-negative number required';
                    } else if (quantityValue && !/^[0-9]+$/.test(quantityValue)) {
                        isValid = false;
                        quantityMessage = 'Invalid format (integer only)';
                    }

                    // Validate cost
                    const costValue = $totalBudget.val().trim();
                    let costMessage = '';
                    if (costValue && (isNaN(parseNumeric(costValue)) || parseNumeric(costValue) < 0)) {
                        isValid = false;
                        costMessage = 'Valid non-negative number required';
                    } else if (costValue && !/^[0-9]+(\.[0-9]{1,2})?$/.test(costValue)) {
                        isValid = false;
                        costMessage = 'Invalid format (up to 2 decimals)';
                    }

                    if (!isValid) {
                        if (quantityMessage) {
                            $totalBudgetQuantity.addClass('error-border');
                            updateTooltip($totalBudgetQuantity, quantityMessage);
                        } else {
                            $totalBudgetQuantity.removeClass('error-border');
                            updateTooltip($totalBudgetQuantity, '');
                        }
                        if (costMessage) {
                            $totalBudget.addClass('error-border');
                            updateTooltip($totalBudget, costMessage);
                        } else {
                            $totalBudget.removeClass('error-border');
                            updateTooltip($totalBudget, '');
                        }
                    } else {
                        $totalBudgetQuantity.removeClass('error-border');
                        $totalBudget.removeClass('error-border');
                        updateTooltip($totalBudgetQuantity, '');
                        updateTooltip($totalBudget, '');
                    }

                    return isValid;
                }

                function getFieldFromInput($input) {
                    const name = $input.attr('name');
                    if (name.includes('[total_budget_quantity]')) return 'total_budget_quantity';
                    if (name.includes('[total_budget]')) return 'total_budget';
                    return null;
                }

                function validateParentRow(section, parentIndex) {
                    if (!parentIndex) return;
                    const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                    if (!$parentRow.length) return;
                    const $childRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`);
                    if ($childRows.length === 0) return;

                    // Check quantity
                    const quantitySelector = '.total-budget-quantity-input';
                    const $parentQuantityInput = $parentRow.find(quantitySelector);
                    if ($parentQuantityInput.length) {
                        let childQuantitySum = 0;
                        $childRows.each(function() {
                            const $childInput = $(this).find(quantitySelector);
                            childQuantitySum += parseInt($childInput.val()) || 0;
                        });
                        const parentQuantityValue = parseInt($parentQuantityInput.val()) || 0;
                        if (childQuantitySum > parentQuantityValue) {
                            const message =
                                `Children quantity sum (${childQuantitySum}) exceeds parent quantity (${parentQuantityValue})`;
                            $parentQuantityInput.addClass('error-border');
                            updateTooltip($parentQuantityInput, message);
                            $childRows.find(quantitySelector).addClass('error-border');
                            $childRows.each(function() {
                                const $childInput = $(this).find(quantitySelector);
                                updateTooltip($childInput, message);
                            });
                        } else {
                            clearChildErrors($childRows, quantitySelector);
                        }
                    }

                    // Check cost
                    const costSelector = '.total-budget-input';
                    const $parentCostInput = $parentRow.find(costSelector);
                    if ($parentCostInput.length) {
                        let childCostSum = 0;
                        $childRows.each(function() {
                            const $childInput = $(this).find(costSelector);
                            childCostSum += parseNumeric($childInput.val());
                        });
                        const parentCostValue = parseNumeric($parentCostInput.val());
                        if (childCostSum > parentCostValue + 0.01) {
                            const message =
                                `Children cost sum (${childCostSum.toFixed(2)}) exceeds parent cost (${parentCostValue.toFixed(2)})`;
                            $parentCostInput.addClass('error-border');
                            updateTooltip($parentCostInput, message);
                            $childRows.find(costSelector).addClass('error-border');
                            $childRows.each(function() {
                                const $childInput = $(this).find(costSelector);
                                updateTooltip($childInput, message);
                            });
                        } else {
                            clearChildErrors($childRows, costSelector);
                        }
                    }

                    validateParentRow(section, $parentRow.data('parent')); // Recurse
                }

                function clearChildErrors($childRows, selector) {
                    $childRows.each(function() {
                        const $childInput = $(this).find(selector);
                        const currentTooltip = tippyInstances.get($childInput[0])?.props.content || '';
                        if (currentTooltip.includes('exceeds parent')) {
                            $childInput.removeClass('error-border');
                            updateTooltip($childInput, '');
                        }
                    });
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

                // Global handler for validation + capping (children only)
                $(document).on('input', '.total-budget-quantity-input, .total-budget-input', function() {
                    const $input = $(this);
                    const $row = $input.closest('tr');
                    const section = $row.closest('table').attr('id').replace('-activities', '');
                    const index = $row.data('index');
                    const depth = $row.data('depth') || 0;
                    const field = getFieldFromInput($input);

                    // CAPS FOR CHILDREN ONLY
                    if (depth > 0 && field) {
                        const parentIndex = $row.data('parent');
                        const $parentRow = $(`#${section}-activities tr[data-index="${parentIndex}"]`);
                        const $siblingRows = $(`#${section}-activities tr[data-parent="${parentIndex}"]`).not(
                            $row);
                        let selector = field === 'total_budget_quantity' ? '.total-budget-quantity-input' :
                            '.total-budget-input';
                        const $parentInput = $parentRow.find(selector);
                        const parentValue = field === 'total_budget_quantity' ? parseInt($parentInput.val()) ||
                            0 : parseNumeric($parentInput.val());
                        let childValue = field === 'total_budget_quantity' ? parseInt($input.val()) || 0 :
                            parseNumeric($input.val());
                        let sumSiblings = 0;
                        $siblingRows.each(function() {
                            const $siblingInput = $(this).find(selector);
                            sumSiblings += field === 'total_budget_quantity' ? parseInt($siblingInput
                                .val()) || 0 : parseNumeric($siblingInput.val());
                        });
                        const maxAllowed = Math.max(0, parentValue - sumSiblings);
                        if (childValue > maxAllowed) {
                            childValue = maxAllowed;
                            const decimals = field === 'total_budget_quantity' ? 0 : 2;
                            $input.val(childValue.toFixed(decimals));
                            $input.addClass('error-border');
                            updateTooltip($input,
                                `Capped at remaining (${maxAllowed.toFixed(decimals)}) for ${field === 'total_budget_quantity' ? 'quantity' : 'cost'}`
                                );
                            $parentInput.addClass('error-border');
                            updateTooltip($parentInput,
                                `Children sum for ${field === 'total_budget_quantity' ? 'quantity' : 'cost'} exceeds parent`
                                );
                        } else {
                            $input.removeClass('error-border');
                            updateTooltip($input, '');
                        }
                    }

                    // ALWAYS VALIDATE PARENTS FIRST (hierarchy)
                    validateParentRows(section);
                    // THEN VALIDATE ROW
                    validateRow(section, index);
                    // UPDATE TOTALS
                    updateTotals();
                });

                // Validate numerics on blur
                $(document).on('blur', '.numeric-input', function() {
                    const $input = $(this);
                    const val = $input.val();
                    const num = parseNumeric(val);
                    if (!isNaN(num) && num >= 0) {
                        const isQuantity = $input.hasClass('total-budget-quantity-input');
                        const decimals = isQuantity ? 0 : 2;
                        $input.val(num.toFixed(decimals));
                    }
                });

                initializeTooltips($('.tooltip-error'));

                // FORCE VALIDATE ALL ON LOAD
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
                                const isProgram = $input.is('[name*="[program]"]');
                                const isQuantity = $input.hasClass(
                                    'total-budget-quantity-input');
                                if (!isProgram && (!value || isNaN(parseNumeric(
                                        value)) || parseNumeric(value) < 0)) {
                                    $input.addClass('error-border');
                                    updateTooltip($input,
                                        'Valid non-negative number required');
                                    hasErrors = true;
                                } else if (!isProgram && value) {
                                    const pattern = isQuantity ? /^[0-9]+$/ :
                                        /^[0-9]+(\.[0-9]{1,2})?$/;
                                    if (!pattern.test(value)) {
                                        $input.addClass('error-border');
                                        updateTooltip($input, isQuantity ?
                                            'Invalid format (integer only)' :
                                            'Invalid format (up to 2 decimals)');
                                        hasErrors = true;
                                    }
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

                    $form.off('submit').submit();
                });

                $("#close-error").on('click', function() {
                    $("#error-message").addClass("hidden");
                    $("#error-text").text("");
                    $('.tooltip-error').removeClass('error-border');
                    $('.tooltip-error').each(function() {
                        updateTooltip($(this), '');
                    });
                });

                // Add initial rows
                addRow('capital');
                addRow('recurrent');
                updateRowNumbers('capital');
                updateRowNumbers('recurrent');
                updateTotals();
                validateParentRows('capital');
                validateParentRows('recurrent');
            });
        </script>
    @endpush
</x-layouts.app>
