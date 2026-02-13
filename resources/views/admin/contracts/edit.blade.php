@php
    $csrfToken = csrf_token();
    $user = auth()->user();
    $roles = $user ? $user->roles->pluck('id')->toArray() : [];

    $highestRoleType = 'guest';
    if ($user) {
        if (in_array(\App\Models\Role::SUPERADMIN, $roles) || in_array(\App\Models\Role::ADMIN, $roles)) {
            $highestRoleType = 'superadmin';
        } elseif (in_array(\App\Models\Role::PROJECT_USER, $roles)) {
            $highestRoleType = 'project_user';
        } elseif (in_array(\App\Models\Role::DIRECTORATE_USER, $roles)) {
            $highestRoleType = 'directorate_user';
        } else {
            $highestRoleType = 'standard';
        }
    }
@endphp

<x-layouts.app>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.contract.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.contract.title_singular') }}
                @if ($contract->project_id)
                    for - <span
                        class="font-semibold text-blue-600 dark:text-blue-400">{{ $contract->project->title ?? 'Unknown' }}</span>
                @endif
            </p>
        </div>

        @can('contract_access')
            <a href="{{ route('admin.contract.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
                {{ trans('global.back_to_list') }}
            </a>
        @endcan
    </div>

    @if ($contract->project_id)
        <div
            class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 uppercase tracking-wide">Project
                    Context</h3>
                <div class="mt-2 flex items-center gap-2">
                    <span
                        class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ $contract->project->title ?? 'Unknown' }}
                    </span>
                    <span class="text-gray-400">/</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $contract->directorate->title ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-end">
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase">Current Budget Status</span>
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                    Project: {{ $contract->project->title ?? 'N/A' }}
                </span>
            </div>
        </div>
    @endif

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <form class="w-full" action="{{ route('admin.contract.update', $contract) }}" method="POST" id="contract-form">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-100 text-red-700 border border-red-500 rounded-lg dark:bg-red-900 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="error-message"
                class="mb-6 hidden bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Left Column --}}
                <div
                    class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 h-full">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                        {{ trans('global.contract.title_singular') }} {{ trans('global.information') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-6">

                        <!-- Project Selection Section -->
                        <div class="mb-4">
                            @if ($highestRoleType === 'superadmin')
                                {{-- CASE: Superadmin - Full Dropdowns --}}

                                <label for="directorate_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Directorate
                                </label>
                                <select id="directorate_id" name="directorate_id"
                                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a Directorate</option>
                                    @foreach ($directorates as $directorate)
                                        <option value="{{ $directorate->id }}"
                                            {{ old('directorate_id', $contract->directorate_id ?? '') == $directorate->id ? 'selected' : '' }}>
                                            {{ $directorate->title }}
                                        </option>
                                    @endforeach
                                </select>

                                <div id="budget-info-container-directorate" class="mt-2 hidden"></div>
                            @else
                                {{-- CASE: Restricted User - Hidden/Readonly or Single Project Context --}}

                                <div
                                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                                    <label class="block text-sm font-bold text-blue-800 dark:text-blue-300 mb-1">
                                        Creating contract for:
                                    </label>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            {{-- Project Icon --}}
                                            <div
                                                class="bg-blue-100 dark:bg-blue-800 p-2 rounded-full text-blue-600 dark:text-blue-300">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="block text-lg font-semibold text-gray-800 dark:text-white">
                                                    {{ $contract->project->title ?? 'Unknown Project' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Hidden Inputs --}}
                                <input type="hidden" name="directorate_id"
                                    value="{{ old('directorate_id', $contract->directorate_id ?? '') }}">
                                <input type="hidden" name="project_id"
                                    value="{{ old('project_id', $contract->project_id ?? '') }}">
                            @endif
                        </div>

                        <div class="col-span-full">
                            <x-forms.input label="{{ trans('global.contract.fields.title') }}" name="title"
                                type="text" :value="old('title', $contract->title)" placeholder="Enter contract name" :error="$errors->first('title')"
                                required />
                        </div>

                        <div class="col-span-full">
                            <x-forms.text-area label="{{ trans('global.contract.fields.description') }}"
                                name="description" :value="old('description', $contract->description)" placeholder="Enter contract description"
                                :error="$errors->first('description')" rows="4" />
                        </div>

                        <div>
                            <x-forms.input label="{{ trans('global.contract.fields.contractor') }}" name="contractor"
                                type="text" :value="old('contractor', $contract->contractor)" placeholder="Enter contractor name"
                                :error="$errors->first('contractor')" />
                        </div>

                        <div>
                            <x-forms.input label="{{ trans('global.contract.fields.contract_amount') }}"
                                name="contract_amount" type="number" step="0.01" :value="old('contract_amount', $contract->contract_amount)"
                                placeholder="0.00" :error="$errors->first('contract_amount')" id="contract-amount" required />
                        </div>

                        <div class="col-span-full">
                            <x-forms.input label="{{ trans('global.contract.fields.contract_variation_amount') }}"
                                name="contract_variation_amount" type="number" step="0.01" :value="old('contract_variation_amount', $contract->contract_variation_amount ?? '')"
                                placeholder="0.00" :error="$errors->first('contract_variation_amount')" />
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-6">
                    {{-- Status & Priority --}}
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
                                        ->all()" :selected="old('status_id', $contract->status_id)"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('status_id')"
                                    class="js-single-select" />
                            </div>

                            <div>
                                <x-forms.select label="{{ trans('global.contract.fields.priority_id') }}"
                                    name="priority_id" id="priority_id" :options="collect($priorities)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('priority_id', $contract->priority_id)"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('priority_id')"
                                    class="js-single-select" />
                            </div>
                        </div>
                    </div>

                    {{-- Dates & Progress --}}
                    <div
                        class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.contract.headers.date_progress') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full md:col-span-2">
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
                                    )" :error="$errors->first('agreement_effective_date')" required />
                            </div>

                            <div>
                                <x-forms.date-input
                                    label="{{ trans('global.contract.fields.agreement_completion_date') }}"
                                    name="agreement_completion_date" :value="old(
                                        'agreement_completion_date',
                                        $contract->agreement_completion_date
                                            ? $contract->agreement_completion_date->format('Y-m-d')
                                            : '',
                                    )" :error="$errors->first('agreement_completion_date')" required />
                            </div>

                            <div class="col-span-full md:col-span-1">
                                <x-forms.input label="{{ trans('global.contract.fields.initial_contract_period') }}"
                                    name="initial_contract_period" type="number" :value="old('initial_contract_period', $contract->initial_contract_period)" placeholder="0"
                                    :error="$errors->first('initial_contract_period')" readonly
                                    class="bg-gray-100 dark:bg-gray-800 cursor-not-allowed" />
                                <p class="text-xs text-gray-500 mt-1">Calculated automatically from dates.</p>
                            </div>

                            <div class="col-span-full md:col-span-1">
                                <x-forms.input label="{{ trans('global.contract.fields.progress') }} (%)"
                                    name="progress" type="number" step="0.01" min="0" max="100"
                                    :value="old('progress', $contract->progress)" placeholder="0.00" :error="$errors->first('progress')" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="{{ request()->get('return_url') ?? route('admin.contract.index') }}"
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none">
                    Cancel
                </a>
                <x-buttons.primary type="submit" id="submit-button">
                    {{ trans('global.save') }}
                </x-buttons.primary>
            </div>
        </form>
    </div>

    @push('scripts')
        @if ($highestRoleType === 'superadmin')
            <script>
                (function waitForJQuery() {
                    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.jquery !== 'undefined') {
                        initializeScript(window.jQuery);
                    } else {
                        setTimeout(waitForJQuery, 50);
                    }
                })();

                function initializeScript($) {
                    $(document).ready(function() {
                        const errorMessage = $('#error-message');
                        const errorText = $('#error-text');
                        const submitButton = $('#submit-button');
                        const contractForm = $('#contract-form');
                        const effectiveDateInput = $('input[name="agreement_effective_date"]');
                        const completionDateInput = $('input[name="agreement_completion_date"]');
                        const initialPeriodInput = $('input[name="initial_contract_period"]');

                        // Project Inputs
                        const directorateInput = $('#directorate_id');
                        const directorateContainer = $('#budget-info-container-directorate');

                        // 1. Calculate Initial Contract Period
                        function calculateInitialPeriod() {
                            const effectiveDate = effectiveDateInput.val();
                            const completionDate = completionDateInput.val();

                            if (effectiveDate && completionDate) {
                                const effective = new Date(effectiveDate);
                                const completion = new Date(completionDate);

                                if (!isNaN(effective.getTime()) && !isNaN(completion.getTime())) {
                                    const diffTime = completion - effective;
                                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                                    if (diffDays >= 0) {
                                        initialPeriodInput.val(diffDays);
                                        errorMessage.addClass('hidden');
                                    } else {
                                        initialPeriodInput.val('');
                                        errorMessage.removeClass('hidden');
                                        errorText.text('Completion date must be after effective date.');
                                    }
                                } else {
                                    initialPeriodInput.val('');
                                }
                            } else {
                                initialPeriodInput.val('');
                            }
                        }

                        effectiveDateInput.on('change', calculateInitialPeriod);
                        completionDateInput.on('change', calculateInitialPeriod);
                        calculateInitialPeriod();

                        // 2. Handle Form Submission
                        contractForm.on('submit', function(e) {
                            if (!effectiveDateInput.val() || !completionDateInput.val()) {
                                e.preventDefault();
                                errorMessage.removeClass('hidden');
                                errorText.text('Please ensure both effective and completion dates are filled.');
                                return;
                            }
                            submitButton.prop('disabled', true).text('Saving...');
                        });

                        $('#close-error').on('click', function() {
                            errorMessage.addClass('hidden');
                            errorText.text('');
                        });
                    });
                }
            </script>
        @else
            <script>
                (function waitForJQuery() {
                    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.jquery !== 'undefined') {
                        initializeScript(window.jQuery);
                    } else {
                        setTimeout(waitForJQuery, 50);
                    }
                })();

                function initializeScript($) {
                    $(document).ready(function() {
                        const errorMessage = $('#error-message');
                        const errorText = $('#error-text');
                        const submitButton = $('#submit-button');
                        const contractForm = $('#contract-form');
                        const effectiveDateInput = $('input[name="agreement_effective_date"]');
                        const completionDateInput = $('input[name="agreement_completion_date"]');
                        const initialPeriodInput = $('input[name="initial_contract_period"]');

                        // 1. Calculate Initial Contract Period
                        function calculateInitialPeriod() {
                            const effectiveDate = effectiveDateInput.val();
                            const completionDate = completionDateInput.val();

                            if (effectiveDate && completionDate) {
                                const effective = new Date(effectiveDate);
                                const completion = new Date(completionDate);

                                if (!isNaN(effective.getTime()) && !isNaN(completion.getTime())) {
                                    const diffTime = completion - effective;
                                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                                    if (diffDays >= 0) {
                                        initialPeriodInput.val(diffDays);
                                        errorMessage.addClass('hidden');
                                    } else {
                                        initialPeriodInput.val('');
                                        errorMessage.removeClass('hidden');
                                        errorText.text('Completion date must be after effective date.');
                                    }
                                } else {
                                    initialPeriodInput.val('');
                                }
                            } else {
                                initialPeriodInput.val('');
                            }
                        }

                        effectiveDateInput.on('change', calculateInitialPeriod);
                        completionDateInput.on('change', calculateInitialPeriod);
                        calculateInitialPeriod();

                        // 2. Handle Form Submission
                        contractForm.on('submit', function(e) {
                            if (!effectiveDateInput.val() || !completionDateInput.val()) {
                                e.preventDefault();
                                errorMessage.removeClass('hidden');
                                errorText.text('Please ensure both effective and completion dates are filled.');
                                return;
                            }
                            submitButton.prop('disabled', true).text('Saving...');
                        });

                        $('#close-error').on('click', function() {
                            errorMessage.addClass('hidden');
                            errorText.text('');
                        });
                    });
                }
            </script>
        @endif
    @endpush
</x-layouts.app>
