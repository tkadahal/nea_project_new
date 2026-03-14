<x-layouts.app>
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .sortable-ghost {
                opacity: 0.4;
                background-color: #e0f2fe;
            }

            .sortable-chosen {
                border: 2px solid #3b82f6;
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
                background-color: #f0f9ff;
            }

            .sortable-drag {
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
                transform: rotate(1.5deg);
                cursor: grabbing;
            }

            .cursor-move {
                cursor: grab;
            }

            .cursor-move:active {
                cursor: grabbing;
            }

            /* Hide content by default */
            .accordion-content {
                display: none;
            }

            /* Simple chevron rotation */
            .accordion-chevron {
                transition: transform 0.25s ease;
            }

            .accordion-chevron.open {
                transform: rotate(180deg);
            }

            .modal-backdrop {
                background-color: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(3px);
            }
        </style>
    @endpush

    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    Schedule Template Library
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Click project type to expand • Drag to reorder activities
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button onclick="expandAll()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    Expand All
                </button>

                <button onclick="collapseAll()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Collapse All
                </button>

                <a href="{{ route('admin.library.create') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-md hover:from-green-700 hover:to-emerald-700 shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create New
                </a>
            </div>
        </div>

        <!-- Messages -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-md">
                <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Project Types -->
        @forelse($projectTypes as $typeKey => $typeName)
            @php
                $schedules = $schedulesByType->get($typeKey, collect());
                $count = $schedules->count();
                $leafCount = $schedules->filter(fn($s) => $s->isLeaf())->count();
                $parentCount = $count - $leafCount;
            @endphp

            <div
                class="mb-5 accordion-item bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">

                <!-- Header (clickable) -->
                <div class="accordion-header cursor-pointer select-none" data-type="{{ $typeKey }}">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="p-2.5 bg-white/20 rounded">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">{{ $typeName }}</h2>
                                    <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-sm text-blue-100">
                                        <span>{{ $count }} total</span>
                                        @if ($count > 0)
                                            <span class="text-green-200">{{ $leafCount }} trackable</span>
                                            <span class="text-blue-200">{{ $parentCount }} parents</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <a href="{{ route('admin.library.create') }}?project_type={{ $typeKey }}"
                                    class="px-3 py-1.5 bg-white/25 hover:bg-white/35 rounded text-white text-sm flex items-center gap-1.5"
                                    onclick="event.stopPropagation()">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add
                                </a>

                                <svg class="w-7 h-7 text-white accordion-chevron" id="chevron-{{ $typeKey }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="accordion-content" id="content-{{ $typeKey }}">
                    @if ($schedules->isEmpty())
                        <div class="p-12 text-center border-t border-gray-200 dark:border-gray-700">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                No templates for <strong>{{ $typeName }}</strong> yet
                            </p>
                            <a href="{{ route('admin.library.create') }}?project_type={{ $typeKey }}"
                                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                                Create First Template
                            </a>
                        </div>
                    @else
                        <div id="sortable-{{ $typeKey }}" class="divide-y divide-gray-200 dark:divide-gray-700"
                            data-project-type="{{ $typeKey }}">
                            @foreach ($schedules as $schedule)
                                <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                    data-id="{{ $schedule->id }}">
                                    <div class="flex items-start gap-4">
                                        <!-- Drag handle -->
                                        <div class="cursor-move mt-1">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 8h16M4 16h16" />
                                            </svg>
                                        </div>

                                        <!-- Main content -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                @if ($schedule->isLeaf())
                                                    <svg class="w-5 h-5 text-green-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-blue-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                @endif

                                                <code
                                                    class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $schedule->code }}</code>
                                                <span class="text-gray-400">•</span>
                                                <span
                                                    class="font-medium text-gray-900 dark:text-gray-100">{{ $schedule->name }}</span>

                                                @if (!$schedule->isLeaf())
                                                    <span
                                                        class="ml-2 px-2.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded">
                                                        Parent
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($schedule->description)
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 pl-8">
                                                    {{ $schedule->description }}
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.library.edit', $schedule) }}"
                                                class="p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-gray-700 rounded">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            <form action="{{ route('admin.library.destroy', $schedule) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this schedule template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-700 rounded">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-6 rounded">
                <p class="text-yellow-700 dark:text-yellow-300">
                    No project types configured yet.
                </p>
            </div>
        @endforelse
    </div>

    <!-- Renumber Confirmation Modal -->
    <div id="confirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="modal-backdrop fixed inset-0" onclick="cancelRenumber()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 z-10">
            <div class="text-center">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                    <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Renumber Activity Codes?
                </h3>
                <p class="mt-3 text-gray-600 dark:text-gray-400">
                    You've changed the order. Do you want to update the codes to match the new sequence?
                </p>

                <div
                    class="mt-5 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg max-h-60 overflow-auto text-left text-sm">
                    <p class="font-medium mb-2">Preview of changes:</p>
                    <div id="changePreview" class="space-y-1 font-mono text-xs"></div>
                </div>

                <div class="mt-6 flex gap-4">
                    <button onclick="cancelRenumber()"
                        class="flex-1 py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                        No, keep original codes
                    </button>
                    <button onclick="confirmRenumber()"
                        class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Yes, renumber now
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            let pendingRenumber = null;
            let sortables = {};

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            // Force collapse everything on page load
            function forceInitialCollapse() {
                document.querySelectorAll('.accordion-content').forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.accordion-chevron').forEach(el => {
                    el.classList.remove('open');
                });
            }

            // Toggle accordion
            function toggleAccordion(typeKey) {
                const content = document.getElementById(`content-${typeKey}`);
                const chevron = document.getElementById(`chevron-${typeKey}`);

                if (!content || !chevron) return;

                const isOpen = content.style.display === 'block';

                content.style.display = isOpen ? 'none' : 'block';
                chevron.classList.toggle('open', !isOpen);

                // Initialize sortable only when first opened
                if (!isOpen && !sortables[typeKey]) {
                    initSortable(typeKey);
                }
            }

            function initSortable(typeKey) {
                const container = document.getElementById(`sortable-${typeKey}`);
                if (!container) return;

                sortables[typeKey] = new Sortable(container, {
                    animation: 150,
                    handle: '.cursor-move',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',

                    onEnd: evt => {
                        const items = Array.from(container.querySelectorAll('[data-id]'));
                        const order = items.map((el, idx) => ({
                            id: parseInt(el.dataset.id),
                            sort_order: idx,
                            code: el.querySelector('code')?.textContent.trim() || '',
                            name: el.querySelector('.font-medium')?.textContent.trim() || ''
                        }));

                        saveOrder(order, typeKey).then(result => {
                            if (result.success) {
                                pendingRenumber = {
                                    typeKey,
                                    order
                                };
                                showRenumberModal(order);
                            } else {
                                alert('Failed to save order');
                                location.reload();
                            }
                        });
                    }
                });
            }

            async function saveOrder(schedules, typeKey) {
                try {
                    const res = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            schedules
                        })
                    });
                    return await res.json();
                } catch (e) {
                    console.error(e);
                    return {
                        success: false
                    };
                }
            }

            function showRenumberModal(schedules) {
                const preview = document.getElementById('changePreview');
                preview.innerHTML = '';

                const byPhase = {};
                schedules.forEach(s => {
                    const phase = s.code.charAt(0);
                    byPhase[phase] = byPhase[phase] || [];
                    byPhase[phase].push(s);
                });

                let hasChange = false;
                Object.keys(byPhase).sort().forEach(phase => {
                    byPhase[phase].forEach((item, i) => {
                        const newCode = phase + '.' + (i + 1);
                        if (item.code !== newCode) {
                            hasChange = true;
                            const div = document.createElement('div');
                            div.innerHTML =
                                `<span class="text-red-600">${item.code}</span> → <span class="text-green-600 font-bold">${newCode}</span> ${item.name}`;
                            preview.appendChild(div);
                        }
                    });
                });

                if (!hasChange) {
                    preview.innerHTML = '<div class="text-gray-500 italic">No changes needed – codes already match order</div>';
                }

                document.getElementById('confirmModal').classList.remove('hidden');
            }

            async function confirmRenumber() {
                if (!pendingRenumber) return;
                const {
                    typeKey
                } = pendingRenumber;

                document.getElementById('confirmModal').classList.add('hidden');

                try {
                    const res = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            project_type: typeKey
                        })
                    });
                    const data = await res.json();

                    if (data.success) {
                        alert(`Renumbered ${data.changes?.length || 0} codes`);
                        location.reload();
                    } else {
                        alert('Renumber failed');
                    }
                } catch (e) {
                    alert('Network error');
                }

                pendingRenumber = null;
            }

            function cancelRenumber() {
                document.getElementById('confirmModal').classList.add('hidden');
                pendingRenumber = null;
            }

            function expandAll() {
                document.querySelectorAll('.accordion-content').forEach(el => {
                    const key = el.id.replace('content-', '');
                    el.style.display = 'block';
                    document.getElementById(`chevron-${key}`)?.classList.add('open');

                    if (!sortables[key]) {
                        initSortable(key);
                    }
                });
            }

            function collapseAll() {
                document.querySelectorAll('.accordion-content').forEach(el => {
                    const key = el.id.replace('content-', '');
                    el.style.display = 'none';
                    document.getElementById(`chevron-${key}`)?.classList.remove('open');
                });
            }

            // Event delegation for accordion headers
            document.addEventListener('click', e => {
                const header = e.target.closest('.accordion-header');
                if (!header) return;

                // Prevent toggle when clicking links/buttons inside
                if (e.target.closest('a, button')) return;

                const typeKey = header.dataset.type;
                if (typeKey) toggleAccordion(typeKey);
            });

            // Run on page load
            document.addEventListener('DOMContentLoaded', () => {
                // 1. Force collapse everything (extra safety)
                forceInitialCollapse();

                // 2. Optional: auto-init sortables only for sections that might be open (usually none)
                //    but we don't need this since we start collapsed

                console.log("Page loaded – all accordions collapsed by default");
            });
        </script>
    @endpush
</x-layouts.app>
