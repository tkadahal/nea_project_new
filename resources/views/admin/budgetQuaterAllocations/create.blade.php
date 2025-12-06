<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="page-title">
            Quarterly Budget Allocation for {{ $firstProject->title ?? '' }} -
            {{ $selectedFiscalYear->title ?? 'Current Fiscal Year' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} Quarterly Budget Allocation
        </p>
    </div>

    <form id="quaterBudgetAllocation-form" class="w-full" action="{{ route('admin.budgetQuaterAllocation.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.project_id') }}" name="project_id"
                        id="project_id" :options="$projectOptions" :selected="$selectedProjectId ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')" class="js-single-select"
                        required />
                </div>

                <div class="w-full md:w-1/2 relative z-50">
                    <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                        id="fiscal_year_id" :options="$fiscalYears" :selected="$selectedFiscalYearId ?? ''"
                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>
            </div>

            <div id="budget-display" class="mt-2">
                <span class="block text-sm text-gray-500 dark:text-gray-400">
                    Select a project and fiscal year to view budget details.
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

            <!-- Budget Quater Allocation -->
            <div class="mb-8">
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        Quarterly Budget Allocation
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="quater-budget-allocation"
                            class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-200 dark:bg-gray-600">
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-12 text-center">
                                        S.N.
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-64">
                                        Title
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Amount
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q1
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q2
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q3
                                    </th>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32 text-right">
                                        Q4
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="quater-budget-allocation-body">
                                @forelse ($budgetData as $row)
                                    <tr>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                            {{ $row['sn'] }}
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $row['title'] }}
                                            <input type="hidden" name="budget_ids[]" value="{{ $row['budget_id'] }}">
                                            <input type="hidden" name="fields[]" value="{{ $row['field'] }}">
                                            <input type="hidden" name="amounts[]" value="{{ $row['amount'] }}">
                                        </td>
                                        <td
                                            class="amount-cell border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                            {{ number_format($row['amount'], 2) }}
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="q1_allocations[]" min="0" step="0.01"
                                                value="{{ $row['q1'] ?? 0 }}" placeholder="0" class="excel-input"
                                                required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="q2_allocations[]" min="0" step="0.01"
                                                value="{{ $row['q2'] ?? 0 }}" placeholder="0" class="excel-input"
                                                required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="q3_allocations[]" min="0" step="0.01"
                                                value="{{ $row['q3'] ?? 0 }}" placeholder="0" class="excel-input"
                                                required>
                                        </td>
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                            <input type="number" name="q4_allocations[]" min="0" step="0.01"
                                                value="{{ $row['q4'] ?? 0 }}" placeholder="0" class="excel-input"
                                                required>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                            No budget data available for this project and fiscal year.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
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

            .excel-input[readonly] {
                background: transparent;
                color: #9ca3af;
                cursor: not-allowed;
            }

            .dark .excel-input[readonly] {
                color: #d1d5db;
            }

            .amount-cell {
                background-color: rgba(0, 0, 0, 0.05);
            }

            #quater-budget-allocation td,
            #quater-budget-allocation th {
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
        </style>

        <!-- Include Tippy.js for better tooltips -->
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Budget allocation script loaded');

                const projectHidden = document.querySelector(
                    '.js-single-select[data-name="project_id"] .js-hidden-input');
                const fiscalHidden = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const tbody = document.getElementById('quater-budget-allocation-body');
                const pageTitle = document.getElementById('page-title');
                const form = document.getElementById('quaterBudgetAllocation-form');
                const submitButton = document.getElementById('submit-button');

                let lastProjectValue = projectHidden ? projectHidden.value : '';
                let lastFiscalValue = fiscalHidden ? fiscalHidden.value : '';
                const tippyInstances = new WeakMap();

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

                // Validate a single row
                function validateRow(row) {
                    const fieldInput = row.querySelector('input[name="fields[]"]');
                    if (!fieldInput || fieldInput.value === 'total_budget') {
                        return true; // Skip total row
                    }

                    const amountCell = row.querySelector('.amount-cell');
                    const amount = parseFloat(row.querySelector('input[name="amounts[]"]')?.value || 0);

                    const q1Input = row.querySelector('input[name="q1_allocations[]"]');
                    const q2Input = row.querySelector('input[name="q2_allocations[]"]');
                    const q3Input = row.querySelector('input[name="q3_allocations[]"]');
                    const q4Input = row.querySelector('input[name="q4_allocations[]"]');

                    const q1 = parseFloat(q1Input?.value || 0);
                    const q2 = parseFloat(q2Input?.value || 0);
                    const q3 = parseFloat(q3Input?.value || 0);
                    const q4 = parseFloat(q4Input?.value || 0);

                    const sum = q1 + q2 + q3 + q4;
                    const tolerance = 0.01;

                    // Only validate if Q4 has been filled
                    const q4Filled = q4Input?.value && q4Input.value.trim() !== '';

                    if (q4Filled && Math.abs(sum - amount) > tolerance) {
                        const message = sum > amount ?
                            `Sum of quarters (${sum.toFixed(2)}) exceeds amount (${amount.toFixed(2)})` :
                            `Sum of quarters (${sum.toFixed(2)}) is less than amount (${amount.toFixed(2)})`;

                        // Add error styling
                        row.classList.add('error-row');
                        [q1Input, q2Input, q3Input, q4Input].forEach(input => {
                            if (input) {
                                input.classList.add('error-border');
                                updateTooltip(input, message);
                            }
                        });
                        if (amountCell) {
                            amountCell.classList.add('error-border');
                        }

                        return false;
                    } else {
                        // Remove error styling
                        row.classList.remove('error-row');
                        [q1Input, q2Input, q3Input, q4Input].forEach(input => {
                            if (input) {
                                input.classList.remove('error-border');
                                updateTooltip(input, '');
                            }
                        });
                        if (amountCell) {
                            amountCell.classList.remove('error-border');
                        }

                        return true;
                    }
                }

                // Validate all rows
                function validateAllRows() {
                    let isValid = true;
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        if (!validateRow(row)) {
                            isValid = false;
                        }
                    });
                    return isValid;
                }

                function rebuildTable(budgetData, projectName, fiscalYearTitle) {
                    console.log('Rebuilding table with', budgetData.length, 'rows');
                    tbody.innerHTML = '';

                    if (budgetData.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.innerHTML = `
                        <td colspan="7" class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                            No budget data available for this project and fiscal year.
                        </td>
                    `;
                        tbody.appendChild(emptyRow);
                    } else {
                        budgetData.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-center">
                                ${row.sn}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                ${row.title}
                                <input type="hidden" name="budget_ids[]" value="${row.budget_id}">
                                <input type="hidden" name="fields[]" value="${row.field}">
                                <input type="hidden" name="amounts[]" value="${row.amount}">
                            </td>
                            <td class="amount-cell border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 text-right">
                                ${parseFloat(row.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                <input type="number" name="q1_allocations[]" min="0" step="0.01" value="${(parseFloat(row.q1 || 0)).toFixed(2)}" placeholder="0" class="excel-input" required>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                <input type="number" name="q2_allocations[]" min="0" step="0.01" value="${(parseFloat(row.q2 || 0)).toFixed(2)}" placeholder="0" class="excel-input" required>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                <input type="number" name="q3_allocations[]" min="0" step="0.01" value="${(parseFloat(row.q3 || 0)).toFixed(2)}" placeholder="0" class="excel-input" required>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 relative">
                                <input type="number" name="q4_allocations[]" min="0" step="0.01" value="${(parseFloat(row.q4 || 0)).toFixed(2)}" placeholder="0" class="excel-input" required>
                            </td>
                        `;
                            tbody.appendChild(tr);

                            // Initialize tooltips for quarter inputs
                            tr.querySelectorAll('input[name^="q"][name$="_allocations[]"]').forEach(input => {
                                initTooltip(input);
                            });
                        });

                        // Make total row readonly
                        const totalRow = Array.from(tbody.querySelectorAll('tr')).find(tr => {
                            const fieldInput = tr.querySelector('input[name="fields[]"]');
                            return fieldInput && fieldInput.value === 'total_budget';
                        });

                        if (totalRow) {
                            const quarterInputs = totalRow.querySelectorAll('input[type="number"]');
                            quarterInputs.forEach(input => {
                                input.readOnly = true;
                            });
                            totalRow.classList.add('bg-gray-50', 'dark:bg-gray-700', 'font-semibold');
                            updateTotal();
                        }
                    }

                    // Update page title
                    if (projectName && fiscalYearTitle) {
                        pageTitle.textContent = `Quarterly Budget Allocation for ${projectName} - ${fiscalYearTitle}`;
                    } else {
                        pageTitle.textContent = 'Quarterly Budget Allocation for - ';
                    }
                }

                function updateTotal() {
                    const totalRow = Array.from(tbody.querySelectorAll('tr')).find(tr => {
                        const fieldInput = tr.querySelector('input[name="fields[]"]');
                        return fieldInput && fieldInput.value === 'total_budget';
                    });

                    if (!totalRow) return;

                    let sumQ1 = 0,
                        sumQ2 = 0,
                        sumQ3 = 0,
                        sumQ4 = 0;

                    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
                        const fieldInput = tr.querySelector('input[name="fields[]"]');
                        if (!fieldInput || fieldInput.value === 'total_budget') return;

                        sumQ1 += parseFloat(tr.querySelector('input[name="q1_allocations[]"]')?.value || 0);
                        sumQ2 += parseFloat(tr.querySelector('input[name="q2_allocations[]"]')?.value || 0);
                        sumQ3 += parseFloat(tr.querySelector('input[name="q3_allocations[]"]')?.value || 0);
                        sumQ4 += parseFloat(tr.querySelector('input[name="q4_allocations[]"]')?.value || 0);
                    });

                    totalRow.querySelector('input[name="q1_allocations[]"]').value = sumQ1.toFixed(2);
                    totalRow.querySelector('input[name="q2_allocations[]"]').value = sumQ2.toFixed(2);
                    totalRow.querySelector('input[name="q3_allocations[]"]').value = sumQ3.toFixed(2);
                    totalRow.querySelector('input[name="q4_allocations[]"]').value = sumQ4.toFixed(2);
                }

                function loadBudgets(trigger = 'unknown') {
                    const projectId = projectHidden ? projectHidden.value : '';
                    const fiscalYearId = fiscalHidden ? fiscalHidden.value : '';

                    console.log(`loadBudgets triggered by ${trigger} - Project: ${projectId}, Fiscal: ${fiscalYearId}`);

                    if (!projectId || !fiscalYearId) {
                        console.log('No project or fiscal year selected, clearing table');
                        rebuildTable([], '', '');
                        return;
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('CSRF token not found!');
                        return;
                    }

                    console.log('Fetching budgets...');

                    fetch(`{{ route('admin.budgetQuaterAllocations.loadBudgets') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                project_id: projectId,
                                fiscal_year_id: fiscalYearId
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
                            rebuildTable(data.budgetData || [], data.projectName || '', data.fiscalYearTitle || '');
                        })
                        .catch(error => {
                            console.error('AJAX error:', error);
                            rebuildTable([], '', '');
                        });
                }

                // Form submit handler with validation
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!validateAllRows()) {
                        alert(
                            'Please correct all validation errors before submitting. Sum of quarters must equal the amount for each row.'
                        );
                        return;
                    }

                    submitButton.disabled = true;
                    submitButton.textContent = '{{ trans('global.saving') }}...';
                    form.submit();
                });

                // Event delegation for quarter input changes
                document.addEventListener('input', function(e) {
                    if (e.target.matches('input[name^="q"][name$="_allocations[]"]')) {
                        const tr = e.target.closest('tr');
                        if (!tr) return;

                        const fieldInput = tr.querySelector('input[name="fields[]"]');
                        if (!fieldInput || fieldInput.value === 'total_budget') return;

                        // Validate the row
                        validateRow(tr);

                        // Update total
                        updateTotal();
                    }
                });

                // MutationObserver for project changes
                if (projectHidden) {
                    const projectObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = projectHidden.value;
                                if (newValue !== lastProjectValue) {
                                    console.log('Project value changed via observer - Old:',
                                        lastProjectValue, 'New:', newValue);
                                    lastProjectValue = newValue;
                                    loadBudgets('project-observer');
                                }
                            }
                        });
                    });
                    projectObserver.observe(projectHidden, {
                        attributes: true
                    });

                    projectHidden.addEventListener('change', function() {
                        console.log('Project change fired via event - Value:', this.value);
                        lastProjectValue = this.value;
                        loadBudgets('project-event');
                    });
                }

                // MutationObserver for fiscal changes
                if (fiscalHidden) {
                    const fiscalObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const newValue = fiscalHidden.value;
                                if (newValue !== lastFiscalValue) {
                                    console.log('Fiscal value changed via observer - Old:',
                                        lastFiscalValue, 'New:', newValue);
                                    lastFiscalValue = newValue;
                                    loadBudgets('fiscal-observer');
                                }
                            }
                        });
                    });
                    fiscalObserver.observe(fiscalHidden, {
                        attributes: true
                    });

                    fiscalHidden.addEventListener('change', function() {
                        console.log('Fiscal change fired via event - Value:', this.value);
                        lastFiscalValue = this.value;
                        loadBudgets('fiscal-event');
                    });
                }

                // Initial load
                if (projectHidden && fiscalHidden && projectHidden.value && fiscalHidden.value) {
                    loadBudgets('initial');
                }

                // Initialize tooltips for existing rows
                const initialRows = tbody.querySelectorAll('tr');
                initialRows.forEach(row => {
                    row.querySelectorAll('input[name^="q"][name$="_allocations[]"]').forEach(input => {
                        initTooltip(input);
                    });
                });

                // Make total row readonly on initial render
                const initialTotalRow = Array.from(tbody.querySelectorAll('tr')).find(tr => {
                    const fieldInput = tr.querySelector('input[name="fields[]"]');
                    return fieldInput && fieldInput.value === 'total_budget';
                });

                if (initialTotalRow) {
                    const quarterInputs = initialTotalRow.querySelectorAll('input[type="number"]');
                    quarterInputs.forEach(input => {
                        input.readOnly = true;
                    });
                    initialTotalRow.classList.add('bg-gray-50', 'dark:bg-gray-700', 'font-semibold');
                    updateTotal();
                }
            });
        </script>
    @endpush
</x-layouts.app>
