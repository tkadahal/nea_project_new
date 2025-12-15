<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Annual Program Report
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Generate consolidated annual program progress report
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form id="report-form" action="{{ route('admin.reports.consolidatedAnnual') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Fiscal Year -->
                <div class="w-full relative z-50">
                    <x-forms.select label="Fiscal Year" name="fiscal_year_id" id="fiscal_year_id" :options="collect($fiscalYears)
                        ->map(
                            fn($fy) => [
                                'value' => (string) $fy->id,
                                'label' => $fy->title,
                                'data-nepali-year' => $fy->title,
                            ],
                        )
                        ->values()
                        ->all()"
                        :selected="''" placeholder="Select Fiscal Year" :error="$errors->first('fiscal_year_id')" class="js-single-select"
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

                <!-- Directorate (Optional) -->
                <div class="w-full relative z-40">
                    <x-forms.multi-select label="Directorate" name="directorate_ids[]" :options="collect($directorates)
                        ->map(
                            fn($dir) => [
                                'value' => (string) $dir->id,
                                'label' => $dir->title,
                            ],
                        )
                        ->values()
                        ->all()"
                        :selected="old('directorate_ids', [])" multiple placeholder="Select Directorates (Optional)" :error="$errors->first('directorate_ids')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Leave empty to include all directorates
                    </p>
                </div>
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
                    <p><span class="font-medium">Directorates:</span> <span id="summary-directorates">All</span></p>
                    <p><span class="font-medium">Total Projects:</span> <span id="summary-projects">-</span></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
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
                // Get hidden inputs for custom selects
                const fiscalYearHiddenInput = document.querySelector(
                    '.js-single-select[data-name="fiscal_year_id"] .js-hidden-input');
                const quarterHiddenInput = document.querySelector(
                    '.js-single-select[data-name="quarter"] .js-hidden-input');
                const directorateHiddenInputs = document.querySelectorAll(
                    '.js-multi-select[data-name="directorate_ids[]"] .js-hidden-input');

                const generateBtn = document.getElementById('generate-btn');
                const previewBtn = document.getElementById('preview-btn');
                const reportSummary = document.getElementById('report-summary');
                const loadingModal = document.getElementById('loading-modal');
                const fiscalYearHidden = document.getElementById('fiscal_year');

                function getFiscalYearValue() {
                    return fiscalYearHiddenInput ? fiscalYearHiddenInput.value : '';
                }

                function getQuarterValue() {
                    return quarterHiddenInput ? quarterHiddenInput.value : '';
                }

                function getDirectorateValues() {
                    const values = [];
                    directorateHiddenInputs.forEach(input => {
                        if (input.value) values.push(input.value);
                    });
                    return values;
                }

                function getFiscalYearText() {
                    const fyId = getFiscalYearValue();
                    if (!fyId) return '-';
                    const select = document.querySelector('.js-single-select[data-name="fiscal_year_id"]');
                    const option = select?.querySelector(`[data-value="${fyId}"]`);
                    return option?.textContent.trim() || fyId;
                }

                function getQuarterText() {
                    const quarter = getQuarterValue();
                    if (!quarter) return '-';
                    const select = document.querySelector('.js-single-select[data-name="quarter"]');
                    const option = select?.querySelector(`[data-value="${quarter}"]`);
                    return option?.textContent.trim() || quarter;
                }

                function getDirectorateText() {
                    const values = getDirectorateValues();
                    if (values.length === 0) return 'All Directorates';

                    const labels = [];
                    values.forEach(value => {
                        const select = document.querySelector(
                        '.js-multi-select[data-name="directorate_ids[]"]');
                        const option = select?.querySelector(`[data-value="${value}"]`);
                        if (option) labels.push(option.textContent.trim());
                    });
                    return labels.join(', ') || 'Selected';
                }

                // Update hidden fiscal year field when selection changes
                if (fiscalYearHiddenInput) {
                    const observer = new MutationObserver(function() {
                        const fyId = getFiscalYearValue();
                        const fyText = getFiscalYearText();
                        fiscalYearHidden.value = fyText;
                        validateForm();
                    });
                    observer.observe(fiscalYearHiddenInput, {
                        attributes: true,
                        attributeFilter: ['value']
                    });
                }

                if (quarterHiddenInput) {
                    const observer = new MutationObserver(validateForm);
                    observer.observe(quarterHiddenInput, {
                        attributes: true,
                        attributeFilter: ['value']
                    });
                }

                function validateForm() {
                    const isValid = getFiscalYearValue() && getQuarterValue();
                    generateBtn.disabled = !isValid;
                    previewBtn.disabled = !isValid;
                }

                // Preview Summary
                previewBtn.addEventListener('click', async function() {
                    const fiscalYearId = getFiscalYearValue();
                    const quarter = getQuarterValue();
                    const directorateIds = getDirectorateValues();

                    if (!fiscalYearId || !quarter) {
                        alert('Please select Fiscal Year and Quarter');
                        return;
                    }

                    // Show loading
                    loadingModal.classList.remove('hidden');

                    try {
                        // Fetch project count
                        const url = new URL('{{ route('admin.reports.projectCount') }}', window.location
                            .origin);
                        url.searchParams.append('fiscal_year_id', fiscalYearId);
                        if (directorateIds.length > 0) {
                            directorateIds.forEach(id => url.searchParams.append('directorate_ids[]', id));
                        }

                        const response = await fetch(url);
                        const data = await response.json();

                        // Update summary
                        document.getElementById('summary-fy').textContent = getFiscalYearText();
                        document.getElementById('summary-quarter').textContent = getQuarterText();
                        document.getElementById('summary-directorates').textContent = getDirectorateText();
                        document.getElementById('summary-projects').textContent = data.project_count || 0;

                        reportSummary.classList.remove('hidden');
                    } catch (error) {
                        console.error('Error fetching summary:', error);
                        alert('Failed to load summary. Please try again.');
                    } finally {
                        loadingModal.classList.add('hidden');
                    }
                });

                // Generate Report
                document.getElementById('report-form').addEventListener('submit', function(e) {
                    // Get values and add them to the form
                    const fyId = getFiscalYearValue();
                    const quarter = getQuarterValue();
                    const directorateIds = getDirectorateValues();

                    if (!fyId || !quarter) {
                        e.preventDefault();
                        alert('Please select Fiscal Year and Quarter');
                        return;
                    }

                    // Update form action with proper parameters
                    const form = this;
                    const url = new URL(form.action, window.location.origin);
                    url.searchParams.set('fiscal_year_id', fyId);
                    url.searchParams.set('quarter', quarter);
                    url.searchParams.set('fiscal_year', fiscalYearHidden.value);
                    url.searchParams.set('include_data', '1');

                    if (directorateIds.length > 0) {
                        directorateIds.forEach(id => url.searchParams.append('directorate_ids[]', id));
                    }

                    form.action = url.toString();

                    loadingModal.classList.remove('hidden');
                    generateBtn.disabled = true;
                    generateBtn.innerHTML =
                        '<svg class="inline-block w-5 h-5 mr-2 -mt-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Generating...';

                    // Allow form to submit naturally (will download file)
                    setTimeout(() => {
                        loadingModal.classList.add('hidden');
                        generateBtn.disabled = false;
                        generateBtn.innerHTML =
                            '<svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Generate Report';
                    }, 2000);
                });

                // Initialize
                validateForm();
            });
        </script>
    @endpush
</x-layouts.app>
