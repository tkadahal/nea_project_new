<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.expense.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.expense.title_singular') }}
        </p>
    </div>

    <form id="projectExpense-form" class="w-full" action="{{ route('admin.projectExpense.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf

        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/3 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="collect($projectOptions)->firstWhere('selected', true)['value'] ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/3 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYears" :selected="$selectedFiscalYearId ??
                            (collect($fiscalYears)->firstWhere('selected', true)['value'] ?? '')"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/3 relative z-50">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Quarter <span class="text-red-500">*</span>
                    </label>
                    <select id="quarter_selector" name="selected_quarter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        required>
                        <option value="">{{ trans('global.pleaseSelect') }}</option>
                        <option value="q1" {{ ($selectedQuarter ?? '') === 'q1' ? 'selected' : '' }}>Q1</option>
                        <option value="q2" {{ ($selectedQuarter ?? '') === 'q2' ? 'selected' : '' }}>Q2</option>
                        <option value="q3" {{ ($selectedQuarter ?? '') === 'q3' ? 'selected' : '' }}>Q3</option>
                        <option value="q4" {{ ($selectedQuarter ?? '') === 'q4' ? 'selected' : '' }}>Q4</option>
                    </select>
                    @error('selected_quarter')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @if (isset($quarterStatus))
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex gap-2">
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full {{ $quarterStatus['q1'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                Q1
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full {{ $quarterStatus['q2'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                Q2
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full {{ $quarterStatus['q3'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                Q3
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full {{ $quarterStatus['q4'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                Q4
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end mt-4 gap-2">
                <button id="download-template" type="button"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg shadow-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Download Template
                </button>
                <label for="excel-upload"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 cursor-pointer">
                    Upload Excel
                </label>
                <input type="file" id="excel-upload" accept=".xlsx,.xls" class="hidden">
            </div>

            <div id="budget-display"
                class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <span class="block text-sm text-blue-700 dark:text-blue-300">
                    Select a project, fiscal year, and quarter to view budget details and load activities.
                </span>
            </div>

            <div id="loading-indicator" class="mt-4 hidden">
                <div class="flex items-center justify-center p-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Loading activities...</span>
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

            <!-- Capital Expenses Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Capital Expenses - <span id="capital-quarter-label">Select Quarter</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th rowspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #</th>
                                    <th rowspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Annual Target</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Quarterly Target</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Quarterly Expense</th>
                                </tr>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Budget</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Budget</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                                <tr id="capital-empty">
                                    <td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Select a project, fiscal year, and quarter to load capital expenses
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Capital Total -->
                    <div
                        class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                Total Amount: <span id="capital-total-amt">0.00</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recurrent Expenses Section -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Recurrent Expenses - <span id="recurrent-quarter-label">Select Quarter</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th rowspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #</th>
                                    <th rowspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Annual Target</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Quarterly Target</th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">
                                        Quarterly Expense</th>
                                </tr>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Budget</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Budget</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
                                <tr id="recurrent-empty">
                                    <td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Select a project, fiscal year, and quarter to load recurrent expenses
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Recurrent Total -->
                    <div
                        class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                Total Amount: <span id="recurrent-total-amt">0.00</span>
                            </span>
                        </div>
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
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>

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

            .projectExpense-row[data-depth="1"] td:nth-child(2) {
                padding-left: 20px;
            }

            .projectExpense-row[data-depth="2"] td:nth-child(2) {
                padding-left: 40px;
            }

            .projectExpense-row[data-depth="3"] td:nth-child(2) {
                padding-left: 60px;
            }

            .expense-input {
                width: 100%;
                border: none;
                background: transparent;
                text-align: right;
                padding: 0;
                margin: 0;
                font: inherit;
                color: inherit;
            }

            .expense-input:focus {
                background-color: rgba(59, 130, 246, 0.1);
                border: 1px solid #3b82f6;
                border-radius: 2px;
                padding: 1px 2px;
            }

            @media (max-width: 768px) {
                table {
                    font-size: 0.875rem;
                }
            }
        </style>

        <script>
            function waitForJQuery(callback, maxRetries = 50, interval = 100) {
                if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.jquery) {
                    callback(jQuery);
                } else if (maxRetries > 0) {
                    setTimeout(() => waitForJQuery(callback, maxRetries - 1, interval), interval);
                }
            }

            waitForJQuery(function($) {
                const tippyInstances = new WeakMap();
                let parentMap = {};
                let parentToChildren = {};
                let activityElements = {};
                let selectedQuarter = '';
                let lastProjectValue = '';
                let lastFiscalValue = '';

                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');

                if (projectHidden) lastProjectValue = projectHidden.value || '';
                if (fiscalHidden) lastFiscalValue = fiscalHidden.value || '';

                function parseNumeric(val) {
                    return parseFloat((val || '0').replace(/,/g, '')) || 0;
                }

                function formatNumber(num, decimals = 2) {
                    return Number(num).toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    });
                }

                function checkSelections() {
                    const hasAll = !!(lastProjectValue && lastFiscalValue && selectedQuarter);
                    $('#download-template').prop('disabled', !hasAll);
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
                    const instance = tippyInstances.get($element[0]);
                    if (instance) {
                        instance.setContent(message || ' ');
                        instance[message ? 'show' : 'hide']();
                    }
                }

                // ==================== INPUT FORMATTING: BOTH QTY & AMT ACCEPT DECIMALS ====================

                // Unified handler for both qty and amt fields (both allow decimals now)
                $(document).on('input', '.expense-input:not([disabled])', function() {
                    let value = this.value.replace(/[^0-9.]/g, ''); // Allow only digits and dot

                    // Prevent multiple decimal points
                    const parts = value.split('.');
                    if (parts.length > 2) {
                        value = parts[0] + '.' + parts.slice(1).join('');
                    }

                    // Limit to 2 decimal places
                    if (value.includes('.')) {
                        const [integer, decimal] = value.split('.');
                        if (decimal && decimal.length > 2) {
                            value = integer + '.' + decimal.substring(0, 2);
                        }
                    }

                    // Add thousand separators
                    const formatted = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                    this.value = formatted;
                });

                // Paste handling (clean invalid chars)
                $(document).on('paste', '.expense-input:not([disabled])', function(e) {
                    e.preventDefault();
                    const text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                    this.value = text.replace(/[^0-9.]/g, '');
                    $(this).trigger('input');
                });

                // ==================== VALIDATION & TOTALS ====================

                function validateParent(pId, type) {
                    const children = parentToChildren[pId];
                    if (!children || children.length === 0) return true;

                    const pInput = $(activityElements[pId]).find(`input[data-type="${type}"]`);
                    if (pInput.is(':disabled')) return true;

                    let childSum = 0;
                    children.forEach(cId => {
                        const cInput = $(activityElements[cId]).find(`input[data-type="${type}"]`);
                        childSum += parseNumeric(cInput.val());
                    });

                    const pVal = parseNumeric(pInput.val());
                    const diff = Math.abs(childSum - pVal);

                    if (diff > 0.01) {
                        const message = childSum > pVal ?
                            `Children sum EXCEEDS parent by ${(childSum - pVal).toFixed(2)}` :
                            `Children sum is ${(pVal - childSum).toFixed(2)} LESS than parent`;

                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(`input[data-type="${type}"]`);
                            cInput.addClass('error-border');
                            updateTooltip(cInput, message);
                        });
                        return false;
                    } else {
                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(`input[data-type="${type}"]`);
                            cInput.removeClass('error-border');
                            updateTooltip(cInput, '');
                        });
                        return true;
                    }
                }

                function validateAllAncestors(activityId, type) {
                    let current = activityId;
                    while (current) {
                        const parent = parentMap[current];
                        if (parent) validateParent(parent, type);
                        current = parent;
                    }
                }

                function updateTotals(section) {
                    let totalAmt = 0;
                    $(`#${section}-tbody tr[data-index][data-depth="0"]`).each(function() {
                        const id = parseInt($(this).data('index'));
                        totalAmt += getActivityTotal(id, 'amt');
                    });
                    $(`#${section}-total-amt`).text(formatNumber(totalAmt, 2));

                    Object.keys(parentToChildren).forEach(pId => {
                        const children = parentToChildren[pId];
                        let qtySum = 0,
                            amtSum = 0;
                        children.forEach(cId => {
                            qtySum += getActivityTotal(cId, 'qty');
                            amtSum += getActivityTotal(cId, 'amt');
                        });
                        $(`.total-display[data-parent-id="${pId}"][data-type="qty"]`).text(formatNumber(qtySum,
                            2));
                        $(`.total-display[data-parent-id="${pId}"][data-type="amt"]`).text(formatNumber(amtSum,
                            2));

                        const pQty = $(activityElements[pId]).find('input[data-type="qty"]');
                        const pAmt = $(activityElements[pId]).find('input[data-type="amt"]');
                        if (pQty.is(':disabled')) pQty.val(formatNumber(qtySum, 2));
                        if (pAmt.is(':disabled')) pAmt.val(formatNumber(amtSum, 2));
                    });
                }

                function getActivityTotal(id, type) {
                    const children = parentToChildren[id];
                    if (children && children.length > 0) {
                        return children.reduce((sum, cId) => sum + getActivityTotal(cId, type), 0);
                    }
                    const val = $(activityElements[id]).find(`input[data-type="${type}"]`).val();
                    return parseNumeric(val);
                }

                // ==================== LOAD & POPULATE ACTIVITIES ====================

                function loadProjectActivities(projectId, fiscalYearId, quarter) {
                    if (!projectId || !fiscalYearId || !quarter) {
                        $('#capital-tbody, #recurrent-tbody').html(
                            '<tr><td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project, fiscal year, and quarter to load expenses</td></tr>'
                        );
                        $('#budget-display').html(
                            '<span class="block text-sm text-blue-700 dark:text-blue-300">Select a project, fiscal year, and quarter to view budget details and load activities.</span>'
                            );
                        updateTotals('capital');
                        updateTotals('recurrent');
                        return;
                    }

                    selectedQuarter = quarter;
                    $('#capital-quarter-label, #recurrent-quarter-label').text(
                        `Quarter ${quarter.replace('q', '').toUpperCase()}`);

                    $('#loading-indicator').removeClass('hidden');
                    $('#submit-button').prop('disabled', true);

                    $.ajax({
                        url: `/admin/projectExpense/${projectId}/${fiscalYearId}`,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            $('#loading-indicator').addClass('hidden');
                            $('#submit-button').prop('disabled', false);

                            if (response.success === false || response.error) {
                                showError(response.error || response.message ||
                                'Failed to load activities');
                                return;
                            }

                            if (response.budgetDetails) {
                                $('#budget-display').html(
                                    `<div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md"><span class="block text-sm font-semibold text-green-700 dark:text-green-300">${response.budgetDetails}</span></div>`
                                );
                            }

                            parentMap = {};
                            parentToChildren = {};
                            activityElements = {};

                            populateActivities('capital', response.capital || []);
                            populateActivities('recurrent', response.recurrent || []);
                            checkSelections();
                        },
                        error: function() {
                            $('#loading-indicator').addClass('hidden');
                            $('#submit-button').prop('disabled', false);
                            showError('Failed to load project activities');
                        }
                    });
                }

                function populateActivities(section, activities) {
                    const tbody = $(`#${section}-tbody`);
                    if (!activities || activities.length === 0) {
                        tbody.html(
                            `<tr id="${section}-empty"><td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">No ${section} activities found</td></tr>`
                            );
                        updateTotals(section);
                        return;
                    }

                    tbody.empty();

                    function buildActivityRows(activity, depth = 0, childIndex = 0, parentId = null) {
                        parentMap[activity.id] = parentId;
                        const displayNumber = activity.sort_index || (childIndex + 1);
                        const hasChildren = activity.children && activity.children.length > 0;
                        const bgClass = hasChildren ? 'bg-gray-100 dark:bg-gray-700' : '';
                        const fontClass = depth === 0 ? 'font-bold' : depth === 1 ? 'font-medium' : '';

                        const quarterNum = selectedQuarter.charAt(1);
                        const plannedQty = activity.planned_quantity || 0;
                        const plannedBudget = activity.planned_budget || 0;
                        const qPlannedQty = activity[`q${quarterNum}_quantity`] || 0;
                        const qPlannedAmt = activity[`q${quarterNum}_amount`] || 0;

                        let expQty = parseNumeric(activity[`q${quarterNum}_qty`] || '0');
                        let expAmt = parseNumeric(activity[`q${quarterNum}_amt`] || '0');

                        const qtyVal = expQty === 0 ? '' : formatNumber(expQty, 2);
                        const amtVal = expAmt === 0 ? '' : formatNumber(expAmt, 2);
                        const disabled = hasChildren;

                        const row = `
                    <tr class="projectExpense-row ${bgClass}" data-depth="${depth}" data-index="${activity.id}">
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm">${displayNumber}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: ${depth * 20}px;">
                            <input type="hidden" name="${section}[${activity.id}][activity_id]" value="${activity.id}">
                            <input type="hidden" name="${section}[${activity.id}][parent_id]" value="${activity.parent_id || ''}">
                            <span class="${fontClass}">${activity.title || activity.program || 'Untitled'}</span>
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">${hasChildren ? '0.00' : formatNumber(plannedQty, 2)}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">${hasChildren ? '0.00' : formatNumber(plannedBudget, 2)}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">${hasChildren ? '0.00' : formatNumber(qPlannedQty, 2)}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">${hasChildren ? '0.00' : formatNumber(qPlannedAmt, 2)}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                            <input type="text" name="${section}[${activity.id}][${selectedQuarter}_qty]" value="${qtyVal}" placeholder="0.00"
                                class="expense-input tooltip-error w-full ${disabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                                data-type="qty" ${disabled ? 'disabled readonly' : ''}>
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                            <input type="text" name="${section}[${activity.id}][${selectedQuarter}_amt]" value="${amtVal}" placeholder="0.00"
                                class="expense-input tooltip-error w-full ${disabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                                data-type="amt" ${disabled ? 'disabled readonly' : ''}>
                        </td>
                    </tr>`;

                        const $row = $(row);
                        tbody.append($row);
                        activityElements[activity.id] = $row[0];

                        if (hasChildren) {
                            parentToChildren[activity.id] = activity.children.map(c => c.id);
                            activity.children.forEach((child, idx) => buildActivityRows(child, depth + 1, idx + 1,
                                activity.id));

                            const totalRow = `
                        <tr class="projectExpense-total-row bg-blue-50 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-600" data-parent-id="${activity.id}">
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1"></td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 font-bold text-blue-700 dark:text-blue-300" style="padding-left: ${(depth + 1) * 20}px;">Total of ${displayNumber}</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">0.00</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">0.00</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">0.00</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-600 dark:text-gray-400">0.00</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                                <span class="total-display" data-parent-id="${activity.id}" data-type="qty">0.00</span>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                                <span class="total-display" data-parent-id="${activity.id}" data-type="amt">0.00</span>
                            </td>
                        </tr>`;
                            tbody.append(totalRow);
                        }
                    }

                    activities.forEach((act, i) => buildActivityRows(act, 0, i + 1, null));

                    initializeTooltips(tbody.find('.tooltip-error'));
                    updateTotals(section);

                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(p => validateParent(parseInt(p), type));
                    });
                }

                // ==================== REST OF EVENT HANDLERS (UNCHANGED) ====================

                function showError(msg) {
                    $('#error-text').text(msg);
                    $('#error-message').removeClass('hidden');
                }

                function resetQuarterAndReload() {
                    $('#quarter_selector').val('');
                    selectedQuarter = '';
                    $('#capital-quarter-label, #recurrent-quarter-label').text('Select Quarter');
                    loadProjectActivities(lastProjectValue, lastFiscalValue, '');
                }

                $('#quarter_selector').on('change', function() {
                    const q = $(this).val();
                    selectedQuarter = q;
                    checkSelections();
                    if (lastProjectValue && lastFiscalValue && q) {
                        loadProjectActivities(lastProjectValue, lastFiscalValue, q);
                    } else if (q) {
                        showError('Please select both Project and Fiscal Year first');
                        $(this).val('');
                        selectedQuarter = '';
                    }
                });

                $('#download-template').on('click', function() {
                    if (lastProjectValue && lastFiscalValue && selectedQuarter) {
                        window.location.href =
                            `/admin/projectExpense/downloadExcel/${lastProjectValue}/${lastFiscalValue}?quarter=${selectedQuarter}`;
                    } else {
                        showError('Please select all fields first');
                    }
                });

                $('#excel-upload').on('click', function() {
                    if (!lastProjectValue || !lastFiscalValue || !selectedQuarter) {
                        showError('Please select Project, Fiscal Year, and Quarter first');
                        return;
                    }
                    window.location.href =
                        `/admin/projectExpense/${lastProjectValue}/${lastFiscalValue}/upload?quarter=${selectedQuarter}`;
                });

                if (projectHidden) {
                    new MutationObserver(muts => {
                        muts.forEach(mut => {
                            if (mut.attributeName === 'value') {
                                const newVal = projectHidden.value;
                                if (newVal !== lastProjectValue) {
                                    lastProjectValue = newVal;
                                    resetQuarterAndReload();
                                    checkSelections();
                                }
                            }
                        });
                    }).observe(projectHidden, {
                        attributes: true
                    });
                }

                if (fiscalHidden) {
                    new MutationObserver(muts => {
                        muts.forEach(mut => {
                            if (mut.attributeName === 'value') {
                                const newVal = fiscalHidden.value;
                                if (newVal !== lastFiscalValue) {
                                    lastFiscalValue = newVal;
                                    resetQuarterAndReload();
                                    checkSelections();
                                }
                            }
                        });
                    }).observe(fiscalHidden, {
                        attributes: true
                    });
                }

                $(document).on('input', '.expense-input:not([disabled])', function() {
                    const $input = $(this);
                    const type = $input.data('type');
                    const id = parseInt($input.closest('tr').data('index'));

                    if (parentToChildren[id]) validateParent(id, type);
                    validateAllAncestors(id, type);

                    const section = $input.closest('tbody').attr('id').replace('-tbody', '');
                    updateTotals(section);
                });

                $('#close-error').on('click', () => {
                    $('#error-message').addClass('hidden');
                    $('.expense-input').removeClass('error-border');
                    tippyInstances.forEach((inst, el) => inst.hide());
                });

                $('#projectExpense-form').on('submit', function(e) {
                    e.preventDefault();

                    if (!lastProjectValue || !lastFiscalValue || !selectedQuarter) {
                        showError('Please complete all selections');
                        return;
                    }

                    let hasError = false;
                    $('.expense-input').each(function() {
                        const val = $(this).val().trim();
                        const num = parseNumeric(val);
                        if (val && (isNaN(num) || num < 0)) {
                            $(this).addClass('error-border');
                            updateTooltip($(this), 'Invalid number');
                            hasError = true;
                        }
                    });

                    if (hasError) {
                        showError('Please correct invalid inputs');
                        return;
                    }

                    let hierarchyError = false;
                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(pId => {
                            if (!validateParent(parseInt(pId), type)) hierarchyError = true;
                        });
                    });

                    if (hierarchyError) {
                        showError('Child values must exactly match parent totals');
                        return;
                    }

                    $('#submit-button').prop('disabled', true).text('Saving...');
                    this.submit();
                });

                if (lastProjectValue && lastFiscalValue && $('#quarter_selector').val()) {
                    loadProjectActivities(lastProjectValue, lastFiscalValue, $('#quarter_selector').val());
                }

                initializeTooltips($('.tooltip-error'));
                updateTotals('capital');
                updateTotals('recurrent');
                checkSelections();
            });
        </script>
    @endpush
</x-layouts.app>
