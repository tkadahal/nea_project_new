<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Upload Excel Expenses
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Upload your filled expense template for {{ $projectModel->title }} - {{ $fiscalYearModel->title }}
            ({{ $quarter }})
        </p>
    </div>

    @if (session('success'))
        <div
            class="mb-4 p-4 bg-green-100 text-green-800 border border-green-300 rounded-lg dark:bg-green-900 dark:text-green-200 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div
            class="mb-4 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info">
        <p><strong>Instructions for Uploading Expenses:</strong></p>
        <ul>
            <li>Download the template below, fill actual quantities and amounts in the editable columns (G: Qty, H:
                Amt).</li>
            <li>Maintain hierarchy using the serial column (e.g., 1 for parent, 1.1 for child).</li>
            <li><strong>Validation Rules:</strong></li>
            <li>Actual values must be non-negative numbers.</li>
            <li>Subtotals (auto-calculated) must match sums of children; parents show blanks if hierarchy is correct.
            </li>
            <li>Only changes for the selected quarter ({{ $quarter }}) will be saved; other quarters are preserved.
            </li>
            <li>Blanks will clear existing data for that row/quarter.</li>
        </ul>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <!-- Download Template Button -->
        <div class="mb-6">
            {{-- <a href="{{ route('projectExpense.template.download', [$projectModel->id, $fiscalYearModel->id]) }}?quarter={{ $quarter }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Download Template for {{ $quarter }}
            </a> --}}
        </div>

        <!-- Upload Form -->
        <form action="{{ route('admin.projectExpense.upload', [$projectModel->id, $fiscalYearModel->id]) }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            {{-- Hidden inputs to pass project/fy/quarter on submit --}}
            <input type="hidden" name="project_id" value="{{ $projectModel->id }}">
            <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYearModel->id }}">
            <input type="text" name="quarter" value="{{ $quarter }}">

            <div class="mb-6">
                <label for="excel_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Upload Filled Excel File <span class="text-red-500">*</span>
                </label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-200 dark:hover:file:bg-gray-600">
                @error('excel_file')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex gap-4">
                <x-buttons.primary type="submit" :disabled="false">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Upload & Process
                </x-buttons.primary>
                <a href="{{ route('admin.projectExpense.create', ['project_id' => $projectModel->id, 'fiscal_year_id' => $fiscalYearModel->id]) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Create
                </a>
            </div>
        </form>
    </div>
</x-layouts.app>
