<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.task.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.task.title_singular') }}
        </p>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <form id="task-form" class="w-full" action="{{ route('admin.task.store') }}" method="POST">
            @csrf

            @if ($errors->any())
                <div
                    class="col-span-full mb-6 p-4 bg-red-100 text-red-800 border border-red-300 rounded-lg dark:bg-red-900 dark:text-red-200 dark:border-red-700">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="error-message"
                class="col-span-full mb-6 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                <span id="error-text"></span>
                <button type="button" id="close-error" class="absolute top-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>

            @php
                $parentId = old('parent_id', request()->query('parent_id', $preselectedData['parent_id'] ?? null));
                $parentTitle = $parentId && isset($tasks[$parentId]) ? $tasks[$parentId] : null;
            @endphp

            @if ($parentId && $parentTitle)
                <div
                    class="mb-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg border border-blue-200 dark:border-blue-700">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ trans('global.task.fields.subtask_of') }}: <span
                            class="font-semibold">{{ $parentTitle }}</span>
                    </p>
                    <input type="hidden" name="parent_id" value="{{ $parentId }}">
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.task.title_singular') }} {{ trans('global.information') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.task.fields.directorate_id') }}"
                                    name="directorate_id" id="directorate_id" :options="collect($directorates)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('directorate_id', $preselectedData['directorate_id'] ?? '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('directorate_id')"
                                    :disabled="$preselectedData !== null" class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.task.fields.department_id') }}"
                                    name="department_id" id="department_id" :options="isset($preselectedData['department_id']) &&
                                    $preselectedData['department_id']
                                        ? collect($departments)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()
                                        : []" :selected="old('department_id', $preselectedData['department_id'] ?? '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('department_id')"
                                    class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.task.fields.project_id') }}"
                                    name="projects[]" id="projects" :options="$preselectedData && isset($preselectedData['projects'])
                                        ? collect($projects)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()
                                        : []" :selected="old('projects', $preselectedData['projects'] ?? [])"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('projects')"
                                    :disabled="$preselectedData !== null" class="js-multi-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.multi-select label="{{ trans('global.task.fields.user_id') }}" name="users[]"
                                    id="users" :options="$preselectedData && isset($preselectedData['users'])
                                        ? collect($users)
                                            ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                            ->values()
                                            ->all()
                                        : []" :selected="old('users', $preselectedData['users'] ?? [])"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('users')"
                                    class="js-multi-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.task.fields.title') }}" name="title"
                                    type="text" :value="old('title', '')" placeholder="Enter task title"
                                    :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.task.fields.description') }}"
                                    name="description" :value="old('description', '')" placeholder="Enter task description"
                                    :error="$errors->first('description')" rows="5" />
                            </div>

                            <input type="hidden" name="assigned_by" value="{{ Auth::id() }}">
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.task.headers.date_progress') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <x-forms.date-input label="{{ trans('global.task.fields.start_date') }}"
                                    name="start_date" :value="old('start_date', '')" :error="$errors->first('start_date')" />
                            </div>
                            <div>
                                <x-forms.date-input label="{{ trans('global.task.fields.due_date') }}" name="due_date"
                                    :value="old('due_date', '')" :error="$errors->first('due_date')" />
                            </div>
                            <div>
                                <x-forms.date-input label="{{ trans('global.task.fields.completion_date') }}"
                                    name="completion_date" :value="old('completion_date', '')" :error="$errors->first('completion_date')" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.task.headers.status_priority') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.select label="{{ trans('global.task.fields.status_id') }}" name="status_id"
                                    id="status_id" :options="collect($statuses)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('status_id', collect($statuses)->keys()->first())" :error="$errors->first('status_id')"
                                    class="js-single-select" />
                            </div>
                            <div>
                                <x-forms.select label="{{ trans('global.task.fields.priority_id') }}"
                                    name="priority_id" id="priority_id" :options="collect($priorities)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('priority_id', '')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('priority_id')"
                                    class="js-single-select" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.task.headers.subTasks') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div id="subtasks-container" class="space-y-2">
                                <!-- Dynamic subtask checkboxes will be added here -->
                            </div>
                            <div class="flex items-center space-x-4">
                                <input type="text" id="subtask-title-input"
                                    class="block w-full p-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="{{ trans('global.task.fields.subtask_title_placeholder') }}" />
                                <button type="button" id="add-subtask"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                    {{ trans('global.add') }}
                                </button>
                            </div>
                            <input type="hidden" name="subtasks" id="subtasks-hidden">
                            @if ($errors->first('subtasks.*'))
                                <p class="text-red-500 text-sm mt-2">{{ $errors->first('subtasks.*') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <x-buttons.primary id="submit-button" type="submit" :disabled="false">
                    {{ trans('global.save') }}
                </x-buttons.primary>
            </div>
        </form>
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
                        "{{ trans('global.task.errors.form_init_failed') }}");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;

                // Form submission handling
                const $form = $('#task-form');
                const $submitButton = $('#submit-button');
                const $errorMessage = $('#error-message');
                const $errorText = $('#error-text');
                const $closeError = $('#close-error');

                function showError(message) {
                    $errorText.text(message);
                    $errorMessage.removeClass('hidden');
                }

                function hideError() {
                    $errorMessage.addClass('hidden');
                    $errorText.text('');
                }

                $closeError.on('click', hideError);

                $form.on('submit', function(e) {
                    e.preventDefault();
                    console.log('Form submit attempted');

                    if ($submitButton.prop('disabled')) {
                        console.log('Form submission prevented: button is disabled');
                        return;
                    }

                    const directorateId = $('input[name="directorate_id"].js-hidden-input').val();
                    const title = $('input[name="title"]').val();
                    const users = $('.js-multi-select[data-name="users"]').data('selected') || [];
                    const startDate = $('input[name="start_date"]').val();
                    const statusId = $('input[name="status_id"].js-hidden-input').val();
                    const priorityId = $('input[name="priority_id"].js-hidden-input').val();

                    if (!directorateId || !title || !users.length || !startDate || !statusId || !priorityId) {
                        showError('{{ trans('global.task.errors.missing_fields') }}');
                        console.log('Form submission prevented: missing required fields', {
                            directorateId,
                            title,
                            users,
                            startDate,
                            statusId,
                            priorityId
                        });
                        return;
                    }

                    $submitButton
                        .prop('disabled', true)
                        .addClass('opacity-50 cursor-not-allowed')
                        .text('{{ trans('global.saving') }}...');
                    console.log('Submit button disabled');

                    $.ajax({
                        url: $form.attr('action'),
                        method: 'POST',
                        data: $form.serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            console.log('Form submission success:', response);
                            hideError();
                            window.location.href = '{{ route('admin.task.index') }}';
                        },
                        error: function(xhr) {
                            console.error('Form submission error:', xhr.status, xhr.responseJSON);
                            $submitButton
                                .prop('disabled', false)
                                .removeClass('opacity-50 cursor-not-allowed')
                                .text('{{ trans('global.save') }}');
                            showError(
                                xhr.responseJSON?.message ||
                                '{{ trans('global.task.errors.create_failed') }}'
                            );
                        }
                    });
                });

                // Debounce function
                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                }

                // Subtasks management
                const $subtasksContainer = $("#subtasks-container");
                const $subtasksHiddenInput = $("#subtasks-hidden");
                const $subtaskTitleInput = $("#subtask-title-input");
                let subtaskCounter = 0;

                function updateSubtasksHiddenInput() {
                    const subtasks = [];
                    $subtasksContainer.find(".subtask-checkbox").each(function() {
                        const $checkbox = $(this).find('input[type="checkbox"]');
                        const title = $checkbox.data('title').trim();
                        const completed = $checkbox.prop('checked');
                        if (title) {
                            subtasks.push({
                                title,
                                completed
                            });
                        }
                    });
                    $subtasksHiddenInput.val(JSON.stringify(subtasks));
                }

                function addSubtask(title = "", completed = false) {
                    if (!title) return; // Prevent adding empty subtasks
                    const subtaskId = `subtask-${subtaskCounter++}`;
                    const $subtask = $(`
                        <div class="subtask-checkbox flex items-center space-x-3 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                            <input type="checkbox" class="h-5 w-5 text-blue-600 dark:text-blue-500 border-gray-300 rounded"
                                   ${completed ? 'checked' : ''} data-title="${title.replace(/"/g, '&quot;')}">
                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">${title}</span>
                            <button type="button" class="remove-subtask text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `);
                    $subtasksContainer.append($subtask);
                    updateSubtasksHiddenInput();

                    // Add event listeners for checkbox changes
                    $subtask.find('input[type="checkbox"]').on("change", updateSubtasksHiddenInput);

                    // Add remove event listener
                    $subtask.find(".remove-subtask").on("click", function() {
                        $subtask.remove();
                        updateSubtasksHiddenInput();
                    });
                }

                $("#add-subtask").on("click", function() {
                    const title = $subtaskTitleInput.val().trim();
                    if (title) {
                        addSubtask(title);
                        $subtaskTitleInput.val(""); // Clear input after adding
                        hideError();
                    } else {
                        showError("{{ trans('global.task.errors.subtask_title_required') }}");
                    }
                });

                // Allow adding subtask with Enter key
                $subtaskTitleInput.on("keypress", function(e) {
                    if (e.which === 13) { // Enter key
                        e.preventDefault();
                        $("#add-subtask").trigger("click");
                    }
                });

                // Initialize with any old subtask data
                const oldSubtasks = @json(old('subtasks', []));
                if (oldSubtasks.length > 0) {
                    oldSubtasks.forEach(subtask => {
                        addSubtask(subtask.title || "", subtask.completed || false);
                    });
                }

                // Initialize single-select dropdowns
                $(".js-single-select").each(function() {
                    const $container = $(this);
                    const componentId = $container.attr("id");
                    const dataName = $container.data("name");
                    let currentOptions = $container.data("options") || [];
                    let currentSelectedValue = $container.data("selected") || "";
                    const $optionsContainer = $container.find(".js-options-container");
                    const $selectedLabel = $container.find(".js-selected-label");
                    const $hiddenInput = $container.find(".js-hidden-input");
                    const $dropdown = $container.find(".js-dropdown");
                    const $searchInput = $container.find(".js-search-input");

                    if (!$optionsContainer.length || !$hiddenInput.length) {
                        console.error(`Required elements not found in container #${componentId}. HTML:`,
                            $container[0].outerHTML);
                        return;
                    }

                    function renderOptions(searchTerm = "") {
                        $optionsContainer.empty();
                        $container.find(".js-no-options").toggleClass("hidden", currentOptions.length > 0);
                        if (!currentOptions.length) {
                            $selectedLabel.text($container.data("placeholder") || "Select an option");
                            $hiddenInput.val("");
                            currentSelectedValue = "";
                            $container.data("selected", currentSelectedValue);
                            $container.attr("data-selected", "");
                            return;
                        }
                        const filteredOptions = searchTerm ?
                            currentOptions.filter((opt) => opt.label.toLowerCase().includes(searchTerm
                                .toLowerCase())) :
                            currentOptions;
                        $.each(filteredOptions, function(index, option) {
                            const $option = $(`
                                <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `);
                            if (currentSelectedValue === String(option.value)) {
                                $option.addClass("bg-blue-100 dark:bg-blue-900");
                            }
                            $optionsContainer.append($option);
                        });
                        const selectedOption = currentOptions.find((opt) => String(opt.value) === String(
                            currentSelectedValue));
                        $selectedLabel.text(selectedOption ? selectedOption.label : $container.data(
                            "placeholder") || "Select an option");
                        $hiddenInput.val(selectedOption ? currentSelectedValue : "");
                        if (!selectedOption) {
                            currentSelectedValue = "";
                            $container.data("selected", "");
                            $container.attr("data-selected", "");
                        }
                    }

                    function updateHiddenInput() {
                        $hiddenInput.val(currentSelectedValue || "").trigger("change");
                    }

                    $container.off("options-updated").on("options-updated", function(event, data) {
                        console.log(`Updating options for #${componentId}:`, data.options);
                        currentOptions = data?.options || [];
                        currentSelectedValue = data?.selected && currentOptions.some((opt) => String(opt
                                .value) === String(data.selected)) ?
                            String(data.selected) :
                            "";
                        $container.data("selected", currentSelectedValue);
                        $container.attr("data-selected", currentSelectedValue);
                        renderOptions();
                        updateHiddenInput();
                    });

                    $optionsContainer.off("click", ".js-option").on("click", ".js-option", function(e) {
                        e.stopPropagation();
                        const $option = $(this);
                        const value = String($option.data("value"));
                        currentSelectedValue = value;
                        $container.data("selected", currentSelectedValue);
                        $container.attr("data-selected", currentSelectedValue);
                        $selectedLabel.text($option.text().trim());
                        $dropdown.addClass("hidden");
                        updateHiddenInput();
                    });

                    $searchInput.off("input").on("input", function() {
                        renderOptions($(this).val());
                    });

                    $container.find(".js-toggle-dropdown").off("click").on("click", function(e) {
                        e.stopPropagation();
                        if ($container.find("select").prop("disabled"))
                            return;
                        $(".js-dropdown").not($dropdown).addClass("hidden");
                        $dropdown.toggleClass("hidden");
                        if (!$dropdown.hasClass("hidden")) {
                            $searchInput.val("");
                            renderOptions();
                            $searchInput.focus();
                        }
                    });

                    $(document).off("click.dropdown-" + componentId).on("click.dropdown-" + componentId,
                        function(e) {
                            if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                                $dropdown.addClass("hidden");
                                console.log(`Dropdown closed for #${componentId}`);
                            }
                        });

                    const $clearButton = $container.find(".js-clear-button");
                    if ($clearButton.length) {
                        $clearButton.off("click").on("click", function(e) {
                            e.stopPropagation();
                            if ($container.find("select").prop("disabled"))
                                return;
                            currentSelectedValue = "";
                            $container.data("selected", currentSelectedValue);
                            $container.attr("data-selected", "");
                            $selectedLabel.text($container.data("placeholder") || "Select an option");
                            $dropdown.addClass("hidden");
                            updateHiddenInput();
                            renderOptions();
                        });
                    }

                    renderOptions();
                });

                // Initialize multi-select dropdowns
                $(".js-multi-select").each(function() {
                    const $container = $(this);
                    const componentId = $container.attr("id");
                    const dataName = $container.data("name");
                    let currentOptions = $container.data("options") || [];
                    let currentSelectedValues = $container.data("selected") || [];
                    const $optionsContainer = $container.find(".js-options-container");
                    const $selectedContainer = $container.find(".js-selected-container");
                    const $hiddenInput = $container.find(".js-hidden-input");
                    const $dropdown = $container.find(".js-dropdown");
                    const $searchInput = $container.find(".js-search-input");

                    if (!$optionsContainer.length || !$hiddenInput.length || !$selectedContainer.length) {
                        console.error(`Required elements not found in container #${componentId}. HTML:`,
                            $container[0].outerHTML);
                        return;
                    }

                    function renderOptions(searchTerm = "") {
                        $optionsContainer.empty();
                        $container.find(".js-no-options").toggleClass("hidden", currentOptions.length > 0);
                        if (!currentOptions.length) {
                            $selectedContainer.empty();
                            $hiddenInput.val("");
                            currentSelectedValues = [];
                            $container.data("selected", currentSelectedValues);
                            $container.attr("data-selected", JSON.stringify(currentSelectedValues));
                            return;
                        }
                        const filteredOptions = searchTerm ?
                            currentOptions.filter((opt) => opt.label.toLowerCase().includes(searchTerm
                                .toLowerCase())) :
                            currentOptions;
                        $.each(filteredOptions, function(index, option) {
                            if (!currentSelectedValues.includes(String(option.value))) {
                                const $option = $(`
                                    <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                        ${option.label}
                                    </div>
                                `);
                                $optionsContainer.append($option);
                            }
                        });
                        renderSelected();
                        updateHiddenInput();
                    }

                    function renderSelected() {
                        $selectedContainer.empty();
                        const selectedOptions = currentOptions.filter((opt) =>
                            currentSelectedValues.includes(String(opt.value))
                        );
                        $.each(selectedOptions, function(index, option) {
                            const $selectedItem = $(`
                                <div class="js-selected-item inline-flex items-center px-2 py-1 m-1 text-sm bg-blue-100 text-blue-800 rounded dark:bg-blue-900 dark:text-blue-200">
                                    ${option.label}
                                    <button type="button" class="js-remove-selected ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" data-value="${option.value}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            `);
                            $selectedContainer.append($selectedItem);
                        });
                        $hiddenInput.val(JSON.stringify(currentSelectedValues));
                    }

                    function updateHiddenInput() {
                        $hiddenInput.val(JSON.stringify(currentSelectedValues)).trigger("change");
                    }

                    $container.off("options-updated").on("options-updated", function(event, data) {
                        console.log(`Updating options for #${componentId}:`, data.options);
                        currentOptions = data?.options || [];
                        currentSelectedValues = data?.selected && Array.isArray(data.selected) ?
                            data.selected.filter((val) => currentOptions.some((opt) => String(opt
                                .value) === String(val))) : [];
                        $container.data("selected", currentSelectedValues);
                        $container.attr("data-selected", JSON.stringify(currentSelectedValues));
                        renderOptions();
                        updateHiddenInput();
                    });

                    $optionsContainer.off("click", ".js-option").on("click", ".js-option", function(e) {
                        e.stopPropagation();
                        if ($container.find("select").prop("disabled"))
                            return;
                        const $option = $(this);
                        const value = String($option.data("value"));
                        if (!currentSelectedValues.includes(value)) {
                            currentSelectedValues.push(value);
                            $container.data("selected", currentSelectedValues);
                            $container.attr("data-selected", JSON.stringify(currentSelectedValues));
                            renderOptions();
                            $dropdown.addClass("hidden");
                        }
                    });

                    $selectedContainer.off("click", ".js-remove-selected").on("click", ".js-remove-selected",
                        function(e) {
                            e.stopPropagation();
                            if ($container.find("select").prop("disabled"))
                                return;
                            const value = String($(this).data("value"));
                            currentSelectedValues = currentSelectedValues.filter((val) => val !== value);
                            $container.data("selected", currentSelectedValues);
                            $container.attr("data-selected", JSON.stringify(currentSelectedValues));
                            renderOptions();
                        });

                    $searchInput.off("input").on("input", function() {
                        if ($container.find("select").prop("disabled"))
                            return;
                        renderOptions($(this).val());
                    });

                    $container.find(".js-toggle-dropdown").off("click").on("click", function(e) {
                        e.stopPropagation();
                        if ($container.find("select").prop("disabled"))
                            return;
                        $(".js-dropdown").not($dropdown).addClass("hidden");
                        $dropdown.toggleClass("hidden");
                        if (!$dropdown.hasClass("hidden")) {
                            $searchInput.val("");
                            renderOptions();
                            $searchInput.focus();
                        }
                    });

                    $(document).off("click.dropdown-" + componentId).on("click.dropdown-" + componentId,
                        function(e) {
                            if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                                $dropdown.addClass("hidden");
                                console.log(`Dropdown closed for #${componentId}`);
                            }
                        });

                    const $clearButton = $container.find(".js-clear-button");
                    if ($clearButton.length) {
                        $clearButton.off("click").on("click", function(e) {
                            e.stopPropagation();
                            if ($container.find("select").prop("disabled"))
                                return;
                            currentSelectedValues = [];
                            $container.data("selected", currentSelectedValues);
                            $container.attr("data-selected", JSON.stringify(currentSelectedValues));
                            $selectedContainer.empty();
                            $dropdown.addClass("hidden");
                            updateHiddenInput();
                            renderOptions();
                        });
                    }

                    renderOptions();
                });

                // AJAX handlers
                const fixedProjectId = @json($preselectedData ? $preselectedData['projects'][0] : null);
                const directorateContainer = $('.js-single-select[data-name="directorate_id"]');
                const directorateInput = directorateContainer.find(".js-hidden-input");
                const departmentContainer = $('.js-single-select[data-name="department_id"]');
                const departmentInput = departmentContainer.find(".js-hidden-input");
                const projectsContainer = $('.js-multi-select[data-name="projects"]');
                const usersContainer = $('.js-multi-select[data-name="users"]');

                function updateSelectOptions(container, options, selected = []) {
                    const containerId = container.attr("id") || "undefined";
                    console.log(`Updating select options for #${containerId}:`, options, "Selected:", selected);
                    container.data("options", options);
                    container.data("selected", Array.isArray(selected) ? selected : selected ? [selected] : []);
                    container.attr("data-selected", Array.isArray(selected) ? JSON.stringify(selected) : selected ||
                        "");
                    container.trigger("options-updated", {
                        options,
                        selected: Array.isArray(selected) ? selected : selected ? [selected] : []
                    });
                }

                // Load department, projects, and users when directorate changes (only if no fixedProjectId)
                if (!fixedProjectId) {
                    directorateInput.on("change", function() {
                        const directorateId = $(this).val();
                        console.log("Directorate changed:", directorateId);

                        // Reset department, projects, and users if no valid directorate_id
                        if (!directorateId || isNaN(directorateId) || directorateId <= 0) {
                            console.log("No valid directorate_id, resetting department, projects, and users");
                            updateSelectOptions(departmentContainer, [], "");
                            updateSelectOptions(projectsContainer, [], []);
                            updateSelectOptions(usersContainer, [], []);
                            return;
                        }

                        // Load department
                        const $departmentOptionsContainer = departmentContainer.find(".js-options-container");
                        $departmentOptionsContainer.empty().append(
                            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>'
                        );

                        const departmentsUrl = "{{ route('admin.tasks.departments', ':directorate_id') }}"
                            .replace(
                                ':directorate_id', encodeURIComponent(directorateId));
                        console.log("Departments AJAX URL:", departmentsUrl);

                        $.ajax({
                            url: departmentsUrl,
                            method: "GET",
                            dataType: "json",
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            success: function(data) {
                                console.log("Departments AJAX success:", data);
                                const formattedData = Array.isArray(data) ?
                                    data.map((department) => ({
                                        value: String(department.value),
                                        label: String(department.label),
                                    })).filter((opt) => opt.value && opt.label) : [];
                                const validOldDepartment = @json(old('department_id', ''))
                                    .toString();
                                const selectedDepartment = formattedData.some((opt) => String(opt
                                    .value) === validOldDepartment) ? validOldDepartment : "";
                                updateSelectOptions(departmentContainer, formattedData,
                                    selectedDepartment);
                                if (formattedData.length === 0) {
                                    showError("{{ trans('global.task.errors.no_departments') }}");
                                }
                                // Trigger department change to load users
                                departmentInput.trigger("change");
                            },
                            error: function(xhr) {
                                console.error("Departments AJAX error:", xhr.status, xhr.statusText,
                                    xhr.responseJSON);
                                updateSelectOptions(departmentContainer, [], "");
                                showError(
                                    "{{ trans('global.task.errors.departments_fetch_failed') }}" +
                                    (xhr.responseJSON
                                        ?.message || "Unknown error"));
                                // Trigger department change to reset users
                                departmentInput.trigger("change");
                            }
                        });

                        // Load projects
                        const $projectsOptionsContainer = projectsContainer.find(".js-options-container");
                        $projectsOptionsContainer.empty().append(
                            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>'
                        );

                        const projectsUrl = "{{ route('admin.tasks.projects', ':directorate_id') }}".replace(
                            ':directorate_id', encodeURIComponent(directorateId));
                        console.log("Projects AJAX URL:", projectsUrl);

                        $.ajax({
                            url: projectsUrl,
                            method: "GET",
                            dataType: "json",
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            success: function(data) {
                                console.log("Projects AJAX success:", data);
                                const formattedData = Array.isArray(data) ?
                                    data.map((project) => ({
                                        value: String(project.value),
                                        label: String(project.label),
                                    })).filter((opt) => opt.value && opt.label) : [];
                                const validOldProjects = @json(old('projects', []))
                                    .filter((projectId) => formattedData.some((opt) => String(opt
                                        .value) === String(projectId)));
                                updateSelectOptions(projectsContainer, formattedData,
                                    validOldProjects);
                                if (formattedData.length === 0) {
                                    showError("{{ trans('global.task.errors.no_projects') }}");
                                }
                                // Trigger projects change to load users
                                projectsContainer.trigger("change");
                            },
                            error: function(xhr) {
                                console.error("Projects AJAX error:", xhr.status, xhr.statusText,
                                    xhr.responseJSON);
                                updateSelectOptions(projectsContainer, [], []);
                                showError(
                                    "{{ trans('global.task.errors.projects_fetch_failed') }}" +
                                    (xhr.responseJSON?.message ||
                                        "Unknown error"));
                                // Trigger projects change to reset users
                                projectsContainer.trigger("change");
                            }
                        });
                    });

                    // Load users when department changes
                    departmentInput.on("change", function() {
                        const departmentId = $(this).val();
                        const directorateId = directorateInput.val();
                        const selectedProjects = projectsContainer.data("selected") || [];
                        console.log("Department changed:", departmentId, "Directorate:", directorateId,
                            "Projects:", selectedProjects);

                        // If projects are selected, users are loaded based on projects
                        if (selectedProjects.length > 0) {
                            return; // Projects take precedence, handled by projectsContainer change
                        }

                        const $usersOptionsContainer = usersContainer.find(".js-options-container");
                        $usersOptionsContainer.empty().append(
                            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>'
                        );

                        const usersUrl = "{{ route('admin.tasks.users_by_directorate_or_department') }}";
                        const queryParams = departmentId ? {
                            department_id: departmentId
                        } : directorateId ? {
                            directorate_id: directorateId
                        } : {};
                        console.log("Users AJAX URL:", usersUrl, "Params:", queryParams);

                        $.ajax({
                            url: usersUrl,
                            method: "GET",
                            data: queryParams,
                            dataType: "json",
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            success: function(data) {
                                console.log("Users AJAX success:", data);
                                const formattedData = Array.isArray(data) ?
                                    data.map((user) => ({
                                        value: String(user.value),
                                        label: String(user.label),
                                    })).filter((opt) => opt.value && opt.label) : [];
                                const validOldUsers = @json(old('users', []))
                                    .filter((userId) => formattedData.some((opt) => String(opt
                                        .value) === String(userId)));
                                updateSelectOptions(usersContainer, formattedData, validOldUsers);
                                if (formattedData.length === 0) {
                                    //showError("{{ trans('global.task.errors.no_users') }}");
                                }
                            },
                            error: function(xhr) {
                                console.error("Users AJAX error:", xhr.status, xhr.statusText, xhr
                                    .responseJSON);
                                updateSelectOptions(usersContainer, [], []);
                                // showError("{{ trans('global.task.errors.users_fetch_failed') }}" +
                                //     (xhr.responseJSON?.message ||
                                //         "Unknown error"));
                            }
                        });
                    });
                }

                // Load users when projects change (only if no fixedProjectId)
                const debouncedFetchUsers = debounce(function(selectedProjects) {
                    console.log("Fetching users for projects:", selectedProjects);

                    if (!selectedProjects.length) {
                        console.log("No projects selected, checking department or directorate");
                        departmentInput.trigger("change"); // Fall back to department or directorate
                        return;
                    }

                    const $optionsContainer = usersContainer.find(".js-options-container");
                    $optionsContainer.empty().append(
                        '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>');

                    $.ajax({
                        url: "{{ route('admin.tasks.users_by_projects') }}",
                        method: "GET",
                        data: {
                            project_ids: selectedProjects
                        },
                        dataType: "json",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(data) {
                            console.log("Users AJAX success:", data);
                            const formattedData = Array.isArray(data) ?
                                data.map((user) => ({
                                    value: String(user.value),
                                    label: String(user.label),
                                })).filter((opt) => opt.value && opt.label) : [];
                            const validOldUsers = @json(old('users', []))
                                .filter((userId) => formattedData.some((opt) => String(opt
                                    .value) === String(userId)));
                            updateSelectOptions(usersContainer, formattedData, validOldUsers);
                            if (formattedData.length === 0) {
                                showError("{{ trans('global.task.errors.no_users') }}");
                            }
                        },
                        error: function(xhr) {
                            console.error("Users AJAX error:", xhr.status, xhr.statusText, xhr
                                .responseJSON);
                            updateSelectOptions(usersContainer, [], []);
                            showError("{{ trans('global.task.errors.users_fetch_failed') }}" + (xhr
                                .responseJSON?.message ||
                                "Unknown error"));
                        }
                    });
                }, 300);

                if (!fixedProjectId) {
                    projectsContainer.on("change", function() {
                        const selectedProjects = $(this).data("selected") || [];
                        console.log("Projects selection changed:", selectedProjects);
                        debouncedFetchUsers(selectedProjects);
                    });
                }

                // Trigger initial change for pre-selected directorate or department (only if no fixedProjectId)
                if (!fixedProjectId) {
                    const initialDirectorateId = directorateInput.val();
                    if (initialDirectorateId && !isNaN(initialDirectorateId) && initialDirectorateId > 0) {
                        console.log("Initial directorate_id:", initialDirectorateId);
                        directorateInput.trigger("change");
                    } else {
                        console.log("No valid initial directorate_id, checking department");
                        departmentInput.trigger("change");
                    }
                }
            });
        </script>
    @endpush
</x-layouts.app>
