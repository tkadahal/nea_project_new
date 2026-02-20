<x-layouts.app>
    <!-- Full width container -->
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Edit Schedule: {{ $schedule->code }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Update progress and track date revisions for {{ $schedule->name }}
            </p>

            <nav class="flex mt-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.project.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Projects
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.project.show', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($project->title, 30) }}
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Edit
                                {{ $schedule->code }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Main Content Area -->
            <div class="flex-1">

                <!-- Main Update Form -->
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                    <div class="px-6 py-4 bg-blue-600 dark:bg-blue-800 border-b border-blue-700">
                        <h2 class="text-lg font-semibold text-white">Edit Schedule Progress & Dates</h2>
                        <p class="text-blue-100 text-sm">{{ $schedule->code }} - {{ $schedule->name }}</p>
                    </div>

                    <div class="p-6">
                        <form action="{{ route('admin.projects.schedules.update', [$project, $schedule]) }}"
                            method="POST" class="max-w-full">
                            @csrf
                            @method('PUT')

                            <!-- Error Display -->
                            @if ($errors->any())
                                <div
                                    class="mb-6 p-4 bg-red-50 text-red-800 border border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800 rounded">
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Section 1: Basic Information (Read Only) -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Activity Information
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity
                                            Code</label>
                                        <input type="text" value="{{ $schedule->code }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hierarchy
                                            Level</label>
                                        <input type="text" value="Level {{ $schedule->level }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity
                                            Name</label>
                                        <input type="text" value="{{ $schedule->name }}" disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                    @if ($schedule->parent)
                                        <div class="md:col-span-2">
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent
                                                Activity</label>
                                            <input type="text"
                                                value="{{ $schedule->parent->code }} - {{ $schedule->parent->name }}"
                                                disabled
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 cursor-not-allowed">
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Section 2: Progress Update -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Progress
                                </h3>
                                <div>
                                    <label for="progress"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Progress (%) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center mb-4">
                                        <div class="md:col-span-9">
                                            <input type="range" id="progress-slider" name="progress" min="0"
                                                max="100" step="0.5"
                                                value="{{ old('progress', $assignment->pivot->progress) }}"
                                                oninput="document.getElementById('progress-value').value = this.value; updateProgressBar(this.value)"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="number" id="progress-value" name="progress" min="0"
                                                max="100" step="0.5"
                                                value="{{ old('progress', $assignment->pivot->progress) }}"
                                                oninput="document.getElementById('progress-slider').value = this.value; updateProgressBar(this.value)"
                                                required
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        </div>
                                    </div>

                                    <!-- Visual Progress Bar -->
                                    <div class="mb-2">
                                        <div
                                            class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span>Current Status</span>
                                            <span
                                                id="progress-text-display">{{ old('progress', $assignment->pivot->progress) }}%</span>
                                        </div>
                                        <div
                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-4 overflow-hidden">
                                            <div id="progress-bar"
                                                class="h-4 rounded-full text-xs font-medium text-white text-center leading-4 transition-all duration-300 shadow-sm"
                                                style="width: {{ old('progress', $assignment->pivot->progress) }}%">
                                            </div>
                                        </div>
                                    </div>

                                    @error('progress')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Section 3: Planned Dates (Baseline) -->
                            <div
                                class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 border-l-4 border-l-blue-500">
                                <div class="flex items-center mb-4">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                        </path>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Planned Dates
                                        (Baseline)</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="start_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                            Start Date</label>
                                        <input type="date" id="start_date" name="start_date"
                                            value="{{ old('start_date', $assignment->pivot->start_date) }}"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        @error('start_date')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="end_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planned
                                            End Date</label>
                                        <input type="date" id="end_date" name="end_date"
                                            value="{{ old('end_date', $assignment->pivot->end_date) }}"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        @error('end_date')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2"><i
                                        class="fas fa-info-circle"></i> These are your baseline/original planned dates
                                </p>
                            </div>

                            <!-- Section 3.5: Actual Dates (Read Only) -->
                            <div
                                class="mb-8 p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 border-l-4 border-l-yellow-500">
                                <div class="flex items-center mb-4">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Current Actual
                                        Dates
                                    </h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current
                                            Actual Start</label>
                                        <input type="date"
                                            value="{{ $assignment->pivot->actual_start_date ? \Carbon\Carbon::parse($assignment->pivot->actual_start_date)->format('Y-m-d') : '' }}"
                                            disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm p-2.5 cursor-not-allowed">
                                        <small class="text-yellow-700 dark:text-yellow-300">Updates are tracked via the
                                            "Add Date Revision" form below.</small>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current
                                            Actual End</label>
                                        <input type="date"
                                            value="{{ $assignment->pivot->actual_end_date ? \Carbon\Carbon::parse($assignment->pivot->actual_end_date)->format('Y-m-d') : '' }}"
                                            disabled
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm p-2.5 cursor-not-allowed">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Remarks -->
                            <div
                                class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    Remarks
                                </h3>
                                <div>
                                    <textarea id="remarks" name="remarks" rows="4" placeholder="Add any notes regarding this activity..."
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">{{ old('remarks', $assignment->pivot->remarks) }}</textarea>
                                    @error('remarks')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div
                                class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                    class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Schedule
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Date Revisions Section (The Green Form) -->
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800 border-l-4 border-l-yellow-500">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Actual Date
                                    Revisions</h2>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">Track actual dates when they
                                    change (extensions, delays, etc.)</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @php
                            $revisions = \App\Models\ProjectScheduleDateRevision::where('project_id', $project->id)
                                ->where('schedule_id', $schedule->id)
                                ->orderBy('created_at', 'desc')
                                ->get();
                        @endphp

                        @if ($revisions->isNotEmpty())
                            <div class="mb-8 overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actual Start</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actual End</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Reason</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Remarks</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Revised By</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Date Added</th>
                                            <th scope="col" class="relative px-6 py-3"><span
                                                    class="sr-only">Action</span></th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($revisions as $revision)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->actual_start_date ? \Carbon\Carbon::parse($revision->actual_start_date)->format('M d, Y') : '-' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->actual_end_date ? \Carbon\Carbon::parse($revision->actual_end_date)->format('M d, Y') : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                        {{ $revision->revision_reason }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $revision->remarks ?? '-' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $revision->revisedBy->name ?? 'N/A' }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $revision->created_at->format('M d, Y H:i') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form
                                                        action="{{ route('admin.projects.schedules.delete-date-revision', [$project, $revision]) }}"
                                                        method="POST" class="inline-block"
                                                        onsubmit="return confirm('Delete this date revision?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                            title="Delete Revision">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-4 mb-8">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            No revisions yet
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                            <p>Add one below when actual dates change.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Add New Revision Form -->
                        <div
                            class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 overflow-hidden">
                            <div class="px-6 py-4 bg-green-600 border-b border-green-700">
                                <h3 class="text-base font-semibold text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add New Date Revision
                                </h3>
                            </div>
                            <div class="p-6">
                                <form
                                    action="{{ route('admin.projects.schedules.add-date-revision', [$project, $schedule]) }}"
                                    method="POST">
                                    @csrf

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                                Start Date</label>
                                            <input type="date" name="actual_start_date"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm p-2.5">
                                            <small class="text-gray-500 dark:text-gray-400">When did this activity
                                                actually start?</small>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual
                                                End Date</label>
                                            <input type="date" name="actual_end_date"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm p-2.5">
                                            <small class="text-gray-500 dark:text-gray-400">When did/will it actually
                                                end?</small>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason
                                            for Revision <span class="text-red-500">*</span></label>
                                        <input type="text" name="revision_reason"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm p-2.5"
                                            required
                                            placeholder="e.g., Extension due to weather, Material delay, Design change, etc.">
                                    </div>

                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Additional
                                            Remarks</label>
                                        <textarea name="remarks"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm p-2.5"
                                            rows="2" placeholder="Any additional details..."></textarea>
                                    </div>

                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-800">
                                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Date Revision
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slider = document.getElementById('progress-slider');
                if (slider) {
                    updateProgressBar(slider.value);
                }
            });

            function updateProgressBar(value) {
                const progressBar = document.getElementById('progress-bar');
                const textDisplay = document 'progress-text-display');
            if (!progressBar) return;

            // Update width and text
            progressBar.style.width = value + '%';
            progressBar.textContent = value + '%';
            if (textDisplay) textDisplay.textContent = value + '%';

            // Tailwind color classes
            progressBar.classList.remove('bg-gray-400', 'bg-blue-500', 'bg-yellow-500', 'bg-green-500');

            if (value >= 100) {
                progressBar.classList.add('bg-green-500');
            } else if (value >= 75) {
                progressBar.classList.add('bg-blue-500');
            } else if (value >= 50) {
                progressBar.classList.add('bg-yellow-500');
            } else {
                progressBar.classList.add('bg-gray-400');
            }
            }
        </script>
    @endpush
</x-layouts.app>
