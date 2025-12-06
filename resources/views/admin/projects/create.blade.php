<x-layouts.app>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ trans('global.project.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ trans('global.create') }} {{ trans('global.project.title_singular') }}
        </p>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-6">
        <form id="project-form" class="w-full" action="{{ route('admin.project.store') }}" method="POST"
            enctype="multipart/form-data">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.project.title_singular') }} {{ trans('global.information') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.project.fields.directorate_id') }}"
                                    name="directorate_id" id="directorate_id" :options="collect($directorates)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('directorate_id')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('directorate_id')"
                                    class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.project.fields.departments') }}"
                                    name="department_id" id="department_select" :options="[]" :selected="old('department_id')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" allow-clear="true"
                                    data-selected="{{ old('department_id') }}" :error="$errors->first('department_id')"
                                    class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.select label="{{ trans('global.project.fields.project_manager') }}"
                                    name="project_manager" id="project_manager_select" :options="[]"
                                    :selected="old('project_manager')" placeholder="{{ trans('global.pleaseSelect') }}"
                                    allow-clear="true" data-selected="{{ old('project_manager') }}" :error="$errors->first('project_manager')"
                                    class="js-single-select" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.input label="{{ trans('global.project.fields.title') }}" name="title"
                                    type="text" :value="old('title')" :error="$errors->first('title')" />
                            </div>

                            <div class="col-span-full">
                                <x-forms.text-area label="{{ trans('global.project.fields.description') }}"
                                    name="description" :value="old('description')" :error="$errors->first('description')" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.project.headers.date_progress') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.date-input label="{{ trans('global.project.fields.start_date') }}"
                                    name="start_date" :value="old('start_date')" :error="$errors->first('start_date')" />
                            </div>
                            <div>
                                <x-forms.date-input label="{{ trans('global.project.fields.end_date') }}"
                                    name="end_date" :value="old('end_date')" :error="$errors->first('end_date')" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.project.headers.status_priority') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-forms.select label="{{ trans('global.project.fields.status_id') }}" name="status_id"
                                    id="status_id" :options="collect($statuses)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('status_id')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('status_id')"
                                    class="js-single-select" />
                            </div>
                            <div>
                                <x-forms.select label="{{ trans('global.project.fields.priority_id') }}"
                                    name="priority_id" id="priority_id" :options="collect($priorities)
                                        ->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])
                                        ->values()
                                        ->all()" :selected="old('priority_id')"
                                    placeholder="{{ trans('global.pleaseSelect') }}" :error="$errors->first('priority_id')"
                                    class="js-single-select" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                            {{ trans('global.project.headers.attachments') }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <div class="space-y-4">
                                    <div>
                                        <x-forms.file-input
                                            label="{{ trans('global.project.fields.upload_documents') }}"
                                            name="files" multiple accept=".pdf,.png,.jpg" maxSize="10MB" />
                                    </div>
                                </div>
                            </div>
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
            function waitForJQuery(callback, retries = 50) {
                if (
                    typeof jQuery !== "undefined" &&
                    jQuery.fn.jquery &&
                    document.readyState === "complete"
                ) {
                    console.log('jQuery loaded, version:', jQuery.fn.jquery);
                    callback();
                } else if (retries > 0) {
                    console.warn(
                        "jQuery or DOM not ready, retrying... jQuery:",
                        typeof jQuery !== "undefined" ? jQuery.fn.jquery : "undefined",
                        "DOM:",
                        document.readyState,
                        "Retries left:",
                        retries
                    );
                    setTimeout(function() {
                        waitForJQuery(callback, retries - 1);
                    }, 100);
                } else {
                    console.error("Failed to load jQuery or DOM after maximum retries.");
                }
            }

            waitForJQuery(function() {
                const $ = jQuery;

                // Initialize custom select components
                $(".js-single-select").each(function() {
                    const $container = $(this);
                    const componentId = $container.attr("id");
                    const dataName = $container.data("name");
                    let currentOptions = $container.data("options") || [];
                    let currentSelectedValue = String($container.data("selected") || "");
                    const $optionsContainer = $container.find(".js-options-container");
                    const $selectedLabel = $container.find(".js-selected-label");
                    const $hiddenInput = $container.find("input.js-hidden-input");
                    const $dropdown = $container.find(".js-dropdown");
                    const $searchInput = $container.find(".js-search-input");

                    if (!$optionsContainer.length || !$hiddenInput.length) {
                        console.error(
                            `Required elements (.js-options-container or .js-hidden-input) not found in container #${componentId}. Container HTML:`,
                            $container[0].outerHTML
                        );
                        return;
                    }

                    function renderOptions(searchTerm = "") {
                        $optionsContainer.empty();
                        if (!currentOptions || currentOptions.length === 0) {
                            $container.find(".js-no-options").removeClass("hidden");
                            $selectedLabel.text(
                                $container.data("placeholder") || "{{ trans('global.pleaseSelect') }}"
                            );
                            $hiddenInput.val("");
                            currentSelectedValue = "";
                            $container.data("selected", "");
                            $container.attr("data-selected", "");
                            return;
                        }
                        $container.find(".js-no-options").addClass("hidden");
                        const filteredOptions = searchTerm ?
                            currentOptions.filter((option) =>
                                String(option.label).toLowerCase().includes(String(searchTerm).toLowerCase())
                            ) :
                            currentOptions;
                        $.each(filteredOptions, function(index, option) {
                            const $option = $(`
                                <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `);
                            $optionsContainer.append($option);
                        });

                        const selectedOption = currentOptions.find(
                            (opt) => String(opt.value) === String(currentSelectedValue)
                        );
                        $selectedLabel.text(
                            selectedOption ?
                            selectedOption.label :
                            $container.data("placeholder") || "{{ trans('global.pleaseSelect') }}"
                        );
                        $hiddenInput.val(selectedOption ? currentSelectedValue : "");
                        if (!selectedOption) {
                            currentSelectedValue = "";
                            $container.data("selected", "");
                            $container.attr("data-selected", "");
                        }
                    }

                    function updateHiddenInput() {
                        $hiddenInput.val(currentSelectedValue || "");
                        $hiddenInput.trigger("change");
                    }

                    const $clearButton = $container.find(".js-clear-button");
                    if ($clearButton.length) {
                        $clearButton.off("click").on("click", function(e) {
                            e.stopPropagation();
                            currentSelectedValue = "";
                            $container.data("selected", "");
                            $container.attr("data-selected", "");
                            $selectedLabel.text(
                                $container.data("placeholder") ||
                                "{{ trans('global.pleaseSelect') }}"
                            );
                            $dropdown.addClass("hidden");
                            updateHiddenInput();
                            renderOptions();
                        });
                    }

                    $container
                        .off("options-updated")
                        .on("options-updated", function(event, data) {
                            currentOptions = data?.options || $container.data("options") || [];
                            let newSelected = data?.selected !== undefined ? String(data.selected) :
                                currentSelectedValue;
                            if (newSelected && !currentOptions.some(opt => String(opt.value) ===
                                    newSelected)) {
                                newSelected = "";
                            }
                            currentSelectedValue = newSelected;
                            renderOptions();
                            updateHiddenInput();
                        });

                    $optionsContainer
                        .off("click", ".js-option")
                        .on("click", ".js-option", function(e) {
                            e.stopPropagation();
                            const $option = $(this);
                            currentSelectedValue = String($option.data("value"));
                            $container.data("selected", currentSelectedValue);
                            $container.attr("data-selected", currentSelectedValue);
                            $selectedLabel.text($option.text().trim());
                            $dropdown.addClass("hidden");
                            updateHiddenInput();
                            console.log(`${dataName} selected:`, currentSelectedValue);
                        });

                    $searchInput.off("input").on("input", function() {
                        renderOptions($(this).val());
                    });

                    $container
                        .find(".js-toggle-dropdown")
                        .off("click")
                        .on("click", function(e) {
                            e.stopPropagation();
                            $(".js-dropdown").not($dropdown).addClass("hidden");
                            $dropdown.toggleClass("hidden");
                            console.log(`Dropdown toggled for #${componentId}:`, !$dropdown.hasClass(
                                "hidden"));
                            if (!$dropdown.hasClass("hidden")) {
                                $searchInput.val('');
                                renderOptions();
                                $searchInput.focus();
                            }
                        });

                    $(document)
                        .off("click.dropdown-" + componentId)
                        .on("click.dropdown-" + componentId, function(e) {
                            if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                                $dropdown.addClass("hidden");
                                console.log(`Dropdown closed for #${componentId}`);
                            }
                        });

                    renderOptions();
                    console.log(`Options parsed for #${componentId}:`, currentOptions);
                    console.log(`Selected parsed for #${componentId}:`, currentSelectedValue);
                });

                // Handle directorate, department, and user selection
                const directorateContainer = $('.js-single-select[data-name="directorate_id"]');
                const directorateInput = directorateContainer.find("input.js-hidden-input");
                const departmentSelectContainer = $('.js-single-select[data-name="department_id"]');
                const userSelectContainer = $('.js-single-select[data-name="project_manager"]');

                function updateSelectOptions(container, options, selected = "") {
                    container.data("options", options);
                    container.data("selected", selected);
                    container.attr("data-selected", selected);
                    container.trigger("options-updated", {
                        options,
                        selected
                    });
                    console.log(`Updated options for ${container.data("name")}:`, options, 'Selected:', selected);
                }

                function loadDepartments(directorateId, selectedDepartmentId = "") {
                    if (!directorateId) {
                        updateSelectOptions(departmentSelectContainer, [], "");
                        return;
                    }
                    const $optionsContainer = departmentSelectContainer.find(".js-options-container");
                    $optionsContainer
                        .empty()
                        .append('<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>');

                    $.ajax({
                        url: `/admin/projects/departments/${encodeURIComponent(directorateId)}`,
                        method: "GET",
                        dataType: "json",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(data) {
                            const formattedData = Array.isArray(data) ?
                                data
                                .map((dept) => ({
                                    value: String(dept.value),
                                    label: String(dept.label),
                                }))
                                .filter((opt) => opt.value && opt.label) :
                                [];
                            let initialDepartmentSelected = "";
                            if (selectedDepartmentId && formattedData.some(opt => String(opt.value) ===
                                    String(selectedDepartmentId))) {
                                initialDepartmentSelected = String(selectedDepartmentId);
                            } else if (departmentSelectContainer.data('selected') && formattedData.some(
                                    opt => String(opt.value) === String(departmentSelectContainer.data(
                                        'selected')))) {
                                initialDepartmentSelected = String(departmentSelectContainer.data(
                                    'selected'));
                            }
                            updateSelectOptions(departmentSelectContainer, formattedData,
                                initialDepartmentSelected);
                        },
                        error: function(xhr) {
                            updateSelectOptions(departmentSelectContainer, [], "");
                            $optionsContainer
                                .empty()
                                .append(
                                    '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load departments</div>'
                                );
                            $("#error-message").removeClass("hidden");
                            $("#error-text").text("Failed to load departments: " + (xhr.responseJSON
                                ?.message || "Unknown error"));
                        }
                    });
                }

                function loadUsers(directorateId, selectedUserId = "") {
                    if (!directorateId) {
                        updateSelectOptions(userSelectContainer, [], "");
                        return;
                    }
                    const $optionsContainer = userSelectContainer.find(".js-options-container");
                    $optionsContainer
                        .empty()
                        .append('<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>');

                    $.ajax({
                        url: `/admin/projects/users/${encodeURIComponent(directorateId)}`,
                        method: "GET",
                        dataType: "json",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(data) {
                            const formattedData = Array.isArray(data) ?
                                data
                                .map((user) => ({
                                    value: String(user.value),
                                    label: String(user.label),
                                }))
                                .filter((opt) => opt.value && opt.label) :
                                [];
                            let initialUserSelected = "";
                            if (selectedUserId && formattedData.some(opt => String(opt.value) === String(
                                    selectedUserId))) {
                                initialUserSelected = String(selectedUserId);
                            } else if (userSelectContainer.data('selected') && formattedData.some(opt =>
                                    String(opt.value) === String(userSelectContainer.data('selected')))) {
                                initialUserSelected = String(userSelectContainer.data('selected'));
                            }
                            updateSelectOptions(userSelectContainer, formattedData, initialUserSelected);
                        },
                        error: function(xhr) {
                            updateSelectOptions(userSelectContainer, [], "");
                            $optionsContainer
                                .empty()
                                .append(
                                    '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load users</div>'
                                );
                            $("#error-message").removeClass("hidden");
                            $("#error-text").text("Failed to load users: " + (xhr.responseJSON?.message ||
                                "Unknown error"));
                        }
                    });
                }

                directorateInput.off("change").on("change", function() {
                    const directorateId = $(this).val();
                    console.log('Directorate changed:', directorateId);
                    loadDepartments(directorateId);
                    loadUsers(directorateId);
                });

                // Form submission handling
                const $form = $('#project-form');
                const $submitButton = $('#submit-button');

                $form.on('submit', function(e) {
                    console.log('Form submit attempted');
                    if ($submitButton.prop('disabled')) {
                        e.preventDefault();
                        console.log('Form submission prevented: button is disabled');
                        return;
                    }

                    $submitButton
                        .prop('disabled', true)
                        .addClass('opacity-50 cursor-not-allowed')
                        .text('{{ trans('global.saving') }}...');
                    console.log('Submit button disabled');

                    // Perform AJAX form submission
                    e.preventDefault(); // Prevent default form submission
                    $.ajax({
                        url: $form.attr('action'),
                        method: 'POST',
                        data: new FormData($form[0]),
                        processData: false,
                        contentType: false,
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function(response) {
                            console.log('Form submission success:', response);
                            $("#error-message").addClass("hidden");
                            $("#error-text").text("");
                            // Redirect or show success message
                            window.location.href = '{{ route('admin.project.index') }}';
                        },
                        error: function(xhr) {
                            console.error('Form submission error:', xhr.status, xhr.responseJSON);
                            $submitButton
                                .prop('disabled', false)
                                .removeClass('opacity-50 cursor-not-allowed')
                                .text('{{ trans('global.save') }}');
                            $("#error-message").removeClass("hidden");
                            $("#error-text").text(
                                xhr.responseJSON?.message ||
                                "Failed to create project. Please try again."
                            );
                        }
                    });
                });

                // Initialize with existing values
                const initialDirectorateId = directorateInput.val();
                const initialDepartmentId = departmentSelectContainer.data('selected');
                const initialProjectManagerId = userSelectContainer.data('selected');

                if (initialDirectorateId) {
                    loadDepartments(initialDirectorateId, initialDepartmentId);
                    loadUsers(initialDirectorateId, initialProjectManagerId);
                } else {
                    updateSelectOptions(departmentSelectContainer, [], "");
                    updateSelectOptions(userSelectContainer, [], "");
                }

                $("#close-error").off("click").on("click", function() {
                    $("#error-message").addClass("hidden");
                    $("#error-text").text("");
                });
            });
        </script>
    @endpush
</x-layouts.app>
