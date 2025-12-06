function waitForDependencies(callback, retries = 50) {
    if (document.readyState === "complete" && typeof jQuery !== "undefined") {
        callback();
    } else if (retries > 0) {
        console.warn(
            "DOM/jQuery not ready, retrying...",
            `jQuery: ${typeof jQuery !== "undefined" ? jQuery.fn.jquery : "undefined"}`,
            `DOM: ${document.readyState}`,
            `Retries left: ${retries}`,
        );
        setTimeout(() => waitForDependencies(callback, retries - 1), 100);
    } else {
        console.error("Failed to load dependencies after maximum retries");
    }
}

waitForDependencies(function () {
    const $ = jQuery;
    console.log("Dependencies loaded:", {
        jQueryVersion: $.fn.jquery,
        DOMState: document.readyState,
    });

    // ======================
    // 1. CUSTOM DROPDOWN COMPONENT (for budget table only)
    // ======================
    $(".js-budget-single-select").each(function () {
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
                `Required elements not found in container #${componentId}. Container HTML:`,
                $container[0].outerHTML,
            );
            return;
        }

        // Unbind potential app.js event handlers
        $(document).off("click.select", `#${componentId} .js-option`);
        $optionsContainer.off("click.select");
        $container.find(".js-toggle-dropdown").off("click.select");
        $searchInput.off("click.select");

        function renderOptions(searchTerm = "") {
            console.log(`Rendering options for ${componentId}:`, {
                options: currentOptions,
                selected: currentSelectedValue,
                searchTerm,
                rowIndex: $container.closest(".budget-entry-row").index(),
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
                    <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                         data-value="${option.value}">
                        ${option.label}
                    </div>
                `);
                $optionsContainer.append($option);
            });

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
                rowIndex: $container.closest(".budget-entry-row").index(),
            });
        }

        // Event: Options updated
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

        // Event: Option selected
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
                console.log(`Option selected for ${componentId}:`, {
                    selectedValue: currentSelectedValue,
                    rowIndex: $container.closest(".budget-entry-row").index(),
                    hiddenInputName: $hiddenInput.attr("name"),
                    hiddenInputValue: $hiddenInput.val(),
                });
            });

        // Event: Search input
        $searchInput.off("input").on("input", function () {
            renderOptions($(this).val());
        });

        // Event: Toggle dropdown
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

        // Event: Close dropdown when clicking outside
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

        // Initial render
        console.log(`Initial options for ${componentId}:`, currentOptions);
        console.log(
            `Initial selected value for ${componentId}:`,
            currentSelectedValue,
        );
        renderOptions();
    });

    // ======================
    // 2. DEPENDENT DROPDOWN LOGIC
    // ======================
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

    // Validation check
    if (!directorateContainer.length || !directorateInput.length) {
        console.error(
            "Directorate container or input not found. Available IDs:",
            $("[id]")
                .map((i, el) => el.id)
                .get(),
        );
    }

    if (!departmentSelectContainer.length) {
        console.error(
            "Department select container not found. Available .js-single-select elements:",
            $(".js-single-select")
                .map((i, el) => ({
                    id: el.id,
                    dataName: $(el).data("name"),
                }))
                .get(),
        );
    }

    if (!userSelectContainer.length) {
        console.error(
            "User select container not found. Available .js-single-select elements:",
            $(".js-single-select")
                .map((i, el) => ({
                    id: el.id,
                    dataName: $(el).data("name"),
                }))
                .get(),
        );
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
        container.trigger("options-updated", { options, selected });
    }

    function loadDepartments(directorateId) {
        if (!directorateId) {
            console.log("Clearing department options");
            updateSelectOptions(departmentSelectContainer, [], "");
            return;
        }

        const $optionsContainer = departmentSelectContainer.find(
            ".js-options-container",
        );
        if (!$optionsContainer.length) {
            console.error(
                "No .js-options-container found. Container HTML:",
                departmentSelectContainer[0].outerHTML,
            );
            return;
        }

        // Show loading state
        $optionsContainer.html(
            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>',
        );

        // Reset current selection
        departmentSelectContainer.data("selected", "");
        departmentSelectContainer.attr("data-selected", "");
        departmentSelectContainer.find(".js-hidden-input").val("");
        departmentSelectContainer
            .find(".js-selected-label")
            .text(
                departmentSelectContainer.data("placeholder") ||
                    "Select an option",
            );

        // AJAX request
        $.ajax({
            url: `/admin/projects/departments/${encodeURIComponent(directorateId)}`,
            method: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            beforeSend: function () {
                console.log("AJAX request started for departments:", this.url);
            },
            success: function (data) {
                console.log("Departments response:", data);
                const formattedData = Array.isArray(data)
                    ? data
                          .map((dept) => ({
                              value: String(dept.value),
                              label: String(dept.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];

                updateSelectOptions(
                    departmentSelectContainer,
                    formattedData,
                    "",
                );
            },
            error: function (xhr) {
                console.error("AJAX error for departments:", xhr);
                updateSelectOptions(departmentSelectContainer, [], "");
                $optionsContainer.html(
                    '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load departments</div>',
                );

                // Show error message
                $("#error-message").removeClass("hidden");
                $("#error-text").text(
                    "Failed to load departments: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },
            complete: function () {
                console.log(
                    "AJAX request completed for directorate:",
                    directorateId,
                );
            },
        });
    }

    function loadUsers(directorateId) {
        if (!directorateId) {
            console.log("Clearing user options");
            updateSelectOptions(userSelectContainer, [], "");
            return;
        }

        const $optionsContainer = userSelectContainer.find(
            ".js-options-container",
        );
        if (!$optionsContainer.length) {
            console.error(
                "No .js-options-container found. Container HTML:",
                userSelectContainer[0].outerHTML,
            );
            return;
        }

        // Show loading state
        $optionsContainer.html(
            '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading...</div>',
        );

        // Reset current selection
        userSelectContainer.data("selected", "");
        userSelectContainer.attr("data-selected", "");
        userSelectContainer.find(".js-hidden-input").val("");
        userSelectContainer
            .find(".js-selected-label")
            .text(
                userSelectContainer.data("placeholder") || "Select an option",
            );

        // AJAX request
        $.ajax({
            url: `/admin/projects/users/${encodeURIComponent(directorateId)}`,
            method: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            beforeSend: function () {
                console.log("AJAX request started for users:", this.url);
            },
            success: function (data) {
                console.log("Users response:", data);
                const formattedData = Array.isArray(data)
                    ? data
                          .map((user) => ({
                              value: String(user.value),
                              label: String(user.label),
                          }))
                          .filter((opt) => opt.value && opt.label)
                    : [];

                updateSelectOptions(userSelectContainer, formattedData, "");
            },
            error: function (xhr) {
                console.error("AJAX error for users:", xhr);
                updateSelectOptions(userSelectContainer, [], "");
                $optionsContainer.html(
                    '<div class="px-4 py-2 text-sm text-red-500 dark:text-red-400">Failed to load users</div>',
                );

                // Show error message
                $("#error-message").removeClass("hidden");
                $("#error-text").text(
                    "Failed to load users: " +
                        (xhr.responseJSON?.message || "Unknown error"),
                );
            },
            complete: function () {
                console.log(
                    "AJAX request completed for directorate:",
                    directorateId,
                );
            },
        });
    }

    // Event: Directorate change
    directorateInput.off("change").on("change", function () {
        const directorateId = $(this).val();
        console.log("Directorate changed:", directorateId);
        loadDepartments(directorateId);
        loadUsers(directorateId);
    });

    // Initial load
    if (directorateInput.val()) {
        console.log("Initial directorate:", directorateInput.val());
        loadDepartments(directorateInput.val());
        loadUsers(directorateInput.val());
    } else {
        console.log("No initial directorate - clearing options");
        updateSelectOptions(departmentSelectContainer, [], "");
        updateSelectOptions(userSelectContainer, [], "");
    }

    // Error message close handler
    $("#close-error").on("click", function () {
        $("#error-message").addClass("hidden");
        $("#error-text").text("");
    });

    // ======================
    // 3. BUDGET TABLE COMPONENT
    // ======================
    const budgetTableBody = $("#budget-entries-table tbody");
    const addBudgetButton = $("#add-budget-row");

    function updateRowIndices() {
        budgetTableBody.find(".budget-entry-row").each(function (index) {
            const $row = $(this);

            // Update fiscal year dropdown
            $row.find(".js-budget-single-select").each(function () {
                const $container = $(this);
                const name = $container.data("name");
                if (name && name.includes("fiscal_year_id")) {
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

                    // Preserve selection
                    const existingValue = $container.attr("data-selected");
                    const $hiddenInput = $container.find(".js-hidden-input");
                    const $selectedLabel =
                        $container.find(".js-selected-label");
                    const options = JSON.parse(
                        $container.attr("data-options") || "[]",
                    );

                    if (!existingValue) {
                        $container.data("selected", "");
                        $container.attr("data-selected", "");
                        $selectedLabel.text(
                            $container.data("placeholder") ||
                                "Select an option",
                        );
                        $hiddenInput.val("");
                    } else {
                        const selectedOption = options.find(
                            (opt) => opt.value === existingValue,
                        );
                        $selectedLabel.text(
                            selectedOption
                                ? selectedOption.label
                                : $container.data("placeholder") ||
                                      "Select an option",
                        );
                        $hiddenInput.val(existingValue);
                    }

                    // Update hidden input
                    $hiddenInput.attr(
                        "name",
                        `budgets[${index}][fiscal_year_id]`,
                    );

                    // Reinitialize dropdown
                    // Unbind app.js event handlers
                    $(document).off("click.select", `#${newId} .js-option`);
                    $container
                        .find(".js-options-container")
                        .off("click.select");
                    $container.find(".js-toggle-dropdown").off("click.select");
                    $container.find(".js-search-input").off("click.select");

                    // Initialize dropdown
                    $container.each(function () {
                        const $container = $(this);
                        const componentId = $container.attr("id");
                        let currentOptions = $container.data("options") || [];
                        let currentSelectedValue =
                            $container.data("selected") || "";
                        const $optionsContainer = $container.find(
                            ".js-options-container",
                        );
                        const $selectedLabel =
                            $container.find(".js-selected-label");
                        const $hiddenInput = $container.find(
                            "input.js-hidden-input",
                        );
                        const $dropdown = $container.find(".js-dropdown");
                        const $searchInput =
                            $container.find(".js-search-input");

                        function renderOptions(searchTerm = "") {
                            $optionsContainer.empty();
                            if (
                                !currentOptions ||
                                currentOptions.length === 0
                            ) {
                                $container
                                    .find(".js-no-options")
                                    .removeClass("hidden");
                                $selectedLabel.text(
                                    $container.data("placeholder") ||
                                        "Select an option",
                                );
                                $hiddenInput.val("");
                                currentSelectedValue = "";
                                $container.data("selected", "");
                                $container.attr("data-selected", "");
                                return;
                            }

                            $container
                                .find(".js-no-options")
                                .addClass("hidden");
                            const filteredOptions = searchTerm
                                ? currentOptions.filter((option) =>
                                      option.label
                                          .toLowerCase()
                                          .includes(searchTerm.toLowerCase()),
                                  )
                                : currentOptions;

                            $.each(filteredOptions, function (index, option) {
                                const $option = $(`
                                    <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                                         data-value="${option.value}">
                                        ${option.label}
                                    </div>
                                `);
                                $optionsContainer.append($option);
                            });

                            const selectedOption = currentOptions.find(
                                (opt) =>
                                    String(opt.value) ===
                                    String(currentSelectedValue),
                            );

                            $selectedLabel.text(
                                selectedOption
                                    ? selectedOption.label
                                    : $container.data("placeholder") ||
                                          "Select an option",
                            );

                            $hiddenInput.val(
                                selectedOption ? currentSelectedValue : "",
                            );

                            if (!selectedOption) {
                                currentSelectedValue = "";
                                $container.data("selected", "");
                                $container.attr("data-selected", "");
                            }
                        }

                        function updateHiddenInput() {
                            $hiddenInput.val(currentSelectedValue || "");
                            $hiddenInput.trigger("change");
                            console.log(
                                `Updated hidden input for ${componentId}:`,
                                {
                                    name: $container.data("name"),
                                    value: currentSelectedValue,
                                    rowIndex: $container
                                        .closest(".budget-entry-row")
                                        .index(),
                                },
                            );
                        }

                        // Event: Options updated
                        $container
                            .off("options-updated")
                            .on("options-updated", function (event, data) {
                                currentOptions =
                                    data?.options ||
                                    $container.data("options") ||
                                    [];
                                currentSelectedValue =
                                    data?.selected &&
                                    currentOptions.some(
                                        (opt) =>
                                            String(opt.value) ===
                                            String(data.selected),
                                    )
                                        ? String(data.selected)
                                        : "";
                                renderOptions();
                                updateHiddenInput();
                            });

                        // Event: Option selected
                        $optionsContainer
                            .off("click", ".js-option")
                            .on("click", ".js-option", function (e) {
                                e.stopPropagation();
                                const $option = $(this);
                                currentSelectedValue = $option.data("value");
                                $container.data(
                                    "selected",
                                    currentSelectedValue,
                                );
                                $container.attr(
                                    "data-selected",
                                    currentSelectedValue,
                                );
                                $selectedLabel.text($option.text().trim());
                                $dropdown.addClass("hidden");
                                updateHiddenInput();
                                console.log(
                                    `Option selected for ${componentId}:`,
                                    {
                                        selectedValue: currentSelectedValue,
                                        rowIndex: $container
                                            .closest(".budget-entry-row")
                                            .index(),
                                        hiddenInputName:
                                            $hiddenInput.attr("name"),
                                        hiddenInputValue: $hiddenInput.val(),
                                    },
                                );
                            });

                        // Event: Search input
                        $searchInput.off("input").on("input", function () {
                            renderOptions($(this).val());
                        });

                        // Event: Toggle dropdown
                        $container
                            .find(".js-toggle-dropdown")
                            .off("click")
                            .on("click", function (e) {
                                e.stopPropagation();
                                $dropdown.toggleClass("hidden");
                                if (!$dropdown.hasClass("hidden")) {
                                    $searchInput.focus();
                                }
                            });

                        // Event: Close dropdown when clicking outside
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

                        // Initial render
                        renderOptions();
                    });
                }
            });

            // Update other inputs
            $row.find("input:not(.js-hidden-input)").each(function () {
                const $input = $(this);
                $input.attr(
                    "name",
                    $input
                        .attr("name")
                        .replace(/budgets\[\d+\]/, `budgets[${index}]`),
                );

                $input.attr(
                    "id",
                    $input.attr("id").replace(/(\w+)_\d+/, `$1_${index}`),
                );
            });

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

    // Event: Add budget row
    addBudgetButton.on("click", function () {
        const lastRow = budgetTableBody.find(".budget-entry-row").last();
        const newRow = lastRow.clone(true);

        // Clear values
        newRow.find("input").val("");
        newRow.find(".js-selected-label").text("Select an option...");
        newRow.find(".js-hidden-input").val("");
        newRow
            .find(".js-budget-single-select")
            .attr("data-selected", "")
            .data("selected", "");

        // Remove validation errors
        newRow.find(".text-red-600").remove();
        newRow.find(".border-red-500").removeClass("border-red-500");

        // Add to table
        budgetTableBody.append(newRow);
        updateRowIndices();
    });

    // Event: Remove budget row
    budgetTableBody.on("click", ".remove-budget-btn", function () {
        if (budgetTableBody.find(".budget-entry-row").length > 1) {
            $(this).closest(".budget-entry-row").remove();
            updateRowIndices();
        }
    });

    // Initial setup
    updateRowIndices();
});
