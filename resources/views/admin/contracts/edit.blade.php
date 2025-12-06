<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.contract.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.contract.title_singular') }}
            </p>
        </div>

        @can('contract_access')
            <a href="{{ route('admin.contract.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan

    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form class="w-full" action="{{ route('admin.contract.update', $contract) }}" method="POST" id="contract-form">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-400 text-white border border-red-500 rounded-lg dark:bg-red-900 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="error-message"
                class="mb-6 hidden bg-gray-100 border border-gray-400 text-gray-800 px-4 py-3 rounded-lg relative dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-gray-500 dark:text-gray-400" role="button"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div
                        class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 h-full">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.contract.title_singular') }} {{ trans('global.information') }}
                        </h3>

                        <div class="grid grid-cols-1 gap-6">
                            @if (Auth::user()->roles->pluck('id')->contains(\App\Models\Role::SUPERADMIN))
                                <div class="col-span-full">
                                    <x-forms.select label="{{ trans('global.contract.fields.directorate_id') }}"
                                        name="directorate_id" id="directorate_id" :options="collect($directorates)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()" :selected="old('directorate_id', $contract->directorate_id ?? '')"
                                        placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('directorate_id')"
                                        class="js-single-select" />
                                </div>
                            @else
                                <input type="hidden" name="directorate_id"
                                    value="{{ old('directorate_id', Auth::user()->directorate_id ?? ($contract->directorate_id ?? '')) }}">
                            @endif

                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.contract.fields.project_id') }}"
                                    name="project_id" id="project_id" :options="collect($projects)
                                        ->map(
                                            fn($project) => [
                                                'value' => (string) $project['id'],
                                                'label' => $project['title'],
                                                'total_budget' => $project['total_budget'],
                                                'remaining_budget' => $project['remaining_budget'],
                                            ],
                                        )
                                        ->values()
                                        ->all()" :selected="old('project_id', $contract->project_id ?? '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('project_id')"
                                    :class="Auth::user()->roles->pluck('id')->contains(\App\Models\Role::SUPERADMIN)
                                        ? 'js-single-select'
                                        : ''" />
                                <div id="project-budget"
                                    class="mt-2 text-sm text-gray-600 dark:text-gray-400 {{ $projects->isEmpty() ? 'hidden' : '' }}">
                                    {{ trans('global.contract.fields.available_budget') }} :
                                    <span id="budget-amount">
                                        {{ $projects->isNotEmpty() ? $projects->firstWhere('id', old('project_id', $contract->project_id ?? ''))['remaining_budget'] ?? 'N/A' : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.contract.fields.title') }}" name="title"
                                    type="text" :value="old('title', $contract->title ?? '')" placeholder="Enter contract name"
                                    :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.contract.fields.description') }}"
                                    name="description" :value="old('description', $contract->description ?? '')" placeholder="Enter contract description"
                                    :error="$errors->first('description')" rows="4" />
                            </div>

                            <div>
                                <x-forms.input label="{{ trans('global.contract.fields.contractor') }}"
                                    name="contractor" type="text" :value="old('contractor', $contract->contractor ?? '')"
                                    placeholder="Enter contractor name" :error="$errors->first('contractor')" />
                            </div>

                            <div>
                                <x-forms.input label="{{ trans('global.contract.fields.contract_amount') }}"
                                    name="contract_amount" type="number" step="0.01" :value="old('contract_amount', $contract->contract_amount ?? '')"
                                    placeholder="0.00" :error="$errors->first('contract_amount')" id="contract-amount" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.contract.fields.contract_variation_amount') }}"
                                    name="contract_variation_amount" type="number" step="0.01" :value="old(
                                        'contract_variation_amount',
                                        $contract->contract_variation_amount ?? '',
                                    )"
                                    placeholder="0.00" :error="$errors->first('contract_variation_amount')" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.contract.headers.status_priority') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.select label="{{ trans('global.contract.fields.status_id') }}"
                                    name="status_id" id="status_id" :options="collect($statuses)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('status_id', $contract->status_id ?? '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('status_id')"
                                    class="js-single-select" />
                            </div>

                            <div>
                                <x-forms.select label="{{ trans('global.contract.fields.priority_id') }}"
                                    name="priority_id" id="priority_id" :options="collect($priorities)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('priority_id', $contract->priority_id ?? '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('priority_id')"
                                    class="js-single-select" />
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.contract.headers.date_progress') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <x-forms.date-input
                                    label="{{ trans('global.contract.fields.contract_agreement_date') }}"
                                    name="contract_agreement_date" :value="old(
                                        'contract_agreement_date',
                                        $contract->contract_agreement_date
                                            ? $contract->contract_agreement_date->format('Y-m-d')
                                            : '',
                                    )" :error="$errors->first('contract_agreement_date')" />
                            </div>

                            <div>
                                <x-forms.date-input
                                    label="{{ trans('global.contract.fields.agreement_effective_date') }}"
                                    name="agreement_effective_date" :value="old(
                                        'agreement_effective_date',
                                        $contract->agreement_effective_date
                                            ? $contract->agreement_effective_date->format('Y-m-d')
                                            : '',
                                    )" :error="$errors->first('agreement_effective_date')" />
                            </div>

                            <div>
                                <x-forms.date-input
                                    label="{{ trans('global.contract.fields.agreement_completion_date') }}"
                                    name="agreement_completion_date" :value="old(
                                        'agreement_completion_date',
                                        $contract->agreement_completion_date
                                            ? $contract->agreement_completion_date->format('Y-m-d')
                                            : '',
                                    )" :error="$errors->first('agreement_completion_date')" />
                            </div>

                            <div class="col-span-full md:col-span-1">
                                <x-forms.input label="{{ trans('global.contract.fields.initial_contract_period') }}"
                                    name="initial_contract_period" type="number" :value="old('initial_contract_period', $contract->initial_contract_period ?? '')" placeholder="0"
                                    :error="$errors->first('initial_contract_period')" />
                            </div>

                            <div class="col-span-full md:col-span-1">
                                <x-forms.input label="{{ trans('global.contract.fields.progress') }} (%)"
                                    name="progress" type="number" step="0.01" min="0" max="100"
                                    :value="old('progress', $contract->progress ?? '')" placeholder="0.00" :error="$errors->first('progress')" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <x-buttons.primary>
                    {{ trans('global.save') }}
                </x-buttons.primary>
                <a href="{{ route('admin.contract.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 ml-2">
                    {{ trans('global.cancel') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        @if (Auth::user()->roles->pluck('id')->contains(\App\Models\Role::SUPERADMIN))
            <script>
                (function waitForJQuery() {
                    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.jquery !== 'undefined') {
                        console.log('jQuery version:', window.jQuery.fn.jquery);
                        initializeScript(window.jQuery);
                    } else {
                        console.log('Waiting for jQuery...');
                        setTimeout(waitForJQuery, 50);
                    }
                })();

                function initializeScript($) {
                    $(document).ready(function() {
                        // Target js-single-select divs by data-name
                        const directorateContainer = $('.js-single-select[data-name="directorate_id"]');
                        const projectContainer = $('.js-single-select[data-name="project_id"]');
                        // Target hidden inputs for value changes
                        const directorateInput = directorateContainer.find('input[type="hidden"][name="directorate_id"]');
                        const projectInput = projectContainer.find('input[type="hidden"][name="project_id"]');
                        const errorMessage = $('#error-message');
                        const errorText = $('#error-text');
                        const contractAmountInput = $('#contract-amount');
                        const budgetDisplay = $('#project-budget');
                        const budgetAmount = $('#budget-amount');
                        const submitButton = $('#submit-button');
                        let remainingBudget = 0;

                        console.log('Superadmin script initialized (edit)');
                        console.log('Directorate container:', directorateContainer.length ? 'Found' : 'Not found',
                            directorateContainer);
                        console.log('Project container:', projectContainer.length ? 'Found' : 'Not found',
                            projectContainer);
                        console.log('Directorate container HTML:', directorateContainer.length ? directorateContainer[0]
                            .outerHTML : 'N/A');
                        console.log('Project container HTML:', projectContainer.length ? projectContainer[0].outerHTML :
                            'N/A');
                        console.log('Directorate input:', directorateInput.length ? 'Found' : 'Not found',
                            directorateInput);
                        console.log('Project input:', projectInput.length ? 'Found' : 'Not found', projectInput);

                        directorateInput.on('change', function() {
                            const directorateId = $(this).val();
                            console.log('Directorate changed:', directorateId);

                            if (directorateId) {
                                // Clear project options
                                projectContainer.trigger('options-updated', {
                                    options: [],
                                    selected: null
                                });
                                projectContainer.find('.js-selected-label').text(projectContainer.attr(
                                    'data-placeholder'));
                                budgetDisplay.addClass('hidden');
                                budgetAmount.text('N/A');
                                remainingBudget = 0;
                                submitButton.prop('disabled', true);

                                $.ajax({
                                    url: '/admin/contracts/projects/' + encodeURIComponent(directorateId),
                                    type: 'GET',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function(data) {
                                        console.log('AJAX success, projects received:', data);
                                        const options = Array.isArray(data) ? data.map(project => ({
                                            value: String(project.value),
                                            label: String(project.label ||
                                                'Untitled Project'),
                                            total_budget: project.total_budget || '0.00',
                                            remaining_budget: project.remaining_budget ||
                                                '0.00'
                                        })).filter(opt => opt.value && opt.label && opt.label !==
                                            'undefined') : [];
                                        console.log('Processed options:', options);
                                        if (options.length === 0) {
                                            console.warn('No projects returned for directorate:',
                                                directorateId);
                                            errorMessage.removeClass('hidden');
                                            errorText.text(
                                                'No projects available for this directorate.');
                                            submitButton.prop('disabled', true);
                                        } else {
                                            // Update project dropdown options
                                            const selectedProjectId = @json(old('project_id', $contract->project_id ?? ''));
                                            projectContainer.trigger('options-updated', {
                                                options: options,
                                                selected: options.some(opt => String(opt
                                                        .value) === String(
                                                        selectedProjectId)) ?
                                                    String(selectedProjectId) : null
                                            });
                                            errorMessage.addClass('hidden');
                                            errorText.text('');
                                        }
                                    },
                                    error: function(xhr) {
                                        console.error('AJAX error:', xhr.status, xhr.statusText, xhr
                                            .responseJSON);
                                        projectContainer.trigger('options-updated', {
                                            options: [],
                                            selected: null
                                        });
                                        errorMessage.removeClass('hidden');
                                        errorText.text('Failed to load projects: ' + (xhr.responseJSON
                                            ?.message || 'AJAX error'));
                                        budgetDisplay.addClass('hidden');
                                        budgetAmount.text('N/A');
                                        remainingBudget = 0;
                                        submitButton.prop('disabled', true);
                                    }
                                });
                            } else {
                                console.log('Directorate cleared');
                                projectContainer.trigger('options-updated', {
                                    options: [],
                                    selected: null
                                });
                                budgetDisplay.addClass('hidden');
                                budgetAmount.text('N/A');
                                remainingBudget = 0;
                                submitButton.prop('disabled', true);
                                errorMessage.addClass('hidden');
                                errorText.text('');
                            }
                        });

                        projectInput.on('change', function() {
                            const projectId = $(this).val();
                            console.log('Project changed:', projectId);

                            if (projectId) {
                                const projectOptions = JSON.parse(projectContainer.attr('data-options') || '[]');
                                const selectedOption = projectOptions.find(opt => String(opt.value) === String(
                                    projectId));
                                const remainingBudgetValue = selectedOption ? selectedOption.remaining_budget :
                                    null;
                                console.log('Selected option budget:', remainingBudgetValue);
                                if (remainingBudgetValue !== undefined && remainingBudgetValue !== null) {
                                    remainingBudget = parseFloat(String(remainingBudgetValue).replace(/,/g, '')) ||
                                        0;
                                    budgetDisplay.removeClass('hidden');
                                    budgetAmount.text(remainingBudgetValue);
                                    validateContractAmount();
                                } else {
                                    budgetDisplay.addClass('hidden');
                                    budgetAmount.text('N/A');
                                    remainingBudget = 0;
                                    submitButton.prop('disabled', true);
                                    errorMessage.addClass('hidden');
                                    errorText.text('');
                                }
                            } else {
                                budgetDisplay.addClass('hidden');
                                budgetAmount.text('N/A');
                                remainingBudget = 0;
                                submitButton.prop('disabled', true);
                                errorMessage.addClass('hidden');
                                errorText.text('');
                            }
                        });

                        function validateContractAmount() {
                            const amount = parseFloat(contractAmountInput.val()) || 0;
                            console.log('Validating contract amount:', amount, 'Remaining budget:', remainingBudget);
                            if (amount > remainingBudget && remainingBudget !== 0) {
                                errorMessage.removeClass('hidden');
                                errorText.text(
                                    `Contract amount (${amount.toFixed(2)}) exceeds available budget (${remainingBudget.toFixed(2)}).`
                                );
                                submitButton.prop('disabled', true);
                            } else {
                                errorMessage.addClass('hidden');
                                errorText.text('');
                                submitButton.prop('disabled', remainingBudget === 0);
                            }
                        }

                        contractAmountInput.on('input', validateContractAmount);

                        $('#close-error').on('click', function() {
                            console.log('Close error clicked');
                            errorMessage.addClass('hidden');
                            errorText.text('');
                            submitButton.prop('disabled', remainingBudget === 0 || parseFloat(contractAmountInput
                                .val()) > remainingBudget);
                        });

                        // Trigger initial change if directorate is selected
                        if (directorateContainer.length && directorateInput.val()) {
                            console.log('Triggering initial directorate change:', directorateInput.val());
                            directorateInput.trigger('change');
                        } else {
                            console.warn('No initial directorate selected or container not found');
                            // Trigger project change to initialize budget display
                            if (projectInput.val()) {
                                console.log('Triggering initial project change:', projectInput.val());
                                projectInput.trigger('change');
                            }
                        }
                    });
                }
            </script>
        @else
            <script>
                (function waitForJQuery() {
                    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.jquery !== 'undefined') {
                        console.log('jQuery version:', window.jQuery.fn.jquery);
                        initializeScript(window.jQuery);
                    } else {
                        console.log('Waiting for jQuery...');
                        setTimeout(waitForJQuery, 50);
                    }
                })();

                function initializeScript($) {
                    $(document).ready(function() {
                        const projectContainer = $('.js-single-select[data-name="project_id"]');
                        const projectInput = projectContainer.find('input[type="hidden"][name="project_id"]');
                        const errorMessage = $('#error-message');
                        const errorText = $('#error-text');
                        const contractAmountInput = $('#contract-amount');
                        const budgetDisplay = $('#project-budget');
                        const budgetAmount = $('#budget-amount');
                        const submitButton = $('#submit-button');
                        let remainingBudget = 0;

                        console.log('Non-superadmin script initialized (edit)');
                        console.log('Project container:', projectContainer.length ? 'Found' : 'Not found',
                            projectContainer);
                        console.log('Project container HTML:', projectContainer.length ? projectContainer[0].outerHTML :
                            'N/A');
                        console.log('Project input:', projectInput.length ? 'Found' : 'Not found', projectInput);

                        projectInput.on('change', function() {
                            const projectId = $(this).val();
                            console.log('Project changed:', projectId);

                            if (projectId) {
                                const projectOptions = JSON.parse(projectContainer.attr('data-options') || '[]');
                                const selectedOption = projectOptions.find(opt => String(opt.value) === String(
                                    projectId));
                                const remainingBudgetValue = selectedOption ? selectedOption.remaining_budget :
                                    null;
                                console.log('Selected option budget:', remainingBudgetValue);
                                if (remainingBudgetValue !== undefined && remainingBudgetValue !== null) {
                                    remainingBudget = parseFloat(String(remainingBudgetValue).replace(/,/g, '')) ||
                                        0;
                                    budgetDisplay.removeClass('hidden');
                                    budgetAmount.text(remainingBudgetValue);
                                    validateContractAmount();
                                } else {
                                    budgetDisplay.addClass('hidden');
                                    budgetAmount.text('N/A');
                                    remainingBudget = 0;
                                    submitButton.prop('disabled', true);
                                    errorMessage.addClass('hidden');
                                    errorText.text('');
                                }
                            } else {
                                budgetDisplay.addClass('hidden');
                                budgetAmount.text('N/A');
                                remainingBudget = 0;
                                submitButton.prop('disabled', true);
                                errorMessage.addClass('hidden');
                                errorText.text('');
                            }
                        });

                        function validateContractAmount() {
                            const amount = parseFloat(contractAmountInput.val()) || 0;
                            console.log('Validating contract amount:', amount, 'Remaining budget:', remainingBudget);
                            if (amount > remainingBudget && remainingBudget !== 0) {
                                errorMessage.removeClass('hidden');
                                errorText.text(
                                    `Contract amount (${amount.toFixed(2)}) exceeds available budget (${remainingBudget.toFixed(2)}).`
                                );
                                submitButton.prop('disabled', true);
                            } else {
                                errorMessage.addClass('hidden');
                                errorText.text('');
                                submitButton.prop('disabled', remainingBudget === 0);
                            }
                        }

                        contractAmountInput.on('input', validateContractAmount);

                        $('#close-error').on('click', function() {
                            console.log('Close error clicked');
                            errorMessage.addClass('hidden');
                            errorText.text('');
                            submitButton.prop('disabled', remainingBudget === 0 || parseFloat(contractAmountInput
                                .val()) > remainingBudget);
                        });

                        const initialProjectId = @json(old('project_id', $contract->project_id ?? ''));
                        if (initialProjectId && projectContainer.length) {
                            console.log('Setting initial project:', initialProjectId);
                            projectInput.val(initialProjectId).trigger('change');
                        }
                    });
                }
            </script>
        @endif
    @endpush
</x-layouts.app>
