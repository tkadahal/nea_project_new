<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.user.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.edit') }} {{ trans('global.user.title_singular') }}
            </p>
        </div>
        <a href="{{ route('admin.user.index') }}"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300
                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-900">
            {{ trans('global.back_to_list') }}
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                <form class="max-w-3xl mx-auto" action="{{ route('admin.user.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-800 border border-red-300 rounded">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="error-message"
                        class="mb-4 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative">
                        <span id="error-text"></span>
                        <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.user.title_singular') }} {{ trans('global.information') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.input label="{{ trans('global.user.fields.employee_id') }}" name="employee_id"
                                    type="number" :value="old('employee_id', $user->employee_id)" :error="$errors->first('employee_id')" />
                            </div>
                            <div>
                                <x-forms.input label="{{ trans('global.user.fields.name') }}" name="name"
                                    type="text" :value="old('name', $user->name)" :error="$errors->first('name')" />
                            </div>
                            <div>
                                <x-forms.input label="{{ trans('global.user.fields.mobile_number') }}"
                                    name="mobile_number" type="number" :value="old('mobile_number', $user->mobile_number)" :error="$errors->first('mobile_number')" />
                            </div>
                            <div>
                                <x-forms.input label="{{ trans('global.user.fields.email') }}" name="email"
                                    type="email" :value="old('email', $user->email)" :error="$errors->first('email')" />
                            </div>
                            <div>
                                <x-forms.input label="{{ trans('global.user.fields.password') }}" name="password"
                                    type="password" :error="$errors->first('password')" />
                            </div>
                            @if (!$isDirectorateOrProjectUser)
                                <div>
                                    <x-forms.multi-select label="{{ trans('global.user.fields.roles') }}"
                                        name="roles[]" id="roles" :options="collect($roles)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()" :selected="old(
                                            'roles',
                                            $user->roles->pluck('id')->map(fn($id) => (string) $id)->toArray(),
                                        )"
                                        placeholder="Select role" :error="$errors->first('roles')" />
                                </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $authUser = Auth::user();
                        $isSameUser = $authUser->id === $user->id;

                        $isRole4 = $authUser->roles->contains('id', 4);

                        $isProjectManager = isset($project) && $authUser->id === optional($project->projectManager)->id;
                    @endphp

                    @if (!$isSameUser && (!$isRole4 || $isProjectManager))
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                {{ __('Assignments') }}
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                @if (!$isDirectorateOrProjectUser)
                                    <div>
                                        <x-forms.select label="{{ trans('global.user.fields.directorate_id') }}"
                                            name="directorate_id" id="directorate_id" :options="collect($directorates)
                                                ->map(
                                                    fn($label, $value) => [
                                                        'value' => (string) $value,
                                                        'label' => $label,
                                                    ],
                                                )
                                                ->values()
                                                ->all()"
                                            :selected="old('directorate_id', (string) $user->directorate_id)" placeholder="Select directorate" :error="$errors->first('directorate_id')" />
                                    </div>
                                @endif
                                <div>
                                    <x-forms.multi-select label="{{ trans('global.user.fields.projects') }}"
                                        name="projects[]" id="projects" :options="collect($projects)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()" :selected="old(
                                            'projects',
                                            $user->projects->pluck('id')->map(fn($id) => (string) $id)->toArray(),
                                        )"
                                        placeholder="Select projects" :error="$errors->first('projects')" />
                                </div>
                            </div>
                        </div>
                    @endif


                    <div class="flex space-x-2">
                        <x-buttons.primary>
                            {{ trans('global.save') }}
                        </x-buttons.primary>
                        <a href="{{ route('admin.user.index') }}"
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            {{ trans('global.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function waitForJQuery(callback, retries = 50, interval = 100) {
                if (typeof jQuery !== "undefined" && jQuery.fn.jquery && document.readyState !== "loading") {
                    console.log("jQuery and DOM ready. jQuery version:", jQuery.fn.jquery, "DOM state:", document.readyState);
                    callback();
                } else if (retries > 0) {
                    console.warn("jQuery or DOM not ready, retrying... jQuery:", typeof jQuery !== "undefined" ? jQuery.fn
                        .jquery : "undefined", "DOM:", document.readyState, "Retries left:", retries);
                    setTimeout(function() {
                        waitForJQuery(callback, retries - 1, interval);
                    }, interval);
                } else {
                    console.error("Failed to load jQuery or DOM after maximum retries.");
                    $("#error-message").removeClass("hidden").find("#error-text").text(
                        "Failed to initialize form due to missing jQuery or DOM.");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;

                window.debugProjectsData = [];

                const $projectsContainer = $('.js-multi-select[data-name="projects"]');
                console.log('Projects container found:', $projectsContainer.length, 'ID:', $projectsContainer.attr(
                    'id'));

                const userProjects = @json($user->projects->pluck('id')->map(fn($id) => (string) $id)->toArray());
                console.log('Initial user projects:', userProjects);

                const initialOptions = @json(collect($projects)->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all());
                console.log('Initial options:', initialOptions);

                $projectsContainer
                    .data('options', initialOptions)
                    .data('selected', userProjects)
                    .trigger('options-updated', {
                        options: initialOptions,
                        selected: userProjects
                    });
                console.log('Initial projects set:', initialOptions, 'Selected:', userProjects);

                @if (!$isDirectorateOrProjectUser)
                    $('input[name="directorate_id"].js-hidden-input').on('change', function() {
                        const directorateId = $(this).val();
                        console.log('Directorate changed:', directorateId);

                        if (!directorateId || isNaN(directorateId) || directorateId <= 0) {
                            console.log('No valid directorate_id, resetting projects');
                            $projectsContainer
                                .data('options', [])
                                .data('selected', [])
                                .trigger('options-updated', {
                                    options: [],
                                    selected: []
                                });
                            $('#error-message').addClass('hidden').find('#error-text').text('');
                            return;
                        }

                        const $optionsContainer = $projectsContainer.find('.js-options-container');
                        $optionsContainer.empty().append(
                            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>'
                        );

                        const projectsUrl = '{{ route('admin.users.projects', ':directorateId') }}'.replace(
                            ':directorateId', encodeURIComponent(directorateId));
                        console.log('Projects AJAX URL:', projectsUrl);

                        $.ajax({
                            url: projectsUrl,
                            type: 'GET',
                            dataType: 'json',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            success: function(data) {
                                console.log('Projects AJAX success:', data);
                                const formattedData = Array.isArray(data) ?
                                    data.map(project => ({
                                        value: String(project.value),
                                        label: String(project.label)
                                    })).filter(opt => opt.value && opt.label) : [];
                                console.log('Formatted projects:', formattedData);
                                window.debugProjectsData = formattedData;
                                const validOldProjects = @json(old('projects', []))
                                    .length > 0 ?
                                    @json(old('projects', [])) :
                                    userProjects;
                                const validProjects = validOldProjects.filter(projectId =>
                                    formattedData.some(opt => String(opt.value) === String(
                                        projectId))
                                );
                                console.log('Valid projects:', validProjects);
                                $projectsContainer
                                    .data('options', formattedData)
                                    .data('selected', validProjects)
                                    .trigger('options-updated', {
                                        options: formattedData,
                                        selected: validProjects
                                    });
                                if (formattedData.length === 0) {
                                    $('#error-message').removeClass('hidden').find('#error-text')
                                        .text(
                                            'No projects available for the selected directorate.'
                                        );
                                } else {
                                    $('#error-message').addClass('hidden').find('#error-text').text(
                                        '');
                                }
                            },
                            error: function(xhr) {
                                console.error('Projects AJAX error:', xhr.status, xhr.statusText,
                                    xhr.responseJSON);
                                $projectsContainer
                                    .data('options', [])
                                    .data('selected', [])
                                    .trigger('options-updated', {
                                        options: [],
                                        selected: []
                                    });
                                $('#error-message').removeClass('hidden').find('#error-text').text(
                                    'Failed to load projects: ' + (xhr.responseJSON?.message ||
                                        'Unknown error')
                                );
                            }
                        });
                    });

                    const preSelected = $('input[name="directorate_id"].js-hidden-input').val();
                    if (preSelected && !isNaN(preSelected) && preSelected > 0) {
                        console.log('Initial directorate:', preSelected);
                        setTimeout(() => {
                            $('input[name="directorate_id"].js-hidden-input').trigger('change');
                        }, 200);
                    } else {
                        console.log('No valid pre-selected directorate, using initial projects');
                    }
                @endif

                $('#close-error').on('click', function() {
                    $('#error-message').addClass('hidden').find('#error-text').text('');
                });
            });
        </script>
    @endpush
</x-layouts.app>
