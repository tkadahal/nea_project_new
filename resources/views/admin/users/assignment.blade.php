<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('Assign Users to Projects') }}</h1>
    </div>

    <div class="max-w-5xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <form action="{{ route('admin.users.assignUserToProject.store') }}" method="POST" id="assignment-form">
            @csrf

            <!-- User Selection -->
            <div class="mb-8">
                <label class="block text-sm font-medium mb-2">{{ __('1. Select User') }}</label>

                <div class="mb-4">
                    <x-forms.select label="{{ __('Filter by Directorate (optional)') }}" name="temp_user_directorate"
                        id="user-directorate-filter" :options="[['value' => '', 'label' => __('All Users')]] +
                            $directorates->map(fn($d) => ['value' => (string) $d->id, 'label' => $d->title])->all()" :selected="''" />
                </div>

                <x-forms.select name="user_id" id="user-select" placeholder="{{ __('Type to search users...') }}"
                    :options="[]" required />
            </div>

            <!-- Project Selection -->
            <div class="mb-8">
                <label class="block text-sm font-medium mb-2">{{ __('2. Select Project') }}</label>

                <div class="mb-4">
                    <x-forms.select label="{{ __('Filter by Directorate (optional)') }}" name="temp_project_directorate"
                        id="project-directorate-filter" :options="[['value' => '', 'label' => __('All Projects')]] +
                            $directorates->map(fn($d) => ['value' => (string) $d->id, 'label' => $d->title])->all()" :selected="''" />
                </div>

                <x-forms.select name="project_id" id="project-select"
                    placeholder="{{ __('Type to search projects...') }}" :options="[]" required />
            </div>

            <div class="text-center">
                <x-buttons.primary type="submit" class="px-8 py-3" id="submit-assignment" disabled>
                    {{ __('Complete Assignment') }}
                </x-buttons.primary>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function waitForJQuery(callback, retries = 50) {
                if (typeof jQuery !== "undefined" && jQuery.fn.jquery && document.readyState === "complete") {
                    console.log('jQuery loaded, version:', jQuery.fn.jquery);
                    callback();
                } else if (retries > 0) {
                    setTimeout(() => waitForJQuery(callback, retries - 1), 100);
                } else {
                    console.error("jQuery failed to load after retries.");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;

                // Get the actual container elements using data-name (this is how the working form does it)
                const $userSelectContainer = $('.js-single-select[data-name="user_id"]');
                const $projectSelectContainer = $('.js-single-select[data-name="project_id"]');

                // URLs from Blade
                const loadUsersUrl = '{{ route('admin.users.loadUsers', ':id') }}';
                const loadProjectsUrl = '{{ route('admin.users.loadProjects', ':id') }}';

                // Reusable function to update any single-select
                function updateSelectOptions($container, options, selected = null) {
                    if ($container.length === 0) {
                        console.error('Select container not found!');
                        return;
                    }

                    $container
                        .data('options', options)
                        .data('selected', selected)
                        .attr('data-selected', selected)
                        .trigger('options-updated', {
                            options: options,
                            selected: selected
                        });

                    console.log('Updated select:', $container.data('name'), 'Options:', options.length, 'Selected:',
                        selected);
                }

                // Load users when directorate filter changes
                $(document).on('change', 'input[name="temp_user_directorate"].js-hidden-input', function() {
                    const dirId = $(this).val() || '0';
                    const url = loadUsersUrl.replace(':id', dirId);

                    console.log('Loading users from:', url);

                    $.get(url).done(function(data) {
                        console.log('Users received:', data);
                        const options = data.map(u => ({
                            value: String(u.id),
                            label: `${u.name} (${u.employee_id || 'No ID'} - ${u.email})`
                        }));

                        updateSelectOptions($userSelectContainer, options, null);
                    }).fail(function() {
                        updateSelectOptions($userSelectContainer, [], null);
                    });
                });

                // Load projects when directorate filter changes
                $(document).on('change', 'input[name="temp_project_directorate"].js-hidden-input', function() {
                    const dirId = $(this).val() || '0';
                    const url = loadProjectsUrl.replace(':id', dirId);

                    console.log('Loading projects from:', url);

                    $.get(url).done(function(data) {
                        console.log('Projects received:', data);
                        const options = data.map(p => ({
                            value: String(p.id),
                            label: `${p.title} ${p.status ? '(' + p.status + ')' : ''}`
                        }));

                        updateSelectOptions($projectSelectContainer, options, null);
                    }).fail(function() {
                        updateSelectOptions($projectSelectContainer, [], null);
                    });
                });

                // Initial load — trigger both filters
                setTimeout(() => {
                    $('input[name="temp_user_directorate"].js-hidden-input').trigger('change');
                    $('input[name="temp_project_directorate"].js-hidden-input').trigger('change');
                }, 400);

                // Enable submit button when both are selected
                function checkSubmitButton() {
                    const userVal = $('input[name="user_id"].js-hidden-input').val();
                    const projectVal = $('input[name="project_id"].js-hidden-input').val();
                    $('#submit-assignment').prop('disabled', !(userVal && projectVal));
                }

                $(document).on('change',
                    'input[name="user_id"].js-hidden-input, input[name="project_id"].js-hidden-input',
                    checkSubmitButton);

                // Initial check
                checkSubmitButton();
            });
        </script>
    @endpush
</x-layouts.app>
