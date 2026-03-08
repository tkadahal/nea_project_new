<x-layouts.app>
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-8 max-w-5xl mx-auto">

        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Schedule Manager</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Drag to reorder activities or use the tools below to auto-renumber codes.
            </p>
        </div>

        <!-- Controls Toolbar -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row gap-4 items-end md:items-center justify-between">

                <!-- Project Type Selector -->
                <div class="w-full md:w-1/3">
                    <label for="manage_project_type"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Project Type
                    </label>
                    <select id="manage_project_type"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Choose Type --</option>
                        @foreach ($projectTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 w-full md:w-auto">
                    <button id="btn-preview" disabled
                        class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        Preview Renumbering
                    </button>

                    <button id="btn-save-order" disabled
                        class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Save Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Draggable List Container -->
        <div id="schedule-container"
            class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 min-h-[300px] border-2 border-dashed border-gray-300 dark:border-gray-700 flex items-center justify-center">
            <p class="text-gray-400 italic">Select a project type above to load schedules.</p>
        </div>

    </div>

    <!-- Preview Modal -->
    <div id="preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">

                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Preview Renumbering
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    The following codes will be updated based on the current sort order.
                                    <span class="text-red-500 font-bold">This action will update the database.</span>
                                </p>

                                <!-- Changes Table -->
                                <div
                                    class="mt-4 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col"
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Current</th>
                                                <th scope="col"
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    New</th>
                                                <th scope="col"
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Activity</th>
                                            </tr>
                                        </thead>
                                        <tbody id="preview-table-body"
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <!-- Rows injected via JS -->
                                        </tbody>
                                    </table>
                                </div>
                                <p id="preview-count" class="text-xs text-gray-500 mt-2 text-right"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btn-confirm-renumber"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm & Renumber
                    </button>
                    <button type="button" id="btn-cancel-modal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <style>
            .sortable-ghost {
                opacity: 0.4;
                background-color: #f3f4f6;
            }

            .sortable-drag {
                cursor: grabbing;
            }

            .drag-handle {
                cursor: grab;
            }

            .drag-handle:active {
                cursor: grabbing;
            }
        </style>

        <script>
            const projectTypeSelect = document.getElementById('manage_project_type');
            const scheduleContainer = document.getElementById('schedule-container');
            const btnPreview = document.getElementById('btn-preview');
            const btnSaveOrder = document.getElementById('btn-save-order');

            // Modal Elements
            const modal = document.getElementById('preview-modal');
            const btnConfirmRenumber = document.getElementById('btn-confirm-renumber');
            const btnCancelModal = document.getElementById('btn-cancel-modal');
            const previewTableBody = document.getElementById('preview-table-body');

            let sortableInstance = null;
            let currentProjectType = null;

            // 1. Load Schedules via AJAX
            projectTypeSelect.addEventListener('change', async function() {
                currentProjectType = this.value;
                if (!currentProjectType) {
                    scheduleContainer.innerHTML = '<p class="text-gray-400">Select a project type.</p>';
                    btnPreview.disabled = true;
                    btnSaveOrder.disabled = true;
                    return;
                }

                // Show loading
                scheduleContainer.innerHTML =
                    '<div class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-500">Loading...</p></div>';

                try {
                    const response = await fetch('{{ route('admin.schedules.library.get-json') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            project_type: currentProjectType
                        })
                    });

                    if (!response.ok) throw new Error('Failed to load');

                    const schedules = await response.json();
                    renderSortableList(schedules);

                    btnPreview.disabled = false;
                    btnSaveOrder.disabled = false;

                } catch (error) {
                    console.error(error);
                    scheduleContainer.innerHTML = '<p class="text-red-500">Error loading schedules.</p>';
                }
            });

            // 2. Render List with SortableJS
            function renderSortableList(schedules) {
                if (schedules.length === 0) {
                    scheduleContainer.innerHTML = '<p class="text-gray-400">No schedules found.</p>';
                    return;
                }

                let html = '<div id="sortable-list" class="space-y-2">';
                schedules.forEach(schedule => {
                    const indent = (schedule.code.match(/\./g) || []).length * 20;
                    const icon = schedule.is_leaf ?
                        '<svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' :
                        '<svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>';

                    html += `
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600 flex items-center group hover:shadow-md transition-shadow" data-id="${schedule.id}">
                        <div class="drag-handle text-gray-400 mr-3 cursor-grab p-1 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </div>
                        <div style="margin-left: ${indent}px" class="flex items-center w-full">
                            <div class="mr-3 flex-shrink-0">${icon}</div>
                            <div class="flex-1">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">${schedule.code}</span>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">${schedule.name}</span>
                            </div>
                        </div>
                    </div>
                    `;
                });
                html += '</div>';
                scheduleContainer.innerHTML = html;

                // Init Sortable
                if (sortableInstance) sortableInstance.destroy();
                sortableInstance = new Sortable(document.getElementById('sortable-list'), {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost'
                });
            }

            // 3. Implement Update Order (Drag & Drop)
            btnSaveOrder.addEventListener('click', async function() {
                if (!currentProjectType) return;

                const items = document.querySelectorAll('#sortable-list > div');
                const schedules = Array.from(items).map((item, index) => ({
                    id: parseInt(item.dataset.id),
                    sort_order: index
                }));

                // Show saving state
                const originalText = this.innerHTML;
                this.innerHTML = 'Saving & Renumbering...';
                this.disabled = true;

                try {
                    const response = await fetch('{{ route('admin.schedules.library.update-order') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            schedules
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        showToast(`Order saved! Renumbered ${data.renumbered_count} items.`, 'success');

                        // *** CRITICAL: Update the DOM with the new codes from server ***
                        if (data.schedules) {
                            // Create a map of ID -> new Code for quick lookup
                            const codeMap = {};
                            data.schedules.forEach(s => {
                                codeMap[s.id] = s.code;
                            });

                            // Update the text in the current list without reloading
                            items.forEach(item => {
                                const id = parseInt(item.dataset.id);
                                if (codeMap[id]) {
                                    // Find the code span (it has class 'font-mono')
                                    const codeSpan = item.querySelector('.font-mono');
                                    if (codeSpan) {
                                        codeSpan.textContent = codeMap[id];
                                    }
                                }
                            });
                        }

                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showToast('Error saving order', 'error');
                } finally {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            });

            // 4. Implement Preview Renumber
            btnPreview.addEventListener('click', async function() {
                if (!currentProjectType) return;

                this.disabled = true;
                this.innerText = 'Checking...';

                try {
                    const response = await fetch('{{ route('admin.schedules.library.preview-renumber') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            project_type: currentProjectType
                        })
                    });

                    const data = await response.json();

                    // Render Modal
                    previewTableBody.innerHTML = '';
                    if (data.preview && data.preview.length > 0) {
                        data.preview.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300 font-mono">${row.current_code}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400 font-mono">${row.new_code}</td>
                                <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">${row.name}</td>
                            `;
                            previewTableBody.appendChild(tr);
                        });
                        document.getElementById('preview-count').innerText =
                            `${data.total_changes} changes detected.`;
                        modal.classList.remove('hidden');
                    } else {
                        showToast('No renumbering needed. Codes are already correct.', 'success');
                    }

                } catch (error) {
                    showToast('Error previewing changes', 'error');
                } finally {
                    this.disabled = false;
                    this.innerText = 'Preview Renumbering';
                }
            });

            // 5. Implement Confirm Renumber
            btnConfirmRenumber.addEventListener('click', async function() {
                if (!confirm('Are you sure? This will update activity codes in the database.')) return;

                this.innerText = 'Renumbering...';
                this.disabled = true;

                try {
                    const response = await fetch('{{ route('admin.schedules.library.renumber-codes') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            project_type: currentProjectType
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        modal.classList.add('hidden');
                        // Reload the list to show new codes
                        projectTypeSelect.dispatchEvent(new Event('change'));
                    } else {
                        showToast(data.message || 'Error renumbering', 'error');
                    }

                } catch (error) {
                    showToast('Error executing renumber', 'error');
                } finally {
                    this.innerText = 'Confirm & Renumber';
                    this.disabled = false;
                }
            });

            // Modal Controls
            btnCancelModal.addEventListener('click', () => modal.classList.add('hidden'));

            // Close modal on outside click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.add('hidden');
            });

            // Simple Toast Helper
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className =
                    `fixed bottom-5 right-5 px-6 py-3 rounded shadow-lg text-white z-50 transition-opacity duration-300 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
                toast.innerText = message;
                document.body.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        </script>
    @endpush
</x-layouts.app>
