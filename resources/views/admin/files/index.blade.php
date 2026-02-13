<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.file.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.project.title') }} / {{ trans('global.file.title') }}
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
        <!-- Filters Section -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Directorate Filter -->
            <div>
                <label for="directorate-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('global.directorate.title_singular') }}
                </label>
                <select id="directorate-filter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">{{ __('All Directorates') }}</option>
                    @foreach ($directorates as $directorate)
                        <option value="{{ $directorate->id }}">{{ $directorate->title }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Project Filter -->
            <div>
                <label for="project-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ trans('global.project.title_singular') }}
                </label>
                <select id="project-filter"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">{{ __('All Projects') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" data-directorate="{{ $project->directorate_id }}">
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Filter -->
            <div>
                <label for="search-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Search Files') }}
                </label>
                <input type="text" id="search-filter" placeholder="{{ __('Search by filename...') }}"
                    class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            </div>
        </div>

        <!-- View Toggle & Results Info -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    {{ trans('global.file.fields.allFiles') }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    <span id="results-count">{{ $files->count() }}</span> {{ __('files found') }}
                </p>
            </div>
            <div class="flex space-x-2">
                <button id="grid-view-btn" class="p-2 rounded-md bg-blue-500 text-white"
                    aria-label="{{ trans('global.file.fields.gridView') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <button id="list-view-btn" class="p-2 rounded-md bg-gray-200 dark:bg-gray-700"
                    aria-label="{{ trans('global.file.fields.listView') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loading-spinner" class="hidden flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <!-- Files Container -->
        <div id="files-container">
            @include('admin.files.components.files-grid', ['groupedFiles' => $groupedFiles])
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="mt-6">
            {{ $files->links('admin.files.components.pagination') }}
        </div>

        <!-- Flash Messages -->
        <div id="flash-messages">
            @if (session('success'))
                <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                let currentView = 'grid';
                let filterTimeout = null;

                // Wait for jQuery
                function waitForJQuery() {
                    if (window.jQuery && document.readyState === 'complete') {
                        initializeFileView();
                    } else {
                        setTimeout(waitForJQuery, 50);
                    }
                }

                function initializeFileView() {
                    const $ = window.jQuery;

                    // View toggle handlers
                    $('#grid-view-btn').on('click', function() {
                        currentView = 'grid';
                        toggleView();
                        updateViewButtons();
                    });

                    $('#list-view-btn').on('click', function() {
                        currentView = 'list';
                        toggleView();
                        updateViewButtons();
                    });

                    // Filter handlers with debounce
                    $('#directorate-filter, #project-filter').on('change', function() {
                        loadFiles();
                    });

                    $('#search-filter').on('keyup', function() {
                        clearTimeout(filterTimeout);
                        filterTimeout = setTimeout(function() {
                            loadFiles();
                        }, 500);
                    });

                    // Directorate change - filter projects
                    $('#directorate-filter').on('change', function() {
                        const directorateId = $(this).val();
                        const $projectFilter = $('#project-filter');

                        $projectFilter.find('option').each(function() {
                            const $option = $(this);
                            if ($option.val() === '') {
                                $option.show();
                                return;
                            }

                            if (!directorateId || $option.data('directorate') == directorateId) {
                                $option.show();
                            } else {
                                $option.hide();
                            }
                        });

                        $projectFilter.val('');
                    });

                    // Initialize folder click handlers
                    initializeFolderHandlers();

                    // Initialize pagination handlers
                    initializePaginationHandlers();
                }

                function toggleView() {
                    const $ = window.jQuery;

                    if (currentView === 'grid') {
                        $('#grid-view').removeClass('hidden').show();
                        $('#list-view').addClass('hidden').hide();
                    } else {
                        $('#grid-view').addClass('hidden').hide();
                        $('#list-view').removeClass('hidden').show();
                    }
                }

                function updateViewButtons() {
                    const $ = window.jQuery;

                    if (currentView === 'grid') {
                        $('#grid-view-btn').addClass('bg-blue-500 text-white').removeClass('bg-gray-200 dark:bg-gray-700');
                        $('#list-view-btn').removeClass('bg-blue-500 text-white').addClass('bg-gray-200 dark:bg-gray-700');
                    } else {
                        $('#list-view-btn').addClass('bg-blue-500 text-white').removeClass('bg-gray-200 dark:bg-gray-700');
                        $('#grid-view-btn').removeClass('bg-blue-500 text-white').addClass('bg-gray-200 dark:bg-gray-700');
                    }
                }

                function loadFiles(page = 1) {
                    const $ = window.jQuery;
                    const directorateId = $('#directorate-filter').val();
                    const projectId = $('#project-filter').val();
                    const search = $('#search-filter').val();

                    // Show loading
                    $('#loading-spinner').removeClass('hidden');
                    $('#files-container').addClass('opacity-50');

                    $.ajax({
                        url: '{{ route('admin.file.index') }}',
                        type: 'GET',
                        data: {
                            directorate_id: directorateId,
                            project_id: projectId,
                            search: search,
                            view: currentView,
                            page: page,
                            ajax: 1
                        },
                        success: function(response) {
                            $('#files-container').html(response.html);
                            $('#pagination-container').html(response.pagination);
                            $('#results-count').text(response.count);

                            // Re-apply view toggle after AJAX load
                            toggleView();

                            // Re-initialize folder handlers
                            initializeFolderHandlers();

                            // Re-initialize pagination handlers
                            initializePaginationHandlers();
                        },
                        error: function(xhr) {
                            console.error('Error loading files:', xhr);
                            showFlashMessage('error', 'Failed to load files. Please try again.');
                        },
                        complete: function() {
                            $('#loading-spinner').addClass('hidden');
                            $('#files-container').removeClass('opacity-50');
                        }
                    });
                }

                function initializePaginationHandlers() {
                    const $ = window.jQuery;

                    // Handle pagination link clicks
                    $('#pagination-container').off('click', '.pagination-link').on('click', '.pagination-link', function(
                        e) {
                        e.preventDefault();
                        const url = $(this).attr('href');
                        const page = new URL(url).searchParams.get('page');
                        loadFiles(page);

                        // Scroll to top of files container
                        $('html, body').animate({
                            scrollTop: $('#files-container').offset().top - 100
                        }, 300);
                    });
                }

                function initializeFolderHandlers() {
                    const $ = window.jQuery;

                    // Grid view folder handlers
                    $('#grid-view .folder').off('click').on('click', function() {
                        const folderId = $(this).data('folder-id');
                        $(`#files-${folderId}`).slideToggle('fast');
                    });

                    // List view folder handlers
                    $('#list-view .folder').off('click').on('click', function() {
                        const folderId = $(this).data('folder-id');
                        $(`.file-row-${folderId}`).toggle('fast');
                    });
                }

                function showFlashMessage(type, message) {
                    const $ = window.jQuery;
                    const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' :
                        'bg-red-100 border-red-400 text-red-700';
                    const html = `
                        <div class="mt-6 ${bgColor} border px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">${message}</span>
                        </div>
                    `;
                    $('#flash-messages').html(html);

                    setTimeout(function() {
                        $('#flash-messages').fadeOut('slow', function() {
                            $(this).html('').show();
                        });
                    }, 3000);
                }

                // Start initialization
                waitForJQuery();
            })();
        </script>
    @endpush
</x-layouts.app>
