<x-layouts.app>

    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Header Section -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                    {{ $project->title }} - Activity Schedules
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
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span
                                    class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Schedules</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="flex items-center space-x-3">
                @if ($schedules->isEmpty())
                    <a href="{{ route('admin.projects.schedules.assign-form', $project) }}"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Assign Schedules
                    </a>
                @else
                    <a href="{{ route('admin.projects.schedules.dashboard', $project) }}"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.projects.schedules.quick-update', $project) }}"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Update
                    </a>
                @endif
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ session('success') }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()"
                                class="inline-flex rounded-md bg-green-50 dark:bg-green-900/30 p-1.5 text-green-500 hover:bg-green-100 dark:hover:bg-green-900/50 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            {{ session('error') }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()"
                                class="inline-flex rounded-md bg-red-50 dark:bg-red-900/30 p-1.5 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/50 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($schedules->isEmpty())
            <!-- Empty State -->
            <div
                class="text-center py-20 bg-white dark:bg-gray-800 rounded-lg shadow ring-1 ring-gray-900/5 dark:ring-gray-700">
                <svg class="mx-auto h-24 w-24 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Activity Schedules Assigned</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This project doesn't have any activity
                    schedules yet.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.projects.schedules.assign-form', $project) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Assign Schedules Now
                    </a>
                </div>
            </div>
        @else
            <!-- Overall Progress Section -->
            <div
                class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-4 py-5 sm:px-6 bg-blue-600 dark:bg-blue-800">
                    <h3 class="text-lg leading-6 font-medium text-white">Overall Project Progress</h3>
                </div>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
                        <div class="flex-grow">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Total
                                    Progress</span>
                                <span
                                    class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ number_format($overallProgress, 2) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6">
                                <div class="bg-green-500 h-6 rounded-full text-xs font-medium text-white text-center leading-6 transition-all duration-500"
                                    style="width: {{ $overallProgress }}%">
                                    {{ number_format($overallProgress, 2) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase Breakdown Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach ($breakdown as $phase)
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-3 border-l-4 border-blue-500">
                                <div class="flex justify-between items-center mb-2">
                                    <h6 class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate w-3/4">
                                        {{ $phase['code'] }}: {{ $phase['name'] }}
                                    </h6>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                                        {{ $phase['weightage'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5 mb-1">
                                    <div class="bg-blue-600 h-2.5 rounded-full text-xs font-medium text-blue-100 text-center leading-2.5"
                                        style="width: {{ $phase['progress'] }}%">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ number_format($phase['progress'], 1) }}%</span>
                                    <span>Contrib: {{ number_format($phase['weighted_contribution'], 2) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Schedule Table -->
            <div
                class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">All Activity Schedules</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Code</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Activity Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Level</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Weight</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Progress</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Start Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    End Date</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($schedules as $schedule)
                                <tr
                                    class="@if ($schedule->level == 1) bg-gray-50 dark:bg-gray-700/50 font-semibold @elseif($schedule->level == 3) bg-gray-50 dark:bg-gray-700/30 @else bg-white dark:bg-gray-800 @endif hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span
                                                class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $schedule->code }}</span>
                                            @if ($schedule->hasChildren())
                                                <svg class="ml-2 h-4 w-4 text-yellow-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                                                    </path>
                                                </svg>
                                            @else
                                                <svg class="ml-2 h-4 w-4 text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300"
                                        style="padding-left: {{ max(24, ($schedule->level - 1) * 24) }}px;">
                                        {{ $schedule->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                            L{{ $schedule->level }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if ($schedule->weightage)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                {{ $schedule->weightage }}%
                                            </span>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if (!$schedule->hasChildren())
                                            <div
                                                class="w-full min-w-[120px] bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                                <div class="h-2.5 rounded-full text-xs font-medium text-white text-center leading-2.5 
                                                    @if ($schedule->pivot->progress >= 100) bg-green-500
                                                    @elseif ($schedule->pivot->progress > 0) bg-blue-500
                                                    @else bg-gray-400 @endif"
                                                    style="width: {{ $schedule->pivot->progress }}%">
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                                                {{ number_format($schedule->pivot->progress, 0) }}%</div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Auto-calculated</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $schedule->pivot->start_date ? \Carbon\Carbon::parse($schedule->pivot->start_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $schedule->pivot->end_date ? \Carbon\Carbon::parse($schedule->pivot->end_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if (!$schedule->hasChildren())
                                            <a href="{{ route('admin.projects.schedules.edit', [$project, $schedule]) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 flex items-center justify-end">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                        @endif
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
