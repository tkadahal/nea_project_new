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

            <!-- Updated Button Container: Added flex-wrap and reduced sizes -->
            <div class="flex flex-wrap items-center gap-2">

                <a href="{{ route('admin.projects.schedules.dashboard', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.projects.schedules.tree', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Tree View
                </a>

                <a href="{{ route('admin.projects.schedules.quick-update', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Update
                </a>

                <form action="{{ route('admin.projects.schedules.recalculate-timeline', $project) }}" method="POST"
                    class="inline"
                    onsubmit="return confirm('Recalculate all dependencies and dates? This will update the timeline based on actual progress.')">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Recalculate
                    </button>
                </form>

                {{-- Critical Path Button --}}
                <a href="{{ route('admin.projects.schedules.critical-path', $project) }}"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Critical Path
                </a>
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
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
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
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
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

        @if (!$schedules->isEmpty())
            @php
                // Check how many active schedules are missing dates
                $activeSchedules = $schedules->where('pivot.status', 'active');
                $missingDates = $activeSchedules
                    ->filter(function ($schedule) {
                        return empty($schedule->pivot->start_date) || empty($schedule->pivot->end_date);
                    })
                    ->count();

                $totalActive = $activeSchedules->count();
                $hasAllDates = $missingDates === 0;
            @endphp

            {{-- Warning: Missing Dates --}}
            @if (!$hasAllDates && $missingDates > 0)
                <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Planned Dates Missing
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <p class="mb-2">
                                    <strong>{{ $missingDates }}</strong> out of <strong>{{ $totalActive }}</strong>
                                    active activities are missing planned start/end dates.
                                </p>
                                <p class="mb-3">
                                    Without dates set, you cannot:
                                </p>
                                <ul class="list-disc list-inside space-y-1 mb-3">
                                    <li>View Critical Path Analysis</li>
                                    <li>Use automatic timeline ripple effects</li>
                                    <li>Track schedule variance</li>
                                    <li>Generate accurate Gantt charts</li>
                                </ul>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.projects.schedules.quick-update', $project) }}"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-800 dark:text-yellow-100 dark:hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Set Dates Now
                                    </a>
                                    <button type="button"
                                        onclick="this.parentElement.parentElement.parentElement.parentElement.remove()"
                                        class="text-sm text-yellow-700 dark:text-yellow-300 hover:text-yellow-900 dark:hover:text-yellow-100 underline">
                                        Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Success: All Dates Set --}}
            @if ($hasAllDates && $totalActive > 0)
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                All Active Schedules Have Dates
                            </h3>
                            <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                <p>
                                    All <strong>{{ $totalActive }}</strong> active activities have planned start and
                                    end dates set.
                                    You can now use Critical Path Analysis and timeline features.
                                </p>
                            </div>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()"
                                class="inline-flex rounded-md bg-green-50 dark:bg-green-900/30 p-1.5 text-green-500 hover:bg-green-100 dark:hover:bg-green-900/50 focus:outline-none">
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
            @endif
        @endif

        @php
            // Define color schemes for each phase
            $phaseColors = [
                'A' => [
                    'border' => 'border-blue-500',
                    'bg' => 'bg-blue-600',
                    'bg_light' => 'bg-blue-50',
                    'bg_dark' => 'bg-blue-900/30',
                    'text' => 'text-blue-700',
                    'text_dark' => 'text-blue-300',
                    'progress' => 'bg-blue-600',
                ],
                'B' => [
                    'border' => 'border-green-500',
                    'bg' => 'bg-green-600',
                    'bg_light' => 'bg-green-50',
                    'bg_dark' => 'bg-green-900/30',
                    'text' => 'text-green-700',
                    'text_dark' => 'text-green-300',
                    'progress' => 'bg-green-600',
                ],
                'C' => [
                    'border' => 'border-red-500',
                    'bg' => 'bg-red-600',
                    'bg_light' => 'bg-red-50',
                    'bg_dark' => 'bg-red-900/30',
                    'text' => 'text-red-700',
                    'text_dark' => 'text-red-300',
                    'progress' => 'bg-red-600',
                ],
                'D' => [
                    'border' => 'border-indigo-500',
                    'bg' => 'bg-indigo-600',
                    'bg_light' => 'bg-indigo-50',
                    'bg_dark' => 'bg-indigo-900/30',
                    'text' => 'text-indigo-700',
                    'text_dark' => 'text-indigo-300',
                    'progress' => 'bg-indigo-600',
                ],
                'E' => [
                    'border' => 'border-rose-500',
                    'bg' => 'bg-rose-600',
                    'bg_light' => 'bg-rose-50',
                    'bg_dark' => 'bg-rose-900/30',
                    'text' => 'text-rose-700',
                    'text_dark' => 'text-rose-300',
                    'progress' => 'bg-rose-600',
                ],
            ];

            function getPhaseColor($code, $phaseColors)
            {
                $phase = substr($code, 0, 1);
                return $phaseColors[$phase] ?? $phaseColors['A'];
            }
        @endphp

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
                <div
                    class="px-4 py-5 sm:px-6 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900">
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
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-6 rounded-full text-xs font-medium text-white text-center leading-6 transition-all duration-500 shadow-sm"
                                    style="width: {{ $overallProgress }}%">
                                    {{ number_format($overallProgress, 2) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase Breakdown Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach ($breakdown as $phase)
                            @php $colors = getPhaseColor($phase['code'], $phaseColors); @endphp
                            <div
                                class="{{ $colors['bg_light'] }} dark:{{ $colors['bg_dark'] }} rounded-md p-3 border-l-4 {{ $colors['border'] }} shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-center mb-2">
                                    <h6 class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate w-3/4">
                                        {{ $phase['code'] }}: {{ $phase['name'] }}
                                    </h6>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-gray-600">
                                        {{ $phase['weightage'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5 mb-1">
                                    <div class="{{ $colors['progress'] }} h-2.5 rounded-full text-xs font-medium text-center leading-2.5 shadow-sm"
                                        style="width: {{ $phase['progress'] }}%"></div>
                                </div>
                                <div
                                    class="flex justify-between text-xs {{ $colors['text'] }} dark:{{ $colors['text_dark'] }}">
                                    <span class="font-semibold">{{ number_format($phase['progress'], 1) }}%</span>
                                    <span>Contrib: {{ number_format($phase['weighted_contribution'], 2) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Status Breakdown --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $allSchedules = $project->activitySchedules;
                    $activeCount = $allSchedules->where('pivot.status', 'active')->count();
                    $notNeededCount = $allSchedules->where('pivot.status', 'not_needed')->count();
                    $completedCount = $allSchedules->where('pivot.status', 'completed')->count();
                    $cancelledCount = $allSchedules->where('pivot.status', 'cancelled')->count();
                @endphp

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-green-600 mr-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Active Activities</div>
                            <div class="text-2xl font-bold text-green-600">{{ $activeCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-gray-600 mr-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Not Needed</div>
                            <div class="text-2xl font-bold text-gray-600">{{ $notNeededCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $completedCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-red-600 mr-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Cancelled</div>
                            <div class="text-2xl font-bold text-red-600">{{ $cancelledCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grouped Schedule Table -->
            <div
                class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">All Activity Schedules</h3>
                </div>

                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <select id="bulk-status"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3 border">
                            <option value="">Bulk Actions...</option>
                            <option value="not_needed">Mark as Not Needed</option>
                            <option value="active">Mark as Active</option>
                            <option value="cancelled">Mark as Cancelled</option>
                        </select>

                        <button onclick="applyBulkStatus()"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Apply to Selected
                        </button>

                        <span class="text-sm text-gray-500 dark:text-gray-400" id="selected-count"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                        onclick="toggleAllCheckboxes(this)">
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Code</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Activity Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Level</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Weight</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Progress</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Start Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    End Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Dependencies
                                </th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>

                        {{-- ═══════════════════════════════════════════════════════════════ --}}
                        {{-- COMPLETELY DISABLED VERSION - NO INTERACTION POSSIBLE --}}
                        {{-- Replace the entire table body in index.blade.php --}}
                        {{-- ═══════════════════════════════════════════════════════════════ --}}

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                            @php
                                $grouped = $schedules->groupBy(fn($s) => substr($s->code, 0, 1))->sortKeys();
                            @endphp

                            @foreach ($grouped as $phaseCode => $group)
                                @php
                                    $phase = $group->firstWhere('level', 1);
                                    if (!$phase) {
                                        continue;
                                    }
                                    $colors = getPhaseColor($phaseCode, $phaseColors);
                                @endphp

                                <!-- Phase Header Row -->
                                <tr
                                    class="{{ $colors['bg_dark'] }} border-l-4 {{ $colors['border'] }} font-semibold">
                                    <td colspan="12" class="px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span
                                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-bold {{ $colors['bg'] }} text-white">
                                                    {{ $phase->code }}
                                                </span>
                                                <span class="text-base font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $phase->name }}
                                                </span>
                                            </div>
                                            @if ($phase->weightage)
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded text-sm font-medium bg-white/20 dark:bg-gray-800/40 text-gray-900 dark:text-gray-100 ring-1 ring-white/30 dark:ring-gray-700">
                                                    Weight: {{ $phase->weightage }}%
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- Activity Rows -->
                                @foreach ($group->where('level', '>', 1)->sortBy('code', SORT_NATURAL) as $schedule)
                                    @php
                                        $colors = getPhaseColor($schedule->code, $phaseColors);
                                        $status = $schedule->pivot->status ?? 'active';
                                        $isActive = $status === 'active';
                                        $isCompleted = $status === 'completed';
                                        $isNotNeeded = $status === 'not_needed';
                                        $isCancelled = $status === 'cancelled';
                                        $isDisabled = $isNotNeeded || $isCancelled;

                                        $statusConfig = [
                                            'active' => [
                                                'bg' => 'bg-green-100 dark:bg-green-900',
                                                'text' => 'text-green-800 dark:text-green-200',
                                                'label' => 'Active',
                                            ],
                                            'not_needed' => [
                                                'bg' => 'bg-gray-100 dark:bg-gray-700',
                                                'text' => 'text-gray-600 dark:text-gray-300',
                                                'label' => 'Not Needed',
                                            ],
                                            'cancelled' => [
                                                'bg' => 'bg-red-100 dark:bg-red-900',
                                                'text' => 'text-red-800 dark:text-red-200',
                                                'label' => 'Cancelled',
                                            ],
                                            'completed' => [
                                                'bg' => 'bg-blue-100 dark:bg-blue-900',
                                                'text' => 'text-blue-800 dark:text-blue-200',
                                                'label' => 'Completed',
                                            ],
                                        ];
                                        $config = $statusConfig[$status] ?? $statusConfig['active'];
                                    @endphp

                                    {{-- ✅ COMPLETELY DISABLED ROW - NO INTERACTION --}}
                                    <tr
                                        class="{{ $schedule->level == 2 ? 'bg-gray-50 dark:bg-gray-800/30' : '' }}
                {{ $schedule->level == 3 ? 'bg-gray-100/50 dark:bg-gray-900/20' : '' }}
                {{ $isDisabled ? 'opacity-40 bg-gray-100 dark:bg-gray-900' : '' }}
                {{ $isDisabled ? 'pointer-events-none' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40' }}
                transition-colors">

                                        {{-- ✅ CHECKBOX - Completely disabled, visually hidden for disabled rows --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($isDisabled)
                                                <div
                                                    class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 bg-gray-200 dark:bg-gray-700">
                                                </div>
                                            @else
                                                <input type="checkbox"
                                                    class="schedule-checkbox rounded border-gray-300 dark:border-gray-600"
                                                    value="{{ $schedule->id }}">
                                            @endif
                                        </td>

                                        {{-- CODE --}}
                                        <td class="px-6 py-4 whitespace-nowrap"
                                            style="padding-left: {{ max(24, $schedule->level * 24) }}px !important;">
                                            <div class="flex items-center">
                                                <span
                                                    class="text-sm font-medium {{ $isDisabled ? 'text-gray-400 dark:text-gray-600' : 'text-gray-900 dark:text-gray-100' }}">
                                                    {{ $schedule->code }}
                                                </span>
                                                @if ($schedule->children_count > 0)
                                                    <svg class="ml-2 h-4 w-4 {{ $isDisabled ? 'text-gray-400' : 'text-yellow-500' }}"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                @else
                                                    <svg class="ml-2 h-4 w-4 {{ $isDisabled ? 'text-gray-400' : $colors['text'] }} dark:{{ $isDisabled ? 'text-gray-600' : $colors['text_dark'] }}"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- NAME - Strikethrough for disabled --}}
                                        <td
                                            class="px-6 py-4 text-sm {{ $isDisabled ? 'text-gray-400 dark:text-gray-600 line-through decoration-2' : 'text-gray-600 dark:text-gray-300' }}">
                                            {{ $schedule->name }}
                                            @if ($isDisabled)
                                                <span
                                                    class="ml-2 text-xs text-gray-500 dark:text-gray-500 italic">(Excluded
                                                    from project)</span>
                                            @endif
                                        </td>

                                        {{-- LEVEL --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isDisabled ? 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                                L{{ $schedule->level }}
                                            </span>
                                        </td>

                                        {{-- WEIGHT --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if ($schedule->weightage)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isDisabled ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-500' : $colors['bg'] . ' text-white' }}">
                                                    {{ $schedule->weightage }}%
                                                </span>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">—</span>
                                            @endif
                                        </td>

                                        {{-- STATUS --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }}">
                                                @if ($isDisabled)
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                                {{ $config['label'] }}
                                            </span>
                                        </td>

                                        {{-- PROGRESS --}}
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($isDisabled)
                                                <div class="text-center">
                                                    <span
                                                        class="text-sm text-gray-400 dark:text-gray-600 font-medium">—</span>
                                                    <div class="text-xs text-gray-400 dark:text-gray-600 italic">
                                                        Excluded</div>
                                                </div>
                                            @elseif (!$schedule->children_count > 0)
                                                <div
                                                    class="w-full min-w-[120px] bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                                    <div class="h-2.5 rounded-full text-xs font-medium text-white text-center leading-2.5 shadow-sm
                                @if ($schedule->pivot->progress >= 100) bg-gradient-to-r from-green-500 to-green-600
                                @elseif ($schedule->pivot->progress > 0) {{ $colors['progress'] }}
                                @else bg-gray-400 @endif"
                                                        style="width: {{ $schedule->pivot->progress }}%">
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                                                    {{ number_format($schedule->pivot->progress, 0) }}%
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Auto-calculated</span>
                                            @endif
                                        </td>

                                        {{-- DATES --}}
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-400 dark:text-gray-600' : 'text-gray-500 dark:text-gray-400' }} text-center">
                                            {{ $schedule->pivot->start_date ? \Carbon\Carbon::parse($schedule->pivot->start_date)->format('M d, Y') : '—' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-400 dark:text-gray-600' : 'text-gray-500 dark:text-gray-400' }} text-center">
                                            {{ $schedule->pivot->end_date ? \Carbon\Carbon::parse($schedule->pivot->end_date)->format('M d, Y') : '—' }}
                                        </td>

                                        {{-- DEPENDENCIES --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($isDisabled)
                                                <svg class="h-5 w-5 text-gray-300 dark:text-gray-700 inline opacity-30"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                </svg>
                                            @else
                                                <a href="{{ route('admin.projects.schedules.dependencies', [$project, $schedule]) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    title="View Dependencies">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </td>

                                        {{-- ACTIONS - Only reactivate button for disabled --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if ($isDisabled)
                                                {{-- ✅ REACTIVATE BUTTON - Use pointer-events-auto to override parent --}}
                                                <div class="pointer-events-auto">
                                                    <form
                                                        action="{{ route('admin.projects.schedules.mark-active', [$project, $schedule]) }}"
                                                        method="POST" class="inline"
                                                        onsubmit="return confirm('Reactivate this activity? It will be included in project calculations again.')">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center px-3 py-2 border-2 border-green-600 dark:border-green-500 text-sm font-semibold rounded-md text-green-700 dark:text-green-400 bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-green-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-all">
                                                            <svg class="-ml-0.5 mr-2 h-5 w-5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                            </svg>
                                                            Reactivate
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($isActive && !$schedule->children_count > 0)
                                                <a href="{{ route('admin.projects.schedules.edit', [$project, $schedule]) }}"
                                                    class="{{ $colors['text'] }} hover:opacity-80 dark:{{ $colors['text_dark'] }} hover:{{ $colors['text_dark'] }}/80 mr-3"
                                                    title="Edit">
                                                    <svg class="h-5 w-5 inline" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>

                                                <form
                                                    action="{{ route('admin.projects.schedules.mark-not-needed', [$project, $schedule]) }}"
                                                    method="POST" class="inline"
                                                    onsubmit="return confirm('Mark as Not Needed? This will exclude it from project calculations and reset its progress.')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 hover:dark:text-gray-200 text-xs hover:underline">
                                                        Not Needed
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            function toggleAllCheckboxes(source) {
                const checkboxes = document.querySelectorAll('.schedule-checkbox');
                checkboxes.forEach(cb => cb.checked = source.checked);
                updateSelectedCount();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const checkboxes = document.querySelectorAll('.schedule-checkbox');
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', updateSelectedCount);
                });
            });

            function updateSelectedCount() {
                const checked = document.querySelectorAll('.schedule-checkbox:checked').length;
                const counter = document.getElementById('selected-count');
                if (counter) {
                    counter.textContent = checked > 0 ? `${checked} selected` : '';
                }
            }

            function applyBulkStatus() {
                const status = document.getElementById('bulk-status').value;
                if (!status) {
                    alert('Please select an action');
                    return;
                }

                const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
                if (checkboxes.length === 0) {
                    alert('Please select at least one activity');
                    return;
                }

                const scheduleIds = Array.from(checkboxes).map(cb => cb.value);

                if (!confirm(`Apply "${status.replace('_', ' ')}" to ${scheduleIds.length} activities?`)) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.projects.schedules.bulk-mark-status', $project) }}';

                form.innerHTML = `
        @csrf
        <input type="hidden" name="status" value="${status}">
        ${scheduleIds.map(id => `<input type="hidden" name="schedule_ids[]" value="${id}">`).join('')}
    `;

                document.body.appendChild(form);
                form.submit();
            }
        </script>
    @endpush

</x-layouts.app>
