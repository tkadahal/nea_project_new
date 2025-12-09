<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.expense.title') }} Funding Allocation
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Allocate funding sources across all quarters
        </p>
    </div>

    <form id="projectExpenseFunding-form" class="w-full"
        action="{{ route('admin.projectExpenseFundingAllocation.store') }}" method="POST" enctype="multipart/form-data">
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
            </div>
        </div>

        @if (!$quarter)
            <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h3
                    class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                    Select Quarter for Funding Allocation
                </h3>
                @if (!empty($filledQuarters))
                    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg dark:bg-green-900 dark:text-green-200">
                        Completed quarters: {{ implode(', ', array_map(fn($q) => 'Q' . $q, $filledQuarters)) }}
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @for ($q = 1; $q <= 4; $q++)
                        @php
                            $isFilled = in_array($q, $filledQuarters ?? []);
                        @endphp
                        <a href="{{ route('admin.projectExpenseFundingAllocation.create', ['project_id' => $selectedProjectId, 'fiscal_year_id' => $selectedFiscalYearId, 'quarter' => $q]) }}"
                            class="block p-4 text-center rounded-lg font-medium transition-colors
                                  {{ $isFilled ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800' : 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800' }}">
                            {{ $isFilled ? 'Edit' : 'Allocate' }}<br>
                            <span class="text-lg">Q{{ $q }}</span>
                        </a>
                    @endfor
                </div>
            </div>
        @endif

        @if ($quarter)
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

                <!-- Funding Allocation Table - Single Quarter -->
                <div class="mb-8">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            Funding Allocations for Quarter Q{{ $quarter }}
                        </h3>

                        @if ($quarterTotal == 0)
                            <div
                                class="p-4 bg-yellow-100 text-yellow-800 border border-yellow-300 rounded-lg dark:bg-yellow-900 dark:text-yellow-200 dark:border-yellow-700 mb-4">
                                No expenses defined for this quarter. Allocations cannot be set.
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                                <thead>
                                    <tr class="bg-gray-200 dark:bg-gray-600 sticky top-0 z-10">
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-24">
                                            Quarter</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Total Expense</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Internal Budget</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Government Loan</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Government Share</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Foreign Loan</th>
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 w-32">
                                            Foreign Subsidy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="allocation-row" data-quarter="{{ $quarter }}">
                                        <td
                                            class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm text-left">
                                            <div class="font-medium">Q{{ $quarter }}</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">Total Activities:
                                                {{ count($activityDetails) }}</div>
                                            <input type="hidden" name="activity_details"
                                                value="{{ json_encode($activityDetails) }}">
                                            <input type="hidden" name="quarter" value="{{ $quarter }}">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="total_amount"
                                                value="{{ number_format($quarterTotal, 2) }}" placeholder="0.00"
                                                readonly
                                                class="allocation-input numeric-input w-full bg-gray-100 cursor-not-allowed">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="internal_allocations"
                                                value="{{ number_format($existingAllocations['internal'] ?? 0, 2) }}"
                                                placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?"
                                                class="allocation-input tooltip-error numeric-input w-full"
                                                data-source="internal" data-quarter="{{ $quarter }}">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="gov_loan_allocations"
                                                value="{{ number_format($existingAllocations['government_loan'] ?? 0, 2) }}"
                                                placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?"
                                                class="allocation-input tooltip-error numeric-input w-full"
                                                data-source="government_loan" data-quarter="{{ $quarter }}">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="gov_share_allocations"
                                                value="{{ number_format($existingAllocations['government_share'] ?? 0, 2) }}"
                                                placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?"
                                                class="allocation-input tooltip-error numeric-input w-full"
                                                data-source="government_share" data-quarter="{{ $quarter }}">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="foreign_loan_allocations"
                                                value="{{ number_format($existingAllocations['foreign_loan'] ?? 0, 2) }}"
                                                placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?"
                                                class="allocation-input tooltip-error numeric-input w-full"
                                                data-source="foreign_loan" data-quarter="{{ $quarter }}">
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right">
                                            <input type="text" name="foreign_subsidy_allocations"
                                                value="{{ number_format($existingAllocations['foreign_subsidy'] ?? 0, 2) }}"
                                                placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?"
                                                class="allocation-input tooltip-error numeric-input w-full"
                                                data-source="foreign_subsidy" data-quarter="{{ $quarter }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <x-buttons.primary id="submit-button" type="submit" :disabled="$quarterTotal == 0">
                        {{ trans('global.save') }}
                    </x-buttons.primary>
                </div>
            </div>
        @endif
    </form>

    @push('scripts')
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>

        <style>
            .allocation-input {
                width: 100%;
                border: none;
                background: transparent;
                text-align: right;
                padding: 0;
                margin: 0;
                font: inherit;
                color: inherit;
            }

            .allocation-input:focus {
                background-color: rgba(59, 130, 246, 0.1);
                border: 1px solid #3b82f6;
                border-radius: 2px;
                padding: 1px 2px;
            }

            .allocation-input[readonly] {
                background-color: rgba(209, 213, 219, 0.5);
                color: #6b7280;
            }

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

                function validateRow() {
                    const totalInput = $('input[name="total_amount"]');
                    const sourceInputs = $(
                        'input[name="internal_allocations"], ' +
                        'input[name="gov_share_allocations"], ' +
                        'input[name="gov_loan_allocations"], ' +
                        'input[name="foreign_loan_allocations"], ' +
                        'input[name="foreign_subsidy_allocations"]'
                    );
                    let sumAlloc = 0;
                    sourceInputs.each(function() {
                        sumAlloc += parseNumeric($(this).val());
                    });
                    const totalVal = parseNumeric(totalInput.val());
                    const difference = Math.abs(sumAlloc - totalVal);
                    if (difference > 0.01) {
                        sourceInputs.addClass('error-border');
                        let message = sumAlloc > totalVal ? `Sum exceeds total by ${(sumAlloc - totalVal).toFixed(2)}` :
                            `Sum short of total by ${(totalVal - sumAlloc).toFixed(2)}`;
                        sourceInputs.each(function() {
                            updateTooltip($(this), message);
                        });
                        return false;
                    } else {
                        sourceInputs.removeClass('error-border');
                        sourceInputs.each(function() {
                            updateTooltip($(this), '');
                        });
                        return true;
                    }
                }

                function showError(message) {
                    $('#error-text').text(message);
                    $('#error-message').removeClass('hidden');
                }

                // Redirect on project or fiscal year change (reset to quarter selection)
                $('#project_id, #fiscal_year_id').on('change', function() {
                    const projectId = $('#project_id').val();
                    const fiscalYearId = $('#fiscal_year_id').val();
                    if (projectId && fiscalYearId) {
                        const url = new URL(window.location);
                        url.searchParams.set('project_id', projectId);
                        url.searchParams.set('fiscal_year_id', fiscalYearId);
                        url.searchParams.delete('quarter');
                        window.location.href = url.toString();
                    }
                });

                // Event handlers for input validation
                $(document).on('input', '.allocation-input:not([readonly])', function() {
                    validateRow();
                });

                $('#projectExpenseFunding-form').on('submit', function(e) {
                    e.preventDefault();
                    let hasErrors = false;

                    $('.allocation-input:not([readonly])').each(function() {
                        const $input = $(this);
                        const val = $input.val().trim();
                        const num = parseNumeric(val);
                        if (val && (isNaN(num) || num < 0)) {
                            $input.addClass('error-border');
                            updateTooltip($input, 'Valid non-negative number required');
                            hasErrors = true;
                        }
                    });

                    // Validate row sum
                    if (!validateRow()) {
                        hasErrors = true;
                    }

                    if (hasErrors) {
                        showError('Please correct errors in the allocation inputs.');
                        return;
                    }

                    $('#submit-button').prop('disabled', true).text('Saving...');
                    this.submit();
                });

                $('#close-error').on('click', function() {
                    $('#error-message').addClass('hidden');
                    $('.allocation-input').removeClass('error-border').each(function() {
                        updateTooltip($(this), '');
                    });
                });

                initializeTooltips($('.tooltip-error'));
            });
        </script>
    @endpush
</x-layouts.app>
