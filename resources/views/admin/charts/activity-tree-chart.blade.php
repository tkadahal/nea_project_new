<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Project Activity Structure
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Visualize the activity hierarchy for Project #{{ $projectId }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">

            <a href="{{ route('admin.project.show', $projectId) }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900 text-sm"
                aria-label="{{ trans('global.back_to_list') }}">
                {{ trans('global.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">

        @if ($capitalRoots->isNotEmpty() || $recurrentRoots->isNotEmpty())

            <div class="space-y-8">

                <!-- =========================
                     CAPITAL SECTION
                     ========================= -->
                @if ($capitalRoots->isNotEmpty())
                    <div>
                        <h2
                            class="text-yellow-700 dark:text-yellow-400 font-bold text-xl mb-4 border-b border-yellow-200 dark:border-yellow-900 pb-2">
                            Capital Activities
                        </h2>
                        <div class="space-y-2">
                            @foreach ($capitalRoots as $root)
                                <div class="root-item">
                                    <!-- Root Header -->
                                    <div onclick="toggleTree({{ $root->id }})"
                                        class="cursor-pointer flex items-center bg-yellow-50 dark:bg-yellow-900/10 p-3 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/20 transition-colors border border-yellow-200 dark:border-yellow-800">
                                        <span id="icon-{{ $root->id }}"
                                            class="text-yellow-600 font-bold mr-3 text-lg select-none">+</span>
                                        <span class="font-bold text-lg text-gray-800 dark:text-gray-200">
                                            {{ $root->program }}
                                        </span>
                                    </div>

                                    <!-- Hidden Children -->
                                    <div id="children-{{ $root->id }}"
                                        class="hidden ml-6 pl-4 border-l-2 border-yellow-300 dark:border-yellow-700 mt-2 space-y-2">
                                        @if ($root->children->isNotEmpty())
                                            @foreach ($root->children as $child)
                                                @include('admin.charts.components.tree-item', [
                                                    'item' => $child,
                                                ])
                                            @endforeach
                                        @else
                                            <p class="text-xs text-gray-400 italic ml-4">No sub-activities found.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif


                <!-- =========================
                     RECURRENT SECTION
                     ========================= -->
                @if ($recurrentRoots->isNotEmpty())
                    <div>
                        <h2
                            class="text-green-700 dark:text-green-400 font-bold text-xl mb-4 border-b border-green-200 dark:border-green-900 pb-2">
                            Recurrent Activities
                        </h2>
                        <div class="space-y-2">
                            @foreach ($recurrentRoots as $root)
                                <div class="root-item">
                                    <!-- Root Header -->
                                    <div onclick="toggleTree({{ $root->id }})"
                                        class="cursor-pointer flex items-center bg-green-50 dark:bg-green-900/10 p-3 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/20 transition-colors border border-green-200 dark:border-green-800">
                                        <span id="icon-{{ $root->id }}"
                                            class="text-green-600 font-bold mr-3 text-lg select-none">+</span>
                                        <span class="font-bold text-lg text-gray-800 dark:text-gray-200">
                                            {{ $root->program }}
                                        </span>
                                    </div>

                                    <!-- Hidden Children -->
                                    <div id="children-{{ $root->id }}"
                                        class="hidden ml-6 pl-4 border-l-2 border-green-300 dark:border-green-700 mt-2 space-y-2">
                                        @if ($root->children->isNotEmpty())
                                            @foreach ($root->children as $child)
                                                @include('admin.charts.components.tree-item', [
                                                    'item' => $child,
                                                ])
                                            @endforeach
                                        @else
                                            <p class="text-xs text-gray-400 italic ml-4">No sub-activities found.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        @else
            <div class="text-center py-10 text-gray-500">
                No activity structure found for this project.
            </div>
        @endif

    </div>

    <script>
        function toggleTree(id) {
            var container = document.getElementById('children-' + id);
            var icon = document.getElementById('icon-' + id);

            if (container) {
                if (container.classList.contains('hidden')) {
                    container.classList.remove('hidden');
                    if (icon) icon.textContent = '-';
                } else {
                    container.classList.add('hidden');
                    if (icon) icon.textContent = '+';
                }
            }
        }
    </script>
</x-layouts.app>
