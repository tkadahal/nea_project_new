<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header Section -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                    Project Schedules Overview
                </h1>
                <nav class="flex mt-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('admin.project.index') }}"
                                class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                Projects
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">All
                                    Schedules</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Optional: Add a global charts button or filters here later -->
                <span
                    class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                    Showing {{ $projects->count() }} accessible projects
                </span>
            </div>
        </div>

        <!-- Empty State -->
        @if ($projects->isEmpty())
            <div
                class="text-center py-20 bg-white dark:bg-gray-800 rounded-lg shadow ring-1 ring-gray-900/5 dark:ring-gray-700">
                <svg class="mx-auto h-24 w-24 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Projects Available</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">You don't have access to any projects yet.</p>
            </div>
        @else
            <!-- Stats Summary (Optional) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div
                    class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Projects</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ $projects->count() }}</dd>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                    <dd class="mt-1 text-3xl font-semibold text-green-600">
                        {{ $projects->filter(fn($p) => $p->progress >= 100)->count() }}
                    </dd>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                    <dd class="mt-1 text-3xl font-semibold text-blue-600">
                        {{ $projects->filter(fn($p) => $p->progress > 0 && $p->progress < 100)->count() }}
                    </dd>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-500 truncate">Not Started</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-400">
                        {{ $projects->filter(fn($p) => $p->progress == 0)->count() }}
                    </dd>
                </div>
            </div>

            <!-- Projects Table -->
            <div
                class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Project List</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Project Details</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Directorate/Dept</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/3">
                                    Overall Progress</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($projects as $project)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div
                                                    class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-700">
                                                    {{ substr($project->title, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $project->title }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    Code: {{ $project->project_code ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $project->directorate->name ?? '-' }}</div>
                                        @if ($project->department)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $project->department->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap align-middle">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ number_format($project->progress, 2) }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full text-xs font-medium text-white text-center leading-2.5 shadow-sm transition-all duration-500
                                            @if ($project->progress >= 100) bg-gradient-to-r from-green-500 to-green-600
                                            @elseif ($project->progress > 0) bg-gradient-to-r from-blue-500 to-blue-600
                                            @else bg-gray-400 @endif"
                                                style="width: {{ $project->progress }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($project->progress >= 100)
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Completed
                                            </span>
                                        @elseif ($project->progress > 0)
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                In Progress
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Not Started
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            View Schedules &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
