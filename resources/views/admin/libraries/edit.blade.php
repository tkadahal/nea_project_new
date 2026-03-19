<x-layouts.app>
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        Edit Activity Schedule Template
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Update "{{ $schedule->code }} – {{ Str::limit($schedule->name, 40) }}"
                    </p>
                </div>

                <a href="{{ route('admin.library.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                    </svg>
                    Back to Library
                </a>
            </div>
        </div>

        <!-- Info Alert -->
        <div
            class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 p-5 rounded-lg">
            <div class="flex items-start">
                <svg class="h-6 w-6 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-base font-semibold text-blue-800 dark:text-blue-300 mb-2">
                        Editing Schedule Template
                    </h3>
                    <div class="text-sm text-blue-700 dark:text-blue-400 space-y-2">
                        <p><strong>Project Type:</strong> Fixed – cannot be changed after creation</p>
                        <p><strong>Current:</strong> {{ $schedule->projectType?->name ?? '—' }}</p>
                        <p><strong>Code Format:</strong> A, B, C (parents) or A.1, B.2 (children) or A.1.1
                            (sub-children)</p>
                        <p><strong>Parent:</strong> Must belong to the same project type</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Left Side: Form -->
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                    <!-- Form Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 border-b border-blue-700">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Edit Schedule Template
                        </h3>
                    </div>

                    <form action="{{ route('admin.library.update', $schedule->id) }}" method="POST" class="p-6">
                        @csrf
                        @method('PUT')

                        <!-- Project Type (read-only) -->
                        <div
                            class="mb-6 p-5 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border-2 border-purple-200 dark:border-purple-700 rounded-lg">
                            <label
                                class="block text-sm font-bold text-purple-900 dark:text-purple-300 mb-3 uppercase tracking-wide">
                                🏗️ Project Type
                            </label>
                            <div
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 py-2 px-3 border text-base font-medium text-gray-700 dark:text-gray-300">
                                {{ $schedule->projectType?->name ?? '—' }}
                            </div>
                            <input type="hidden" name="project_type_id" value="{{ $schedule->project_type_id }}">
                            <p class="mt-2 text-xs text-purple-700 dark:text-purple-400 font-medium">
                                Project type cannot be changed after creation
                            </p>
                        </div>

                        <!-- Activity Code -->
                        <div class="mb-6">
                            <label for="code"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Activity Code *
                            </label>
                            <input type="text" name="code" id="code"
                                value="{{ old('code', $schedule->code) }}"
                                placeholder="e.g., A, B, C (parents) or A.1, B.2 (children)" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border text-base @error('code') border-red-500 @enderror">
                            @error('code')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <strong>Format:</strong> Letter (A, B) for parents OR Letter.Number (A.1) for children
                                OR Letter.Number.Number (A.1.1) for sub-children
                            </p>
                        </div>

                        <!-- Activity Name -->
                        <div class="mb-6">
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Activity Name *
                            </label>
                            <input type="text" name="name" id="name"
                                value="{{ old('name', $schedule->name) }}"
                                placeholder="e.g., Design Phase, Site Survey, Tower Installation" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border text-base @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description (Optional)
                            </label>
                            <textarea name="description" id="description" rows="4"
                                placeholder="Detailed description of this activity, its scope, deliverables, and requirements..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border @error('description') border-red-500 @enderror">{{ old('description', $schedule->description ?? '') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Parent Activity -->
                        <div class="mb-6">
                            <label for="parent_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Parent Activity (Optional)
                            </label>
                            <select name="parent_id" id="parent_id"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border @error('parent_id') border-red-500 @enderror">
                                <option value="">-- No Parent (Top Level Activity) --</option>
                                @foreach ($schedulesByProjectType[$schedule->project_type_id] ?? [] as $opt)
                                    @if ($opt['id'] !== $schedule->id)
                                        <!-- Prevent self-parent -->
                                        <option value="{{ $opt['id'] }}"
                                            {{ old('parent_id', $schedule->parent_id) == $opt['id'] ? 'selected' : '' }}>
                                            {{ $opt['code'] }} - {{ $opt['name'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Select a parent activity to create hierarchical structure
                            </p>
                        </div>

                        <!-- Weightage (Optional) -->
                        <div class="mb-6">
                            <label for="weightage"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Weightage (%) - Optional
                            </label>
                            <input type="number" name="weightage" id="weightage"
                                value="{{ old('weightage', $schedule->weightage ?? '') }}" min="0"
                                max="100" step="0.01" placeholder="e.g., 25, 30, 15"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border @error('weightage') border-red-500 @enderror">
                            @error('weightage')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Weightage for top-level activities. Leave empty for child activities.
                            </p>
                        </div>

                        <!-- Example Structures -->
                        <div
                            class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Example Schedule Structures
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <!-- Transmission Line Example -->
                                <div
                                    class="p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                    <p class="font-bold text-gray-700 dark:text-gray-300 mb-2">📡 Transmission Line:
                                    </p>
                                    <div class="space-y-1 font-mono text-gray-600 dark:text-gray-400">
                                        <div class="font-bold">A - Design Phase</div>
                                        <div class="ml-4">A.1 - Route Survey</div>
                                        <div class="ml-8">A.1.1 - Topographic</div>
                                        <div class="ml-4">A.2 - Detailed Design</div>
                                        <div class="font-bold mt-2">B - Construction</div>
                                        <div class="ml-4">B.1 - Foundation</div>
                                        <div class="ml-4">B.2 - Tower Erection</div>
                                    </div>
                                </div>

                                <!-- Substation Example -->
                                <div
                                    class="p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                    <p class="font-bold text-gray-700 dark:text-gray-300 mb-2">⚡ Substation:</p>
                                    <div class="space-y-1 font-mono text-gray-600 dark:text-gray-400">
                                        <div class="font-bold">A - Civil Works</div>
                                        <div class="ml-4">A.1 - Foundation</div>
                                        <div class="ml-4">A.2 - Buildings</div>
                                        <div class="font-bold mt-2">B - Equipment</div>
                                        <div class="ml-4">B.1 - Transformers</div>
                                        <div class="ml-4">B.2 - Switchgear</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div
                            class="flex items-center justify-between pt-6 border-t-2 border-gray-200 dark:border-gray-600">
                            <a href="{{ route('admin.library.index') }}"
                                class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">
                                Cancel
                            </a>

                            <button type="submit"
                                class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-md hover:from-blue-700 hover:to-indigo-700 font-semibold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                Update Schedule Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Side: Existing Schedules Reference -->
            <div class="xl:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden sticky top-6">
                    <!-- Header -->
                    <div
                        class="px-4 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            Existing Templates
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            For: {{ $schedule->projectType?->name ?? '—' }} • Drag to reorder
                        </p>
                    </div>

                    <!-- List -->
                    <div id="schedules-list" class="p-4 max-h-[700px] overflow-y-auto">
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm italic">Loading existing schedules...</p>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div
                        class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-t border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Legend:</p>
                        <div class="space-y-1.5">
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Leaf Activity (trackable)
                            </div>
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 text-blue-500 mr-2 flex-shrink-0" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                                    </path>
                                </svg>
                                Parent Activity (container)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- SortableJS for Drag & Drop -->
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <!-- Custom Styles for Drag & Drop -->
        <style>
            .sortable-ghost {
                opacity: 0.3;
                background-color: #e0e7ff !important;
            }

            .sortable-chosen {
                border: 2px solid #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
                background-color: #eff6ff !important;
            }

            .dark .sortable-chosen {
                background-color: #1e3a8a !important;
            }

            .sortable-drag {
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
                transform: rotate(2deg);
                cursor: grabbing !important;
            }

            .cursor-move {
                cursor: grab;
                transition: opacity 0.2s;
            }

            .cursor-move:hover {
                opacity: 0.7;
            }

            .cursor-move:active {
                cursor: grabbing !important;
            }

            .sortable-list {
                min-height: 50px;
            }
        </style>

        <script>
            // Schedules data by project type (passed from controller)
            const schedulesByType = @json($schedulesByProjectType ?? []);
            let sortableInstance = null;

            // Load schedules for current project type on page load (edit)
            document.addEventListener('DOMContentLoaded', function() {
                const projectTypeId = '{{ $schedule->project_type_id }}';
                if (projectTypeId && schedulesByType[projectTypeId]) {
                    loadSchedules(projectTypeId);
                }
            });

            function loadSchedules(typeId) {
                const parentSelect = document.getElementById('parent_id');
                const schedulesList = document.getElementById('schedules-list');

                // Destroy existing sortable instance
                if (sortableInstance) {
                    sortableInstance.destroy();
                    sortableInstance = null;
                }

                // Clear parent dropdown
                parentSelect.innerHTML = '<option value="">-- No Parent (Top Level Activity) --</option>';

                const schedules = schedulesByType[typeId] || [];

                if (schedules.length === 0) {
                    schedulesList.innerHTML = `
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-sm italic">No schedules yet for this project type.</p>
                        </div>`;
                    return;
                }

                // Populate parent dropdown (only parents with weightage > 0)
                schedules.forEach(schedule => {
                    if (schedule.weightage > 0 && schedule.id !== {{ $schedule->id }}) {
                        const option = document.createElement('option');
                        option.value = schedule.id;
                        option.textContent = `${schedule.code} - ${schedule.name}`;
                        if (schedule.id == {{ $schedule->parent_id ?? 'null' }}) {
                            option.selected = true;
                        }
                        parentSelect.appendChild(option);
                    }
                });

                // Populate sidebar list
                let html = '<div class="space-y-2 sortable-list">';
                schedules.forEach(schedule => {
                    const level = (schedule.code.match(/\./g) || []).length;
                    const indent = level * 16;
                    const isLeaf = schedule.is_leaf;

                    html += `
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-move" data-id="${schedule.id}">
                        <div class="flex items-start" style="padding-left: ${indent}px;">
                            <!-- Drag Handle -->
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 flex-shrink-0 mt-0.5 mr-2 cursor-grab active:cursor-grabbing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                            
                            ${isLeaf ? 
                                '<svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                                '<svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>'
                            }
                            <div class="ml-2 flex-1 min-w-0">
                                <code class="text-xs font-bold text-blue-600 dark:text-blue-400">${schedule.code}</code>
                                <p class="text-xs text-gray-700 dark:text-gray-300 truncate mt-0.5">${schedule.name}</p>
                                ${!isLeaf ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 mt-1">Parent</span>' : ''}
                            </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
                schedulesList.innerHTML = html;

                // Initialize Sortable
                const sortableList = schedulesList.querySelector('.sortable-list');
                if (sortableList) {
                    sortableInstance = new Sortable(sortableList, {
                        animation: 150,
                        handle: '.cursor-move',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: function(evt) {
                            const items = sortableList.querySelectorAll('[data-id]');
                            const updatedSchedules = Array.from(items).map((item, index) => ({
                                id: parseInt(item.dataset.id),
                                sort_order: index
                            }));
                            saveOrder(updatedSchedules, typeId);
                        }
                    });
                }
            }

            // Save order to server
            function saveOrder(schedules, typeId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrfToken) {
                    console.error('❌ CSRF token not found');
                    showToast('Security token missing', 'error');
                    return;
                }

                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            schedules
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Order updated successfully', 'success');
                        } else {
                            showToast(data.message || 'Failed to update order', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error updating order:', error);
                        showToast('Error updating order', 'error');
                    });
            }

            // Toast notification
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-opacity duration-300 ${
                    type === 'success' ? 'bg-green-600' : 'bg-red-600'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Auto-suggest code based on parent (Silent)
            document.getElementById('parent_id')?.addEventListener('change', function() {
                if (!this.value) return;

                const codeInput = document.getElementById('code');

                // Only auto-fill if code field is EMPTY
                if (codeInput.value.trim()) return;

                const selectedText = this.options[this.selectedIndex].text;
                const parentCode = selectedText.split(' - ')[0];
                const suggestedCode = parentCode + '.1';

                codeInput.value = suggestedCode;
                codeInput.focus();
            });

            // Real-time code validation
            document.getElementById('code')?.addEventListener('input', function() {
                const code = this.value.trim().toUpperCase();
                this.value = code; // Auto uppercase

                let feedback = document.querySelector('.code-feedback');
                if (feedback) feedback.remove();

                if (code) {
                    const pattern = /^[A-Z](\.\d+)*$/;
                    feedback = document.createElement('p');
                    feedback.className = 'code-feedback mt-2 text-xs font-medium';

                    if (pattern.test(code)) {
                        feedback.classList.add('text-green-600', 'dark:text-green-400');
                        feedback.innerHTML = '✓ Valid code format';
                    } else {
                        feedback.classList.add('text-yellow-600', 'dark:text-yellow-400');
                        feedback.innerHTML = '⚠ Format: A (parent) or A.1 (child) or A.1.1 (sub-child)';
                    }

                    this.parentElement.appendChild(feedback);
                }
            });
        </script>
    @endpush
</x-layouts.app>
