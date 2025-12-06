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
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="collect($projectOptions)->firstWhere('selected', true)['value'] ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYears" :selected="$selectedFiscalYearId ??
                            (collect($fiscalYears)->firstWhere('selected', true)['value'] ?? '')"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>
            </div>

            <div id="budget-display"
                class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <span class="block text-sm text-blue-700 dark:text-blue-300">
                    Select a project and fiscal year to view budget details and load activities.
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
                        Capital Expenses
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q1
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q2
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q3
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q4
                                    </th>
                                </tr>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                                <!-- Dynamic content will be loaded here -->
                                <tr id="capital-empty">
                                    <td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Select a project and fiscal year to load capital expenses
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Capital Quarterly Totals -->
                    <div
                        class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                Total: <span id="capital-grand-amt-total">0.00</span>
                            </span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                Q1: <span id="capital-q1-amt-total">0.00</span> |
                                Q2: <span id="capital-q2-amt-total">0.00</span> |
                                Q3: <span id="capital-q3-amt-total">0.00</span> |
                                Q4: <span id="capital-q4-amt-total">0.00</span>
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
                        Recurrent Expenses
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q1
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q2
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q3
                                    </th>
                                    <th colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-center bg-blue-100 dark:bg-blue-800">
                                        Q4
                                    </th>
                                </tr>
                                <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Qty</th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Amt</th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
                                <!-- Dynamic content will be loaded here -->
                                <tr id="recurrent-empty">
                                    <td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Select a project and fiscal year to load recurrent expenses
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Recurrent Quarterly Totals -->
                    <div
                        class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                Total: <span id="recurrent-grand-amt-total">0.00</span>
                            </span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                Q1: <span id="recurrent-q1-amt-total">0.00</span> |
                                Q2: <span id="recurrent-q2-amt-total">0.00</span> |
                                Q3: <span id="recurrent-q3-amt-total">0.00</span> |
                                Q4: <span id="recurrent-q4-amt-total">0.00</span>
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
            // Wait for jQuery function (polls until jQuery is available)
            function waitForJQuery(callback, maxRetries = 50, interval = 100) {
                if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.jquery) {
                    callback(jQuery);
                } else {
                    if (maxRetries > 0) {
                        setTimeout(function() {
                            waitForJQuery(callback, maxRetries - 1, interval);
                        }, interval);
                    }
                }
            }

            // Invoke the wait function immediately
            waitForJQuery(function($) {

                const tippyInstances = new WeakMap();

                let parentMap = {};
                let parentToChildren = {};
                let activityElements = {};

                function parseNumeric(val) {
                    return parseFloat((val || '0').replace(/,/g, '')) || 0;
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
                        instance.setContent(message);
                        instance[message ? 'show' : 'hide']();
                    }
                }

                /**
                 * Validate parent-child relationship for a specific quarter and type (qty or amt)
                 * Only shows error on CHILD inputs, not parent
                 * Skips validation for disabled parents
                 * Returns true if validation passes, false if errors exist
                 */
                function validateParent(pId, quarter, type) {
                    // Get all direct children of this parent
                    const children = parentToChildren[pId];
                    if (!children || children.length === 0) {
                        return true; // No children, nothing to validate
                    }

                    // Skip validation if parent is disabled (it auto-calculates)
                    const pInput = $(activityElements[pId]).find(
                        `input[data-quarter="${quarter}"][data-type="${type}"]`);
                    if (pInput.is(':disabled')) {
                        return true; // Parent is disabled, no need to validate
                    }

                    // Calculate sum of all children for this quarter and type
                    let childSum = 0;
                    children.forEach(cId => {
                        const cInput = $(activityElements[cId]).find(
                            `input[data-quarter="${quarter}"][data-type="${type}"]`);
                        childSum += parseNumeric(cInput.val());
                    });

                    // Get parent value
                    const pVal = parseNumeric(pInput.val());

                    // Check for EQUALITY (parent must equal sum of children)
                    const difference = Math.abs(childSum - pVal);

                    if (difference > 0.01) {
                        // Only add error to CHILDREN, not parent
                        let message = '';
                        if (childSum > pVal) {
                            message =
                                `Sum of children (${childSum.toFixed(2)}) EXCEEDS parent ${type} (${pVal.toFixed(2)}). Adjust children values.`;
                        } else {
                            message =
                                `Sum of children (${childSum.toFixed(2)}) is LESS than parent ${type} (${pVal.toFixed(2)}). Add ${(pVal - childSum).toFixed(2)} more.`;
                        }

                        // Add error ONLY to child inputs
                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(
                                `input[data-quarter="${quarter}"][data-type="${type}"]`);
                            cInput.addClass('error-border');
                            updateTooltip(cInput, message);
                        });

                        return false; // Validation failed
                    } else {
                        // Clear errors on children only
                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(
                                `input[data-quarter="${quarter}"][data-type="${type}"]`);
                            const currentError = tippyInstances.get(cInput[0])?.props.content || '';
                            if (currentError.includes('Sum of children') || currentError.includes('parent')) {
                                cInput.removeClass('error-border');
                                updateTooltip(cInput, '');
                            }
                        });

                        return true; // Validation passed
                    }
                }

                /**
                 * Validate ALL ancestors in the hierarchy (goes up the chain) for a specific type
                 * This handles: 1.1.1 changes -> validates 1.1 AND 1
                 */
                function validateAllAncestors(activityId, quarter, type) {
                    let currentId = activityId;

                    // Walk up the parent chain
                    while (currentId) {
                        const parentId = parentMap[currentId];

                        if (parentId) {
                            // Validate this parent against its children
                            validateParent(parentId, quarter, type);
                        }

                        // Move up to the next level
                        currentId = parentId;
                    }
                }

                /**
                 * Update quarterly totals for each section
                 * Sums ALL top-level rows (depth 0), whether they have children or not
                 * - For parents: recurses to sum children via getActivityTotal
                 * - For leaves: directly uses their input value
                 * Also update all parent totals rows at any depth
                 */
                function updateQuarterlyTotals(section) {
                    const quarters = ['q1', 'q2', 'q3', 'q4'];
                    let grandAmtTotal = 0;

                    // Update bottom totals - sum ALL top-level rows (1, 2, 3, 4, 5...)
                    quarters.forEach(q => {
                        let quarterAmtTotal = 0;

                        // Find all top-level rows (depth 0, regardless of children)
                        $(`#${section}-tbody tr[data-index][data-depth="0"]`).each(function() {
                            const id = parseInt($(this).data('index'));

                            // Use getActivityTotal for every top-level row - it handles recursion for parents or direct value for leaves
                            quarterAmtTotal += getActivityTotal(id, q, 'amt');
                        });

                        $(`#${section}-${q}-amt-total`).text(quarterAmtTotal.toFixed(2));
                        grandAmtTotal += quarterAmtTotal;
                    });
                    $(`#${section}-grand-amt-total`).text(grandAmtTotal.toFixed(2));

                    // Update ALL parent totals rows (at any depth) - for both qty and amt
                    Object.keys(parentToChildren).forEach(parentId => {
                        const children = parentToChildren[parentId];

                        quarters.forEach(q => {
                            let childrenQtySum = 0;
                            let childrenAmtSum = 0;
                            children.forEach(childId => {
                                childrenQtySum += getActivityTotal(childId, q, 'qty');
                                childrenAmtSum += getActivityTotal(childId, q, 'amt');
                            });

                            // Update the total display spans
                            $(`.total-display[data-parent-id="${parentId}"][data-quarter="${q}"][data-type="qty"]`)
                                .text(Math.round(childrenQtySum).toLocaleString());
                            $(`.total-display[data-parent-id="${parentId}"][data-quarter="${q}"][data-type="amt"]`)
                                .text(childrenAmtSum.toFixed(2));

                            // Also update the disabled parent inputs
                            const parentQtyInput = $(activityElements[parentId]).find(
                                `input[data-quarter="${q}"][data-type="qty"]`);
                            const parentAmtInput = $(activityElements[parentId]).find(
                                `input[data-quarter="${q}"][data-type="amt"]`);
                            if (parentQtyInput.is(':disabled')) {
                                parentQtyInput.val(Math.round(childrenQtySum).toLocaleString());
                            }
                            if (parentAmtInput.is(':disabled')) {
                                parentAmtInput.val(childrenAmtSum.toFixed(2));
                            }
                        });
                    });
                }

                /**
                 * Helper: Get total value for an activity (recursive for nested parents)
                 * If activity is a parent, sum its children. If leaf, return its value.
                 * @param {string} type - 'qty' or 'amt'
                 */
                function getActivityTotal(activityId, quarter, type) {
                    const children = parentToChildren[activityId];

                    if (children && children.length > 0) {
                        // This is a parent - sum its children recursively
                        let sum = 0;
                        children.forEach(childId => {
                            sum += getActivityTotal(childId, quarter, type);
                        });
                        return sum;
                    } else {
                        // This is a leaf - return its input value
                        const input = $(activityElements[activityId]).find(
                            `input[data-quarter="${quarter}"][data-type="${type}"]`);
                        const val = parseNumeric(input.val());
                        return type === 'qty' ? Math.round(val) : val;
                    }
                }

                function loadProjectActivities(projectId, fiscalYearId, trigger = 'unknown') {

                    if (!projectId || !fiscalYearId) {
                        $('#capital-tbody').html(
                            '<tr id="capital-empty"><td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project and fiscal year to load capital expenses</td></tr>'
                        );
                        $('#recurrent-tbody').html(
                            '<tr id="recurrent-empty"><td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project and fiscal year to load recurrent expenses</td></tr>'
                        );
                        $('#budget-display').html(
                            '<span class="block text-sm text-blue-700 dark:text-blue-300">Select a project and fiscal year to view budget details and load activities.</span>'
                        );
                        updateQuarterlyTotals('capital');
                        updateQuarterlyTotals('recurrent');
                        return;
                    }

                    $('#loading-indicator').removeClass('hidden');
                    $('#submit-button').prop('disabled', true);
                    $('#budget-display').html(
                        '<span class="block text-sm text-gray-500 dark:text-gray-400">Loading activities and budget...</span>'
                    );

                    const url = `/admin/project-activities/${projectId}/${fiscalYearId}`;

                    $.ajax({
                        url: url,
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
                                    `<div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                            <span class="block text-sm font-semibold text-green-700 dark:text-green-300">
                                ${response.budgetDetails}
                            </span>
                        </div>`
                                );
                            }

                            // Reset maps
                            parentMap = {};
                            parentToChildren = {};
                            activityElements = {};

                            populateActivities('capital', response.capital || []);
                            populateActivities('recurrent', response.recurrent || []);
                        },
                        error: function(xhr, status, error) {
                            $('#loading-indicator').addClass('hidden');
                            $('#submit-button').prop('disabled', false);

                            let errorMsg = 'Failed to load project activities';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.status === 404) {
                                errorMsg = 'Activities endpoint not found. Check your route configuration.';
                            } else if (xhr.status === 500) {
                                errorMsg = 'Server error loading activities. Check logs for details.';
                            }

                            showError(errorMsg);
                        }
                    });
                }

                function populateActivities(section, activities) {

                    const tbody = $(`#${section}-tbody`);

                    if (!activities || activities.length === 0) {
                        tbody.html(
                            `<tr id="${section}-empty">
                    <td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No ${section} activities found for selected project and fiscal year
                    </td>
                </tr>`
                        );
                        updateQuarterlyTotals(section);
                        return;
                    }

                    tbody.empty();

                    /**
                     * Properly build hierarchy with parent tracking
                     * ALL parents (any depth) with children are disabled, totals row added after each
                     */
                    function buildActivityRows(activity, depth = 0, parentNumber = '', childIndex = 0, parentId =
                        null) {
                        // Store parent relationship
                        parentMap[activity.id] = parentId;

                        const displayNumber = parentNumber ? `${parentNumber}.${childIndex}` : (childIndex + 1)
                            .toString();
                        const hasChildren = activity.children && activity.children.length > 0;

                        // Determine styling based on depth and whether it has children
                        const bgClass = hasChildren ? 'bg-gray-100 dark:bg-gray-700' : '';
                        const fontClass = depth === 0 ? 'font-bold' : depth === 1 ? 'font-medium' : '';

                        let row = `<tr class="projectExpense-row ${bgClass}" data-depth="${depth}" data-index="${activity.id}">
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                    ${displayNumber}
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: ${depth * 20}px;">
                    <input type="hidden" name="${section}[${activity.id}][activity_id]" value="${activity.id}">
                    <input type="hidden" name="${section}[${activity.id}][parent_id]" value="${parentId || ''}">
                    <span class="${fontClass}">${activity.title || activity.program || 'Untitled Activity'}</span>
                </td>`;

                        const quarters = ['q1', 'q2', 'q3', 'q4'];
                        const sourceKeys = ['gov_share', 'gov_loan', 'foreign_loan', 'foreign_subsidy', 'internal'];

                        quarters.forEach(q => {
                            // Qty input
                            let qQty = parseNumeric(activity[`${q}_qty`] || '0');
                            const qtyValue = qQty === 0 ? '' : Math.round(qQty).toLocaleString();
                            const qtyPlaceholder = qQty === 0 ? '0' : '';

                            // Amt input
                            let qAmt = 0;
                            sourceKeys.forEach(sub => {
                                qAmt += parseNumeric(activity[`${q}_${sub}`] || '0');
                            });
                            const amtValue = qAmt === 0 ? '' : qAmt.toFixed(2);
                            const amtPlaceholder = qAmt === 0 ? '0.00' : '';

                            // FIXED: Disable inputs for ANY parent (any depth) that has children
                            const isDisabled = hasChildren;

                            // Qty td
                            row += `<td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input type="text"
                           name="${section}[${activity.id}][${q}_qty]"
                           value="${qtyValue}"
                           placeholder="${qtyPlaceholder}"
                           pattern="[0-9]+"
                           class="expense-input tooltip-error numeric-input w-full ${isDisabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                           data-quarter="${q}"
                           data-type="qty"
                           ${isDisabled ? 'disabled readonly' : ''}>
                </td>`;

                            // Amt td
                            row += `<td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input type="text"
                           name="${section}[${activity.id}][${q}_amt]"
                           value="${amtValue}"
                           placeholder="${amtPlaceholder}"
                           pattern="[0-9]+(\\.[0-9]{1,2})?"
                           class="expense-input tooltip-error numeric-input w-full ${isDisabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                           data-quarter="${q}"
                           data-type="amt"
                           ${isDisabled ? 'disabled readonly' : ''}>
                </td>`;
                        });

                        row += '</tr>';
                        const tr = $(row);
                        tbody.append(tr);
                        activityElements[activity.id] = tr[0];

                        // Process children recursively
                        if (hasChildren) {
                            // Store children IDs for this parent
                            parentToChildren[activity.id] = activity.children.map(c => c.id);

                            activity.children.forEach((child, idx) => {
                                // Recursive call - pass current activity.id as parentId
                                buildActivityRows(child, depth + 1, displayNumber, idx + 1, activity.id);
                            });

                            // Add totals row after all children (for ANY parent at ANY depth)
                            let totalRow = `<tr class="projectExpense-total-row bg-blue-50 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-600" data-parent-id="${activity.id}">
                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm font-bold text-blue-700 dark:text-blue-300">

                    </td>
                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 font-bold text-blue-700 dark:text-blue-300" style="padding-left: ${(depth + 1) * 20}px;">
                        Total of ${displayNumber}
                    </td>`;

                            quarters.forEach(q => {
                                // Qty total td
                                totalRow += `<td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                        <span class="total-display" data-parent-id="${activity.id}" data-quarter="${q}" data-type="qty">0</span>
                    </td>`;

                                // Amt total td
                                totalRow += `<td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                        <span class="total-display" data-parent-id="${activity.id}" data-quarter="${q}" data-type="amt">0.00</span>
                    </td>`;
                            });

                            totalRow += '</tr>';
                            tbody.append(totalRow);
                        }
                    }

                    activities.forEach((activity, index) => {
                        buildActivityRows(activity, 0, '', index, null);
                    });

                    // Initial validation for ALL hierarchy levels for both types
                    const quarters = ['q1', 'q2', 'q3', 'q4'];
                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(pId => {
                            quarters.forEach(q => validateParent(parseInt(pId), q, type));
                        });
                    });

                    initializeTooltips(tbody.find('.tooltip-error'));
                    updateQuarterlyTotals(section);
                }

                function showError(message) {
                    $('#error-text').text(message);
                    $('#error-message').removeClass('hidden');
                }

                // ============================================
                // SETUP MUTATION OBSERVERS
                // ============================================
                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');

                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';

                if (projectHidden) {
                    const projectObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = projectHidden.value;
                                if (newValue !== lastProjectValue) {
                                    lastProjectValue = newValue;
                                    loadProjectActivities(newValue, lastFiscalValue,
                                        'project-observer');
                                }
                            }
                        });
                    });
                    projectObserver.observe(projectHidden, {
                        attributes: true
                    });

                    projectHidden.addEventListener('change', function() {
                        lastProjectValue = this.value;
                        loadProjectActivities(this.value, lastFiscalValue, 'project-event');
                    });
                }

                if (fiscalHidden) {
                    const fiscalObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = fiscalHidden.value;
                                if (newValue !== lastFiscalValue) {
                                    lastFiscalValue = newValue;
                                    loadProjectActivities(lastProjectValue, newValue,
                                        'fiscal-observer');
                                }
                            }
                        });
                    });
                    fiscalObserver.observe(fiscalHidden, {
                        attributes: true
                    });

                    fiscalHidden.addEventListener('change', function() {
                        lastFiscalValue = this.value;
                        loadProjectActivities(lastProjectValue, this.value, 'fiscal-event');
                    });
                }

                // Initial load if both are already selected
                if (lastProjectValue && lastFiscalValue) {
                    loadProjectActivities(lastProjectValue, lastFiscalValue, 'initial');
                }

                /**
                 * Input handler now validates ALL ancestor levels for the specific type
                 * AND prevents tab/navigation if errors exist
                 */
                $(document).on('input', '.expense-input', function() {
                    const $input = $(this);
                    const type = $input.data('type');
                    const quarter = $input.data('quarter');
                    const activityId = parseInt($input.closest('tr').data('index'));

                    // Validate THIS activity if it's a parent
                    if (parentToChildren[activityId]) {
                        validateParent(activityId, quarter, type);
                    }

                    // Validate ALL ancestors up the chain (handles 1.1.1 -> 1.1 -> 1)
                    validateAllAncestors(activityId, quarter, type);

                    const $tbody = $input.closest('tbody');
                    const section = $tbody.attr('id').replace('-tbody', '');
                    updateQuarterlyTotals(section);
                });

                /**
                 * Prevent Tab key and blur if there are validation errors
                 */
                $(document).on('keydown', '.expense-input', function(e) {
                    // Check if Tab key is pressed
                    if (e.key === 'Tab') {
                        const $input = $(this);

                        // Check if this input has an error
                        if ($input.hasClass('error-border')) {
                            e.preventDefault();
                            e.stopPropagation();

                            // Show alert or flash error message
                            const errorMsg = tippyInstances.get($input[0])?.props.content ||
                                'Please correct the error before proceeding';

                            // Flash the error message
                            $('#error-text').text(errorMsg);
                            $('#error-message').removeClass('hidden');

                            // Auto-hide after 3 seconds
                            setTimeout(function() {
                                $('#error-message').addClass('hidden');
                            }, 3000);

                            return false;
                        }
                    }
                });

                /**
                 * Prevent blur/changing focus if there are errors
                 */
                $(document).on('blur', '.expense-input', function(e) {
                    const $input = $(this);

                    // If input has error, refocus it
                    if ($input.hasClass('error-border')) {
                        e.preventDefault();

                        // Small delay to ensure the blur completes first
                        setTimeout(function() {
                            $input.focus();
                        }, 100);

                        return false;
                    }
                });

                // Form submission
                $('#projectExpense-form').on('submit', function(e) {
                    e.preventDefault();
                    let hasErrors = false;

                    const projectId = projectHidden ? projectHidden.value : '';
                    const fiscalYearId = fiscalHidden ? fiscalHidden.value : '';

                    if (!projectId || !fiscalYearId) {
                        showError('Please select both Project and Fiscal Year');
                        return;
                    }

                    // Validate numeric inputs for both qty and amt
                    $('.expense-input').each(function() {
                        const $input = $(this);
                        const type = $input.data('type');
                        const val = $input.val().trim();
                        let isValid = true;
                        if (type === 'qty') {
                            const num = parseInt(val) || 0;
                            isValid = !isNaN(num) && num >= 0;
                        } else {
                            const num = parseNumeric(val);
                            isValid = !isNaN(num) && num >= 0;
                        }
                        if (val && !isValid) {
                            $input.addClass('error-border');
                            updateTooltip($input, `Valid non-negative ${type} required`);
                            hasErrors = true;
                        }
                    });

                    if (hasErrors) {
                        showError('Please correct errors in the expense inputs.');
                        return;
                    }

                    // Check EQUALITY for all parent-child relationships for both types
                    let hierarchyErrors = false;
                    const quarters = ['q1', 'q2', 'q3', 'q4'];
                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(pId => {
                            quarters.forEach(q => {
                                const children = parentToChildren[pId];
                                let childSum = 0;

                                children.forEach(cId => {
                                    const cInput = $(activityElements[cId])
                                        .find(
                                            `input[data-quarter="${q}"][data-type="${type}"]`
                                            );
                                    childSum += type === 'qty' ? (parseInt(
                                        cInput.val()) || 0) : parseNumeric(
                                        cInput.val());
                                });

                                const pVal = type === 'qty' ? (parseInt($(
                                    activityElements[pId]).find(
                                    `input[data-quarter="${q}"][data-type="${type}"]`
                                    ).val()) || 0) : parseNumeric($(
                                    activityElements[pId]).find(
                                    `input[data-quarter="${q}"][data-type="${type}"]`
                                    ).val());
                                const difference = Math.abs(childSum - pVal);

                                // Check for INEQUALITY
                                if (difference > 0.01) {
                                    // Mark ONLY children with errors
                                    let message = '';
                                    if (childSum > pVal) {
                                        message =
                                            `Sum EXCEEDS parent ${type} by ${Math.abs(childSum - pVal).toFixed(2)}`;
                                    } else {
                                        message =
                                            `Sum is ${Math.abs(pVal - childSum).toFixed(2)} LESS than parent ${type}`;
                                    }

                                    children.forEach(cId => {
                                        const cInput = $(activityElements[cId])
                                            .find(
                                                `input[data-quarter="${q}"][data-type="${type}"]`
                                                );
                                        cInput.addClass('error-border');
                                        updateTooltip(cInput, message);
                                    });

                                    hierarchyErrors = true;
                                }
                            });
                        });
                    });

                    if (hierarchyErrors) {
                        showError(
                            'Child values must sum to exactly match parent values for both quantity and amount. Please correct all mismatches before submitting.'
                        );
                        return;
                    }

                    $('#submit-button').prop('disabled', true).text('Saving...');
                    this.submit();
                });

                $('#close-error').on('click', function() {
                    $('#error-message').addClass('hidden');
                    $('.expense-input').removeClass('error-border').each(function() {
                        updateTooltip($(this), '');
                    });
                });

                // Initialize
                initializeTooltips($('.tooltip-error'));
                updateQuarterlyTotals('capital');
                updateQuarterlyTotals('recurrent');
            });
        </script>
    @endpush
</x-layouts.app>
