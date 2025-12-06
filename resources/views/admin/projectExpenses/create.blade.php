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
                    {{-- Optional: Re-add status dots if you compute $quarterStatus later --}}
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
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Quantity
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="capital-tbody">
                                <tr id="capital-empty">
                                    <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
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
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12">
                                        #
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Activity/Program
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Quantity
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="recurrent-tbody">
                                <tr id="recurrent-empty">
                                    <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
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
                } else {
                    if (maxRetries > 0) {
                        setTimeout(function() {
                            waitForJQuery(callback, maxRetries - 1, interval);
                        }, interval);
                    }
                }
            }

            waitForJQuery(function($) {
                const tippyInstances = new WeakMap();
                let parentMap = {};
                let parentToChildren = {};
                let activityElements = {};
                let selectedQuarter = '';

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
                    const difference = Math.abs(childSum - pVal);

                    if (difference > 0.01) {
                        let message = '';
                        if (childSum > pVal) {
                            message =
                                `Sum of children (${childSum.toFixed(2)}) EXCEEDS parent ${type} (${pVal.toFixed(2)})`;
                        } else {
                            message =
                                `Sum of children (${childSum.toFixed(2)}) is LESS than parent ${type} (${pVal.toFixed(2)})`;
                        }

                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(`input[data-type="${type}"]`);
                            cInput.addClass('error-border');
                            updateTooltip(cInput, message);
                        });
                        return false;
                    } else {
                        children.forEach(cId => {
                            const cInput = $(activityElements[cId]).find(`input[data-type="${type}"]`);
                            const currentError = tippyInstances.get(cInput[0])?.props.content || '';
                            if (currentError.includes('Sum of children') || currentError.includes('parent')) {
                                cInput.removeClass('error-border');
                                updateTooltip(cInput, '');
                            }
                        });
                        return true;
                    }
                }

                function validateAllAncestors(activityId, type) {
                    let currentId = activityId;
                    while (currentId) {
                        const parentId = parentMap[currentId];
                        if (parentId) {
                            validateParent(parentId, type);
                        }
                        currentId = parentId;
                    }
                }

                function updateTotals(section) {
                    let totalAmt = 0;

                    $(`#${section}-tbody tr[data-index][data-depth="0"]`).each(function() {
                        const id = parseInt($(this).data('index'));
                        totalAmt += getActivityTotal(id, 'amt');
                    });

                    $(`#${section}-total-amt`).text(totalAmt.toFixed(2));

                    // Update parent totals
                    Object.keys(parentToChildren).forEach(parentId => {
                        const children = parentToChildren[parentId];
                        let childrenQtySum = 0;
                        let childrenAmtSum = 0;

                        children.forEach(childId => {
                            childrenQtySum += getActivityTotal(childId, 'qty');
                            childrenAmtSum += getActivityTotal(childId, 'amt');
                        });

                        $(`.total-display[data-parent-id="${parentId}"][data-type="qty"]`)
                            .text(Math.round(childrenQtySum).toLocaleString());
                        $(`.total-display[data-parent-id="${parentId}"][data-type="amt"]`)
                            .text(childrenAmtSum.toFixed(2));

                        const parentQtyInput = $(activityElements[parentId]).find('input[data-type="qty"]');
                        const parentAmtInput = $(activityElements[parentId]).find('input[data-type="amt"]');
                        if (parentQtyInput.is(':disabled')) {
                            parentQtyInput.val(Math.round(childrenQtySum).toLocaleString());
                        }
                        if (parentAmtInput.is(':disabled')) {
                            parentAmtInput.val(childrenAmtSum.toFixed(2));
                        }
                    });
                }

                function getActivityTotal(activityId, type) {
                    const children = parentToChildren[activityId];
                    if (children && children.length > 0) {
                        let sum = 0;
                        children.forEach(childId => {
                            sum += getActivityTotal(childId, type);
                        });
                        return sum;
                    } else {
                        const input = $(activityElements[activityId]).find(`input[data-type="${type}"]`);
                        const val = parseNumeric(input.val());
                        return type === 'qty' ? Math.round(val) : val;
                    }
                }

                function loadProjectActivities(projectId, fiscalYearId, quarter) {
                    if (!projectId || !fiscalYearId || !quarter) {
                        $('#capital-tbody').html(
                            '<tr id="capital-empty"><td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project, fiscal year, and quarter to load capital expenses</td></tr>'
                        );
                        $('#recurrent-tbody').html(
                            '<tr id="recurrent-empty"><td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project, fiscal year, and quarter to load recurrent expenses</td></tr>'
                        );
                        $('#budget-display').html(
                            '<span class="block text-sm text-blue-700 dark:text-blue-300">Select a project, fiscal year, and quarter to view budget details and load activities.</span>'
                        );
                        updateTotals('capital');
                        updateTotals('recurrent');
                        return;
                    }

                    selectedQuarter = quarter;
                    $('#capital-quarter-label').text(`Quarter ${quarter.replace('q', '').toUpperCase()}`);
                    $('#recurrent-quarter-label').text(`Quarter ${quarter.replace('q', '').toUpperCase()}`);

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
                        },
                        error: function(xhr) {
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
                            `<tr id="${section}-empty"><td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">No ${section} activities found for the selected fiscal year</td></tr>`
                        );
                        updateTotals(section);
                        return;
                    }

                    tbody.empty();

                    function buildActivityRows(activity, depth = 0, parentNumber = '', childIndex = 0, parentId =
                        null) {
                        parentMap[activity.id] = parentId;
                        const displayNumber = parentNumber ? `${parentNumber}.${childIndex}` : (childIndex + 1)
                            .toString();
                        const hasChildren = activity.children && activity.children.length > 0;
                        const bgClass = hasChildren ? 'bg-gray-100 dark:bg-gray-700' : '';
                        const fontClass = depth === 0 ? 'font-bold' : depth === 1 ? 'font-medium' : '';

                        // Pre-fill from existing expense data (assumes controller includes quarterly expense fields in JSON, e.g., 'q1_qty', 'q1_amt' from loaded expenses)
                        let qQty = parseNumeric(activity[`${selectedQuarter}_qty`] || activity[
                            `q${selectedQuarter.charAt(1)}_qty`] || '0');
                        let qAmt = parseNumeric(activity[`${selectedQuarter}_amt`] || activity[
                            `q${selectedQuarter.charAt(1)}_amt`] || '0');

                        const qtyValue = qQty === 0 ? '' : Math.round(qQty).toLocaleString();
                        const amtValue = qAmt === 0 ? '' : qAmt.toFixed(2);
                        const isDisabled = hasChildren;

                        let row = `<tr class="projectExpense-row ${bgClass}" data-depth="${depth}" data-index="${activity.id}">
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm">${displayNumber}</td>
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: ${depth * 20}px;">
                    <input type="hidden" name="${section}[${activity.id}][activity_id]" value="${activity.id}">
                    <input type="hidden" name="${section}[${activity.id}][parent_id]" value="${activity.parent_id || ''}">
                    <span class="${fontClass}">${activity.title || activity.program || 'Untitled'}</span>
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input type="text" name="${section}[${activity.id}][${selectedQuarter}_qty]" value="${qtyValue}"
                        placeholder="0" pattern="[0-9]+"
                        class="expense-input tooltip-error numeric-input w-full ${isDisabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                        data-type="qty" ${isDisabled ? 'disabled readonly' : ''}>
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                    <input type="text" name="${section}[${activity.id}][${selectedQuarter}_amt]" value="${amtValue}"
                        placeholder="0.00" pattern="[0-9]+(\\.[0-9]{1,2})?"
                        class="expense-input tooltip-error numeric-input w-full ${isDisabled ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : ''}"
                        data-type="amt" ${isDisabled ? 'disabled readonly' : ''}>
                </td>
            </tr>`;

                        const tr = $(row);
                        tbody.append(tr);
                        activityElements[activity.id] = tr[0];

                        if (hasChildren) {
                            parentToChildren[activity.id] = activity.children.map(c => c.id);
                            activity.children.forEach((child, idx) => {
                                buildActivityRows(child, depth + 1, displayNumber, idx + 1, activity.id);
                            });

                            let totalRow = `<tr class="projectExpense-total-row bg-blue-50 dark:bg-blue-900/30 border-t-2 border-blue-300 dark:border-blue-600" data-parent-id="${activity.id}">
                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1"></td>
                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 font-bold text-blue-700 dark:text-blue-300" style="padding-left: ${(depth + 1) * 20}px;">Total of ${displayNumber}</td>
                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                        <span class="total-display" data-parent-id="${activity.id}" data-type="qty">0</span>
                    </td>
                    <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right font-bold text-blue-700 dark:text-blue-300">
                        <span class="total-display" data-parent-id="${activity.id}" data-type="amt">0.00</span>
                    </td>
                </tr>`;
                            tbody.append(totalRow);
                        }
                    }

                    activities.forEach((activity, index) => {
                        buildActivityRows(activity, 0, '', index + 1, null);
                    });

                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(pId => {
                            validateParent(parseInt(pId), type);
                        });
                    });

                    initializeTooltips(tbody.find('.tooltip-error'));
                    updateTotals(section);
                }

                function showError(message) {
                    $('#error-text').text(message);
                    $('#error-message').removeClass('hidden');
                }

                // NEW: Reset quarter on project or FY change
                function resetQuarterAndReload() {
                    $('#quarter_selector').val(''); // Clear selection
                    $('#capital-quarter-label').text('Select Quarter');
                    $('#recurrent-quarter-label').text('Select Quarter');
                    loadProjectActivities(lastProjectValue, lastFiscalValue, ''); // Clear tables
                }

                // Event handlers
                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');

                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';

                // Quarter selector change
                $('#quarter_selector').on('change', function() {
                    const quarter = $(this).val();
                    if (lastProjectValue && lastFiscalValue && quarter) {
                        loadProjectActivities(lastProjectValue, lastFiscalValue, quarter);
                    }
                });

                if (projectHidden) {
                    const projectObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = projectHidden.value;
                                if (newValue !== lastProjectValue) {
                                    lastProjectValue = newValue;
                                    resetQuarterAndReload();
                                }
                            }
                        });
                    });
                    projectObserver.observe(projectHidden, {
                        attributes: true
                    });

                    projectHidden.addEventListener('change', function() {
                        lastProjectValue = this.value;
                        resetQuarterAndReload();
                    });
                }

                if (fiscalHidden) {
                    const fiscalObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = fiscalHidden.value;
                                if (newValue !== lastFiscalValue) {
                                    lastFiscalValue = newValue;
                                    resetQuarterAndReload();
                                }
                            }
                        });
                    });
                    fiscalObserver.observe(fiscalHidden, {
                        attributes: true
                    });

                    fiscalHidden.addEventListener('change', function() {
                        lastFiscalValue = this.value;
                        resetQuarterAndReload();
                    });
                }

                $(document).on('input', '.expense-input', function() {
                    const $input = $(this);
                    const type = $input.data('type');
                    const activityId = parseInt($input.closest('tr').data('index'));

                    if (parentToChildren[activityId]) {
                        validateParent(activityId, type);
                    }

                    validateAllAncestors(activityId, type);

                    const $tbody = $input.closest('tbody');
                    const section = $tbody.attr('id').replace('-tbody', '');
                    updateTotals(section);
                });

                $(document).on('keydown', '.expense-input', function(e) {
                    if (e.key === 'Tab') {
                        const $input = $(this);
                        if ($input.hasClass('error-border')) {
                            e.preventDefault();
                            e.stopPropagation();
                            const errorMsg = tippyInstances.get($input[0])?.props.content ||
                                'Please correct the error';
                            $('#error-text').text(errorMsg);
                            $('#error-message').removeClass('hidden');
                            setTimeout(function() {
                                $('#error-message').addClass('hidden');
                            }, 3000);
                            return false;
                        }
                    }
                });

                $(document).on('blur', '.expense-input', function(e) {
                    const $input = $(this);
                    if ($input.hasClass('error-border')) {
                        e.preventDefault();
                        setTimeout(function() {
                            $input.focus();
                        }, 100);
                        return false;
                    }
                });

                $('#projectExpense-form').on('submit', function(e) {
                    e.preventDefault();
                    let hasErrors = false;

                    const projectId = projectHidden ? projectHidden.value : '';
                    const fiscalYearId = fiscalHidden ? fiscalHidden.value : '';
                    const quarter = $('#quarter_selector').val();

                    if (!projectId || !fiscalYearId) {
                        showError('Please select both Project and Fiscal Year');
                        return;
                    }

                    if (!quarter) {
                        showError('Please select a Quarter');
                        return;
                    }

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

                    let hierarchyErrors = false;
                    ['qty', 'amt'].forEach(type => {
                        Object.keys(parentToChildren).forEach(pId => {
                            const children = parentToChildren[pId];
                            let childSum = 0;

                            children.forEach(cId => {
                                const cInput = $(activityElements[cId]).find(
                                    `input[data-type="${type}"]`);
                                childSum += type === 'qty' ? (parseInt(cInput.val()) ||
                                    0) : parseNumeric(cInput.val());
                            });

                            const pVal = type === 'qty' ?
                                (parseInt($(activityElements[pId]).find(
                                    `input[data-type="${type}"]`).val()) || 0) :
                                parseNumeric($(activityElements[pId]).find(
                                    `input[data-type="${type}"]`).val());
                            const difference = Math.abs(childSum - pVal);

                            if (difference > 0.01) {
                                let message = childSum > pVal ?
                                    `Sum EXCEEDS parent ${type} by ${Math.abs(childSum - pVal).toFixed(2)}` :
                                    `Sum is ${Math.abs(pVal - childSum).toFixed(2)} LESS than parent ${type}`;

                                children.forEach(cId => {
                                    const cInput = $(activityElements[cId]).find(
                                        `input[data-type="${type}"]`);
                                    cInput.addClass('error-border');
                                    updateTooltip(cInput, message);
                                });

                                hierarchyErrors = true;
                            }
                        });
                    });

                    if (hierarchyErrors) {
                        showError(
                            'Child values must sum to exactly match parent values. Please correct all mismatches.'
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

                initializeTooltips($('.tooltip-error'));
                updateTotals('capital');
                updateTotals('recurrent');

                // Auto-load if quarter is pre-selected (initial load only)
                const initialQuarter = $('#quarter_selector').val();
                if (lastProjectValue && lastFiscalValue && initialQuarter) {
                    loadProjectActivities(lastProjectValue, lastFiscalValue, initialQuarter);
                }
            });
        </script>
    @endpush
</x-layouts.app>
