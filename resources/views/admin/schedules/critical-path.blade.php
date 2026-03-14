<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header --}}
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
                            <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" stroke="currentColor"
                                viewBox="0 0 6 10">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.project.show', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($project->title, 30) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" stroke="currentColor"
                                viewBox="0 0 6 10">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.projects.schedules.index', $project) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                Schedules
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" stroke="currentColor"
                                viewBox="0 0 6 10">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Critical
                                Path</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Critical Path Analysis
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $project->title }}
                    </p>
                </div>

                <a href="{{ route('admin.projects.schedules.index', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Schedules
                </a>
            </div>
        </div>

        {{-- ✅ CHECK IF WE HAVE VALID DATA --}}
        @if (!isset($hasValidDates) || !$hasValidDates)
            {{-- Empty State - No Valid Dates --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-6 text-xl font-medium text-gray-900 dark:text-white">No Critical Path Data Available
                    </h3>
                    <p class="mt-3 text-base text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                        {{ $message ?? 'Please set planned start and end dates for all activities before calculating the critical path.' }}
                    </p>

                    <div
                        class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 max-w-3xl mx-auto text-left">
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-3">To use Critical Path
                            Analysis:</h4>
                        <ol class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
                            <li class="flex items-start">
                                <span
                                    class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-600 text-white text-xs font-bold mr-3 flex-shrink-0">1</span>
                                <span>Go to the schedules list or quick update page</span>
                            </li>
                            <li class="flex items-start">
                                <span
                                    class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-600 text-white text-xs font-bold mr-3 flex-shrink-0">2</span>
                                <span>Set <strong>Planned Start Date</strong> and <strong>Planned End Date</strong> for
                                    each activity</span>
                            </li>
                            <li class="flex items-start">
                                <span
                                    class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-600 text-white text-xs font-bold mr-3 flex-shrink-0">3</span>
                                <span>Click <strong>"Recalculate Timeline"</strong> to sync dependencies</span>
                            </li>
                            <li class="flex items-start">
                                <span
                                    class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-600 text-white text-xs font-bold mr-3 flex-shrink-0">4</span>
                                <span>Return to this page to view the critical path</span>
                            </li>
                        </ol>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-4">
                        <a href="{{ route('admin.projects.schedules.quick-update', $project) }}"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
                            <svg class="-ml-1 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Quick Update Dates
                        </a>

                        <a href="{{ route('admin.projects.schedules.index', $project) }}"
                            class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm">
                            View Schedules
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- ✅ ONLY SHOW THIS SECTION IF WE HAVE VALID DATA --}}

            {{-- Project Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Critical Activities
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ count($cpm['critical_activities']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Project Duration
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $cpm['project_duration'] }} days
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Activities
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $allSchedules->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Max Slack
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ max($cpm['slacks']) }} days
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div
                class="mb-6 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-red-900 dark:text-red-200">Critical Path Activities</h3>
                        <p class="mt-1 text-sm text-red-800 dark:text-red-300">
                            Activities on the critical path have <strong>zero slack (float)</strong>. Any delay in these
                            activities will delay the entire project. Activities not on the critical path have slack
                            time
                            and can be delayed without affecting the project end date.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Critical Path Table --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">All Activities Analysis</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Code
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Activity
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Early Start
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Early Finish
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Late Start
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Late Finish
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Slack (Float)
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Progress
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($allSchedules->sortBy('code') as $schedule)
                                <tr
                                    class="{{ $schedule->is_critical ? 'bg-red-50 dark:bg-red-900/20' : 'bg-white dark:bg-gray-800' }} hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if ($schedule->is_critical)
                                                <svg class="h-5 w-5 text-red-600 dark:text-red-400 mr-2"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                            <span
                                                class="text-sm font-mono font-semibold {{ $schedule->is_critical ? 'text-red-900 dark:text-red-200' : 'text-gray-900 dark:text-gray-100' }}">
                                                {{ $schedule->code }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ $schedule->name }}
                                        </div>
                                        @if ($schedule->level > 1)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Level {{ $schedule->level }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-900 dark:text-gray-100">
                                            Day {{ $schedule->early_start }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-900 dark:text-gray-100">
                                            Day {{ $schedule->early_finish }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-900 dark:text-gray-100">
                                            Day {{ $schedule->late_start }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-900 dark:text-gray-100">
                                            Day {{ $schedule->late_finish }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if ($schedule->slack == 0)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                0 days
                                            </span>
                                        @elseif($schedule->slack <= 2)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                {{ $schedule->slack }} days
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                {{ $schedule->slack }} days
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $schedule->pivot->progress >= 100 ? 'bg-green-600' : 'bg-blue-600' }}"
                                                style="width: {{ $schedule->pivot->progress }}%">
                                            </div>
                                        </div>
                                        <div class="text-xs text-center text-gray-500 dark:text-gray-400 mt-1">
                                            {{ number_format($schedule->pivot->progress, 0) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if ($schedule->pivot->progress >= 100)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Complete
                                            </span>
                                        @elseif($schedule->pivot->progress > 0)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                In Progress
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                                Not Started
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Critical Path Only --}}
            @if (count($cpm['critical_activities']) > 0)
                <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div
                        class="px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 dark:from-red-800 dark:to-red-900 border-b">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">
                                Critical Path Sequence
                            </h3>
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                                {{ count($cpm['critical_activities']) }} activities
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach ($cpm['critical_activities'] as $index => $scheduleId)
                                @php
                                    $criticalSchedule = $allSchedules->firstWhere('id', $scheduleId);
                                @endphp

                                @if ($criticalSchedule)
                                    <div
                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 border-2 border-red-600 dark:border-red-500">
                                        <div class="text-center">
                                            <div
                                                class="text-xs font-semibold text-red-900 dark:text-red-200 font-mono mb-1">
                                                {{ $criticalSchedule->code }}
                                            </div>
                                            <div class="text-xs text-red-700 dark:text-red-300">
                                                {{ Str::limit($criticalSchedule->name, 20) }}
                                            </div>
                                        </div>
                                    </div>

                                    @if ($index < count($cpm['critical_activities']) - 1)
                                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
