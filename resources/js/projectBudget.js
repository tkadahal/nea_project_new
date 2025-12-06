// Ensure jQuery is loaded before executing this script
function waitForJQuery(callback, retries = 50) {
    if (
        typeof jQuery !== "undefined" &&
        jQuery.fn.jquery &&
        document.readyState === "complete"
    ) {
        callback();
    } else if (retries > 0) {
        console.warn(
            "jQuery or DOM not ready, retrying... jQuery:",
            typeof jQuery !== "undefined" ? jQuery.fn.jquery : "undefined",
            "DOM:",
            document.readyState,
            "Retries left:",
            retries,
        );
        setTimeout(function () {
            waitForJQuery(callback, retries - 1);
        }, 100);
    } else {
        console.error("Failed to load jQuery or DOM after maximum retries.");
    }
}

waitForJQuery(function () {
    const $ = jQuery;

    // --- Budget Details Logic ---
    const budgetTableBody = $("#budget-entries-table tbody");
    const addBudgetButton = $("#add-budget-row");

    /**
     * Updates the indices of form fields in budget rows and manages the state
     * of the "remove" button for each row.
     */
    function updateRowIndices() {
        budgetTableBody.find(".budget-entry-row").each(function (index) {
            const $row = $(this);

            // Update fiscal year select container
            $row.find(".js-single-select").each(function () {
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

                    // Preserve existing selected value if it exists
                    const existingSelectedValue =
                        $container.attr("data-selected");
                    const $hiddenInput = $container.find(".js-hidden-input");
                    const $selectedLabel =
                        $container.find(".js-selected-label");
                    const options = JSON.parse(
                        $container.attr("data-options") || "[]",
                    );

                    // Only reset if no selected value exists (e.g., for new rows)
                    if (!existingSelectedValue) {
                        $container.data("selected", "");
                        $container.attr("data-selected", "");
                        $selectedLabel.text(
                            $container.data("placeholder") ||
                                "Select an option",
                        );
                        $hiddenInput.val("");
                    } else {
                        // Find the label for the selected value
                        const selectedOption = options.find(
                            (opt) => opt.value === existingSelectedValue,
                        );
                        if (selectedOption) {
                            $selectedLabel.text(selectedOption.label);
                            $hiddenInput.val(existingSelectedValue);
                        } else {
                            $selectedLabel.text(
                                $container.data("placeholder") ||
                                    "Select an option",
                            );
                            $hiddenInput.val("");
                        }
                    }

                    // Update hidden input
                    $hiddenInput.attr(
                        "name",
                        `budgets[${index}][fiscal_year_id]`,
                    );

                    console.log(
                        `Updated select container ID: ${newId}, data-name: budgets[${index}][fiscal_year_id]`,
                    );
                }
            });

            // Update other inputs (e.g., total_budget, internal_budget)
            $row.find("input:not(.js-hidden-input)").each(function () {
                const $input = $(this);
                const name = $input.attr("name");
                if (name) {
                    $input.attr(
                        "name",
                        name.replace(/budgets\[\d+\]/, `budgets[${index}]`),
                    );
                }
                const id = $input.attr("id");
                if (id) {
                    const newId = id.replace(
                        /^(total_budget|internal_budget|foreign_loan_budget|foreign_subsidy_budget)_\d+/,
                        `$1_${index}`,
                    );
                    $input.attr("id", newId);
                }
            });

            // Reinitialize fiscal year dropdown
            $row.find(".js-single-select").each(function () {
                const $container = $(this);
                const name = $container.data("name");
                if (name && name.includes("fiscal_year_id")) {
                    const componentId = $container.attr("id");
                    const options = JSON.parse(
                        $container.attr("data-options") || "[]",
                    );
                    const $optionsContainer = $container.find(
                        ".js-options-container",
                    );
                    const $selectedLabel =
                        $container.find(".js-selected-label");
                    const $hiddenInput = $container.find(".js-hidden-input");
                    const $dropdown = $container.find(".js-dropdown");
                    const $searchInput = $container.find(".js-search-input");

                    // Clear existing options
                    $optionsContainer.empty();

                    // Render options
                    if (options.length > 0) {
                        $.each(options, function (i, option) {
                            const $option = $(`
                                <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `);
                            $optionsContainer.append($option);
                        });
                    } else {
                        $container.find(".js-no-options").removeClass("hidden");
                    }

                    // Bind click event for options
                    $optionsContainer
                        .off("click", ".js-option")
                        .on("click", ".js-option", function (e) {
                            e.stopPropagation();
                            const $option = $(this);
                            $container.data("selected", $option.data("value"));
                            $container.attr(
                                "data-selected",
                                $option.data("value"),
                            );
                            $selectedLabel.text($option.text().trim());
                            $hiddenInput.val($option.data("value"));
                            $dropdown.addClass("hidden");
                            console.log(
                                `Option selected for ${componentId}:`,
                                $option.data("value"),
                            );
                        });

                    // Bind search input
                    $searchInput.off("input").on("input", function () {
                        const searchTerm = $(this).val().toLowerCase();
                        $optionsContainer.empty();
                        const filteredOptions = options.filter((opt) =>
                            opt.label.toLowerCase().includes(searchTerm),
                        );
                        $.each(filteredOptions, function (i, option) {
                            const $option = $(`
                                <div class="js-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `);
                            $optionsContainer.append($option);
                        });
                    });

                    // Bind toggle dropdown
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

                    // Bind document click to close dropdown
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

                    console.log(`Reinitialized dropdown: ${componentId}`);
                }
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

    // Event listener for adding a new budget row
    addBudgetButton.on("click", function () {
        // Get the last existing budget row to use as a template
        const lastRow = budgetTableBody.find(".budget-entry-row").last();

        // Clone the row including event handlers
        const newRow = lastRow.clone(true);

        // Clear input values
        newRow.find("input").val("");
        newRow.find(".js-selected-label").text("Select an option...");
        newRow.find(".js-hidden-input").val("");
        newRow
            .find(".js-single-select")
            .attr("data-selected", "")
            .data("selected", "");

        // Remove any existing error messages
        newRow.find(".text-red-600").remove();
        newRow.find(".border-red-500").removeClass("border-red-500");

        // Append the new row to the table body
        budgetTableBody.append(newRow);

        // Update indices and reinitialize
        updateRowIndices();
    });

    // Event listener for removing a budget row
    budgetTableBody.on("click", ".remove-budget-btn", function () {
        if (budgetTableBody.find(".budget-entry-row").length > 1) {
            $(this).closest(".budget-entry-row").remove();
            updateRowIndices();
        }
    });

    // Initialize row states
    updateRowIndices();
});
