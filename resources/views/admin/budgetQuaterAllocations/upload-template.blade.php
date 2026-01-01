<x-layouts.app title="Upload Quarterly Budget Allocation Template">

    <div class="max-w-4xl mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">Upload Quarterly Budget Allocation Template</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <p class="text-gray-600 mb-6">
                Download the template, fill in the Total Approved amounts, Sources (if foreign), and quarterly
                allocations (Q1-Q4), then upload it here.
            </p>

            <p class="font-semibold text-green-800">Partial Upload Supported!</p>
            <ul class="text-sm text-green-700 mt-2 space-y-1 list-disc list-inside">
                <li>You do <strong>not</strong> need to fill every row</li>
                <li>Only fill the projects and budget types you want to update</li>
                <li>Leave rows completely blank to skip them</li>
                <li>Fill only the quarters you want to allocate</li>
                <li>Source column is only required for Foreign Loan or Subsidy</li>
            </ul>

            {{-- Optional: Uncomment if you want a direct download link on this page --}}
            {{-- <a href="{{ route('admin.budgetQuaterAllocation.downloadTemplate') }}"
                class="inline-block mb-6 px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                Download Latest Template
            </a> --}}

            <form action="{{ route('admin.budgetQuaterAllocation.uploadTemplate') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="template" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Filled Template (.xlsx)
                    </label>
                    <input type="file" id="template" name="template" accept=".xlsx" required
                        class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-green-50 file:text-green-700
                                  hover:file:bg-green-100">

                    @error('template')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if (session('import_errors'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="font-semibold text-red-800">Import Errors:</p>
                        <ul class="list-disc list-inside text-red-700 mt-2 space-y-1">
                            @foreach (session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                <div class="mt-8">
                    <button type="submit"
                        class="px-8 py-3 bg-green-600 text-white font-medium rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Upload & Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
