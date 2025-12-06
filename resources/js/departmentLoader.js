function waitForDOM(callback, retries = 50) {
    if (document.readyState === "complete") {
        callback();
    } else if (retries > 0) {
        console.warn(
            "DOM not ready, retrying... DOM:",

            document.readyState,

            "Retries left:",

            retries,
        );

        setTimeout(function () {
            waitForDOM(callback, retries - 1);
        }, 100);
    } else {
        console.error("Failed to load DOM after maximum retries.");
    }
}

waitForDOM(function () {
    const $ = jQuery;

    console.log(
        "jQuery loaded, version:",

        $.fn.jquery,

        "DOM ready:",

        document.readyState,
    );

    // Custom Dropdown Component Logic

    $(".js-single-select").each(function () {
        const $container = $(this);

        const componentId = $container.attr("id");

        const dataName = $container.data("name");

        let currentOptions = $container.data("options") || [];

        let currentSelectedValue = $container.data("selected") || "";

        const $optionsContainer = $container.find(".js-options-container");

        const $selectedLabel = $container.find(".js-selected-label");

        const $hiddenInput = $container.find("input.js-hidden-input");

        const $dropdown = $container.find(".js-dropdown");

        const $searchInput = $container.find(".js-search-input");

        if (!$optionsContainer.length || !$hiddenInput.length) {
            console.error(
                `Required elements (.js-options-container or .js-hidden-input) not found in container #${componentId}. Container HTML:`,

                $container[0].outerHTML,
            );

            return;
        }

        function renderOptions(searchTerm = "") {
            console.log(`Rendering options for ${componentId}:`, {
                options: currentOptions,

                selected: currentSelectedValue,

                searchTerm,
            });

            $optionsContainer.empty();

            if (!currentOptions || currentOptions.length === 0) {
                console.log(`No options available for ${componentId}`);

                $container.find(".js-no-options").removeClass("hidden");

                $selectedLabel.text(
                    $container.data("placeholder") || "Select an option",
                );

                $hiddenInput.val("");

                currentSelectedValue = "";

                $container.data("selected", "");

                $container.attr("data-selected", "");

                return;
            }

            $container.find(".js-no-options").addClass("hidden");

            const filteredOptions = searchTerm
                ? currentOptions.filter((option) =>
                      option.label

                          .toLowerCase()

                          .includes(searchTerm.toLowerCase()),
                  )
                : currentOptions;

            console.log(
                `Filtered options for ${componentId}:`,

                filteredOptions,
            );

            $.each(filteredOptions, function (index, option) {
                const $option = $(`

<div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">

${option.label}

</div>

`);

                $optionsContainer.append($option);
            });

            // Check if currentSelectedValue is valid in the current options

            const selectedOption = currentOptions.find(
                (opt) => String(opt.value) === String(currentSelectedValue),
            );

            $selectedLabel.text(
                selectedOption
                    ? selectedOption.label
                    : $container.data("placeholder") || "Select an option",
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

            console.log(`Updated hidden input for ${componentId}:`, {
                name: dataName,

                value: currentSelectedValue,
            });
        }

        $container

            .off("options-updated")

            .on("options-updated", function (event, data) {
                console.log(
                    `Options-updated event triggered for ${componentId}:`,

                    data,
                );

                currentOptions =
                    data?.options || $container.data("options") || [];

                currentSelectedValue =
                    data?.selected &&
                    currentOptions.some(
                        (opt) => String(opt.value) === String(data.selected),
                    )
                        ? String(data.selected)
                        : "";

                renderOptions();

                updateHiddenInput();
            });

        $optionsContainer

            .off("click", ".js-option")

            .on("click", ".js-option", function (e) {
                e.stopPropagation();

                const $option = $(this);

                currentSelectedValue = $option.data("value");

                $container.data("selected", currentSelectedValue);

                $container.attr("data-selected", currentSelectedValue);

                $selectedLabel.text($option.text().trim());

                $dropdown.addClass("hidden");

                updateHiddenInput();

                console.log(
                    `Option selected for ${componentId}:`,

                    currentSelectedValue,
                );
            });

        $searchInput.off("input").on("input", function () {
            renderOptions($(this).val());
        });

        $container

            .find(".js-toggle-dropdown")

            .off("click")

            .on("click", function (e) {
                e.stopPropagation();

                $dropdown.toggleClass("hidden");

                if (!$dropdown.hasClass("hidden")) {
                    $searchInput.focus();
                }

                console.log(
                    `Toggled dropdown for ${componentId}:`,

                    !$dropdown.hasClass("hidden"),
                );
            });

        $(document)
            .off("click.dropdown-" + componentId)

            .on("click.dropdown-" + componentId, function (e) {
                if (
                    !$container.is(e.target) &&
                    $container.has(e.target).length === 0
                ) {
                    $dropdown.addClass("hidden");
                }
            });

        console.log(
            `Initial select options for ${componentId}:`,

            currentOptions,
        );

        console.log(
            `Initial selected value for ${componentId}:`,

            currentSelectedValue,
        );

        renderOptions();
    });

    // Dependent Dropdown Loader Logic

    const directorateContainer = $(
        '.js-single-select[data-name="directorate_id"]',
    );

    const directorateInput = directorateContainer.find("input.js-hidden-input");

    const departmentSelectContainer = $(
        '.js-single-select[data-name="department_id"]',
    );

    const userSelectContainer = $(
        '.js-single-select[data-name="project_manager"]',
    );

    console.log(
        "Directorate container:",

        directorateContainer.length
            ? directorateContainer[0].outerHTML
            : "Not found",
    );

    console.log(
        "Directorate input:",

        directorateInput.length ? directorateInput[0].outerHTML : "Not found",
    );

    console.log(
        "Department select container:",

        departmentSelectContainer.length
            ? departmentSelectContainer[0].outerHTML
            : "Not found",
    );

    console.log(
        "User select container:",

        userSelectContainer.length
            ? userSelectContainer[0].outerHTML
            : "Not found",
    );

    if (!directorateContainer.length || !directorateInput.length) {
        console.error(
            "Directorate container or input not found in DOM. Available IDs:",

            $("[id]")
                .map((i, el) => el.id)

                .get(),
        );

        return;
    }

    if (!departmentSelectContainer.length) {
        console.error(
            'Department select container .js-single-select[data-name="department_id"] not found in DOM. Available .js-single-select elements:',

            $(".js-single-select")
                .map((i, el) => ({
                    id: el.id,

                    dataName: $(el).data("name"),
                }))

                .get(),
        );

        return;
    }

    if (!userSelectContainer.length) {
        console.error(
            'User select container .js-single-select[data-name="project_manager"] not found in DOM. Available .js-single-select elements:',

            $(".js-single-select")
                .map((i, el) => ({
                    id: el.id,

                    dataName: $(el).data("name"),
                }))

                .get(),
        );

        return;
    }

    function updateSelectOptions(container, options, selected = "") {
        console.log("Updating select options:", {
            containerId: container.attr("id"),

            options,

            selected,
        });

        container.data("options", options);

        container.data("selected", selected);

        container.attr("data-selected", selected);

        container.trigger("options-updated", {
            options,

            selected,
        });
    }

    function loadDepartments(directorateId) {
        if (!directorateId) {
            console.log(
                "No directorate ID provided, clearing department options",
            );

            updateSelectOptions(departmentSelectContainer, [], "");

            return;
        }

        const $optionsContainer = departmentSelectContainer.find(
            ".js-options-container",
        );

        if (!$optionsContainer.length) {
            console.error(
                "No .js-options-container found in department_select. Container HTML:",

                departmentSelectContainer[0].outerHTML,
            );

            return;
        }

        $optionsContainer

            .empty()

            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>',
            );

        // Clear selected value before loading new options

        departmentSelectContainer.data("selected", "");

        departmentSelectContainer.attr("data-selected", "");

        departmentSelectContainer.find(".js-hidden-input").val("");

        departmentSelectContainer

            .find(".js-selected-label")

            .text(
                departmentSelectContainer.data("placeholder") ||
                    "Select an option",
            );

        $.ajax({
            url: `/admin/projects/departments/${encodeURIComponent(directorateId)}`,

            method: "GET",

            dataType: "json",

            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),

                "X-Requested-With": "XMLHttpRequest",
            },

            beforeSend: function () {
                console.log(
                    "AJAX request started for departments:",

                    `/admin/projects/departments/${directorateId}`,
                );
            },

            success: function (data) {
                console.log("Raw departments response:", data);

                const formattedData = Array.isArray(data)
                    ? data

                          .map((dept) => ({
                              value: String(dept.value),

                              label: String(dept.label),
                          }))

                          .filter((opt) => opt.value && opt.label)
                    : [];

                console.log("Updating department select with:", {
                    options: formattedData,

                    selected: "",
                });

                updateSelectOptions(
                    departmentSelectContainer,

                    formattedData,

                    "",
                );
            },

            error: function (xhr) {
                console.error(
                    "AJAX error for departments:",

                    xhr.status,

                    xhr.statusText,

                    xhr.responseJSON,
                );

                updateSelectOptions(departmentSelectContainer, [], "");

                $optionsContainer

                    .empty()

                    .append(
                        '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load departments</div>',
                    );

                $("#error-message").removeClass("hidden");

                $("#error-text").text(
                    "Failed to load departments: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },

            complete: function () {
                console.log(
                    "AJAX request for departments completed:",

                    directorateId,
                );
            },
        });
    }

    function loadUsers(directorateId) {
        if (!directorateId) {
            console.log("No directorate ID provided, clearing user options");

            updateSelectOptions(userSelectContainer, [], "");

            return;
        }

        const $optionsContainer = userSelectContainer.find(
            ".js-options-container",
        );

        if (!$optionsContainer.length) {
            console.error(
                "No .js-options-container found in project_manager_select. Container HTML:",

                userSelectContainer[0].outerHTML,
            );

            return;
        }

        $optionsContainer

            .empty()

            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>',
            );

        // Clear selected value before loading new options

        userSelectContainer.data("selected", "");

        userSelectContainer.attr("data-selected", "");

        userSelectContainer.find(".js-hidden-input").val("");

        userSelectContainer

            .find(".js-selected-label")

            .text(
                userSelectContainer.data("placeholder") || "Select an option",
            );

        $.ajax({
            url: `/admin/projects/users/${encodeURIComponent(directorateId)}`,

            method: "GET",

            dataType: "json",

            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),

                "X-Requested-With": "XMLHttpRequest",
            },

            beforeSend: function () {
                console.log(
                    "AJAX request started for users:",

                    `/admin/projects/users/${directorateId}`,
                );
            },

            success: function (data) {
                console.log("Raw users response:", data);

                const formattedData = Array.isArray(data)
                    ? data

                          .map((user) => ({
                              value: String(user.value),

                              label: String(user.label),
                          }))

                          .filter((opt) => opt.value && opt.label)
                    : [];

                console.log("Updating user select with:", {
                    options: formattedData,

                    selected: "",
                });

                updateSelectOptions(userSelectContainer, formattedData, "");
            },

            error: function (xhr) {
                console.error(
                    "AJAX error for users:",

                    xhr.status,

                    xhr.statusText,

                    xhr.responseJSON,
                );

                updateSelectOptions(userSelectContainer, [], "");

                $optionsContainer

                    .empty()

                    .append(
                        '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load users</div>',
                    );

                $("#error-message").removeClass("hidden");

                $("#error-text").text(
                    "Failed to load users: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },

            complete: function () {
                console.log("AJAX request for users completed:", directorateId);
            },
        });
    }

    directorateInput.off("change").on("change", function () {
        const directorateId = $(this).val();

        console.log("Directorate changed:", directorateId);

        loadDepartments(directorateId);

        loadUsers(directorateId);
    });

    if (directorateInput.val()) {
        console.log("Initial directorate:", directorateInput.val());

        loadDepartments(directorateInput.val());

        loadUsers(directorateInput.val());
    } else {
        console.log(
            "No initial directorate, clearing department and user options",
        );

        updateSelectOptions(departmentSelectContainer, [], "");

        updateSelectOptions(userSelectContainer, [], "");
    }

    $("#close-error")
        .off("click")

        .on("click", function () {
            $("#error-message").addClass("hidden");

            $("#error-text").text("");
        });
});
