// resources/js/formDependencies.js

function waitForJQuery(callback, retries = 50) {
    if (
        typeof jQuery !== "undefined" &&
        jQuery.fn.jquery &&
        document.readyState === "complete"
    ) {
        callback();
    } else if (retries > 0) {
        console.warn(
            "formDependencies.js: jQuery or DOM not ready, retrying... Retries left:",
            retries,
        );
        setTimeout(function () {
            waitForJQuery(callback, retries - 1);
        }, 100);
    } else {
        console.error(
            "formDependencies.js: Failed to load jQuery or DOM after maximum retries.",
        );
    }
}

waitForJQuery(function () {
    const $ = jQuery;
    console.log("formDependencies.js: jQuery loaded, version:", $.fn.jquery);

    // --- Helper to trigger options-updated event on custom select components ---
    // This event is listened to by the generic initializeSelect function in app.js
    function updateCustomSelectOptions(container, options, selected) {
        console.log(
            `formDependencies.js: Triggering 'options-updated' for ${container.attr("id") || container.data("name")}`,
            { options, selected },
        );
        container.trigger("options-updated", {
            options: options,
            selected: selected,
        });
    }

    // --- Dependent Dropdown Loader Logic (for Project Create, Task Create, and User Create forms) ---

    // Elements for Project Create Form (Directorate -> Department & Project Manager)
    const projectCreateDirectorateContainer = $(
        '.js-single-select[data-name="directorate_id"]#directorate_id',
    );
    const projectCreateDepartmentSelectContainer = $(
        '.js-single-select[data-name="department_id"]',
    );
    const projectCreateProjectManagerSelectContainer = $(
        '.js-single-select[data-name="project_manager"]',
    );

    // Elements for Task Create Form (Directorate -> Projects -> Assignees)
    const taskCreateDirectorateContainer = $(
        '.js-single-select[data-name="directorate_id"]#directorate_id',
    ); // Same ID as project create, handle carefully
    const taskCreateProjectsMultiSelectContainer = $("#projects_multiselect"); // This is a multi-select
    const taskCreateUsersMultiSelectContainer = $("#users_multiselect"); // This is a multi-select

    // Elements for User Create Form (Directorate -> Projects)
    const userCreateDirectorateContainer = $(
        '.js-single-select[data-name="directorate_id"]#directorate_id',
    ); // Same ID as others
    const userCreateProjectsMultiSelectContainer = $("#projects_multiselect"); // This is a multi-select

    // --- Functions for loading data ---

    // Load Departments for Project Create (Directorate -> Department)
    function loadDepartmentsForProject(directorateId) {
        console.log(
            "formDependencies.js: Loading departments for project create, directorate:",
            directorateId,
        );
        if (!projectCreateDepartmentSelectContainer.length) return;

        if (!directorateId) {
            updateCustomSelectOptions(
                projectCreateDepartmentSelectContainer,
                [],
                "",
            );
            return;
        }

        // Show loading state
        projectCreateDepartmentSelectContainer
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading departments...</div>',
            );

        $.ajax({
            url: `/admin/projects/departments/${encodeURIComponent(directorateId)}`,
            method: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data) {
                const formattedData = Array.isArray(data)
                    ? data
                          .map((dept) => ({
                              value: String(dept.value),
                              label: String(dept.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];
                const oldSelected =
                    projectCreateDepartmentSelectContainer.data("selected"); // Get previously selected value
                const validSelected = formattedData.some(
                    (opt) => String(opt.value) === String(oldSelected),
                )
                    ? oldSelected
                    : "";
                updateCustomSelectOptions(
                    projectCreateDepartmentSelectContainer,
                    formattedData,
                    validSelected,
                );
            },
            error: function (xhr) {
                console.error(
                    "formDependencies.js: AJAX error loading departments:",
                    xhr,
                );
                updateCustomSelectOptions(
                    projectCreateDepartmentSelectContainer,
                    [],
                    "",
                );
                $("#error-message")
                    .removeClass("hidden")
                    .find("#error-text")
                    .text(
                        "Failed to load departments: " +
                            (xhr.responseJSON?.message || "Unknown error"),
                    );
            },
        });
    }

    // Load Project Managers (Users) for Project Create (Directorate -> Project Manager)
    function loadProjectManagersForProject(directorateId) {
        console.log(
            "formDependencies.js: Loading project managers for project create, directorate:",
            directorateId,
        );
        if (!projectCreateProjectManagerSelectContainer.length) return;

        if (!directorateId) {
            updateCustomSelectOptions(
                projectCreateProjectManagerSelectContainer,
                [],
                "",
            );
            return;
        }

        // Show loading state
        projectCreateProjectManagerSelectContainer
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading project managers...</div>',
            );

        $.ajax({
            url: `/admin/projects/users/${encodeURIComponent(directorateId)}`, // Assuming this route gives users for a directorate
            method: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (data) {
                const formattedData = Array.isArray(data)
                    ? data
                          .map((user) => ({
                              value: String(user.value),
                              label: String(user.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];
                const oldSelected =
                    projectCreateProjectManagerSelectContainer.data("selected");
                const validSelected = formattedData.some(
                    (opt) => String(opt.value) === String(oldSelected),
                )
                    ? oldSelected
                    : "";
                updateCustomSelectOptions(
                    projectCreateProjectManagerSelectContainer,
                    formattedData,
                    validSelected,
                );
            },
            error: function (xhr) {
                console.error(
                    "formDependencies.js: AJAX error loading project managers:",
                    xhr,
                );
                updateCustomSelectOptions(
                    projectCreateProjectManagerSelectContainer,
                    [],
                    "",
                );
                $("#error-message")
                    .removeClass("hidden")
                    .find("#error-text")
                    .text(
                        "Failed to load project managers: " +
                            (xhr.responseJSON?.message || "Unknown error"),
                    );
            },
        });
    }

    // Load Projects for Task Create or User Create (Directorate -> Projects)
    function loadProjects(directorateId, targetMultiSelectContainer) {
        console.log(
            `formDependencies.js: Loading projects for ${targetMultiSelectContainer.attr("id")}, directorate:`,
            directorateId,
        );
        if (!targetMultiSelectContainer.length) return;

        if (!directorateId) {
            updateCustomSelectOptions(targetMultiSelectContainer, [], []);
            return;
        }

        // Show loading state
        targetMultiSelectContainer
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading projects...</div>',
            );

        $.ajax({
            url: `/api/projects/${encodeURIComponent(directorateId)}`, // API URL for projects
            type: "GET",
            dataType: "json",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (data) {
                const formattedData = Array.isArray(data)
                    ? data
                          .map((project) => ({
                              value: String(project.value),
                              label: String(project.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];
                const oldSelected =
                    targetMultiSelectContainer.data("selected") || [];
                const validSelected = oldSelected.filter((projectId) =>
                    formattedData.some(
                        (opt) => String(opt.value) === String(projectId),
                    ),
                );
                updateCustomSelectOptions(
                    targetMultiSelectContainer,
                    formattedData,
                    validSelected,
                );
            },
            error: function (xhr) {
                console.error(
                    "formDependencies.js: AJAX error loading projects:",
                    xhr,
                );
                updateCustomSelectOptions(targetMultiSelectContainer, [], []);
                $("#error-message")
                    .removeClass("hidden")
                    .find("#error-text")
                    .text(
                        "Failed to load projects: " +
                            (xhr.responseJSON?.message || "Unknown error"),
                    );
            },
        });
    }

    // Load Users by Projects for Task Create (Projects -> Assignees)
    function loadUsersByProjects(projectIds) {
        console.log(
            "formDependencies.js: Loading users by projects, project IDs:",
            projectIds,
        );
        if (!taskCreateUsersMultiSelectContainer.length) return;

        if (!projectIds || projectIds.length === 0) {
            updateCustomSelectOptions(
                taskCreateUsersMultiSelectContainer,
                [],
                [],
            );
            return;
        }

        // Show loading state
        taskCreateUsersMultiSelectContainer
            .find(".js-options-container")
            .empty()
            .append(
                '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading assignees...</div>',
            );

        $.ajax({
            url: `/admin/tasks/users-by-projects?${$.param({ project_ids: projectIds })}`, // Use $.param for array in query string
            method: "GET",
            dataType: "json",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (data) {
                const formattedData = Array.isArray(data)
                    ? data
                          .map((user) => ({
                              value: String(user.value),
                              label: String(user.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];
                const oldSelected =
                    taskCreateUsersMultiSelectContainer.data("selected") || [];
                const validSelected = oldSelected.filter((userId) =>
                    formattedData.some(
                        (opt) => String(opt.value) === String(userId),
                    ),
                );
                updateCustomSelectOptions(
                    taskCreateUsersMultiSelectContainer,
                    formattedData,
                    validSelected,
                );
            },
            error: function (xhr) {
                console.error(
                    "formDependencies.js: AJAX error loading users by projects:",
                    xhr,
                );
                updateCustomSelectOptions(
                    taskCreateUsersMultiSelectContainer,
                    [],
                    [],
                );
                $("#error-message")
                    .removeClass("hidden")
                    .find("#error-text")
                    .text(
                        "Failed to load assignees: " +
                            (xhr.responseJSON?.message || "Unknown error"),
                    );
            },
        });
    }

    // --- Budget Details Logic (from projectBudget.js) ---
    const budgetTableBody = $("#budget-entries-table tbody");
    const addBudgetButton = $("#add-budget-row");

    /**
     * Updates the indices of form fields in budget rows.
     * It also ensures that newly added single-selects are picked up by app.js's initialization.
     */
    function updateRowIndices() {
        budgetTableBody.find(".budget-entry-row").each(function (index) {
            const $row = $(this);

            // Update name and id attributes for inputs
            $row.find("input:not(.js-hidden-input)").each(function () {
                const $element = $(this);
                const originalName = $element.attr("name");
                const originalId = $element.attr("id");

                if (originalName) {
                    $element.attr(
                        "name",
                        originalName.replace(
                            /budgets\[\d+\]/,
                            `budgets[${index}]`,
                        ),
                    );
                }
                if (originalId) {
                    // Update ID for the element itself
                    const newId = originalId.replace(/_\d+$/, `_${index}`);
                    $element.attr("id", newId);
                    // Update 'for' attribute of any associated label
                    $(`label[for="${originalId}"]`).attr("for", newId);
                }
            });

            // For the custom single-select component (fiscal_year_id), update its data-name and ID
            $row.find('.js-single-select[data-name*="fiscal_year_id"]').each(
                function () {
                    const $container = $(this);
                    const newId = `fiscal_year_id_${index}`;
                    $container.attr("id", newId);
                    $container.data(
                        "name",
                        `budgets[${index}][fiscal_year_id]`,
                    );
                    $container.attr(
                        "data-name",
                        `budgets[${index}][fiscal_year_id]`,
                    );

                    // Ensure the `options-updated` event is triggered for this newly indexed select
                    // with its initial options, so app.js can re-render it correctly.
                    const options = JSON.parse(
                        $container.attr("data-options") || "[]",
                    );
                    const selected = $container.data("selected") || "";
                    updateCustomSelectOptions($container, options, selected);
                },
            );

            // Update remove button state
            const removeButton = $row.find(".remove-budget-btn");
            if (budgetTableBody.find(".budget-entry-row").length === 1) {
                removeButton
                    .prop("disabled", true)
                    .addClass("opacity-50 cursor-not-allowed");
            } else {
                removeButton
                    .prop("disabled", false)
                    .removeClass("opacity-50 cursor-not-allowed");
            }
        });
    }

    // Event listener for adding a new budget row
    addBudgetButton.on("click", function () {
        const lastRow = budgetTableBody.find(".budget-entry-row").last();
        const newRow = lastRow.clone(true); // Clone with event handlers

        // Clear input values
        newRow.find("input").val("");

        // For js-single-select elements in the new row:
        newRow.find(".js-single-select").each(function () {
            const $container = $(this);
            // Get original options from the template row's data-options
            const originalOptions = JSON.parse(
                lastRow
                    .find('.js-single-select[data-name*="fiscal_year_id"]')
                    .attr("data-options") || "[]",
            );

            // Clear the selected value in the component's internal data and attributes
            $container.data("selected", "");
            $container.attr("data-selected", "");
            // Clear the hidden input value
            $container.find("input.js-hidden-input").val("");
            // Reset the displayed label to the placeholder
            $container
                .find(".js-selected-label")
                .text($container.data("placeholder") || "Select an option");
            // Ensure dropdown is hidden
            $container.find(".js-dropdown").addClass("hidden");
            // Trigger options-updated to re-render with cleared state (app.js will handle this)
            updateCustomSelectOptions($container, originalOptions, "");
        });

        // Remove any existing error messages (if cloned from a row with errors)
        newRow.find(".text-red-500").remove(); // Assuming error messages have this class
        newRow.find(".border-red-500").removeClass("border-red-500"); // Remove error borders

        budgetTableBody.append(newRow);
        updateRowIndices(); // Re-index all rows
    });

    // Event listener for removing a budget row
    budgetTableBody.on("click", ".remove-budget-btn", function () {
        if (budgetTableBody.find(".budget-entry-row").length > 1) {
            $(this).closest(".budget-entry-row").remove();
            updateRowIndices();
        }
    });

    // --- Event Listeners and Initial Calls for all forms ---

    // Project Create Form: Directorate change listener
    if (projectCreateDirectorateContainer.length) {
        projectCreateDirectorateContainer
            .find("input.js-hidden-input")
            .on("change", function () {
                const directorateId = $(this).val();
                console.log(
                    "formDependencies.js: Project Create Directorate changed:",
                    directorateId,
                );
                loadDepartmentsForProject(directorateId);
                loadProjectManagersForProject(directorateId);
            });

        // Initial load for Project Create form
        const initialDirectorateIdProject = projectCreateDirectorateContainer
            .find("input.js-hidden-input")
            .val();
        if (initialDirectorateIdProject) {
            console.log(
                "formDependencies.js: Initial load for Project Create with directorate:",
                initialDirectorateIdProject,
            );
            loadDepartmentsForProject(initialDirectorateIdProject);
            loadProjectManagersForProject(initialDirectorateIdProject);
        } else {
            // Ensure dependent selects are cleared if no initial directorate
            updateCustomSelectOptions(
                projectCreateDepartmentSelectContainer,
                [],
                "",
            );
            updateCustomSelectOptions(
                projectCreateProjectManagerSelectContainer,
                [],
                "",
            );
        }
    }

    // Task Create Form: Directorate change listener
    if (
        taskCreateDirectorateContainer.length &&
        taskCreateProjectsMultiSelectContainer.length
    ) {
        taskCreateDirectorateContainer
            .find("input.js-hidden-input")
            .on("change", function () {
                const directorateId = $(this).val();
                console.log(
                    "formDependencies.js: Task Create Directorate changed:",
                    directorateId,
                );
                loadProjects(
                    directorateId,
                    taskCreateProjectsMultiSelectContainer,
                );
                // Clear assignees when directorate changes, as projects will change
                updateCustomSelectOptions(
                    taskCreateUsersMultiSelectContainer,
                    [],
                    [],
                );
            });

        // Task Create Form: Projects multi-select selection change listener
        taskCreateProjectsMultiSelectContainer.on(
            "change",
            "input.js-hidden-input-multi",
            function () {
                const selectedProjectIds = $(this)
                    .closest(".js-multi-select")
                    .data("selected"); // Get selected values from data
                console.log(
                    "formDependencies.js: Task Create Projects selection changed:",
                    selectedProjectIds,
                );
                loadUsersByProjects(selectedProjectIds);
            },
        );
        // Also listen for the custom 'change.no-selection' event if no projects are selected
        taskCreateProjectsMultiSelectContainer.on(
            "change.no-selection",
            function () {
                console.log(
                    "formDependencies.js: Task Create Projects selection cleared (no-selection event)",
                );
                loadUsersByProjects([]);
            },
        );

        // Initial load for Task Create form
        const initialDirectorateIdTask = taskCreateDirectorateContainer
            .find("input.js-hidden-input")
            .val();
        if (initialDirectorateIdTask) {
            console.log(
                "formDependencies.js: Initial load for Task Create with directorate:",
                initialDirectorateIdTask,
            );
            loadProjects(
                initialDirectorateIdTask,
                taskCreateProjectsMultiSelectContainer,
            );
            // Users will be loaded once projects are updated via the projects change listener
        } else {
            // Ensure dependent selects are cleared if no initial directorate
            updateCustomSelectOptions(
                taskCreateProjectsMultiSelectContainer,
                [],
                [],
            );
            updateCustomSelectOptions(
                taskCreateUsersMultiSelectContainer,
                [],
                [],
            );
        }
    }

    // User Create Form: Directorate change listener
    if (
        userCreateDirectorateContainer.length &&
        userCreateProjectsMultiSelectContainer.length
    ) {
        userCreateDirectorateContainer
            .find("input.js-hidden-input")
            .on("change", function () {
                const directorateId = $(this).val();
                console.log(
                    "formDependencies.js: User Create Directorate changed:",
                    directorateId,
                );
                loadProjects(
                    directorateId,
                    userCreateProjectsMultiSelectContainer,
                );
            });

        // Initial load for User Create form
        const initialDirectorateIdUser = userCreateDirectorateContainer
            .find("input.js-hidden-input")
            .val();
        if (initialDirectorateIdUser) {
            console.log(
                "formDependencies.js: Initial load for User Create with directorate:",
                initialDirectorateIdUser,
            );
            loadProjects(
                initialDirectorateIdUser,
                userCreateProjectsMultiSelectContainer,
            );
        } else {
            updateCustomSelectOptions(
                userCreateProjectsMultiSelectContainer,
                [],
                [],
            );
        }
    }

    // Initial call for budget rows (if on Project Create form)
    if (budgetTableBody.length) {
        updateRowIndices();
    }

    // General error message close button
    $("#close-error").on("click", function () {
        console.log("formDependencies.js: Close error clicked");
        $("#error-message").addClass("hidden");
        $("#error-text").text("");
    });
});
