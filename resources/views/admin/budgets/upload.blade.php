<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.budget.upload_title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.budget.upload_description') }}
        </p>
    </div>

    <div class="alert alert-info">
        <p><strong>Instructions for Uploading Budgets:</strong></p>
        <ul>
            <li>Download the budget template using the "Download Template" button.</li>
            <li>Do not modify the <strong>Fiscal Year</strong> or <strong>Project Title</strong> columns unless
                necessary.</li>
            <li>Ensure the <strong>Fiscal Year</strong> column contains valid values (e.g., "2082/83").</li>
            <li>Enter budget amounts in the respective columns; the <strong>Total Budget</strong> will auto-calculate.
            </li>
            <li>Delete the instructions section (rows starting with "Instructions:") before uploading.</li>
            <li>Ensure the file is in .xlsx or .xls format and does not exceed 2MB.</li>
        </ul>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <!-- Download Template Button -->
        <div class="mb-6">
            <a href="{{ route('admin.budget.download-template') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                {{ trans('global.budget.download_template') }}
            </a>
        </div>

        <!-- Upload Form -->
        <form action="{{ route('admin.budget.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-6">
                <label for="excel_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ trans('global.budget.upload_file') }}
                </label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-200 dark:hover:file:bg-gray-600">
                @error('excel_file')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex">
                <x-buttons.primary type="submit" :disabled="false">
                    {{ trans('global.budget.upload') }}
                </x-buttons.primary>
                <a href="{{ route('admin.budget.index') }}"
                    class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </form>
    </div>
</x-layouts.app>
