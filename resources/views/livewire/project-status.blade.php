<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700 col-span-12 lg:col-span-4"
        id="project-status-component">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-base sm:text-sm font-semibold text-gray-800 dark:text-white">
                {{ trans('global.project.title_singular') }} {{ trans('global.status.title_singular') }}
            </h2>
            <div class="relative" wire:ignore>
                <button
                    class="dropdown-toggle-project text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </button>
                <div
                    class="dropdown-menu-project hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl z-[1000]">
                    <div class="p-3">
                        @if (!empty($availableDirectorates))
                            <div class="mb-3">
                                <input type="text" id="project-directorate-search"
                                    class="w-full text-sm text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded-md px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                    placeholder="Search directorates...">
                            </div>
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                {{ trans('global.filterDirectorate') }}
                            </h3>
                            <div class="max-h-40 overflow-y-auto custom-scroll">
                                <button wire:click="$set('directorateFilter', null)"
                                    class="directorate-option-project block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md"
                                    data-filter="all" data-name="All Directorates">
                                    {{ trans('global.allDirectorate') }}
                                </button>
                                @foreach ($availableDirectorates as $id => $name)
                                    <button wire:click="$set('directorateFilter', {{ $id }})"
                                        class="directorate-option-project block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md"
                                        data-filter="{{ $id }}" data-name="{{ $name }}">
                                        {{ $name }}
                                    </button>
                                @endforeach
                            </div>
                            <div id="project-no-results"
                                class="hidden px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ trans('global.noRecords') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center mb-4">
            <div class="relative w-full max-w-[140px] sm:max-w-[160px] aspect-square">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white"
                            id="completed-percentage">
                            {{ $project_status['completed'] }}%
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ trans('global.completed') }}
                        </p>
                    </div>
                </div>
                <div wire:ignore>
                    <canvas id="projectStatusChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
        <div class="flex justify-around text-xs sm:text-sm text-gray-600 dark:text-gray-400">
            <div class="text-center">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-green-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                <span>
                    {{ trans('global.completed') }}
                </span>
                <p class="text-gray-800 dark:text-white font-medium" id="completed-text">
                    {{ $project_status['completed'] }}%
                </p>
            </div>
            <div class="text-center">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-purple-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                <span>
                    {{ trans('global.inProgress') }}
                </span>
                <p class="text-gray-800 dark:text-white font-medium" id="in-progress-text">
                    {{ $project_status['in_progress'] }}%</p>
            </div>
            <div class="text-center">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-red-500 rounded-full inline-block mr-1 sm:mr-2"></div>
                <span>
                    {{ trans('global.behind') }}
                </span>
                <p class="text-gray-800 dark:text-white font-medium" id="behind-text">{{ $project_status['behind'] }}%
                </p>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Debug: {{ trans('global.completed') }}={{ $project_status['completed'] }},
            {{ trans('global.inProgress') }}={{ $project_status['in_progress'] }},
            {{ trans('global.behind') }}={{ $project_status['behind'] }}
        </p>
    </div>

    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #e5e7eb;
            /* gray-200 */
            border-radius: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #9ca3af;
            /* gray-400 */
            border-radius: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
            /* gray-500 */
        }

        .dark .custom-scroll::-webkit-scrollbar-track {
            background: #1f2937;
            /* gray-800 */
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb {
            background: #4b5563;
            /* gray-600 */
            border-radius: 4px;
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #374151;
            /* gray-700 */
        }

        /* Firefox */
        .custom-scroll {
            scrollbar-width: thin;
            scrollbar-color: #9ca3af #e5e7eb;
            /* thumb: gray-400, track: gray-200 */
        }

        .dark .custom-scroll {
            scrollbar-color: #4b5563 #1f2937;
            /* thumb: gray-600, track: gray-800 */
        }
    </style>

    <script>
        let projectStatusChart = null;

        function waitForjQuery(callback) {
            if (typeof jQuery !== 'undefined' && jQuery.fn.jquery === '3.7.1') {
                callback(jQuery);
            } else {
                setTimeout(() => waitForjQuery(callback), 100);
            }
        }

        waitForjQuery(function($) {
            const $projectContainer = $('#project-status-component');

            $projectContainer.find('.dropdown-toggle-project').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $dropdown = $projectContainer.find('.dropdown-menu-project');
                $dropdown.toggleClass('hidden');
                console.log('Project dropdown toggled, visible:', !$dropdown.hasClass('hidden'),
                    'position:', $dropdown.position());
                $projectContainer.find('#project-directorate-search').val('');
                $projectContainer.find('.directorate-option-project').removeClass('hidden');
                $projectContainer.find('#project-no-results').addClass('hidden');
            });

            $(document).on('click', function(event) {
                if (!$(event.target).closest('.dropdown-toggle-project, .dropdown-menu-project').length) {
                    $projectContainer.find('.dropdown-menu-project').addClass('hidden');
                    console.log('Project dropdown closed');
                }
            });

            $projectContainer.find('.directorate-option-project').on('click', function() {
                const filterValue = $(this).data('filter');
                const $dropdown = $projectContainer.find('.dropdown-menu-project');
                $dropdown.addClass('hidden'); // Close dropdown after selection
                console.log('Project filter clicked:', filterValue, 'Dropdown closed');
            });

            $projectContainer.find('#project-directorate-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                console.log('Project search term:', searchTerm);
                let visibleCount = 0;

                $projectContainer.find('.directorate-option-project').each(function() {
                    const name = $(this).data('name').toLowerCase();
                    const isAllDirectorates = $(this).data('filter') === 'all';
                    if (isAllDirectorates || name.includes(searchTerm)) {
                        $(this).removeClass('hidden');
                        visibleCount++;
                    } else {
                        $(this).addClass('hidden');
                    }
                });

                $projectContainer.find('#project-no-results').toggleClass('hidden', visibleCount > 0);
            });

            window.initProjectStatusChart = function(completed, inProgress, behind) {
                console.log('Updating chart with:', completed, inProgress, behind);
                const ctx = document.getElementById('projectStatusChart');
                if (!ctx) {
                    console.error('Canvas element not found');
                    return;
                }

                const context = ctx.getContext('2d');
                const data = {
                    datasets: [{
                        data: [completed, inProgress, behind],
                        backgroundColor: ['#10b981', '#8b5cf6', '#ef4444'],
                        borderWidth: 0,
                    }],
                    labels: ['Completed', 'In-progress', 'Behind']
                };

                if (projectStatusChart) {
                    projectStatusChart.data.datasets[0].data = data.datasets[0].data;
                    projectStatusChart.update();
                } else {
                    projectStatusChart = new Chart(context, {
                        type: 'doughnut',
                        data: data,
                        options: {
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: true
                                }
                            },
                            maintainAspectRatio: true,
                            responsive: true
                        }
                    });
                }
            };

            window.initProjectStatusChart({{ $project_status['completed'] }},
                {{ $project_status['in_progress'] }}, {{ $project_status['behind'] }});

            const component = document.getElementById('project-status-component');
            if (component) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            const completed = parseInt($projectContainer.find('#completed-text')
                                .text()) || 0;
                            const inProgress = parseInt($projectContainer.find('#in-progress-text')
                                .text()) || 0;
                            const behind = parseInt($projectContainer.find('#behind-text')
                                .text()) || 0;
                            console.log('Project DOM updated, new values:', completed, inProgress,
                                behind);
                            window.initProjectStatusChart(completed, inProgress, behind);
                        }
                    });
                });
                observer.observe(component, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }
        });
    </script>
</div>
