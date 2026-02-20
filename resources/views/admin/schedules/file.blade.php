<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header Section -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                    Schedule Reference Files
                </h1>
                <nav class="flex mt-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('admin.project.index') }}"
                                class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                Projects
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
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
                                <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
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
                                <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span
                                    class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Reference
                                    Files</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.projects.schedules.index', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Schedules
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()"
                        class="ml-auto pl-3 text-green-500 hover:text-green-700">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()"
                        class="ml-auto pl-3 text-red-500 hover:text-red-700">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Upload Form - Left Column -->
            <div class="lg:col-span-1">
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700 sticky top-6">
                    <div
                        class="px-4 py-5 sm:px-6 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900">
                        <h3 class="text-lg leading-6 font-medium text-white flex items-center">
                            <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            Upload Reference File
                        </h3>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('admin.projects.schedules.upload-file', $project) }}" method="POST"
                            enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <!-- Schedule Selection -->
                            <div>
                                <label for="schedule_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Related Schedule <span class="text-red-500">*</span>
                                </label>

                                <select name="schedule_id" id="schedule_id" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border">
                                    <option value="">-- Select Schedule --</option>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id }}">
                                            {{ $schedule->code }} - {{ $schedule->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- File Upload -->
                            <div>
                                <label for="file"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Select File <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-blue-400 transition-colors">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                                            fill="none" viewBox="0 0 48 48">
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                            <label for="file"
                                                class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                <span>Upload a file</span>
                                                <input id="file" name="file" type="file" class="sr-only"
                                                    required accept=".pdf,.xer,.mpp" onchange="updateFileName(this)">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            PDF, XER, MPP up to 50MB
                                        </p>
                                        <p id="file-name"
                                            class="text-sm text-blue-600 dark:text-blue-400 font-medium mt-2"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea id="description" name="description" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm border px-3 py-2"
                                    placeholder="e.g., Baseline schedule, Updated plan, As-built drawings..."></textarea>
                            </div>

                            <!-- File Type Info -->
                            <div
                                class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3 border border-blue-200 dark:border-blue-800">
                                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">Accepted File
                                    Types:</h4>
                                <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                                    <li class="flex items-center">
                                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <strong>PDF</strong> - Reports, drawings, specifications
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="h-4 w-4 mr-2 text-blue-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <strong>XER</strong> - Primavera P6 export files
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <strong>MPP</strong> - Microsoft Project files
                                    </li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit"
                                class="w-full inline-flex justify-center items-center rounded-md border border-transparent bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                                Upload File
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Files List - Right Column -->
            <div class="lg:col-span-2">
                <div
                    class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                Uploaded Reference Files ({{ $files->count() }})
                            </h3>
                            <div class="flex items-center space-x-2">
                                <!-- Filter by Type -->
                                <select id="filter-type" onchange="filterFiles(this.value)"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 pl-3 pr-8 border focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                    <option value="all">All Types</option>
                                    <option value="pdf">PDF Only</option>
                                    <option value="xer">XER Only</option>
                                    <option value="mpp">MPP Only</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($files->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No files uploaded
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by uploading a
                                    reference file.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($files as $file)
                                    <div class="file-item border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow"
                                        data-file-type="{{ $file->file_type }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start space-x-4 flex-grow">
                                                <!-- File Icon -->
                                                <div class="flex-shrink-0">
                                                    @if ($file->file_type === 'pdf')
                                                        <div
                                                            class="h-12 w-12 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                                            <svg class="h-7 w-7 text-red-600 dark:text-red-400"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    @elseif($file->file_type === 'xer')
                                                        <div
                                                            class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                            <svg class="h-7 w-7 text-blue-600 dark:text-blue-400"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="h-12 w-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                                            <svg class="h-7 w-7 text-green-600 dark:text-green-400"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- File Info -->
                                                <div class="flex-grow min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4
                                                            class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                            {{ $file->original_name }}
                                                        </h4>
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                            @if ($file->file_type === 'pdf') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                                            @elseif($file->file_type === 'xer') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                                            @else bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 @endif">
                                                            {{ strtoupper($file->file_type) }}
                                                        </span>
                                                    </div>

                                                    @if ($file->description)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                            {{ $file->description }}</p>
                                                    @endif

                                                    <div
                                                        class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                                </path>
                                                            </svg>
                                                            {{ $file->schedule->code }}
                                                        </span>
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4">
                                                                </path>
                                                            </svg>
                                                            {{ $file->file_size_human }}
                                                        </span>
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                                </path>
                                                            </svg>
                                                            {{ $file->uploadedBy->name ?? 'Unknown' }}
                                                        </span>
                                                        <span class="flex items-center">
                                                            <svg class="h-4 w-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                            {{ $file->created_at->diffForHumans() }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center space-x-2 ml-4">
                                                <a href="{{ route('admin.projects.schedules.download-file', [$project, $file]) }}"
                                                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                                    title="Download">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4">
                                                        </path>
                                                    </svg>
                                                </a>
                                                <form
                                                    action="{{ route('admin.projects.schedules.delete-file', [$project, $file]) }}"
                                                    method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this file?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                        title="Delete">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function updateFileName(input) {
                const fileName = input.files[0]?.name;
                const display = document.getElementById('file-name');
                if (fileName) {
                    display.textContent = '📎 ' + fileName;
                } else {
                    display.textContent = '';
                }
            }

            function filterFiles(type) {
                const items = document.querySelectorAll('.file-item');
                items.forEach(item => {
                    if (type === 'all' || item.dataset.fileType === type) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        </script>
    @endpush
</x-layouts.app>
