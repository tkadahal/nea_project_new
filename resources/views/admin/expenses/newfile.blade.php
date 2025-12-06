<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.expense.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.expense.title_singular') }}
        </p>
    </div>

    <form id="projectExpense-form" class="w-full" action="{{ route('admin.expense.store') }}" method="POST"
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

            <div x-data="{ activeTab: 'q1' }">
                <!-- Common Tab Headers -->
                <div class="flex border-b border-gray-200 dark:border-gray-600 mb-4 -mx-6 px-6 overflow-x-auto">
                    <template x-for="tab in ['q1', 'q2', 'q3', 'q4']" :key="tab">
                        <button type="button" class="px-6 py-2 text-sm font-medium whitespace-nowrap flex-shrink-0"
                            :class="activeTab === tab ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            @click="activeTab = tab" x-text="tab.toUpperCase()">
                        </button>
                    </template>
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
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Gov Share
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Gov Loan
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Foreign Loan
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Foreign Subsidy
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            NEA Budget
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="capital-tbody">
                                    <!-- Dynamic content will be loaded here -->
                                    <tr id="capital-empty">
                                        <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            Select a project and fiscal year to load capital expenses
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Capital Quarterly Total -->
                        <div
                            class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                            <div class="flex justify-between items-center">
                                <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                    Total for <span x-text="activeTab.toUpperCase()"></span>:
                                </span>
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    <span id="capital-q1-total" x-show="activeTab === 'q1'">0.00</span>
                                    <span id="capital-q2-total" x-show="activeTab === 'q2'">0.00</span>
                                    <span id="capital-q3-total" x-show="activeTab === 'q3'">0.00</span>
                                    <span id="capital-q4-total" x-show="activeTab === 'q4'">0.00</span>
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
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Gov Share
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Gov Loan
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Foreign Loan
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            Foreign Subsidy
                                        </th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-center">
                                            NEA Budget
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="recurrent-tbody">
                                    <!-- Dynamic content will be loaded here -->
                                    <tr id="recurrent-empty">
                                        <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            Select a project and fiscal year to load recurrent expenses
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Recurrent Quarterly Total -->
                        <div
                            class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-2 border-blue-200 dark:border-blue-700">
                            <div class="flex justify-between items-center">
                                <span class="text-base font-bold text-gray-800 dark:text-gray-200">
                                    Total for <span x-text="activeTab.toUpperCase()"></span>:
                                </span>
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    <span id="recurrent-q1-total" x-show="activeTab === 'q1'">0.00</span>
                                    <span id="recurrent-q2-total" x-show="activeTab === 'q2'">0.00</span>
                                    <span id="recurrent-q3-total" x-show="activeTab === 'q3'">0.00</span>
                                    <span id="recurrent-q4-total" x-show="activeTab === 'q4'">0.00</span>
                                </span>
                            </div>
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
                .tabs button {
                    padding: 0.5rem 0.75rem;
                    font-size: 0.875rem;
                }

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
                    } else {
                        // Silently fail in production
                    }
                }
            }

            // Invoke the wait function immediately
            waitForJQuery(function($) {

                const tippyInstances = new WeakMap();

                function parseNumeric(val) {
                    return parseFloat(val.replace(/,/g, '')) || 0;
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

                // Update quarterly totals for each section
                function updateQuarterlyTotals(section) {
                    const quarters = ['q1', 'q2', 'q3', 'q4'];
                    const subs = ['internal', 'foreign_loan', 'foreign_subsidy', 'gov_loan', 'gov_share'];

                    quarters.forEach(q => {
                        let quarterTotal = 0;
                        subs.forEach(sub => {
                            $(`#${section}-tbody .expense-input[data-quarter="${q}"][data-sub="${sub}"]`)
                                .each(function() {
                                    quarterTotal += parseNumeric($(this).val());
                                });
                        });
                        $(`#${section}-${q}-total`).text(quarterTotal.toFixed(2));
                    });
                }

                function loadProjectActivities(projectId, fiscalYearId, trigger = 'unknown') {

                    if (!projectId || !fiscalYearId) {
                        $('#capital-tbody').html(
                            '<tr id="capital-empty"><td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project and fiscal year to load capital expenses</td></tr>'
                        );
                        $('#recurrent-tbody').html(
                            '<tr id="recurrent-empty"><td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">Select a project and fiscal year to load recurrent expenses</td></tr>'
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

                            // Update budget display
                            if (response.budgetDetails) {
                                $('#budget-display').html(
                                    `<div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                            <span class="block text-sm font-semibold text-green-700 dark:text-green-300">
                                ${response.budgetDetails}
                            </span>
                        </div>`
                                );
                            }

                            // Populate activities
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
                    <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No ${section} activities found for selected project and fiscal year
                    </td>
                </tr>`
                        );
                        updateQuarterlyTotals(section);
                        return;
                    }

                    tbody.empty();

                    function buildActivityRows(activity, depth = 0, parentNumber = '', childIndex = 0) {
                        const displayNumber = parentNumber ? `${parentNumber}.${childIndex}` : (childIndex + 1)
                            .toString();

                        let row = `<tr class="projectExpense-row" data-depth="${depth}" data-index="${activity.id}">
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                    ${displayNumber}
                </td>
                <td class="border border-gray-300 dark:border-gray-600 px-2 py-1" style="padding-left: ${depth * 20}px;">
                    <input type="hidden" name="${section}[${activity.id}][activity_id]" value="${activity.id}">
                    <input type="hidden" name="${section}[${activity.id}][parent_id]" value="${activity.parent_id || ''}">
                    <span class="${depth === 0 ? 'font-medium' : ''}">${activity.title || activity.program || 'Untitled Activity'}</span>
                </td>`;

                        ['q1', 'q2', 'q3', 'q4'].forEach(q => {
                            ['internal', 'foreign_loan', 'foreign_subsidy', 'gov_loan', 'gov_share'].forEach(
                                sub => {
                                    const value = activity[`${q}_${sub}`] || '0.00';
                                    row += `<td x-show="activeTab === '${q}'" class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                        <input type="text"
                               name="${section}[${activity.id}][${q}][${sub}]"
                               value="${value}"
                               pattern="[0-9]+(\\.[0-9]{1,2})?"
                               class="expense-input tooltip-error numeric-input w-full"
                               data-quarter="${q}"
                               data-sub="${sub}">
                    </td>`;
                                });
                        });

                        row += '</tr>';
                        tbody.append(row);

                        if (activity.children && activity.children.length > 0) {
                            activity.children.forEach((child, idx) => {
                                buildActivityRows(child, depth + 1, displayNumber, idx + 1);
                            });
                        }
                    }

                    activities.forEach((activity, index) => {
                        buildActivityRows(activity, 0, '', index);
                    });

                    initializeTooltips(tbody.find('.tooltip-error'));
                    updateQuarterlyTotals(section);
                }

                function showError(message) {
                    $('#error-text').text(message);
                    $('#error-message').removeClass('hidden');
                }

                // ============================================
                // SETUP MUTATION OBSERVERS (LIKE YOUR WORKING FILE)
                // ============================================
                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');

                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';

                // MutationObserver for project changes
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

                    // Fallback: Also add event listener
                    projectHidden.addEventListener('change', function() {
                        lastProjectValue = this.value;
                        loadProjectActivities(this.value, lastFiscalValue, 'project-event');
                    });
                }

                // MutationObserver for fiscal year changes
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

                    // Fallback: Also add event listener
                    fiscalHidden.addEventListener('change', function() {
                        lastFiscalValue = this.value;
                        loadProjectActivities(lastProjectValue, this.value, 'fiscal-event');
                    });
                }

                // Initial load if both are already selected
                @if (isset($preloadActivities) && $preloadActivities && isset($selectedProjectId) && isset($selectedFiscalYearId))
                    // Preload activities on page load
                    loadProjectActivities('{{ $selectedProjectId }}', '{{ $selectedFiscalYearId }}', 'preload');
                @elseif (lastProjectValue && lastFiscalValue)
                    // Fallback: load if both values exist in hidden inputs
                    loadProjectActivities(lastProjectValue, lastFiscalValue, 'initial');
                @endif

                // Attach input event listener
                $(document).on('input', '.expense-input', function() {
                    const $input = $(this);
                    const $tbody = $input.closest('tbody');
                    const section = $tbody.attr('id').replace('-tbody', '');
                    updateQuarterlyTotals(section);
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

                    $('.expense-input').each(function() {
                        const val = $(this).val().trim();
                        if (val && (isNaN(parseNumeric(val)) || parseNumeric(val) < 0)) {
                            $(this).addClass('error-border');
                            updateTooltip($(this), 'Valid non-negative number required');
                            hasErrors = true;
                        } else {
                            $(this).removeClass('error-border');
                            updateTooltip($(this), '');
                        }
                    });

                    if (hasErrors) {
                        showError('Please correct errors in the expense inputs.');
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
