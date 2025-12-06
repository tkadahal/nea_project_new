// resources/js/contract.js
export function initializeContractForm() {
    (function () {
        // Wait for jQuery and DOM to be ready
        function waitForJQuery(callback, retries = 50) {
            if (
                typeof jQuery !== "undefined" &&
                document.readyState === "complete"
            ) {
                console.log("Contract Script: jQuery and DOM ready.");
                callback(jQuery);
            } else if (retries > 0) {
                console.log(
                    "Contract Script: Waiting for jQuery/DOM, retries left:",
                    retries,
                );
                setTimeout(() => waitForJQuery(callback, retries - 1), 100);
            } else {
                console.error(
                    "Contract Script: Failed to load jQuery or DOM after retries.",
                );
            }
        }

        waitForJQuery(function ($) {
            console.log("Contract Script: Initialized.");

            // Select DOM elements
            const $directorateContainer = $("#directorate_id");
            const $projectContainer = $("#project_id");

            // Verify DOM elements exist
            if (!$directorateContainer.length || !$projectContainer.length) {
                console.error("Contract Script: Containers not found.", {
                    directorate: $directorateContainer.length,
                    project: $projectContainer.length,
                });
                return;
            }

            // Helper to update custom select options
            function updateSelectOptions(
                $container,
                options,
                selectedValue = "",
            ) {
                const containerId = $container.attr("id") || "unknown";
                console.log("Contract Script: Updating", containerId, {
                    options,
                    selectedValue,
                });

                // Update data attributes for app.js
                $container.data("options", options);
                $container.data("selected", selectedValue);
                $container.attr("data-options", JSON.stringify(options));
                $container.attr("data-selected", JSON.stringify(selectedValue));

                // Clear current state
                $container.find(".js-options-container").empty();
                $container
                    .find(".js-selected-label")
                    .text($container.data("placeholder") || "Select project");
                $container.find("input.js-hidden-input").val(selectedValue);

                // Log DOM state before triggering event
                console.log(
                    "Contract Script: Pre-update DOM state for",
                    containerId,
                    {
                        optionsContainer: $container
                            .find(".js-options-container")
                            .html(),
                        selectedLabel: $container
                            .find(".js-selected-label")
                            .text(),
                        hiddenInput: $container
                            .find("input.js-hidden-input")
                            .val(),
                    },
                );

                // Trigger options-updated with data
                $container.trigger("options-updated", {
                    options,
                    selected: selectedValue,
                });
                console.log(
                    "Contract Script: Triggered options-updated for",
                    containerId,
                );
            }

            // Load projects for a directorate
            function loadProjects(directorateId, selectedProjectId = "") {
                console.log(
                    "Contract Script: Loading projects for directorate:",
                    directorateId,
                    "Selected:",
                    selectedProjectId,
                );

                // Clear dropdown immediately
                updateSelectOptions($projectContainer, [], "");

                if (!directorateId) {
                    console.log(
                        "Contract Script: No directorate selected, keeping projects cleared.",
                    );
                    return;
                }

                // Show loading state
                $projectContainer
                    .find(".js-options-container")
                    .html(
                        '<div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Loading projects...</div>',
                    );

                $.ajax({
                    url: `/admin/contracts/projects/${encodeURIComponent(directorateId)}`,
                    type: "GET",
                    dataType: "json",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content",
                        ),
                    },
                    success: function (data) {
                        console.log(
                            "Contract Script: AJAX success. Response:",
                            data,
                        );
                        const options = Array.isArray(data)
                            ? data.map((item) => ({
                                  value: String(item.value),
                                  label: String(item.label),
                              }))
                            : [];
                        const validSelected = options.some(
                            (opt) => opt.value === selectedProjectId,
                        )
                            ? selectedProjectId
                            : "";
                        console.log(
                            "Contract Script: Validated selected project:",
                            validSelected,
                        );
                        updateSelectOptions(
                            $projectContainer,
                            options,
                            validSelected,
                        );
                    },
                    error: function (xhr) {
                        console.error(
                            "Contract Script: AJAX error:",
                            xhr.status,
                            xhr.responseJSON,
                        );
                        updateSelectOptions($projectContainer, [], "");
                        $("#error-message")
                            .removeClass("hidden")
                            .find("#error-text")
                            .text(
                                "Failed to load projects: " +
                                    (xhr.responseJSON?.message ||
                                        "Unknown error"),
                            );
                    },
                });
            }

            // Handle directorate change
            $directorateContainer
                .find("input.js-hidden-input")
                .on("change", function () {
                    const directorateId = $(this).val();
                    console.log(
                        "Contract Script: Directorate changed to:",
                        directorateId,
                    );
                    loadProjects(directorateId, "");
                });

            // Initialize on page load
            const initialDirectorateId = $directorateContainer
                .find("input.js-hidden-input")
                .val();
            const initialProjectId =
                "{{ old('project_id', $contract->project_id ?? '') }}";
            console.log("Contract Script: Initial state:", {
                initialDirectorateId,
                initialProjectId,
            });

            if (initialDirectorateId) {
                console.log(
                    "Contract Script: Triggering initial project load for directorate:",
                    initialDirectorateId,
                );
                loadProjects(initialDirectorateId, initialProjectId);
            } else {
                console.log(
                    "Contract Script: No initial directorate, clearing project dropdown.",
                );
                updateSelectOptions($projectContainer, [], "");
            }

            // Close error message
            $("#close-error").on("click", function () {
                console.log("Contract Script: Closing error message.");
                $("#error-message")
                    .addClass("hidden")
                    .find("#error-text")
                    .text("");
            });
        });
    })();
}

// Initialize on DOM load
document.addEventListener("DOMContentLoaded", initializeContractForm);
