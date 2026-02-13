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

            <!-- Header Section: Filters -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                @if (in_array(App\Models\Role::DIRECTORATE_USER, Auth::user()->roles->pluck('id')->toArray()) &&
                        Auth::user()->directorate_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('global.directorate.title') }}
                        </label>
                        <p class="mt-1 text-gray-800 dark:text-gray-100 font-semibold">{{ $directorateTitle }}</p>
                    </div>
                @endif

                @if (
                    !in_array(App\Models\Role::DIRECTORATE_USER, Auth::user()->roles->pluck('id')->toArray()) ||
                        !Auth::user()->directorate_id)
                    <x-forms.select label="{{ trans('global.directorate.title') }} {{ trans('global.filter') }}"
                        name="directorate_id" id="directorate_id" :options="$directorates ?? []" :selected="old('directorate_id', request('directorate_id'))"
                        placeholder="{{ trans('All Direcotrates') ?? 'All Directorates' }}" :error="$errors->first('directorate_id')"
                        class="js-single-select" />
                @endif

                <x-forms.select label="{{ trans('global.budget.fields.fiscal_year_id') }}" name="fiscal_year_id"
                    id="fiscal_year_id" :options="$fiscalYears" :selected="collect($fiscalYears)->firstWhere('selected', true)['value'] ?? old('fiscal_year_id')"
                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                    required />
            </div>

            <!-- Excel Actions -->
            <div class="mb-6 flex flex-wrap items-center gap-4">
                {{-- <a href="#" id="download-template-link"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ trans('global.budget.fields.download_template') }}
                </a>
                <a href="{{ route('admin.budget.upload') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    {{ trans('global.budget.fields.upload_excel') }}
                </a> --}}

                @if (Auth::user()->hasRole(['Super_Admin', 'admin']))
                    <!-- Admin Only: Download Quarterly Template -->
                    <a href="#" id="download-quarter-template-link"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Download Quarterly Template (Admin)
                    </a>

                    <!-- Admin Only: Upload Quarterly Allocation -->
                    <a href="{{ route('admin.budgetQuaterAllocation.uploadIndex') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        Upload Quarterly Allocation (Admin)
                    </a>
                @endif
            </div>

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
                    <svg class="fill-current h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            <!-- Loading Indicator -->
            <div id="table-loading" class="hidden text-center py-12">
                <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="mt-4 text-gray-600 dark:text-gray-400">{{ __('Loading projects...') }}</p>
            </div>

            <!-- Projects Table Container -->
            <div id="projects-table-container" class="overflow-x-auto mb-8">
                @include('admin.budgets.partials.project-table', ['projects' => $projects])
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex justify-between items-center">
                <x-buttons.primary id="submit-button" type="submit">
                    {{ trans('global.save') }}
                </x-buttons.primary>
                <a href="{{ route('admin.budget.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
    </form>

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

        table {
            border-collapse: collapse;
            border: 1px solid #d1d5db;
            font-family: 'Calibri', 'Arial', sans-serif;
            font-size: 0.875rem;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #e5e7eb;
            font-weight: 600;
            text-transform: uppercase;
            color: #374151;
        }

        td {
            background: #ffffff;
            color: #374151;
        }

        .dark th {
            background: #4b5563;
            color: #e5e7eb;
        }

        .dark td {
            background: #1f2937;
            color: #e5e7eb;
        }

        td:hover .excel-input:not([readonly]) {
            background: #f0f9ff;
        }

        .dark td:hover .excel-input:not([readonly]) {
            background: #374151;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    @push('scripts')
        <script>
            (function waitForJQuery() {
                if (window.jQuery) {
                    console.log('[BUDGET] jQuery loaded, initializing budget script');
                    initializeBudgetScript();
                } else {
                    console.log('[BUDGET] Waiting for jQuery...');
                    setTimeout(waitForJQuery, 100);
                }

                function initializeBudgetScript() {
                    const $ = window.jQuery;

                    // === FIND HIDDEN INPUTS ===
                    const $directorateInput = $('input[name="directorate_id"][type="hidden"]');
                    const $fiscalYearInput = $('input[name="fiscal_year_id"][type="hidden"]');

                    console.log('[BUDGET] Directorate input found:', $directorateInput.length);
                    console.log('[BUDGET] Fiscal year input found:', $fiscalYearInput.length);
                    console.log('[BUDGET] Current fiscal year:', $fiscalYearInput.val());

                    if ($fiscalYearInput.length === 0) {
                        console.error('[BUDGET] Fiscal year input missing!');
                        return;
                    }

                    // === LOAD PROJECTS FUNCTION ===
                    function loadProjects() {
                        const directorateId = $directorateInput.val() || '';
                        const fiscalYearId = $fiscalYearInput.val() || '';

                        console.log('[BUDGET FILTER] Requesting projects:', {
                            directorateId,
                            fiscalYearId
                        });

                        if (!fiscalYearId) {
                            $('#projects-table-container').html(
                                '<p class="text-center py-12 text-gray-500 dark:text-gray-400">Please select a fiscal year first.</p>'
                            );
                            $('#table-loading').addClass('hidden');
                            return;
                        }

                        $('#table-loading').removeClass('hidden');
                        $('#projects-table-container').addClass('opacity-50 pointer-events-none');

                        $.ajax({
                            url: '{{ route('admin.budget.filter-projects') }}',
                            method: 'GET',
                            data: {
                                directorate_id: directorateId,
                                fiscal_year_id: fiscalYearId
                            },
                            success: function(data) {
                                console.log('[BUDGET] Projects loaded');
                                $('#projects-table-container').html(data);
                                initTotals();
                            },
                            error: function(xhr) {
                                console.error('[BUDGET AJAX ERROR]', xhr.responseText);
                                $('#projects-table-container').html(
                                    '<p class="text-center py-12 text-red-600">Failed to load projects.</p>'
                                );
                            },
                            complete: function() {
                                $('#table-loading').addClass('hidden');
                                $('#projects-table-container').removeClass('opacity-50 pointer-events-none');
                            }
                        });
                    }

                    // === OBSERVE CHANGES ===
                    $directorateInput.on('change', loadProjects);
                    $fiscalYearInput.on('change', loadProjects);

                    let lastFiscal = $fiscalYearInput.val();
                    let lastDirectorate = $directorateInput.val();

                    setInterval(() => {
                        const currentFiscal = $fiscalYearInput.val();
                        const currentDirectorate = $directorateInput.val();

                        if (currentFiscal !== lastFiscal || currentDirectorate !== lastDirectorate) {
                            console.log('[BUDGET POLL] Change detected:', {
                                currentFiscal,
                                currentDirectorate
                            });
                            lastFiscal = currentFiscal;
                            lastDirectorate = currentDirectorate;
                            loadProjects();
                        }
                    }, 500);

                    // === TOTALS ===
                    function updateTotalBudget(projectId) {
                        const $row = $(`input[name="project_id[${projectId}]"]`).closest('tr');
                        if (!$row.length) return;

                        const vals = {
                            govtLoan: parseFloat($row.find(`input[name="government_loan[${projectId}]"]`).val()) || 0,
                            govtShare: parseFloat($row.find(`input[name="government_share[${projectId}]"]`).val()) || 0,
                            foreignLoan: parseFloat($row.find(`input[name="foreign_loan_budget[${projectId}]"]`)
                                .val()) || 0,
                            foreignSubsidy: parseFloat($row.find(`input[name="foreign_subsidy_budget[${projectId}]"]`)
                                .val()) || 0,
                            internal: parseFloat($row.find(`input[name="internal_budget[${projectId}]"]`).val()) || 0,
                        };

                        const total = (vals.govtLoan + vals.govtShare + vals.foreignLoan + vals.foreignSubsidy + vals
                            .internal).toFixed(2);
                        $row.find(`input[name="total_budget[${projectId}]"]`).val(total);
                    }

                    function initTotals() {
                        $('input[name^="project_id["]').each(function() {
                            const match = $(this).attr('name').match(/\[(\d+)\]/);
                            if (match) updateTotalBudget(match[1]);
                        });
                    }

                    $(document).on('input',
                        'input[name^="government_loan["], input[name^="government_share["], input[name^="foreign_loan_budget["], input[name^="foreign_subsidy_budget["], input[name^="internal_budget["]',
                        function() {
                            const match = $(this).attr('name').match(/\[(\d+)\]/);
                            if (match) updateTotalBudget(match[1]);
                        });

                    // === EXCEL NAVIGATION ===
                    $(document).on('keydown', '.excel-input:not([readonly])', function(e) {
                        const $input = $(this);
                        const $tr = $input.closest('tr');
                        const $inputs = $tr.find('.excel-input:not([readonly])');
                        const idx = $inputs.index($input);
                        let $next;

                        if (e.key === 'ArrowRight' || e.key === 'Tab') {
                            e.preventDefault();
                            $next = idx < $inputs.length - 1 ? $inputs.eq(idx + 1) : $tr.next('tr').find(
                                '.excel-input:not([readonly])').first();
                        } else if (e.key === 'ArrowLeft' && idx > 0) {
                            e.preventDefault();
                            $next = $inputs.eq(idx - 1);
                        } else if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            $next = $tr.next('tr').find('.excel-input:not([readonly])').eq(idx);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            $next = $tr.prev('tr').find('.excel-input:not([readonly])').eq(idx);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            $next = $tr.next('tr').find('.excel-input:not([readonly])').eq(idx);
                            if (!$next.length) $('#budget-form').submit();
                        }

                        if ($next && $next.length) $next.focus().select();
                    });

                    $(document).on('focus', '.excel-input:not([readonly])', function() {
                        $(this).select();
                    });

                    $('#close-error').on('click', () => $('#error-message').addClass('hidden'));

                    // === DYNAMIC DOWNLOAD TEMPLATE — NOW INSIDE THE READY FUNCTION! ===
                    $('#download-template-link').on('click', function(e) {
                        e.preventDefault();

                        const directorateId = $directorateInput.val() || '';
                        let url = '{{ route('admin.budget.download-template') }}';

                        if (directorateId) {
                            url += '?directorate_id=' + directorateId;
                        }

                        console.log('[BUDGET] Downloading template for directorate:', directorateId || 'all');
                        window.location.href = url;
                    });

                    // === ADMIN: DYNAMIC QUARTERLY TEMPLATE DOWNLOAD ===
                    $('#download-quarter-template-link').on('click', function(e) {
                        e.preventDefault();

                        const directorateId = $directorateInput.val() || '';
                        const fiscalYearId = $fiscalYearInput.val() || '';

                        if (!fiscalYearId) {
                            alert('Please select a fiscal year first.');
                            return;
                        }

                        let url = '{{ route('admin.budgetQuaterAllocation.download-template') }}';
                        url += '?fiscal_year_id=' + fiscalYearId;

                        if (directorateId) {
                            url += '&directorate_id=' + directorateId;
                        }

                        console.log('[ADMIN QUARTER] Downloading template:', url);
                        window.location.href = url;
                    });

                    // === INITIAL LOAD ===
                    if ($fiscalYearInput.val()) {
                        setTimeout(loadProjects, 800);
                    }

                    initTotals();

                    console.log('[BUDGET] Script fully initialized — filtering + download ready!');
                }
            })();
        </script>
    @endpush
</x-layouts.app>
