// resources/js/projectLoader.js

// Ensure jQuery is loaded before executing this script
function waitForJQuery(callback, retries = 50) {
    console.log(
        "waitForJQuery called from projectLoader.js, jQuery:",
        typeof jQuery,
        "readyState:",
        document.readyState,
    );
    if (
        typeof jQuery !== "undefined" &&
        jQuery.fn.jquery &&
        document.readyState === "complete"
    ) {
        console.log(
            "jQuery and DOM ready in projectLoader.js, executing callback",
        );
        callback();
    } else if (retries > 0) {
        console.warn(
            "jQuery or DOM not ready in projectLoader.js, retrying... Retries left:",
            retries,
        );
        setTimeout(function () {
            waitForJQuery(callback, retries - 1);
        }, 100);
    } else {
        console.error(
            "Failed to load jQuery or DOM in projectLoader.js after maximum retries.",
        );
    }
}

waitForJQuery(function () {
    const $ = jQuery;
    console.log("projectLoader.js: jQuery loaded, version:", $.fn.jquery);

    // --- Generic Multi-Select Component Initialization Logic ---
    // This function will initialize any element with the class .js-multi-select
    function initializeMultiSelect(
        $container,
        onSelectionChangeCallback = null,
    ) {
        const componentId = $container.attr("id");
        const dataName = $container.data("name");
        let currentOptions = [];
        let currentSelectedValues = [];

        // Parse initial options and selected values from data attributes
        try {
            currentOptions = $container.attr("data-options")
                ? JSON.parse($container.attr("data-options"))
                : [];
            if (
                !Array.isArray(currentOptions) ||
                !currentOptions.every(
                    (opt) => opt.value !== undefined && opt.label !== undefined,
                )
            ) {
                console.error(
                    "projectLoader.js: Invalid options data for",
                    componentId,
                    currentOptions,
                );
                currentOptions = [];
            }
        } catch (e) {
            console.error(
                "projectLoader.js: Error parsing data-options for multi-select:",
                e.message,
                "Container:",
                componentId,
            );
            currentOptions = [];
        }

        try {
            currentSelectedValues = $container.attr("data-selected")
                ? JSON.parse($container.attr("data-selected"))
                : [];
            currentSelectedValues = currentSelectedValues
                .flat(Infinity)
                .filter(
                    (val) => val !== null && val !== undefined && val !== "",
                )
                .map((val) => String(val));
        } catch (e) {
            console.error(
                "projectLoader.js: Error parsing data-selected for multi-select:",
                e.message,
                "Container:",
                componentId,
            );
            currentSelectedValues = [];
        }

        const placeholder = $container.data("placeholder");

        const $multiSelectContainer = $container.find(
            ".js-multi-select-container",
        );
        const $searchInput = $container.find(".js-search-input");
        const $dropdown = $container.find(".js-dropdown");
        const $optionsContainer = $container.find(".js-options-container");
        const $noOptionsMessage = $container.find(".js-no-options");
        const $selectAllBtn = $container.find(".js-select-all");
        const $deselectAllBtn = $container.find(".js-deselect-all");

        console.log(
            `projectLoader.js: Initializing multi-select component: ${componentId}`,
            {
                dataName,
                optionsCount: currentOptions.length,
                selected: currentSelectedValues,
            },
        );

        function renderDropdownOptions(searchTerm = "") {
            console.log(
                `projectLoader.js: [${componentId}] Rendering dropdown options, searchTerm:`,
                searchTerm,
            );
            $optionsContainer.empty();
            $noOptionsMessage.addClass("hidden");

            if (!currentOptions || currentOptions.length === 0) {
                $noOptionsMessage
                    .text(`No ${dataName} available.`) // Dynamic message
                    .removeClass("hidden");
                return;
            }

            const filteredOptions = currentOptions.filter((option) =>
                option.label.toLowerCase().includes(searchTerm.toLowerCase()),
            );

            if (filteredOptions.length === 0) {
                $noOptionsMessage
                    .text(`No matching ${dataName} found.`) // Dynamic message
                    .removeClass("hidden");
                return;
            }

            $.each(filteredOptions, function (index, option) {
                const isSelected = currentSelectedValues.includes(
                    String(option.value),
                );
                const $optionElement = $(`
                    <div class="js-option flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
                         data-value="${option.value}">
                        <input type="checkbox"
                               class="js-option-checkbox mr-2 form-checkbox h-4 w-4 text-indigo-600 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-indigo-500"
                               ${isSelected ? "checked" : ""}>
                        <span>${option.label}</span>
                    </div>
                `);
                $optionsContainer.append($optionElement);
            });
        }

        function renderSelectedTags() {
            console.log(
                `projectLoader.js: [${componentId}] Rendering selected tags:`,
                currentSelectedValues,
            );
            $multiSelectContainer.find(".js-selected-option").remove();
            $searchInput.attr(
                "placeholder",
                currentSelectedValues.length > 0 ? "" : placeholder,
            );

            $.each(currentSelectedValues, function (index, value) {
                const option = currentOptions.find(
                    (opt) => String(opt.value) === String(value),
                );
                const label = option ? option.label : `Unknown (ID: ${value})`;
                const $tag = $(`
                    <span class="js-selected-option inline-flex items-center px-2 py-1 bg-gray-200 text-gray-700 text-sm rounded-md dark:bg-gray-600 dark:text-gray-300 m-1"
                          data-value="${value}">
                        <span>${label}</span>
                        <button type="button" class="js-remove-option ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                `);
                $searchInput.before($tag);
            });
        }

        function updateHiddenInputs() {
            console.log(
                `projectLoader.js: [${componentId}] Updating hidden inputs:`,
                currentSelectedValues,
            );
            $container.find(`input[name="${dataName}[]"]`).remove();
            $.each(currentSelectedValues, function (index, value) {
                $container.append(
                    $("<input>", {
                        type: "hidden",
                        name: `${dataName}[]`,
                        value: value,
                    }),
                );
            });
            // Trigger the callback if provided, indicating selection change
            if (onSelectionChangeCallback) {
                onSelectionChangeCallback(currentSelectedValues);
            }
        }

        // Custom event for external scripts to update options/selected values
        $container.on("options-updated", function (event, data) {
            console.log(
                `projectLoader.js: [${componentId}] Options-updated event triggered:`,
                data,
            );
            currentOptions = data?.options || [];
            currentSelectedValues = (data?.selected || [])
                .filter((val) =>
                    currentOptions.some(
                        (opt) => String(opt.value) === String(val),
                    ),
                )
                .map(String);

            // Update data attributes on the container for persistence/initial load
            $container.attr("data-options", JSON.stringify(currentOptions));
            $container.attr(
                "data-selected",
                JSON.stringify(currentSelectedValues),
            );

            renderSelectedTags();
            renderDropdownOptions($searchInput.val());
            updateHiddenInputs(); // This will also trigger onSelectionChangeCallback
        });

        // Event listener for toggling the dropdown
        $multiSelectContainer.on("click", function (e) {
            // Only toggle if the click is not on a selected tag or remove button
            if (
                !$(e.target).closest(".js-selected-option, .js-remove-option")
                    .length
            ) {
                console.log(
                    `projectLoader.js: [${componentId}] Multi-select container clicked, toggling dropdown`,
                );
                $dropdown.toggleClass("hidden");
                if (!$dropdown.hasClass("hidden")) {
                    renderDropdownOptions($searchInput.val());
                    $searchInput.focus();
                }
            }
        });

        // Event delegation for option clicks
        $optionsContainer.on("click", ".js-option", function (e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event from bubbling up to document
            console.log(
                `projectLoader.js: [${componentId}] js-option click handler triggered`,
            );
            const $option = $(this);
            const value = String($option.data("value"));
            const $checkbox = $option.find(".js-option-checkbox");

            if (currentSelectedValues.includes(value)) {
                currentSelectedValues = currentSelectedValues.filter(
                    (v) => v !== value,
                );
                $checkbox.prop("checked", false);
            } else {
                currentSelectedValues.push(value);
                $checkbox.prop("checked", true);
            }

            renderSelectedTags();
            updateHiddenInputs(); // This will trigger onSelectionChangeCallback
            renderDropdownOptions($searchInput.val()); // Re-render to update checkbox state
        });

        // Event delegation for checkbox clicks within options
        $optionsContainer.on("click", ".js-option-checkbox", function (e) {
            e.stopPropagation(); // Stop propagation to prevent parent .js-option from handling it twice
            console.log(
                `projectLoader.js: [${componentId}] js-option-checkbox click handler triggered`,
            );
            const $checkbox = $(this);
            const $option = $checkbox.closest(".js-option");
            const value = String($option.data("value"));

            // Toggle the selected state directly based on checkbox checked state
            if ($checkbox.is(":checked")) {
                if (!currentSelectedValues.includes(value)) {
                    currentSelectedValues.push(value);
                }
            } else {
                currentSelectedValues = currentSelectedValues.filter(
                    (v) => v !== value,
                );
            }

            renderSelectedTags();
            updateHiddenInputs(); // This will trigger onSelectionChangeCallback
            renderDropdownOptions($searchInput.val()); // Re-render to update checkbox state
        });

        // Event delegation for removing selected tags
        $multiSelectContainer.on("click", ".js-remove-option", function (e) {
            e.stopPropagation(); // Prevent bubbling to $multiSelectContainer
            console.log(
                `projectLoader.js: [${componentId}] js-remove-option click handler triggered`,
            );
            const $tag = $(this).closest(".js-selected-option");
            const valueToRemove = String($tag.data("value"));

            currentSelectedValues = currentSelectedValues.filter(
                (v) => v !== valueToRemove,
            );
            renderSelectedTags();
            updateHiddenInputs(); // This will trigger onSelectionChangeCallback
            renderDropdownOptions($searchInput.val());
        });

        // Search input handler
        $searchInput.on("input", function () {
            console.log(
                `projectLoader.js: [${componentId}] Search input changed:`,
                $(this).val(),
            );
            renderDropdownOptions($(this).val());
        });

        // Select All button handler
        $selectAllBtn.on("click", function (e) {
            e.preventDefault(); // Prevent form submission if button is type="submit"
            e.stopPropagation(); // Prevent bubbling
            console.log(
                `projectLoader.js: [${componentId}] Select All clicked`,
            );
            currentSelectedValues = currentOptions.map((option) =>
                String(option.value),
            );
            renderSelectedTags();
            updateHiddenInputs(); // This will trigger onSelectionChangeCallback
            renderDropdownOptions($searchInput.val());
            $dropdown.addClass("hidden"); // Close dropdown after select all
        });

        // Deselect All button handler
        $deselectAllBtn.on("click", function (e) {
            e.preventDefault(); // Prevent form submission if button is type="submit"
            e.stopPropagation(); // Prevent bubbling
            console.log(
                `projectLoader.js: [${componentId}] Deselect All clicked`,
            );
            currentSelectedValues = [];
            renderSelectedTags();
            updateHiddenInputs(); // This will trigger onSelectionChangeCallback
            renderDropdownOptions($searchInput.val());
        });

        // Initial render
        renderSelectedTags();
        renderDropdownOptions("");
    }

    // --- Global Document Click Handler for all custom multi-select dropdowns ---
    $(document).on("click", function (e) {
        // Find all multi-select containers
        const allMultiSelects = $(".js-multi-select");
        // Check if the click target is NOT within any of the multi-select containers
        if (!$(e.target).closest(allMultiSelects).length) {
            console.log(
                "projectLoader.js: Clicked outside any multi-select, closing all dropdowns.",
            );
            allMultiSelects.find(".js-dropdown").addClass("hidden");
        }
    });

    // --- Data Loading Logic for Projects and Users ---

    // References to the specific multi-select containers
    const directorateInput = $('input[name="directorate_id"].js-hidden-input');
    const projectsMultiSelect = $("#projects_multiselect");
    const usersMultiSelect = $("#users_multiselect");

    console.log(
        "projectLoader.js: Directorate input found:",
        directorateInput.length ? "Yes" : "No",
    );
    console.log(
        "projectLoader.js: Projects multi-select container found:",
        projectsMultiSelect.length ? "Yes" : "No",
    );
    console.log(
        "projectLoader.js: Users multi-select container found:",
        usersMultiSelect.length ? "Yes" : "No",
    );

    /**
     * Loads projects via AJAX based on the selected directorate ID.
     * @param {string} directorateId - The ID of the selected directorate.
     */
    function loadProjects(directorateId) {
        console.log(
            "projectLoader.js: Loading projects for directorate:",
            directorateId,
        );
        if (!directorateId) {
            // If no directorate is selected, clear the projects multi-select
            projectsMultiSelect.trigger("options-updated", {
                options: [],
                selected: [],
            });
            return;
        }

        // Show a loading message in the projects multi-select dropdown
        projectsMultiSelect
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading projects...</div>',
            );

        $.ajax({
            url: "/api/projects/" + encodeURIComponent(directorateId), // API URL for projects
            type: "GET",
            dataType: "json",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (data) {
                console.log(
                    "projectLoader.js: Raw projects API response:",
                    data,
                );
                const formattedData = Array.isArray(data)
                    ? data
                          .map((project) => ({
                              value: String(project.value),
                              label: String(project.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];

                // The initializeMultiSelect already handles data-selected parsing on init.
                // For dynamic updates, we pass the old value via the trigger.
                const oldProjects = JSON.parse(
                    projectsMultiSelect.attr("data-selected") || "[]",
                ).map(String);
                const validOldProjects = oldProjects.filter((projectId) =>
                    formattedData.some(
                        (opt) => String(opt.value) === String(projectId),
                    ),
                );

                projectsMultiSelect.trigger("options-updated", {
                    options: formattedData,
                    selected: validOldProjects,
                });
                console.log("projectLoader.js: Projects fetched and updated.");
            },
            error: function (xhr) {
                console.error(
                    "projectLoader.js: Projects AJAX error:",
                    xhr.status,
                    xhr.statusText,
                    xhr.responseJSON,
                );
                projectsMultiSelect.trigger("options-updated", {
                    options: [],
                    selected: [],
                });
                projectsMultiSelect
                    .find(".js-options-container")
                    .empty()
                    .append(
                        '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load projects.</div>',
                    );
                $("#error-message").removeClass("hidden");
                $("#error-text").text(
                    "Failed to load projects: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },
        });
    }

    /**
     * Loads users via AJAX based on the selected project IDs.
     * @param {Array<string>} projectIds - An array of selected project IDs.
     */
    function loadUsers(projectIds) {
        console.log(
            "projectLoader.js: Loading users for projects:",
            projectIds,
        );
        if (!projectIds || projectIds.length === 0) {
            // If no projects are selected, clear the users multi-select
            usersMultiSelect.trigger("options-updated", {
                options: [],
                selected: [],
            });
            return;
        }

        // Show a loading message in the users multi-select dropdown
        usersMultiSelect
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading assignees...</div>',
            );

        $.ajax({
            url: "/admin/tasks/users-by-projects",
            type: "GET",
            dataType: "json",
            data: { project_ids: projectIds }, // Pass project IDs as an array
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (data) {
                console.log("projectLoader.js: Raw users API response:", data);
                const formattedData = Array.isArray(data)
                    ? data
                          .map((user) => ({
                              value: String(user.value),
                              label: String(user.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];

                const oldUsers = JSON.parse(
                    usersMultiSelect.attr("data-selected") || "[]",
                ).map(String);
                const validOldUsers = oldUsers.filter((userId) =>
                    formattedData.some(
                        (opt) => String(opt.value) === String(userId),
                    ),
                );

                usersMultiSelect.trigger("options-updated", {
                    options: formattedData,
                    selected: validOldUsers,
                });
                console.log("projectLoader.js: Users fetched and updated.");
            },
            error: function (xhr) {
                console.error(
                    "projectLoader.js: Users AJAX error:",
                    xhr.status,
                    xhr.statusText,
                    xhr.responseJSON,
                );
                usersMultiSelect.trigger("options-updated", {
                    options: [],
                    selected: [],
                });
                usersMultiSelect
                    .find(".js-options-container")
                    .empty()
                    .append(
                        '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load assignees.</div>',
                    );
                $("#error-message").removeClass("hidden");
                $("#error-text").text(
                    "Failed to load assignees: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },
        });
    }

    // --- Initialize Components and Event Listeners ---

    // Initialize Projects multi-select and set up its selection change listener
    initializeMultiSelect(projectsMultiSelect, function (selectedProjectIds) {
        // This callback is triggered when projects selection changes
        loadUsers(selectedProjectIds);
    });

    // Initialize Users multi-select (no specific selection change callback needed here)
    initializeMultiSelect(usersMultiSelect);

    // Listen for changes on the Directorate select (which is a standard select or js-single-select)
    directorateInput.on("change", function () {
        console.log("projectLoader.js: Directorate changed:", $(this).val());
        const selectedDirectorateId = $(this).val();
        loadProjects(selectedDirectorateId);
        // Clear users when directorate changes, as projects will change
        usersMultiSelect.trigger("options-updated", {
            options: [],
            selected: [],
        });
    });

    // Initial load of projects if a directorate is already selected on page load
    if (directorateInput.val()) {
        console.log(
            "projectLoader.js: Initial directorate:",
            directorateInput.val(),
        );
        loadProjects(directorateInput.val());
    } else {
        projectsMultiSelect.trigger("options-updated", {
            options: [],
            selected: [],
        });
    }

    // Initial load of users if projects are already selected on page load
    // This needs to happen after projects are potentially loaded, so it's triggered by the projects' initial update.
    // However, if the page loads with pre-selected projects AND pre-selected users, we need to handle that.
    // The `initializeMultiSelect` for users already handles its `data-selected` attribute.
    // The `loadProjects` callback will handle triggering `loadUsers` if projects are initially loaded.
    // If there are initial projects, the `loadUsers` will be called via the `initializeMultiSelect` callback.
    // If there are no initial projects, `loadUsers` will be called with an empty array.

    // Close global error message (if it exists on the page)
    $("#close-error").on("click", function () {
        console.log("projectLoader.js: Close error clicked");
        $("#error-message").addClass("hidden");
        $("#error-text").text("");
    });
});
