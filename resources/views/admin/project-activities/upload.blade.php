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
            <input type="hidden" name="force" value="0" id="forceInput">

            <div class="mb-6">
                <label for="excel_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Upload Excel File
                </label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required
                    class="mt-1 block w-full text-sm text-gray-500
                             file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                             file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                             hover:file:bg-blue-100 file:transition">
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
    <dialog id="confirmModal"
        class="backdrop:bg-black/50 max-w-lg w-full mx-auto rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-0
               [margin-block:start_auto!important] [margin-block:end_auto!important]">
        <div class="p-8 bg-white dark:bg-gray-800">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900 mb-4">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Confirm Structural Change</h3>
            </div>

            <div class="mt-4 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                The uploaded program structure differs from the current version. Proceeding will create a new version
                and
                reset the plan to draft.<br><br>
                <strong>This action cannot be undone.</strong>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <button type="button" id="cancelBtn"
                    class="px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition font-medium">
                    No, Cancel
                </button>
                <button type="button" id="confirmBtn"
                    class="px-5 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition font-medium">
                    Yes, Create New Version
                </button>
            </div>
        </div>
    </dialog>

    <!-- General Errors -->
    @if ($errors->any() || session('error'))
        <div
            class="mt-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-200">
            <p class="font-semibold">Upload Failed:</p>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if (session('error'))
                    <li>{{ session('error') }}</li>
                @endif
            </ul>
        </div>
    @endif

    <script>
        const form = document.getElementById('uploadForm');
        const modal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const forceInput = document.getElementById('forceInput');

        function openModal() {
            modal.showModal();
        }

        function closeModal() {
            modal.close();
        }

        form.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excel_file');

            // Basic validation
            if (!fileInput.files || fileInput.files.length === 0) {
                return; // Let Laravel validation handle it
            }

            // If user already confirmed (force=1), allow submit
            if (forceInput.value === '1') {
                return;
            }

            // If backend says confirmation is required → show modal
            @if (session('requires_confirmation'))
                e.preventDefault();
                openModal();
            @endif
        });

        cancelBtn.onclick = closeModal;

        confirmBtn.onclick = () => {
            forceInput.value = '1'; // Now submit with force=1
            closeModal();
            form.submit();
        };

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

    <style>
        /* Force perfect vertical centering for <dialog> */
        dialog {
            margin: auto;
            /* This often works */
            /* Fallback for stubborn browsers (Chrome) */
            margin-block: auto !important;
            max-height: calc(100vh - 2rem);
            /* Prevent overflow on small screens */
        }

        /* Smooth open/close animation */
        @keyframes modalOpen {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        dialog[open] {
            display: flex;
            animation: modalOpen 300ms ease-out;
        }

        /* Optional: fade in backdrop */
        dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 300ms ease-out;
        }

        @keyframes fadeIn {
            from {
                background: transparent;
            }

            to {
                background: rgba(0, 0, 0, 0.5);
            }
        }
    </style>
</x-layouts.app>
