<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Project Budget Allocation') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('Add a budget for a project') }}</p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
                <form class="max-w-3xl mx-auto" action="" method="POST">
                    @csrf

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
                        class="col-span-full mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ __('Budget Information') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <x-forms.select label="Project" name="project_id" id="project_id" :options="collect($projects)
                                    ->map(
                                        fn($project) => ['value' => (string) $project->id, 'label' => $project->title],
                                    )
                                    ->values()
                                    ->all()"
                                    :selected="old('project_id', session('project_id'))" placeholder="Select project" :error="$errors->first('project_id')"
                                    class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="Fiscal Year" name="fiscal_year_id" id="fiscal_year_id"
                                    :options="collect($fiscalYears)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('fiscal_year_id')" placeholder="Select fiscal year"
                                    :error="$errors->first('fiscal_year_id')" class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Internal Budget" name="internal_budget" type="number"
                                    step="0.01" min="0" :value="old('internal_budget', 0)" :error="$errors->first('internal_budget')"
                                    class="budget-component" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Foreign Loan Budget" name="foreign_loan_budget" type="number"
                                    step="0.01" min="0" :value="old('foreign_loan_budget', 0)" :error="$errors->first('foreign_loan_budget')"
                                    class="budget-component" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Foreign Subsidy Budget" name="foreign_subsidy_budget"
                                    type="number" step="0.01" min="0" :value="old('foreign_subsidy_budget', 0)" :error="$errors->first('foreign_subsidy_budget')"
                                    class="budget-component" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="Total Budget" name="total_budget" type="number" step="0.01"
                                    min="0" :value="old('total_budget', 0)" :error="$errors->first('total_budget')" readonly="true"
                                    class="bg-gray-100 dark:bg-gray-600 cursor-not-allowed text-gray-700 dark:text-gray-300" />
                            </div>
                        </div>
                    </div>

                    <div class="col-span-full mt-8 flex">
                        <x-buttons.primary>{{ __('Save Budget') }}</x-buttons.primary>
                        <a href="{{ route('admin.project.index') }}"
                            class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            {{ __('Back to Projects') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
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

        /* Ensure total_budget text is visible */
        input[readonly] {
            opacity: 1 !important;
            color: inherit !important;
        }
    </style>

    <script>
        function waitForJQuery(callback, retries = 50) {
            if (
                typeof jQuery !== "undefined" &&
                jQuery.fn.jquery &&
                document.readyState === "complete"
            ) {
                console.log('jQuery loaded and DOM ready:', jQuery.fn.jquery);
                callback();
            } else if (retries > 0) {
                console.log('Waiting for jQuery, retries left:', retries);
                setTimeout(function() {
                    waitForJQuery(callback, retries - 1);
                }, 100);
            } else {
                console.error('jQuery failed to load after retries');
            }
        }

        waitForJQuery(function() {
            const $ = jQuery.noConflict();

            // Budget calculation logic
            function updateTotalBudget() {
                const $internal = $('input[name="internal_budget"]');
                const $loan = $('input[name="foreign_loan_budget"]');
                const $subsidy = $('input[name="foreign_subsidy_budget"]');
                const $total = $('input[name="total_budget"]');
                const $errorMessage = $('#error-message');
                const $errorText = $('#error-text');

                // Debug: Log input elements
                console.log('Internal Budget Input:', {
                    found: $internal.length,
                    value: $internal.val(),
                    name: $internal.attr('name'),
                    class: $internal.attr('class'),
                    hasBudgetComponent: $internal.hasClass('budget-component'),
                    readonly: $internal.prop('readonly')
                });
                console.log('Foreign Loan Budget Input:', {
                    found: $loan.length,
                    value: $loan.val(),
                    name: $loan.attr('name'),
                    class: $loan.attr('class'),
                    hasBudgetComponent: $loan.hasClass('budget-component'),
                    readonly: $loan.prop('readonly')
                });
                console.log('Foreign Subsidy Budget Input:', {
                    found: $subsidy.length,
                    value: $subsidy.val(),
                    name: $subsidy.attr('name'),
                    class: $subsidy.attr('class'),
                    hasBudgetComponent: $subsidy.hasClass('budget-component'),
                    readonly: $subsidy.prop('readonly')
                });
                console.log('Total Budget Input:', {
                    found: $total.length,
                    value: $total.val(),
                    name: $total.attr('name'),
                    class: $total.attr('class'),
                    hasBudgetComponent: $total.hasClass('budget-component'),
                    readonly: $total.prop('readonly')
                });

                if (!$internal.length || !$loan.length || !$subsidy.length || !$total.length) {
                    $errorText.text('Error: One or more budget inputs not found in the DOM.');
                    $errorMessage.removeClass('hidden');
                    console.error('Budget inputs missing:', {
                        internal: $internal.length,
                        loan: $loan.length,
                        subsidy: $subsidy.length,
                        total: $total.length
                    });
                    return;
                }

                const getValidNumber = (value) => {
                    const num = parseFloat(value);
                    return isNaN(num) || num < 0 ? 0 : num;
                };

                const internal = getValidNumber($internal.val());
                const loan = getValidNumber($loan.val());
                const subsidy = getValidNumber($subsidy.val());

                const total = (internal + loan + subsidy).toFixed(2);
                console.log('Calculated Total:', total);
                $total.val(total).trigger('change').trigger('input');
                $total[0].dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                // Support for Livewire
                if (window.Livewire) {
                    window.Livewire.dispatch('input', {
                        name: 'total_budget',
                        value: total
                    });
                }
                $errorMessage.addClass('hidden');
            }

            // Debounce to prevent excessive updates
            function debounce(func, wait) {
                let timeout;
                return function() {
                    console.log('Debounce triggered for:', {
                        name: this.name,
                        value: this.value
                    });
                    clearTimeout(timeout);
                    timeout = setTimeout(func, wait);
                };
            }

            // Bind events by class with delegation
            $(document).on('input change keyup', '.budget-component', function(e) {
                console.log('Class-based event fired:', {
                    eventType: e.type,
                    name: $(this).attr('name'),
                    value: $(this).val(),
                    hasBudgetComponent: $(this).hasClass('budget-component')
                });
                debounce(updateTotalBudget, 300).call(this);
            });

            // Bind events by name as fallback
            const inputNames = ['internal_budget', 'foreign_loan_budget', 'foreign_subsidy_budget'];
            inputNames.forEach(function(name) {
                const $input = $(`input[name="${name}"]`);
                if ($input.length) {
                    console.log('Binding events to input:', name);
                    $input.on('input change keyup', function(e) {
                        console.log('Name-based event fired:', {
                            eventType: e.type,
                            name: $(this).attr('name'),
                            value: $(this).val(),
                            hasBudgetComponent: $(this).hasClass('budget-component')
                        });
                        debounce(updateTotalBudget, 300).call(this);
                    });
                } else {
                    console.error('Input not found for name:', name);
                }
            });

            // Debug: Log all budget-component inputs
            const $budgetComponents = $('input.budget-component');
            console.log('Found budget-component inputs:', {
                count: $budgetComponents.length,
                names: $budgetComponents.map(function() {
                    return $(this).attr('name');
                }).get()
            });

            // Initialize total budget on page load
            console.log('Initializing total budget on page load');
            updateTotalBudget();

            // Close error message
            $("#close-error").on("click", function() {
                $("#error-message").addClass("hidden");
                $("#error-text").text("");
            });

            // Existing select dropdown logic (unchanged)
            $(".js-single-select").each(function() {
                const $container = $(this);
                const componentId = $container.attr("id");
                const dataName = $container.data("name");
                let currentOptions = $container.data("options") || [];
                let currentSelectedValue = $container.data("selected") || "";
                const $optionsContainer = $container.find(".js-options-container");
                const $selectedLabel = $container.find(".js-selected-label");
                const $hiddenInput = $container.find("input.js-hidden-input");
                const $dropdown = $container.find(".js-dropdown");
                const $searchInput = $container.find(".js-search-input");

                function renderOptions(searchTerm = "") {
                    $optionsContainer.empty();
                    if (!currentOptions || currentOptions.length === 0) {
                        $container.find(".js-no-options").removeClass("hidden");
                        $selectedLabel.text($container.data("placeholder") || "Select an option");
                        $hiddenInput.val("");
                        currentSelectedValue = "";
                        $container.data("selected", "");
                        $container.attr("data-selected", "");
                        return;
                    }
                    $container.find(".js-no-options").addClass("hidden");
                    const filteredOptions = searchTerm ?
                        currentOptions.filter((option) =>
                            option.label.toLowerCase().includes(searchTerm.toLowerCase())
                        ) :
                        currentOptions;
                    $.each(filteredOptions, function(index, option) {
                        const $option = $(`
                            <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                ${option.label}
                            </div>
                        `);
                        $optionsContainer.append($option);
                    });
                    const selectedOption = currentOptions.find(
                        (opt) => String(opt.value) === String(currentSelectedValue),
                    );
                    $selectedLabel.text(
                        selectedOption ?
                        selectedOption.label :
                        $container.data("placeholder") || "Select an option",
                    );
                    $hiddenInput.val(selectedOption ? currentSelectedValue : "");
                    if (!selectedOption) {
                        currentSelectedValue = "";
                        $container.data("selected", "");
                        $container.attr("data-selected", "");
                    }
                }

                function updateHiddenInput() {
                    $hiddenInput.val(currentSelectedValue || "");
                    $hiddenInput.trigger("change");
                }

                $container
                    .off("options-updated")
                    .on("options-updated", function(event, data) {
                        currentOptions = data?.options || $container.data("options") || [];
                        currentSelectedValue =
                            data?.selected &&
                            currentOptions.some(
                                (opt) => String(opt.value) === String(data.selected),
                            ) ?
                            String(data.selected) :
                            "";
                        renderOptions();
                        updateHiddenInput();
                    });

                $optionsContainer
                    .off("click", ".js-option")
                    .on("click", ".js-option", function(e) {
                        e.stopPropagation();
                        const $option = $(this);
                        currentSelectedValue = $option.data("value");
                        $container.data("selected", currentSelectedValue);
                        $container.attr("data-selected", currentSelectedValue);
                        $selectedLabel.text($option.text().trim());
                        $dropdown.addClass("hidden");
                        updateHiddenInput();
                    });

                $searchInput.off("input").on("input", function() {
                    renderOptions($(this).val());
                });

                $container
                    .find(".js-toggle-dropdown")
                    .off("click")
                    .on("click", function(e) {
                        e.stopPropagation();
                        $dropdown.toggleClass("hidden");
                        if (!$dropdown.hasClass("hidden")) {
                            $searchInput.focus();
                        }
                    });

                $(document)
                    .off("click.dropdown-" + componentId)
                    .on("click.dropdown-" + componentId, function(e) {
                        if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                            $dropdown.addClass("hidden");
                        }
                    });

                renderOptions();
            });

            // Pre-select project if project_id is in URL or session
            const urlParams = new URLSearchParams(window.location.search);
            const projectIdFromUrl = urlParams.get('project_id');
            const projectIdFromSession = "{{ session('project_id') }}";
            if (projectIdFromUrl || projectIdFromSession) {
                const projectSelectContainer = $('.js-single-select[data-name="project_id"]');
                projectSelectContainer.data('selected', projectIdFromUrl || projectIdFromSession);
                projectSelectContainer.attr('data-selected', projectIdFromUrl || projectIdFromSession);
                projectSelectContainer.trigger('options-updated', {
                    options: projectSelectContainer.data('options'),
                    selected: projectIdFromUrl || projectIdFromSession,
                });
            }
        });
    </script>
</x-layouts.app>
