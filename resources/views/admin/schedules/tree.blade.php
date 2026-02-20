<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Schedule Hierarchy Tree</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Visual breakdown of project activities and their relationships.
            </p>

            <!-- Breadcrumb -->
            <nav class="mt-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.project.index') }}"
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300">Projects</a>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
                        </svg>
                        <a href="{{ route('admin.project.show', $project) }}"
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300 truncate max-w-[200px] md:max-w-none">
                            {{ Str::limit($project->title, 35) }}
                        </a>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
                        </svg>
                        <a href="{{ route('admin.projects.schedules.index', $project) }}"
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300">Schedules</a>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Tree View</span>
                    </li>
                </ol>
            </nav>

            <!-- Controls -->
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-3">
                    <button onclick="expandAll()"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 0h-4m4 0l-5-5" />
                        </svg>
                        Expand All
                    </button>
                    <button onclick="collapseAll()"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Collapse All
                    </button>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.projects.schedules.index', $project) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        List View
                    </a>
                    <a href="{{ route('admin.projects.schedules.dashboard', $project) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>

        @if ($topLevelSchedules->isEmpty())
            <div
                class="rounded-xl bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 p-6">
                <div class="flex items-start gap-4">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-500 flex-shrink-0" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No schedules found</h3>
                        <p class="mt-2 text-gray-700 dark:text-gray-300">This project doesn't have any activities
                            assigned yet.</p>
                        <a href="{{ route('admin.projects.schedules.assign-form', $project) }}"
                            class="mt-4 inline-flex items-center px-5 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Assign Schedules
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Tree Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 border-b border-blue-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <h2 class="text-lg font-semibold text-white">Project Schedule Structure</h2>
                    </div>
                    <div class="flex items-center gap-5 text-white text-sm">
                        <button onclick="expandAll()"
                            class="hover:text-blue-100 transition-colors flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-700 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 0h-4m4 0l-5-5" />
                            </svg>
                            Expand All
                        </button>
                        <button onclick="collapseAll()"
                            class="hover:text-blue-100 transition-colors flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-700 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Collapse All
                        </button>
                    </div>
                </div>

                <div class="p-5 md:p-6">
                    <div class="space-y-2">
                        @foreach ($topLevelSchedules as $schedule)
                            @include('admin.schedules.partials.tree-node', [
                                'schedule' => $schedule,
                                'project' => $project,
                                'level' => 0,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div
                class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m-1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Legend</h3>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Phase -->
                    <div
                        class="flex flex-col gap-2 p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                Phase
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Top-level major sections or phases of the
                            project</p>
                    </div>

                    <!-- Group -->
                    <div
                        class="flex flex-col gap-2 p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-indigo-500 text-white shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-4l-2 2H5a2 2 0 00-2 2z" />
                                </svg>
                                Group
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Intermediate grouping or sub-phases with
                            child activities</p>
                    </div>

                    <!-- Activity -->
                    <div
                        class="flex flex-col gap-2 p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-green-600 text-white shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m-6-8h6" />
                                </svg>
                                Activity
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Executable task / leaf node (can edit
                            progress)</p>
                    </div>

                    <!-- Progress -->
                    <div
                        class="flex flex-col gap-2 p-4 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-24 h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-gray-400 via-yellow-500 via-blue-500 to-green-500"
                                    style="width: 100%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Progress</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Shows completion %<br>
                            <strong>Colors:</strong>
                            <span class="text-gray-500">0–49%</span> •
                            <span class="text-yellow-600">50–74%</span> •
                            <span class="text-blue-600">75–99%</span> •
                            <span class="text-green-600">100%</span>
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .tree-toggle[aria-expanded="true"] svg {
                transform: rotate(180deg);
            }

            .tree-toggle {
                transition: transform 0.2s ease;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Toggle single node
                document.querySelectorAll('.tree-toggle').forEach(button => {
                    button.addEventListener('click', function() {
                        const nodeId = this.dataset.nodeId;
                        const children = document.getElementById('children-' + nodeId);
                        if (!children) return;

                        const isExpanded = this.getAttribute('aria-expanded') === 'true';
                        const newState = !isExpanded;

                        children.style.display = newState ? 'block' : 'none';
                        this.setAttribute('aria-expanded', newState ? 'true' : 'false');
                    });
                });

                // Global controls
                window.expandAll = () => {
                    document.querySelectorAll('.tree-children').forEach(el => el.style.display = 'block');
                    document.querySelectorAll('.tree-toggle').forEach(btn => btn.setAttribute('aria-expanded',
                        'true'));
                };

                window.collapseAll = () => {
                    document.querySelectorAll('.tree-children').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.tree-toggle').forEach(btn => btn.setAttribute('aria-expanded',
                        'false'));
                };

                // Initialize collapsed state
                document.querySelectorAll('.tree-children').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.tree-toggle').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
            });
        </script>
    @endpush
</x-layouts.app>
