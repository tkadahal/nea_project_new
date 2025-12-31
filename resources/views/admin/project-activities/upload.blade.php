<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Upload Excel
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Upload your project activity plan in Excel format.
        </p>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <p class="font-semibold text-blue-900 dark:text-blue-100">Instructions:</p>
        <ul class="list-disc list-inside mt-2 text-sm text-blue-800 dark:text-blue-200 space-y-1">
            <li>Fill data in the Capital and Recurrent sheets.</li>
            <li>Use the # column for hierarchy (e.g., 1, 1.1, 1.1.1).</li>
            <li>Planned Budget = Q1 + Q2 + Q3 + Q4.</li>
            <li>Parent rows must sum their children.</li>
            <li>All numbers non-negative.</li>
        </ul>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.projectActivity.template') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                Download Excel Template
            </a>
        </div>

        <!-- Main Upload Form -->
        <form id="uploadForm" action="{{ route('admin.projectActivity.upload') }}" method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="mb-6">
                <label for="excel_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Upload Excel File
                </label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required
                    class="mt-1 block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">
                @error('excel_file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-4">
                <x-buttons.primary type="submit">
                    Upload
                </x-buttons.primary>
                <a href="{{ route('admin.projectActivity.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md
                           text-gray-700 bg-white hover:bg-gray-50 transition">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-structural-modal" class="fixed inset-0 bg-black/50 z-50 items-center justify-center hidden"
        role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center mb-6">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/50">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 id="modal-title" class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                    Confirm Structural Change
                </h3>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-300 text-center mb-8 leading-relaxed">
                @if (session('temp_original_name'))
                    You uploaded: <strong>{{ session('temp_original_name') }}</strong><br><br>
                @endif
                The uploaded program structure differs from the current version. Proceeding will create a new version
                and reset the plan to draft.<br><br>
                <strong>This action cannot be undone.</strong>
            </div>

            <div class="flex justify-end gap-3">
                <!-- Cancel: Deletes temp file immediately -->
                <form id="cancelForm" action="{{ route('admin.projectActivity.upload.cancel') }}" method="POST"
                    class="inline">
                    @csrf
                    <button type="submit"
                        class="px-5 py-2 text-gray-700 bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                </form>

                <!-- Confirm Button: Triggers hidden form submit -->
                <button type="button" id="proceedConfirmBtn"
                    class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Yes, Create New Version
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Confirm Form -->
    <form id="confirmForm" action="{{ route('admin.projectActivity.upload.confirm') }}" method="POST" class="hidden">
        @csrf
    </form>

    <!-- Messages -->
    @if ($errors->any())
        <div
            class="mt-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-200">
            <p class="font-semibold">Upload Failed:</p>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div
            class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-200">
            <p class="font-semibold">Success:</p>
            <p class="mt-2 text-sm">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('info'))
        <div
            class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-700 dark:text-blue-200">
            <p class="text-sm">{{ session('info') }}</p>
        </div>
    @endif

    <script>
        const modal = document.getElementById('confirm-structural-modal');
        const confirmBtn = document.getElementById('proceedConfirmBtn');
        const confirmForm = document.getElementById('confirmForm');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Auto-open modal if confirmation required
        @if (session('requires_confirmation'))
            openModal();
        @endif

        // Confirm: Submit the hidden confirm form
        confirmBtn.addEventListener('click', () => {
            closeModal();
            confirmForm.submit();
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close with Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    </script>
</x-layouts.app>
