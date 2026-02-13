<x-layouts.app>
    <!-- Wrapper changed to container-fluid for full width, similar to contracts/index -->
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.project.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Projects
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.project.show', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($project->title, 40) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                Schedules
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Quick
                                Update</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        Quick Update - Leaf Activities
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Update progress for all executable activities at once.
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.projects.schedules.bulk-update', $project) }}" method="POST"
            id="quick-update-form" class="space-y-6">
            @csrf

            @foreach ($leafSchedules as $phaseCode => $schedules)
                @php
                    $firstSchedule = $schedules->first();
                    $phase = $firstSchedule->phase ?? null;
                @endphp

                <!-- Phase Card -->
                <div
                    class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 rounded-lg overflow-hidden">
                    <div
                        class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 px-4 py-4 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                                @if ($phase)
                                    Phase {{ $phase->code }}: {{ $phase->name }}
                                @else
                                    Uncategorized
                                @endif
                            </h3>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if ($phase)
                                <span
                                    class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10 dark:ring-indigo-400/30">
                                    Weight: {{ $phase->weightage }}%
                                </span>
                            @endif
                            <span
                                class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-500/10">
                                {{ $schedules->count() }} activities
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 sm:pl-6 w-[10%]">
                                        Code</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[35%]">
                                        Activity Name</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[15%]">
                                        Current</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[30%]">
                                        Update</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-200 w-[10%]">
                                        Quick Set</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach ($schedules as $schedule)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td
                                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">
                                            {{ $schedule->code }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $schedule->name }}</div>
                                            @if ($schedule->parent && $schedule->parent->code !== $phaseCode)
                                                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                    {{ $schedule->parent->code }} - {{ $schedule->parent->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                                <div class="progress-bar h-2.5 rounded-full text-xs font-medium text-white text-center leading-2.5 transition-all duration-300"
                                                    style="width: {{ $schedule->pivot->progress }}%">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <input type="hidden"
                                                name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][id]"
                                                value="{{ $schedule->id }}">

                                            <div class="flex flex-col space-y-2">
                                                <input type="range"
                                                    class="progress-slider w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                                    name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][progress]"
                                                    data-schedule-id="{{ $schedule->id }}" min="0"
                                                    max="100" step="5"
                                                    value="{{ $schedule->pivot->progress }}"
                                                    oninput="updateProgressDisplay(this)">

                                                <div class="text-center">
                                                    <span
                                                        class="progress-value inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
                                                        {{ $schedule->pivot->progress }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-1">
                                                <button type="button"
                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                    onclick="setProgress(this, 0)" title="Set 0%">
                                                    0%
                                                </button>
                                                <button type="button"
                                                    class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none p-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                                    onclick="setProgress(this, 50)" title="Set 50%">
                                                    50%
                                                </button>
                                                <button type="button"
                                                    class="text-gray-400 hover:text-green-600 dark:hover:text-green-400 focus:outline-none p-1 rounded hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                                    onclick="setProgress(this, 100)" title="Set 100%">
                                                    100%
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <!-- Action Buttons Footer -->
            <div
                class="bg-white dark:bg-gray-800 shadow sm:rounded-lg px-4 py-5 sm:p-6 ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.projects.schedules.index', $project) }}"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Cancel
                        </a>
                        <button type="button" onclick="resetAll()"
                            class="inline-flex items-center justify-center rounded-md border border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/30 px-4 py-2 text-sm font-medium text-yellow-800 dark:text-yellow-400 shadow-sm hover:bg-yellow-100 dark:hover:bg-yellow-900/50 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Reset
                        </button>
                    </div>

                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button type="button" onclick="setAllProgress(0)"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Set All 0%
                        </button>
                        <button type="button" onclick="setAllProgress(100)"
                            class="inline-flex items-center justify-center rounded-md border border-green-600 dark:border-green-500 bg-green-50 dark:bg-green-900/30 px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 shadow-sm hover:bg-green-100 dark:hover:bg-green-900/50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Set All 100%
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 hover:bg-blue-700 px-6 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Store original values for reset
        const originalValues = {};
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.progress-slider').forEach(slider => {
                originalValues[slider.dataset.scheduleId] = slider.value;
                // Initialize progress bar colors
                const row = slider.closest('tr');
                const progressBar = row.querySelector('.progress-bar');
                updateProgressBarColor(progressBar, slider.value);
            });
        });

        // Helper to update colors based on Tailwind classes
        function updateProgressBarColor(element, value) {
            // Remove Tailwind color classes
            element.classList.remove('bg-gray-300', 'bg-blue-500', 'bg-green-500', 'bg-gray-500');

            // Add appropriate Tailwind class
            if (value >= 100) {
                element.classList.add('bg-green-500');
            } else if (value >= 50) {
                element.classList.add('bg-blue-500');
            } else {
                element.classList.add('bg-gray-400'); // Slightly darker for dark mode visibility
            }
        }

        // Update progress display when slider changes
        function updateProgressDisplay(slider) {
            const row = slider.closest('tr');
            const valueDisplay = row.querySelector('.progress-value');
            const progressBar = row.querySelector('.progress-bar');

            valueDisplay.textContent = slider.value + '%';
            progressBar.style.width = slider.value + '%';
            progressBar.textContent = slider.value + '%';

            // Update progress bar color (Tailwind classes)
            updateProgressBarColor(progressBar, slider.value);
        }

        // Set progress for a single row
        function setProgress(button, value) {
            const row = button.closest('tr');
            const slider = row.querySelector('.progress-slider');
            slider.value = value;
            updateProgressDisplay(slider);
        }

        // Set all schedules to a specific value
        function setAllProgress(value) {
            if (confirm(`Are you sure you want to set ALL activities to ${value}%?`)) {
                document.querySelectorAll('.progress-slider').forEach(slider => {
                    slider.value = value;
                    updateProgressDisplay(slider);
                });
            }
        }

        // Reset all to original values
        function resetAll() {
            if (confirm('Reset all changes to original values?')) {
                document.querySelectorAll('.progress-slider').forEach(slider => {
                    const originalValue = originalValues[slider.dataset.scheduleId];
                    slider.value = originalValue;
                    updateProgressDisplay(slider);
                });
            }
        }

        // Confirm before leaving with unsaved changes
        let formSubmitted = false;
        document.getElementById('quick-update-form').addEventListener('submit', function() {
            formSubmitted = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted) {
                let hasChanges = false;
                document.querySelectorAll('.progress-slider').forEach(slider => {
                    if (slider.value != originalValues[slider.dataset.scheduleId]) {
                        hasChanges = true;
                    }
                });

                if (hasChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            }
        });
    </script>
</x-layouts.app>
