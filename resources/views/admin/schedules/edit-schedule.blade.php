<x-layouts.app>
    <!-- Full width container -->
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Edit Schedule Template
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Modify the master activity template structure.
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
                                Schedule Template</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Main Content Area -->
            <div class="flex-1">
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
                    <form action="{{ route('admin.projects.schedules.update-schedule', [$project, $schedule]) }}"
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

                        <!-- Section 1: Basic Information -->
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                Basic Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                                    <input type="text" name="code" value="{{ old('code', $schedule->code) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project
                                        Type</label>
                                    <select name="project_type"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        <option value="transmission_line"
                                            {{ old('project_type', $schedule->project_type) == 'transmission_line' ? 'selected' : '' }}>
                                            Transmission Line
                                        </option>
                                        <option value="substation"
                                            {{ old('project_type', $schedule->project_type) == 'substation' ? 'selected' : '' }}>
                                            Substation
                                        </option>
                                    </select>
                                    @error('project_type')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $schedule->name) }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('name')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea name="description" rows="3"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">{{ old('description', $schedule->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Hierarchy & Configuration -->
                        <div
                            class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3
                                class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                Hierarchy & Configuration
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent
                                        Schedule (Optional)</label>
                                    <select name="parent_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                        <option value="">-- None (Top Level) --</option>
                                        @foreach ($parentSchedules as $parent)
                                            <option value="{{ $parent->id }}"
                                                {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                                {{ $parent->code }} - {{ $parent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <small class="text-gray-500 dark:text-gray-400 block mt-1">Leave empty for top-level
                                        phase</small>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weightage
                                        (%)</label>
                                    <input type="number" name="weightage"
                                        value="{{ old('weightage', $schedule->weightage) }}" min="0"
                                        max="100" step="0.01"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('weightage')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <small class="text-gray-500 dark:text-gray-400 block mt-1">Only for top-level
                                        phases</small>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort
                                        Order</label>
                                    <input type="number" name="sort_order"
                                        value="{{ old('sort_order', $schedule->sort_order) }}"
                                        class="w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                                    @error('sort_order')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
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
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Update Schedule Template
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
