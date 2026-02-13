<x-layouts.app>

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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Assign
                                Schedules</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        Assign Activity Schedules
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Select a template structure for {{ $project->title }}
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Main Form Card -->
            <div
                class="bg-white dark:bg-gray-800 shadow sm:rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div
                    class="px-4 py-5 sm:px-6 bg-blue-600 dark:bg-blue-800 border-b border-blue-700 dark:border-blue-900">
                    <h3 class="text-lg leading-6 font-medium text-white">
                        Select Project Structure
                    </h3>
                </div>

                <div class="p-6">
                    @if ($hasSchedules)
                        <div
                            class="mb-6 rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-4 border-l-4 border-yellow-400">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        Warning: Existing Schedules Found
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>This project already has schedules assigned. Assigning new schedules will
                                            <strong>replace all existing schedules and their progress data</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.projects.schedules.assign', $project) }}" method="POST"
                        id="assign-form">
                        @csrf

                        <div class="mb-6">
                            <label for="project_type"
                                class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">
                                Select Project Type <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2">
                                <select id="project_type" name="project_type" required
                                    onchange="showSchedulePreview(this.value)"
                                    class="block w-full rounded-md border-0 py-2.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 bg-white dark:bg-gray-700">
                                    <option value="">-- Select Project Type --</option>
                                    <option value="transmission_line">Transmission Line</option>
                                    <option value="substation">Substation</option>
                                </select>
                                @error('project_type')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Schedule Preview Area -->
                        <div id="schedule-preview" class="hidden">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Schedule Structure
                                Preview:</h4>

                            <!-- Transmission Line Preview -->
                            <div id="transmission-line-preview" class="preview-content hidden">
                                <div
                                    class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4 border border-gray-200 dark:border-gray-700">
                                    <h5 class="text-base font-bold text-blue-700 dark:text-blue-400 mb-4">Transmission
                                        Line Project Structure</h5>
                                    <ul class="space-y-3">
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase A:</span>
                                            Pre-Construction & Procurement (15%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>13 activities (Desk Study, Survey, Land Acquisition, etc.)</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase B:</span>
                                            Detailed Design & Engineering (10%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Civil Design (4 activities)</li>
                                                <li>Electrical Design (5 activities)</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase C:</span>
                                            Construction (65%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Civil Construction (2 activities)</li>
                                                <li>Factory Testing & Dispatch (2 activities)</li>
                                                <li>Tower Erection & Stringing (4 activities)</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase
                                                D:</span> Testing, Commissioning & Handover (10%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>4 activities</li>
                                            </ul>
                                        </li>
                                    </ul>
                                    <div
                                        class="mt-4 rounded-md bg-blue-50 dark:bg-blue-900/20 p-3 text-sm text-blue-800 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                        <strong>Total:</strong> ~30-35 leaf activities to track
                                    </div>
                                </div>
                            </div>

                            <!-- Substation Preview -->
                            <div id="substation-preview" class="preview-content hidden">
                                <div
                                    class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4 border border-gray-200 dark:border-gray-700">
                                    <h5 class="text-base font-bold text-blue-700 dark:text-blue-400 mb-4">Substation
                                        Project Structure</h5>
                                    <ul class="space-y-3">
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase
                                                A:</span> Pre-Construction & Procurement (10%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>12 activities</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase
                                                B:</span> Detailed Design & Engineering (15%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Electrical Design (Multiple sub-activities)</li>
                                                <li>Civil & Structural Design (Multiple sub-activities)</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase
                                                C:</span> Construction & Installation (65%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>Civil Construction</li>
                                                <li>Factory Testing & Dispatch</li>
                                                <li>Installation Phase</li>
                                            </ul>
                                        </li>
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">Phase
                                                D:</span> Testing, Commissioning & Handover (10%)
                                            <ul
                                                class="mt-1 ml-4 space-y-1 list-disc list-inside text-gray-600 dark:text-gray-400">
                                                <li>6 activities</li>
                                            </ul>
                                        </li>
                                    </ul>
                                    <div
                                        class="mt-4 rounded-md bg-blue-50 dark:bg-blue-900/20 p-3 text-sm text-blue-800 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                        <strong>Total:</strong> ~40-50 leaf activities to track
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-8 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" id="submit-btn" disabled
                                class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="flex items-center">
                                    <svg class="h-5 w-5 mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    @if ($hasSchedules)
                                        Replace & Assign Schedules
                                    @else
                                        Assign Schedules
                                    @endif
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Information Card -->
            <div
                class="bg-white dark:bg-gray-800 shadow sm:rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div
                    class="px-4 py-5 sm:px-6 bg-cyan-600 dark:bg-cyan-800 border-b border-cyan-700 dark:border-cyan-900">
                    <h3 class="text-lg leading-6 font-medium text-white flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        What happens when you assign schedules?
                    </h3>
                </div>
                <div class="p-6">
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <li>All activities for the selected project type will be assigned to this project</li>
                        <li>Each activity will start with 0% progress</li>
                        <li>You can then update progress for individual activities</li>
                        <li>Phase progress will be calculated automatically based on child activities</li>
                        <li>Overall project progress will be calculated using weighted phases</li>
                    </ol>

                    <div
                        class="mt-6 rounded-md bg-green-50 dark:bg-green-900/20 p-4 border border-green-100 dark:border-green-900">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                    Pro Tip
                                </h3>
                                <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                    After assignment, use the "Quick Update" feature to update multiple activities at
                                    once!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function showSchedulePreview(projectType) {
            const preview = document.getElementById('schedule-preview');
            const tlPreview = document.getElementById('transmission-line-preview');
            const ssPreview = document.getElementById('substation-preview');
            const submitBtn = document.getElementById('submit-btn');

            if (projectType === '') {
                preview.classList.add('hidden');
                tlPreview.classList.add('hidden');
                ssPreview.classList.add('hidden');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                preview.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

                if (projectType === 'transmission_line') {
                    tlPreview.classList.remove('hidden');
                    ssPreview.classList.add('hidden');
                } else {
                    tlPreview.classList.add('hidden');
                    ssPreview.classList.remove('hidden');
                }
            }
        }
    </script>
</x-layouts.app>
