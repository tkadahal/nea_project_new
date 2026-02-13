<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Progress Report
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ $isAdmin ? 'Generate Organization Progress Report' : 'Generate Project Progress Report' }}
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form id="report-form" action="{{ route('admin.reports.consolidatedAnnual') }}" method="GET">
            <!-- Fiscal Year and Quarter in one row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Fiscal Year -->
                <div class="w-full relative z-50">
                    <x-forms.select label="Fiscal Year" name="fiscal_year_id" id="fiscal_year_id" :options="$fiscalYearOptions"
                        :selected="$selectedFiscalYearId" placeholder="Select Fiscal Year" :error="$errors->first('fiscal_year_id')" class="js-single-select"
                        required />
                </div>

                <!-- Quarter -->
                <div class="w-full relative z-50">
                    <x-forms.select label="Quarter" name="quarter" id="quarter" :options="[
                        ['value' => 'प्रथम', 'label' => 'प्रथम (First)'],
                        ['value' => 'दोस्रो', 'label' => 'दोस्रो (Second)'],
                        ['value' => 'तेस्रो', 'label' => 'तेस्रो (Third)'],
                        ['value' => 'चौथो', 'label' => 'चौथो (Fourth)'],
                    ]" :selected="''"
                        placeholder="Select Quarter" :error="$errors->first('quarter')" class="js-single-select" required />
                </div>
            </div>

            @if ($isAdmin)
                <!-- ADMIN FILTERS -->

                <!-- Budget Headings (Optional) - Full width -->
                <div class="mb-6">
                    <div class="w-full relative z-40">
                        <x-forms.multi-select label="Budget Headings (Optional)" name="budget_heading_ids[]"
                            :options="collect($budgetHeadings)
                                ->map(
                                    fn($title, $id) => [
                                        'value' => (string) $id,
                                        'label' => $title,
                                    ],
                                )
                                ->values()
                                ->all()" :selected="old('budget_heading_ids', [])" multiple
                            placeholder="Select Budget Headings (Leave empty for all)" :error="$errors->first('budget_heading_ids')" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Leave empty to include all budget headings
                        </p>
                    </div>
                </div>

                <!-- Directorate (Optional) - Full width -->
                <div class="mb-6">
                    <div class="w-full relative z-30">
                        <x-forms.multi-select label="Directorate (Optional)" name="directorate_ids[]" :options="collect($directorates)
                            ->map(
                                fn($dir) => [
                                    'value' => (string) $dir->id,
                                    'label' => $dir->title,
                                ],
                            )
                            ->values()
                            ->all()"
                            :selected="old('directorate_ids', [])" multiple placeholder="Select Directorates (Leave empty for all)"
                            :error="$errors->first('directorate_ids')" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Leave empty to include all directorates
                        </p>
                    </div>
                </div>
            @else
                <!-- USER FILTERS -->

                <!-- Projects (Optional) - Full width -->
                <div class="mb-6">
                    <div class="w-full relative z-40">
                        <x-forms.multi-select label="Select Projects" name="project_ids[]" :options="collect($userProjects)
                            ->map(
                                fn($proj) => [
                                    'value' => (string) $proj->id,
                                    'label' => $proj->title,
                                ],
                            )
                            ->values()
                            ->all()"
                            :selected="old('project_ids', [])" multiple
                            placeholder="{{ $userProjects->count() > 1 ? 'Select Specific Projects (Leave empty for all yours)' : 'Your Project' }}"
                            :error="$errors->first('project_ids')" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $userProjects->count() > 1 ? 'Leave empty to include all your assigned projects' : 'You have access to this project.' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Consolidated Report Checkbox -->
            <div class="mb-8">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" id="consolidated-checkbox"
                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        Do you want consolidated Report?
                    </span>
                </label>
                <input type="hidden" name="consolidated" value="0">
            </div>

            <!-- Hidden inputs -->
            <input type="hidden" name="include_data" value="1">
            <input type="hidden" id="fiscal_year" name="fiscal_year" value="">

            <!-- Summary Section -->
            <div id="report-summary"
                class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 hidden">
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Report Summary</h3>
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p><span class="font-medium">Fiscal Year:</span> <span id="summary-fy">-</span></p>
                    <p><span class="font-medium">Quarter:</span> <span id="summary-quarter">-</span></p>

                    @if ($isAdmin)
                        <p><span class="font-medium">Budget Headings:</span> <span
                                id="summary-budget-headings">All</span></p>
                        <p><span class="font-medium">Directorates:</span> <span id="summary-directorates">All</span></p>
                    @else
                        <p><span class="font-medium">Projects:</span> <span id="summary-projects-text">All Your
                                Projects</span></p>
                    @endif

                    <p><span class="font-medium">Total Projects in Report:</span> <span id="summary-projects">-</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" id="generate-btn"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Generate Report
                </button>

                <button type="button" id="preview-btn"
                    class="px-6 py-2.5 bg-gray-600 text-white rounded-lg shadow-sm hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview Summary
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Modal -->
    <div id="loading-modal"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                <div>
                    <p class="text-gray-800 dark:text-gray-200 font-medium">Generating Report...</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Please wait</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('report-form');
                const generateBtn = document.getElementById('generate-btn');
                const loadingModal = document.getElementById('loading-modal');
                const consolidatedCheckbox = document.getElementById('consolidated-checkbox');
                const fiscalYearHidden = document.getElementById('fiscal_year');

                // Hidden iframe to handle file download
                let downloadIframe = document.getElementById('download-iframe');
                if (!downloadIframe) {
                    downloadIframe = document.createElement('iframe');
                    downloadIframe.id = 'download-iframe';
                    downloadIframe.style.display = 'none';
                    document.body.appendChild(downloadIframe);
                }

                // Selectors for custom select components
                const fiscalYearHiddenInput = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const quarterHiddenInput = document.querySelector(
                    '.js-single-select[data-name="quarter"] .js-hidden-input');

                function getFiscalYearValue() {
                    return fiscalYearHiddenInput ? fiscalYearHiddenInput.value : '';
                }

                function getQuarterValue() {
                    return quarterHiddenInput ? quarterHiddenInput.value : '';
                }

                // Helper to get values from Multi-Selects
                function getMultiSelectValues(name) {
                    const values = [];
                    let inputs = document.querySelectorAll(`input[name="${name}"]`);

                    if (inputs.length === 0) {
                        inputs = document.querySelectorAll(`.js-multi-select[data-name="${name}"] .js-hidden-input`);
                    }

                    if (inputs.length === 0) {
                        inputs = document.querySelectorAll(`input[name^="${name}"]`);
                    }

                    inputs.forEach(input => {
                        const val = input.value.trim();
                        if (val && !values.includes(val)) {
                            values.push(val);
                        }
                    });

                    return values;
                }

                function getBudgetHeadingValues() {
                    return getMultiSelectValues('budget_heading_ids[]');
                }

                function getDirectorateValues() {
                    return getMultiSelectValues('directorate_ids[]');
                }

                function getProjectValues() {
                    return getMultiSelectValues('project_ids[]');
                }

                function getFiscalYearText() {
                    const fyId = getFiscalYearValue();
                    if (!fyId) return '-';
                    const option = document.querySelector(
                        `.js-single-select[data-name="fiscal_year_id"] [data-value="${fyId}"]`);
                    return option?.textContent.trim() || fyId;
                }

                function validateForm() {
                    const isValid = getFiscalYearValue() && getQuarterValue();
                    generateBtn.disabled = !isValid;
                    document.getElementById('preview-btn').disabled = !isValid;
                }

                // Update fiscal_year hidden field when selection changes
                if (fiscalYearHiddenInput) {
                    new MutationObserver(() => {
                        fiscalYearHidden.value = getFiscalYearText();
                        validateForm();
                    }).observe(fiscalYearHiddenInput, {
                        attributes: true,
                        attributeFilter: ['value']
                    });
                }

                if (quarterHiddenInput) {
                    new MutationObserver(validateForm).observe(quarterHiddenInput, {
                        attributes: true,
                        attributeFilter: ['value']
                    });
                }

                // Preview button
                document.getElementById('preview-btn').addEventListener('click', async function() {
                    const fiscalYearId = getFiscalYearValue();

                    // Get relevant filters based on visibility
                    const budgetHeadingIds = getBudgetHeadingValues();
                    const directorateIds = getDirectorateValues();
                    const projectIds = getProjectValues();

                    if (!fiscalYearId || !getQuarterValue()) {
                        alert('Please select Fiscal Year and Quarter');
                        return;
                    }

                    loadingModal.classList.remove('hidden');

                    try {
                        const url = new URL('{{ route('admin.reports.projectCount') }}', window.location
                            .origin);
                        url.searchParams.append('fiscal_year_id', fiscalYearId);

                        // Append IDs only if they exist
                        budgetHeadingIds.forEach(id => url.searchParams.append('budget_heading_ids[]', id));
                        directorateIds.forEach(id => url.searchParams.append('directorate_ids[]', id));
                        projectIds.forEach(id => url.searchParams.append('project_ids[]', id));

                        console.log('🔍 Preview URL:', url.toString());

                        const response = await fetch(url);
                        const data = await response.json();

                        // Update Summary UI
                        document.getElementById('summary-fy').textContent = getFiscalYearText();
                        document.getElementById('summary-quarter').textContent = getQuarterValue();

                        // Conditional UI Updates
                        const summaryBudget = document.getElementById('summary-budget-headings');
                        const summaryDirectorate = document.getElementById('summary-directorates');
                        const summaryProjectsText = document.getElementById('summary-projects-text');

                        if (summaryBudget) {
                            summaryBudget.textContent = budgetHeadingIds.length === 0 ?
                                'All Budget Headings' : `${budgetHeadingIds.length} Selected`;
                        }
                        if (summaryDirectorate) {
                            summaryDirectorate.textContent = directorateIds.length === 0 ?
                                'All Directorates' : `${directorateIds.length} Selected`;
                        }
                        if (summaryProjectsText) {
                            summaryProjectsText.textContent = projectIds.length === 0 ?
                                'All Your Projects' : `${projectIds.length} Selected`;
                        }

                        document.getElementById('summary-projects').textContent = data.project_count || 0;
                        document.getElementById('report-summary').classList.remove('hidden');
                    } catch (error) {
                        console.error('❌ Preview error:', error);
                        alert('Failed to load summary.');
                    } finally {
                        loadingModal.classList.add('hidden');
                    }
                });

                // Form submit handler
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const fyId = getFiscalYearValue();
                    const quarter = getQuarterValue();
                    const budgetHeadingIds = getBudgetHeadingValues();
                    const directorateIds = getDirectorateValues();
                    const projectIds = getProjectValues();

                    if (!fyId || !quarter) {
                        alert('Please select Fiscal Year and Quarter');
                        return;
                    }

                    // Build download URL
                    const baseUrl = '{{ route('admin.reports.consolidatedAnnual') }}';
                    const url = new URL(baseUrl, window.location.origin);

                    url.searchParams.set('fiscal_year_id', fyId);
                    url.searchParams.set('quarter', quarter);
                    url.searchParams.set('fiscal_year', getFiscalYearText());
                    url.searchParams.set('include_data', '1');
                    url.searchParams.set('consolidated', consolidatedCheckbox.checked ? '1' : '0');

                    // Append available IDs
                    budgetHeadingIds.forEach(id => url.searchParams.append('budget_heading_ids[]', id));
                    directorateIds.forEach(id => url.searchParams.append('directorate_ids[]', id));
                    projectIds.forEach(id => url.searchParams.append('project_ids[]', id));

                    console.log('📥 Download URL:', url.toString());
                    console.log('📋 Parameters:', {
                        fiscal_year_id: fyId,
                        quarter: quarter,
                        project_ids: projectIds,
                        budget_heading_ids: budgetHeadingIds,
                        directorate_ids: directorateIds,
                        consolidated: consolidatedCheckbox.checked
                    });

                    // Show loading
                    loadingModal.classList.remove('hidden');
                    generateBtn.disabled = true;
                    generateBtn.innerHTML = `
                        <svg class="inline-block w-5 h-5 mr-2 -mt-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"/>
                            <path d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Generating...
                    `;

                    // Trigger download via hidden iframe
                    downloadIframe.src = url.toString();

                    // Reset UI after delay
                    setTimeout(() => {
                        loadingModal.classList.add('hidden');
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `
                            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Generate Report
                        `;
                    }, 2000);
                });

                validateForm();
            });
        </script>
    @endpush
</x-layouts.app>
