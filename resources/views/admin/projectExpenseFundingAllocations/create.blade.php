<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="page-title">
            Funding Source Allocation for {{ $firstProject->title ?? '' }} -
            {{ $selectedFiscalYear->title ?? 'Current Fiscal Year' }} - Quarter {{ $selectedQuarter ?? '' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} Funding Source Allocations for Expenses
        </p>
    </div>

    <form id="expenseFundingAllocation-form" class="w-full"
        action="{{ route('admin.projectExpenseFundingAllocation.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/3 relative z-50">
                    <x-forms.select label="{{ trans('global.project.fields.title') }}" name="project_id" id="project_id"
                        :options="$projectOptions" :selected="$selectedProjectId ?? ''" placeholder="{{ trans('global.pleaseSelect') }}"
                        :error="$errors->first('project_id')" class="js-single-select" required />
                </div>

                <div class="w-full md:w-1/3 relative z-50">
                    <x-forms.select label="{{ trans('global.fiscal_year.fields.title') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYearOptions" :selected="$selectedFiscalYearId ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/3 relative z-50">
                    <x-forms.select label="Quarter" name="quarter" id="quarter" :options="['' => 'Select Quarter', 1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4']" :selected="$selectedQuarter ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('quarter')" class="js-single-select"
                        required />
                </div>
            </div>

            <div id="budget-remaining-display" class="mt-2">
                <span class="block text-sm text-gray-500 dark:text-gray-400">
                    Select a project, fiscal year, and quarter to view quarterly budget remainings and expenses.
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

            <!-- Quarterly Budget Remainings (for reference) -->
            <div class="mb-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Quarterly Budget Remainings
                    </h4>
                    <div id="remaining-sources" class="grid grid-cols-5 gap-4 text-xs">
                        <!-- Populated via JS -->
                    </div>
                </div>
            </div>

            <!-- Expense Funding Allocation -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Expense Allocations by Funding Source
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="expense-funding-allocation"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 text-center">
                                        S.N.
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Expense Description
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Total Amount
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Internal
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Gov. Share
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Gov. Loan
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Foreign Loan
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Foreign Subsidy
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="expense-funding-allocation-body">
                                @forelse ($expenseData ?? [] as $row)
                                    <tr>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                            {{ $row['sn'] ?? $loop->iteration }}
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $row['description'] ?? '' }}
                                            <input type="hidden" name="quarter_ids[]"
                                                value="{{ $row['quarter_id'] ?? '' }}">
                                        </td>
                                        <td
                                            class="amount-cell border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                            {{ number_format($row['total_amount'] ?? 0, 2) }}
                                            <input type="hidden" name="total_amounts[]"
                                                value="{{ $row['total_amount'] ?? 0 }}">
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="internal_allocations[]" min="0"
                                                step="0.01" value="{{ $row['internal'] ?? 0 }}" placeholder="0"
                                                class="excel-input source-input" data-source="internal" required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="gov_share_allocations[]" min="0"
                                                step="0.01" value="{{ $row['gov_share'] ?? 0 }}" placeholder="0"
                                                class="excel-input source-input" data-source="government_share"
                                                required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="gov_loan_allocations[]" min="0"
                                                step="0.01" value="{{ $row['gov_loan'] ?? 0 }}" placeholder="0"
                                                class="excel-input source-input" data-source="government_loan" required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="foreign_loan_allocations[]" min="0"
                                                step="0.01" value="{{ $row['foreign_loan'] ?? 0 }}" placeholder="0"
                                                class="excel-input source-input" data-source="foreign_loan" required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="foreign_subsidy_allocations[]" min="0"
                                                step="0.01" value="{{ $row['foreign_subsidy'] ?? 0 }}"
                                                placeholder="0" class="excel-input source-input"
                                                data-source="foreign_subsidy" required>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                            No expense data available for this project, fiscal year, and quarter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <!-- Total Row -->
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                                    <td colspan="2"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right pr-4">
                                        Total
                                    </td>
                                    <td id="total-amount"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                    <td id="total-internal"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                    <td id="total-gov-share"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                    <td id="total-gov-loan"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                    <td id="total-foreign-loan"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                    <td id="total-foreign-subsidy"
                                        class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                        0.00
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
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
            .excel-input {
                background: transparent;
                border: none;
                color: inherit;
                font: inherit;
                text-align: right;
                width: 100%;
                padding: 0;
                margin: 0;
                outline: none;
                transition: background-color 0.2s ease;
            }

            .excel-input:focus:not([readonly]) {
                background-color: rgba(59, 130, 246, 0.1);
                border: 1px solid #3b82f6;
                border-radius: 2px;
                padding: 1px 2px;
            }

            .excel-input::-webkit-outer-spin-button,
            .excel-input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            .excel-input {
                -moz-appearance: textfield;
            }

            .amount-cell {
                background-color: rgba(0, 0, 0, 0.05);
            }

            #expense-funding-allocation td,
            #expense-funding-allocation th {
                padding: 12px 8px;
            }

            /* Error styles */
            .error-border {
                border: 2px solid #ef4444 !important;
                border-radius: 4px;
            }

            .error-row {
                background-color: rgba(239, 68, 68, 0.1) !important;
            }

            .dark .error-row {
                background-color: rgba(239, 68, 68, 0.2) !important;
            }

            /* Tooltip styles */
            .tooltip-container {
                position: relative;
            }

            .tooltip-error {
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                background-color: #ef4444;
                color: white;
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 1000;
                margin-bottom: 5px;
                display: none;
            }

            .tooltip-error::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 5px solid transparent;
                border-top-color: #ef4444;
            }

            .tooltip-error.show {
                display: block;
            }

            /* Budget remaining indicators */
            .remaining-item {
                display: flex;
                justify-content: space-between;
                padding: 4px 8px;
                background: white;
                border-radius: 4px;
                border: 1px solid #e5e7eb;
            }

            .dark .remaining-item {
                background: #374151;
                border-color: #4b5563;
            }

            .remaining-value {
                font-weight: bold;
                color: #059669;
            }

            .remaining-value.exceeded {
                color: #dc2626;
            }
        </style>

        <!-- Include Tippy.js for better tooltips -->
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Expense funding allocation script loaded');

                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const quarterSelect = document.getElementById('quarter');
                const tbody = document.getElementById('expense-funding-allocation-body');
                const pageTitle = document.getElementById('page-title');
                const form = document.getElementById('expenseFundingAllocation-form');
                const submitButton = document.getElementById('submit-button');
                const remainingDisplay = document.getElementById('budget-remaining-display');
                const remainingSources = document.getElementById('remaining-sources');

                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';
                let lastQuarterValue = quarterSelect ? quarterSelect.value : '';
                const tippyInstances = new WeakMap();
                const sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];
                const sourceLabels = {
                    internal: 'Internal',
                    government_share: 'Gov. Share',
                    government_loan: 'Gov. Loan',
                    foreign_loan: 'Foreign Loan',
                    foreign_subsidy: 'Foreign Subsidy'
                };

                // Initialize Tippy tooltip for an input
                function initTooltip(input) {
                    if (!tippyInstances.has(input)) {
                        const instance = tippy(input, {
                            content: '',
                            trigger: 'manual',
                            placement: 'top',
                            arrow: true,
                            theme: 'error',
                            onCreate(instance) {
                                instance.popper.style.backgroundColor = '#ef4444';
                            }
                        });
                        tippyInstances.set(input, instance);
                    }
                }

                // Update tooltip content and visibility
                function updateTooltip(input, message) {
                    const instance = tippyInstances.get(input);
                    if (instance) {
                        instance.setContent(message);
                        if (message) {
                            instance.show();
                        } else {
                            instance.hide();
                        }
                    }
                }

                // Validate a single row (sum of sources == total amount)
                function validateRow(row) {
                    const totalHidden = row.querySelector('input[name="total_amounts[]"]');
                    const total = parseFloat(totalHidden?.value || 0);

                    let rowSum = 0;
                    let sourceSums = {};
                    sources.forEach(source => {
                        const input = row.querySelector(`[data-source="${source}"]`);
                        const val = parseFloat(input?.value || 0);
                        rowSum += val;
                        sourceSums[source] = val;
                    });

                    const tolerance = 0.01;
                    const diff = Math.abs(rowSum - total);
                    const isValid = diff <= tolerance;

                    if (!isValid) {
                        const message =
                            `Sum of sources (${rowSum.toFixed(2)}) ${rowSum > total ? 'exceeds' : 'less than'} total (${total.toFixed(2)})`;
                        row.classList.add('error-row');
                        sources.forEach(source => {
                            const input = row.querySelector(`[data-source="${source}"]`);
                            if (input) {
                                input.classList.add('error-border');
                                updateTooltip(input, message);
                            }
                        });
                        const amountCell = row.querySelector('.amount-cell');
                        if (amountCell) amountCell.classList.add('error-border');
                        return false;
                    } else {
                        row.classList.remove('error-row');
                        sources.forEach(source => {
                            const input = row.querySelector(`[data-source="${source}"]`);
                            if (input) {
                                input.classList.remove('error-border');
                                updateTooltip(input, '');
                            }
                        });
                        const amountCell = row.querySelector('.amount-cell');
                        if (amountCell) amountCell.classList.remove('error-border');
                        return true;
                    }
                }

                // Validate all rows and column totals vs budget remainings
                function validateAll() {
                    let isValid = true;
                    const rows = tbody.querySelectorAll('tr:not(:last-child)');
                    rows.forEach(row => {
                        if (!validateRow(row)) isValid = false;
                    });

                    // Check column totals vs remainings (if loaded)
                    sources.forEach(source => {
                        const totalEl = document.getElementById(`total-${source.replace('_', '-')}`);
                        const total = parseFloat(totalEl?.textContent || 0);
                        const remainingEl = document.querySelector(
                            `[data-source="${source}"] .remaining-value`);
                        const remaining = parseFloat(remainingEl?.textContent || 0);
                        if (total > remaining + 0.01) {
                            totalEl.classList.add('exceeded');
                            isValid = false;
                        } else {
                            totalEl.classList.remove('exceeded');
                        }
                    });

                    return isValid;
                }

                function updateTotals() {
                    let grandTotal = 0;
                    let sourceTotals = {};
                    sources.forEach(s => sourceTotals[s] = 0);

                    const rows = tbody.querySelectorAll('tr:not(:last-child)');
                    rows.forEach(row => {
                        const totalHidden = row.querySelector('input[name="total_amounts[]"]');
                        grandTotal += parseFloat(totalHidden?.value || 0);

                        sources.forEach(source => {
                            const input = row.querySelector(`[data-source="${source}"]`);
                            sourceTotals[source] += parseFloat(input?.value || 0);
                        });
                    });

                    document.getElementById('total-amount').textContent = grandTotal.toFixed(2);
                    sources.forEach(source => {
                        const el = document.getElementById(`total-${source.replace('_', '-')}`);
                        if (el) el.textContent = sourceTotals[source].toFixed(2);
                    });

                    validateAll(); // Re-validate after update
                }

                function displayBudgetRemainings(remainings) {
                    remainingSources.innerHTML = '';
                    sources.forEach(source => {
                        const div = document.createElement('div');
                        div.className = 'remaining-item';
                        div.innerHTML = `
                            <span>${sourceLabels[source]}</span>
                            <span class="remaining-value" data-source="${source}">${(remainings[source] || 0).toFixed(2)}</span>
                        `;
                        remainingSources.appendChild(div);
                    });
                    remainingDisplay.style.display = remainings ? 'block' : 'none';
                }

                function rebuildTable(expenseData, remainings, projectName, fiscalYearTitle, quarter) {
                    console.log('Rebuilding table with', expenseData.length, 'rows');
                    tbody.innerHTML = '';

                    if (expenseData.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.innerHTML = `
                            <td colspan="8" class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                No expense data available for this project, fiscal year, and quarter.
                            </td>
                        `;
                        tbody.appendChild(emptyRow);
                    } else {
                        expenseData.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                    ${row.sn}
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                    ${row.description}
                                    <input type="hidden" name="quarter_ids[]" value="${row.quarter_id}">
                                </td>
                                <td class="amount-cell border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                    ${parseFloat(row.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    <input type="hidden" name="total_amounts[]" value="${row.total_amount}">
                                </td>
                                ${sources.map(source => `
                                                                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                                                                <input type="number" name="${source.replace('_', '-')}_allocations[]" min="0" step="0.01"
                                                                                    value="${(parseFloat(row[source] || 0)).toFixed(2)}" placeholder="0" class="excel-input source-input"
                                                                                    data-source="${source}" required>
                                                                            </td>
                                                                        `).join('')}
                            `;
                            tbody.appendChild(tr);

                            // Initialize tooltips
                            tr.querySelectorAll('.source-input').forEach(input => initTooltip(input));
                        });
                    }

                    displayBudgetRemainings(remainings);
                    updateTotals();

                    // Update page title
                    const qLabel = quarter ? `Q${quarter}` : '';
                    pageTitle.textContent =
                        `Funding Source Allocation for ${projectName || ''} - ${fiscalYearTitle || ''} ${qLabel}`;
                }

                function loadExpenseData(trigger = 'unknown') {
                    const projectId = projectHidden ? projectHidden.value : '';
                    const fiscalYearId = fiscalHidden ? fiscalHidden.value : '';
                    const quarter = quarterSelect ? quarterSelect.value : '';

                    console.log(
                        `loadExpenseData triggered by ${trigger} - Project: ${projectId}, Fiscal: ${fiscalYearId}, Quarter: ${quarter}`
                    );

                    if (!projectId || !fiscalYearId || !quarter) {
                        console.log('Missing selection, clearing table');
                        rebuildTable([], {}, '', '', '');
                        return;
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('CSRF token not found!');
                        return;
                    }

                    console.log('Fetching expense data...');

                    fetch(`{{ route('admin.projectExpenseFundingAllocations.loadData') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                project_id: projectId,
                                fiscal_year_id: fiscalYearId,
                                quarter: quarter
                            })
                        })
                        .then(response => {
                            console.log('Fetch response status:', response.status);
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('AJAX success:', data);
                            rebuildTable(
                                data.expenseData || [],
                                data.budgetRemainings || {},
                                data.projectName || '',
                                data.fiscalYearTitle || '',
                                quarter
                            );
                        })
                        .catch(error => {
                            console.error('AJAX error:', error);
                            rebuildTable([], {}, '', '', '');
                        });
                }

                // Form submit handler with validation
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!validateAll()) {
                        alert(
                            'Please correct all validation errors before submitting. Row sums must equal totals, and source totals must not exceed quarterly remainings.'
                        );
                        return;
                    }

                    submitButton.disabled = true;
                    submitButton.textContent = '{{ trans('global.saving') }}...';
                    form.submit();
                });

                // Event delegation for source input changes
                document.addEventListener('input', function(e) {
                    if (e.target.matches('.source-input')) {
                        const tr = e.target.closest('tr');
                        if (!tr) return;

                        validateRow(tr);
                        updateTotals();
                    }
                });

                // Observers and event listeners for selections
                if (projectHidden) {
                    const projectObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = projectHidden.value;
                                if (newValue !== lastProjectValue) {
                                    lastProjectValue = newValue;
                                    loadExpenseData('project-observer');
                                }
                            }
                        });
                    });
                    projectObserver.observe(projectHidden, {
                        attributes: true
                    });

                    projectHidden.addEventListener('change', function() {
                        lastProjectValue = this.value;
                        loadExpenseData('project-event');
                    });
                }

                if (fiscalHidden) {
                    const fiscalObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = fiscalHidden.value;
                                if (newValue !== lastFiscalValue) {
                                    lastFiscalValue = newValue;
                                    loadExpenseData('fiscal-observer');
                                }
                            }
                        });
                    });
                    fiscalObserver.observe(fiscalHidden, {
                        attributes: true
                    });

                    fiscalHidden.addEventListener('change', function() {
                        lastFiscalValue = this.value;
                        loadExpenseData('fiscal-event');
                    });
                }

                if (quarterSelect) {
                    quarterSelect.addEventListener('change', function() {
                        lastQuarterValue = this.value;
                        loadExpenseData('quarter-event');
                    });
                }

                // Initial load
                if (projectHidden && fiscalHidden && quarterSelect && projectHidden.value && fiscalHidden.value &&
                    quarterSelect.value) {
                    loadExpenseData('initial');
                }

                // Initialize tooltips for existing rows
                const initialRows = tbody.querySelectorAll('tr');
                initialRows.forEach(row => {
                    row.querySelectorAll('.source-input').forEach(input => initTooltip(input));
                });
            });
        </script>
    @endpush
</x-layouts.app>
