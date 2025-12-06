if (typeof jQuery === 'undefined') {
    console.log('jQuery is not loaded. Please ensure jQuery is included before this script.');
} else {
    console.log('jQuery version:', jQuery.fn.jquery);
    jQuery(document).ready(function($) {
        $('.js-multi-select').each(function() {
            const $container = $(this);
            const $dropdown = $container.find('.js-dropdown');
            const $optionsContainer = $container.find('.js-options-container');
            const $searchInput = $container.find('.js-search-input');
            const $noOptions = $container.find('.js-no-options');
            const name = $container.attr('data-name');
            const containerId = $container.attr('id');

            let options = [];
            let selectedOptions = [];

            try {
                options = $container.attr('data-options') ? JSON.parse($container.attr('data-options')) : [];
                if (!Array.isArray(options) || !options.every(opt => opt.value !== undefined && opt.label !== undefined)) {
                    console.error('Invalid options data for', name, options);
                    options = [];
                }
            } catch (e) {
                console.error('Error parsing data-options for multi-select:', e.message, 'Container:', containerId);
                options = [];
            }

            try {
                selectedOptions = $container.attr('data-selected') ? JSON.parse($container.attr('data-selected')) : [];
                selectedOptions = selectedOptions
                    .flat(Infinity)
                    .filter(val => val !== null && val !== undefined && val !== '' && !val.includes('@'))
                    .map(val => String(val));
            } catch (e) {
                console.error('Error parsing data-selected for multi-select:', e.message, 'Container:', containerId);
                selectedOptions = [];
            }

            if (name === 'roles' || name === 'projects') {
                console.log(`${name} options:`, options);
                console.log(`${name} selected:`, selectedOptions);
            }

            const placeholder = $container.attr('data-placeholder');

            function renderOptions() {
                $optionsContainer.empty();
                const filteredOptions = options.filter(opt =>
                    !selectedOptions.includes(String(opt.value)) &&
                    opt.label.toLowerCase().includes($searchInput.val().toLowerCase())
                );

                if (filteredOptions.length === 0) {
                    $noOptions.removeClass('hidden');
                } else {
                    $noOptions.addClass('hidden');
                    filteredOptions.forEach(opt => {
                        $optionsContainer.append(
                            `<div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${opt.value}">
                                ${opt.label}
                            </div>`
                        );
                    });
                }
            }

            function renderSelectedOptions() {
                const $selectedContainer = $container.find('.js-multi-select-container');
                $selectedContainer.find('.js-selected-option').remove();
                selectedOptions.forEach(value => {
                    value = String(value);
                    const option = options.find(opt => String(opt.value) === value);
                    const label = option ? option.label : `Unknown (ID: ${value})`;
                    const $optionSpan = $(`<span class="js-selected-option inline-flex items-center px-2 py-1 bg-gray-200 text-gray-700 text-sm rounded-md dark:bg-gray-600 dark:text-gray-300 m-1" data-value="${value}">
                        <span>${label}</span>
                        <button type="button" class="js-remove-option ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>`);
                    $selectedContainer.prepend($optionSpan);
                });

                const $hiddenInputs = $container.find('input[type="hidden"]');
                $hiddenInputs.remove();
                selectedOptions.forEach(value => {
                    $container.append(
                        `<input type="hidden" name="${name}[]" value="${value}">`
                    );
                });

                $searchInput.attr('placeholder', selectedOptions.length ? '' : placeholder);
            }

            renderOptions();
            renderSelectedOptions();

            $searchInput.on('focus', function() {
                $dropdown.removeClass('hidden');
                renderOptions();
            });

            $(document).on('click', function(e) {
                if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                    $dropdown.addClass('hidden');
                }
            });

            $searchInput.on('input', function() {
                renderOptions();
            });

            $searchInput.on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $dropdown.addClass('hidden');
                }
            });

            $optionsContainer.on('click', '.js-option', function() {
                const value = $(this).data('value');
                if (!selectedOptions.includes(String(value))) {
                    selectedOptions.push(String(value));
                    renderSelectedOptions();
                    renderOptions();
                    $searchInput.val('');
                }
            });

            $container.on('click', '.js-remove-option', function() {
                const value = $(this).parent().data('value');
                selectedOptions = selectedOptions.filter(opt => opt !== String(value));
                renderSelectedOptions();
                renderOptions();
            });

            $container.find('.js-select-all').on('click', function(e) {
                e.preventDefault();
                selectedOptions = options.map(opt => String(opt.value));
                renderSelectedOptions();
                renderOptions();
            });

            $container.find('.js-deselect-all').on('click', function(e) {
                e.preventDefault();
                selectedOptions = [];
                renderSelectedOptions();
                renderOptions();
            });

            if (containerId === 'projects') {
                const oldProjects = @json(old('projects', []));
                $container.on('projects-updated', function(event, data) {
                    console.log('Projects-updated event triggered with:', data);
                    options = data.options || [];
                    selectedOptions = (data.selected || []).filter(val =>
                        val !== null && val !== undefined && val !== '' &&
                        options.some(opt => String(opt.value) === String(val))
                    );
                    selectedOptions = selectedOptions.map(String);
                    renderOptions();
                    renderSelectedOptions();
                });
            }
        });

        // AJAX handler for projects
        $('input[name="directorate_id"].js-hidden-input').on('change', function() {
            const directorateId = $(this).val();
            console.log('Initiating AJAX for directorate:', directorateId);

            if (directorateId) {
                $.ajax({
                    url: '/api/projects/' + encodeURIComponent(directorateId),
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        console.log('Raw API response:', data);
                        if (Array.isArray(data) && data.length > 0) {
                            console.log('First project object keys:', Object.keys(data[0]));
                            console.log('First project object:', data[0]);
                        }

                        const formattedData = Array.isArray(data) ? data.map(project => ({
                            value: String(project.value),
                            label: String(project.label)
                        })).filter(opt => opt.value && opt.label) : [];

                        if (Array.isArray(formattedData) && formattedData.length > 0) {
                            const validOldProjects = @json(old('projects', [])).filter(projectId =>
                                formattedData.some(opt => String(opt.value) === String(projectId))
                            );
                            $('#projects').trigger('projects-updated', {
                                options: formattedData,
                                selected: validOldProjects
                            });
                            console.log('Projects fetched:', formattedData, 'Preserved selected:', validOldProjects);
                        } else {
                            $('#error-message').removeClass('hidden');
                            $('#error-text').text('No valid projects found in API response or after mapping.');
                            $('#projects').trigger('projects-updated', {
                                options: [],
                                selected: []
                            });
                            console.log('No valid projects after mapping:', formattedData);
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX error:', xhr.status, xhr.statusText, xhr.responseJSON);
                        $('#error-message').removeClass('hidden');
                        $('#error-text').text('Failed to load projects: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        $('#projects').trigger('projects-updated', {
                            options: [],
                            selected: []
                        });
                    }
                });
            } else {
                $('#projects').trigger('projects-updated', {
                    options: [],
                    selected: []
                });
                console.log('Directorate cleared, projects reset');
            }
        });

        $('#close-error').on('click', function() {
            $('#error-message').addClass('hidden');
            $('#error-text').text('');
        });

        const preSelected = $('input[name="directorate_id"].js-hidden-input').val();
        if (preSelected) {
            console.log('Triggering initial change for pre-selected directorate:', preSelected);
            $('input[name="directorate_id"].js-hidden-input').trigger('change');
        }
    });
}