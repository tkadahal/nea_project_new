<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.budget.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.add') }} {{ trans('global.budget.title_singular') }}
        </p>
    </div>

    <form id="budget-form" action="{{ route('admin.budget.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
            <!-- Header Section -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ trans('global.directorate.title') }}
                    </label>
                    <p class="mt-1 text-gray-800 dark:text-gray-100 font-semibold">{{ $directorateTitle }}</p>
                </div>
                <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                    id="fiscal_year_id" :options="$fiscalYears" :selected="collect($fiscalYears)->firstWhere('selected', true)['value'] ?? ''"
                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select" />
            </div>

            <!-- Excel Actions -->
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.budget.download-template') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                    title="{{ trans('global.budget.fields.download_template') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ trans('global.budget.fields.download_template') }}
                </a>
                <a href="{{ route('admin.budget.upload') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                    title="{{ trans('global.budget.fields.upload_excel') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    {{ trans('global.budget.fields.upload_excel') }}
                </a>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div id="error-message"
                class="mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <!-- Budget Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse bg-white dark:bg-gray-800">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                Id</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                {{ trans('global.budget.fields.project_id') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.government_loan') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.government_share') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.foreign_loan_budget') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                Source</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.foreign_subsidy_budget') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                Source</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.internal_budget') }}</th>
                            <th
                                class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-right">
                                {{ trans('global.budget.fields.total_budget') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                        @foreach ($projects as $index => $project)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center text-sm text-gray-700 dark:text-gray-200">
                                    {{ $index + 1 }}</td>
                                <td
                                    class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm text-gray-700 dark:text-gray-200">
                                    {{ $project->title }}
                                    <input type="hidden" name="project_id[{{ $project->id }}]"
                                        value="{{ $project->id }}">
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="government_loan[{{ $project->id }}]" type="number" step="0.01"
                                        min="0" value="old('government_loan.' . $project->id, '')"
                                        class="w-full border-0 p-1 text-right government-loan-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="government_share[{{ $project->id }}]" type="number" step="0.01"
                                        min="0" value="old('government_share.' . $project->id, '')"
                                        class="w-full border-0 p-1 text-right government-share-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="foreign_loan_budget[{{ $project->id }}]" type="number"
                                        step="0.01" min="0"
                                        value="old('foreign_loan_budget.' . $project->id, '')"
                                        class="w-full border-0 p-1 text-right foreign-loan-budget-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="foreign_loan_source[{{ $project->id }}]" type="text"
                                        value="{{ old('foreign_loan_source.' . $project->id, '') }}"
                                        class="w-full border-0 p-1 text-right foreign-loan-source-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="foreign_subsidy_budget[{{ $project->id }}]" type="number"
                                        step="0.01" min="0"
                                        value="{{ old('foreign_subsidy_budget.' . $project->id, '') }}"
                                        class="w-full border-0 p-1 text-right foreign-subsidy-budget-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="foreign_subsidy_source[{{ $project->id }}]" type="text"
                                        value="{{ old('foreign_subsidy_source.' . $project->id, '') }}"
                                        class="w-full border-0 p-1 text-right foreign-subsidy-source-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="internal_budget[{{ $project->id }}]" type="number" step="0.01"
                                        min="0" value="old('internal_budget.' . $project->id, '')"
                                        class="w-full border-0 p-1 text-right internal-budget-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-1 py-1 text-right relative">
                                    <input name="total_budget[{{ $project->id }}]" type="number" step="0.01"
                                        min="0" value="old('total_budget.' . $project->id, '')"
                                        class="w-full border-0 p-1 text-right total-budget-input tooltip-error"
                                        data-project-id="{{ $project->id }}" placeholder="0.00" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex">
                <x-buttons.primary id="submit-button" type="submit" :disabled="false">
                    {{ trans('global.save') }}
                </x-buttons.primary>
                <a href="{{ route('admin.budget.index') }}"
                    class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
    </form>

    <style>
        /* Scrollbar styles for dropdown */
        .js-options-container::-webkit-scrollbar {
            width: 8px;
        }

        .js-options-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .js-options-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .js-options-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .dark .js-options-container::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .dark .js-options-container::-webkit-scrollbar-thumb {
            background: #6b7280;
        }

        .dark .js-options-container::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .js-options-container {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        .dark .js-options-container {
            scrollbar-color: #6b7280 #1f2937;
        }

        /* Excel-like table styling */
        table {
            border-collapse: collapse;
            border: 1px solid #d1d5db;
            font-family: 'Calibri', 'Arial', sans-serif;
            /* Mimic Excel's default font */
            font-size: 0.875rem;
            /* Match Excel's typical font size */
        }

        th,
        td {
            border: 1px solid #d1d5db;
            /* Thin, Excel-like borders */
            padding: 2px 4px;
            /* Tighter padding for compact look */
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #e5e7eb;
            /* Light gray like Excel headers */
            font-weight: 600;
            text-transform: uppercase;
            color: #374151;
        }

        td {
            background: #ffffff;
            /* White background for cells */
            color: #374151;
        }

        .dark th {
            background: #4b5563;
            /* Dark mode header */
            color: #e5e7eb;
        }

        .dark td {
            background: #1f2937;
            /* Dark mode cell background */
            color: #e5e7eb;
        }

        /* Hover effect for editable cells */
        td:hover .excel-input:not([readonly]) {
            background: #f0f9ff;
            /* Light blue hover like Excel */
        }

        .dark td:hover .excel-input:not([readonly]) {
            background: #374151;
            /* Dark mode hover */
        }
    </style>

    @push('scripts')
        <script>
            function waitForJQuery(callback, retries = 50) {
                if (typeof jQuery !== "undefined" && document.readyState !== "loading") {
                    console.log('jQuery loaded and DOM ready:', jQuery.fn.jquery);
                    callback();
                } else if (retries > 0) {
                    console.log('Waiting for jQuery, retries left:', retries);
                    setTimeout(() => waitForJQuery(callback, retries - 1), 100);
                } else {
                    console.error('jQuery failed to load after retries');
                }
            }

            waitForJQuery(() => {
                const $ = jQuery.noConflict();

                // Budget calculation logic for a specific project
                function updateTotalBudget(projectId) {
                    console.log(`[updateTotalBudget] Processing project ${projectId}`);
                    const $row = $(`input[name="project_id[${projectId}]"]`).closest('tr');
                    if (!$row.length) {
                        console.error(`[updateTotalBudget] Row not found for project ${projectId}`);
                        $('#error-text').text(`Error: Row not found for project ${projectId}.`);
                        $('#error-message').removeClass('hidden');
                        return;
                    }
                    const inputs = {
                        governmentLoan: $row.find(`input[name="government_loan[${projectId}]"]`),
                        governmentShare: $row.find(`input[name="government_share[${projectId}]"]`),
                        foreignLoan: $row.find(`input[name="foreign_loan_budget[${projectId}]"]`),
                        foreignLoanSource: $row.find(`input[name="foreign_loan_source[${projectId}]"]`),
                        foreignSubsidy: $row.find(`input[name="foreign_subsidy_budget[${projectId}]"]`),
                        foreignSubsidySource: $row.find(`input[name="foreign_subsidy_source[${projectId}]"]`),
                        internal: $row.find(`input[name="internal_budget[${projectId}]"]`),
                        total: $row.find(`input[name="total_budget[${projectId}]"]`)
                    };
                    for (const [key, $input] of Object.entries(inputs)) {
                        if (!$input.length) {
                            console.error(`[updateTotalBudget] Input ${key} not found for project ${projectId}`);
                            $('#error-text').text(`Error: Missing ${key} input for project ${projectId}.`);
                            $('#error-message').removeClass('hidden');
                            return;
                        }
                    }
                    const getValidNumber = (value) => {
                        const num = parseFloat(value);
                        return isNaN(num) || num < 0 ? 0 : num;
                    };
                    const total = (
                        getValidNumber(inputs.governmentLoan.val()) +
                        getValidNumber(inputs.governmentShare.val()) +
                        getValidNumber(inputs.foreignLoan.val()) +
                        getValidNumber(inputs.foreignSubsidy.val()) +
                        getValidNumber(inputs.internal.val())
                    ).toFixed(2);
                    inputs.total.val(total);
                    console.log(`[updateTotalBudget] Total for project ${projectId}: ${total}`);
                    $('#error-message').addClass('hidden');
                    if (window.Livewire) {
                        console.log(
                            `[updateTotalBudget] Dispatching Livewire update for total_budget[${projectId}] = ${total}`
                        );
                        window.Livewire.dispatch('input', {
                            name: `total_budget[${projectId}]`,
                            value: total
                        });
                    }
                }

                // Debounce to prevent excessive updates
                function debounce(func, wait) {
                    let timeout;
                    return function(...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                }

                // Event listener for budget component inputs
                $(document).on('input',
                    'input[name^="government_loan["], input[name^="government_share["], input[name^="foreign_loan_budget["], input[name^="foreign_subsidy_budget["], input[name^="internal_budget["]',
                    function() {
                        const inputName = $(this).attr('name');
                        const match = inputName.match(/\[(\d+)\]/);
                        const projectId = match ? match[1] : null;
                        const inputValue = $(this).val();
                        console.log(`[input] Changed: ${inputName} = ${inputValue} for project ${projectId}`);
                        if (projectId) {
                            debounce(() => updateTotalBudget(projectId), 300)();
                        } else {
                            console.error('[input] Project ID not found in input name:', inputName);
                            $('#error-text').text('Error: Project ID not found in input name.');
                            $('#error-message').removeClass('hidden');
                        }
                    });

                // Keyboard navigation for Excel-like experience
                $(document).on('keydown', '.excel-input:not([readonly])', function(e) {
                    const $input = $(this);
                    const $td = $input.closest('td');
                    const $tr = $td.closest('tr');
                    const $inputsInRow = $tr.find('.excel-input:not([readonly])');
                    const currentIndex = $inputsInRow.index($input);
                    let $nextInput = null;

                    switch (e.key) {
                        case 'ArrowRight':
                        case 'Tab':
                            e.preventDefault();
                            if (currentIndex < $inputsInRow.length - 1) {
                                $nextInput = $inputsInRow.eq(currentIndex + 1);
                            } else {
                                // Move to the first input of the next row
                                const $nextRow = $tr.next('tr');
                                if ($nextRow.length) {
                                    $nextInput = $nextRow.find('.excel-input:not([readonly])').first();
                                }
                            }
                            break;
                        case 'ArrowLeft':
                            if (currentIndex > 0) {
                                e.preventDefault();
                                $nextInput = $inputsInRow.eq(currentIndex - 1);
                            }
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            const $nextRow = $tr.next('tr');
                            if ($nextRow.length) {
                                const $inputsInNextRow = $nextRow.find('.excel-input:not([readonly])');
                                if ($inputsInNextRow.length > currentIndex) {
                                    $nextInput = $inputsInNextRow.eq(currentIndex);
                                }
                            }
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            const $prevRow = $tr.prev('tr');
                            if ($prevRow.length) {
                                const $inputsInPrevRow = $prevRow.find('.excel-input:not([readonly])');
                                if ($inputsInPrevRow.length > currentIndex) {
                                    $nextInput = $inputsInPrevRow.eq(currentIndex);
                                }
                            }
                            break;
                        case 'Enter':
                            e.preventDefault();
                            const $nextRowEnter = $tr.next('tr');
                            if ($nextRowEnter.length) {
                                const $inputsInNextRow = $nextRowEnter.find('.excel-input:not([readonly])');
                                if ($inputsInNextRow.length > currentIndex) {
                                    $nextInput = $inputsInNextRow.eq(currentIndex);
                                }
                            } else {
                                // Submit form on Enter at the last row
                                $('#budget-form').submit();
                            }
                            break;
                    }

                    if ($nextInput && $nextInput.length) {
                        $nextInput.focus().select();
                    }
                });

                // Select input content on focus
                $(document).on('focus', '.excel-input:not([readonly])', function() {
                    $(this).select();
                });

                // Initialize totals for all rows on page load
                console.log('[init] Initializing totals for all projects');
                $('input[name^="project_id["]').each(function() {
                    const name = $(this).attr('name');
                    const match = name.match(/\[(\d+)\]/);
                    const projectId = match ? match[1] : null;
                    if (projectId) {
                        console.log(`[init] Initializing total for project ${projectId}`);
                        updateTotalBudget(projectId);
                    } else {
                        console.error('[init] Project ID not found in project_id input name:', name);
                    }
                });

                // Close error message
                $('#close-error').on('click', () => {
                    console.log('[close-error] Closing error message');
                    $('#error-message').addClass('hidden');
                    $('#error-text').text('');
                });

                // Dropdown logic for fiscal_year_id
                $(".js-single-select").each(function() {
                    const $container = $(this);
                    const componentId = $container.attr("id");
                    const dataName = $container.data("name");
                    let currentOptions = $container.data("options") || [];

                    // Handle JSON-encoded data-selected
                    let currentSelectedValue = $container.data("selected");
                    console.log(`[dropdown] Raw data-selected for #${componentId}:`, currentSelectedValue);
                    try {
                        if (typeof currentSelectedValue === "string") {
                            currentSelectedValue = JSON.parse(currentSelectedValue);
                        }
                    } catch (e) {
                        console.error(
                            `[dropdown] Error parsing data-selected for #${componentId}: ${e.message}`);
                        currentSelectedValue = "";
                    }

                    // Fallback to first selected option if data-selected is empty
                    if (!currentSelectedValue && currentOptions.length > 0) {
                        const defaultSelected = currentOptions.find(opt => opt.selected === true);
                        currentSelectedValue = defaultSelected ? defaultSelected.value : "";
                        console.log(
                            `[dropdown] Fallback to default selected value for #${componentId}: ${currentSelectedValue}`
                        );
                    }

                    console.log(
                        `[dropdown] Initializing for #${componentId}, selected: ${currentSelectedValue}`);

                    const $optionsContainer = $container.find(".js-options-container");
                    const $selectedLabel = $container.find(".js-selected-label");
                    const $hiddenInput = $container.find("input.js-hidden-input");
                    const $dropdown = $container.find(".js-dropdown");
                    const $searchInput = $container.find(".js-search-input");

                    function renderOptions(searchTerm = "") {
                        console.log(`[dropdown] Rendering options for #${componentId}, search: ${searchTerm}`);
                        $optionsContainer.empty();
                        if (!currentOptions || currentOptions.length === 0) {
                            console.warn(`[dropdown] No options for #${componentId}`);
                            $container.find(".js-no-options").removeClass("hidden");
                            $selectedLabel.text($container.data("placeholder") ||
                                "{{ trans('global.pleaseSelect') }}");
                            $hiddenInput.val("");
                            currentSelectedValue = "";
                            $container.data("selected", "");
                            $container.attr("data-selected", "");
                            return;
                        }
                        $container.find(".js-no-options").addClass("hidden");
                        const filteredOptions = searchTerm ?
                            currentOptions.filter((option) => option.label.toLowerCase().includes(searchTerm
                                .toLowerCase())) :
                            currentOptions;
                        $.each(filteredOptions, function(index, option) {
                            const isSelected = String(option.value) === String(currentSelectedValue);
                            const $option = $(`
                                <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 ${isSelected ? 'bg-gray-100 dark:bg-gray-600' : ''}" data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `);
                            $optionsContainer.append($option);
                        });
                        const selectedOption = currentOptions.find(
                            (opt) => String(opt.value) === String(currentSelectedValue)
                        );
                        $selectedLabel.text(
                            selectedOption ? selectedOption.label : $container.data("placeholder") ||
                            "{{ trans('global.pleaseSelect') }}"
                        );
                        $hiddenInput.val(selectedOption ? currentSelectedValue : "");
                        console.log(`[dropdown] Selected option for #${componentId}:`, selectedOption);
                    }

                    function updateHiddenInput() {
                        $hiddenInput.val(currentSelectedValue || "");
                        $hiddenInput.trigger("change");
                        console.log(`[dropdown] Updated hidden input for ${dataName}: ${currentSelectedValue}`);
                    }

                    $container.off("options-updated").on("options-updated", function(event, data) {
                        currentOptions = data?.options || $container.data("options") || [];
                        currentSelectedValue = data?.selected && currentOptions.some((opt) => String(opt
                                .value) === String(data.selected)) ?
                            String(data.selected) :
                            currentSelectedValue;
                        console.log(
                            `[dropdown] Options updated for #${componentId}, selected: ${currentSelectedValue}`
                        );
                        renderOptions();
                        updateHiddenInput();
                    });

                    $optionsContainer.off("click", ".js-option").on("click", ".js-option", function(e) {
                        e.stopPropagation();
                        const $option = $(this);
                        currentSelectedValue = $option.data("value");
                        $container.data("selected", currentSelectedValue);
                        $container.attr("data-selected", JSON.stringify(currentSelectedValue));
                        $selectedLabel.text($option.text().trim());
                        $dropdown.addClass("hidden");
                        updateHiddenInput();
                        console.log(
                            `[dropdown] Option selected for #${componentId}: ${currentSelectedValue}`
                        );
                    });

                    $searchInput.off("input").on("input", function() {
                        renderOptions($(this).val());
                    });

                    $container.find(".js-toggle-dropdown").off("click").on("click", function(e) {
                        e.stopPropagation();
                        $dropdown.toggleClass("hidden");
                        console.log(
                            `[dropdown] Dropdown toggled for #${componentId}: ${!$dropdown.hasClass("hidden")}`
                        );
                        if (!$dropdown.hasClass("hidden")) {
                            $searchInput.focus();
                        }
                    });

                    $(document).off("click.dropdown-" + componentId).on("click.dropdown-" + componentId,
                        function(e) {
                            if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                                $dropdown.addClass("hidden");
                                console.log(`[dropdown] Dropdown closed for #${componentId}`);
                            }
                        });

                    // Initialize dropdown
                    renderOptions();
                    console.log(`[dropdown] Initialized for #${componentId}, options:`, currentOptions);
                    console.log(`[dropdown] Selected value for #${componentId}: ${currentSelectedValue}`);
                });

                console.log('[script] Budget calculation, dropdown, and Excel-like navigation script loaded');
            });
        </script>
    @endpush
</x-layouts.app>
