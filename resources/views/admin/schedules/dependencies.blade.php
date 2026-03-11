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
                            <span
                                class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Dependencies</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Activity Dependencies
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $schedule->code }}: {{ $schedule->name }}
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

        {{-- Activity Info Card --}}
        <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900">
                <h3 class="text-lg font-medium text-white">Activity Information</h3>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Code</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $schedule->code }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Level</dt>
                        <dd class="mt-1">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                Level {{ $schedule->level }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Progress</dt>
                        <dd class="mt-1">
                            <div class="flex items-center">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full"
                                        style="width: {{ $assignment->progress ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($assignment->progress ?? 0, 1) }}%
                                </span>
                            </div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Planned Duration</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            @if ($assignment->start_date && $assignment->end_date)
                                {{ \Carbon\Carbon::parse($assignment->start_date)->diffInDays($assignment->end_date) }}
                                days
                            @else
                                Not set
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Predecessors Card --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-orange-500 to-orange-600 dark:from-orange-700 dark:to-orange-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Predecessors
                        </h3>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                            {{ $schedule->predecessors->count() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-orange-100">
                        Activities that must complete before this one
                    </p>
                </div>

                <div class="px-6 py-4">
                    @if ($schedule->predecessors->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No prerequisites</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                This activity can start immediately
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($schedule->predecessors as $predecessor)
                                @php
                                    $predAssignment = DB::table('project_schedule_assignments')
                                        ->where('project_id', $project->id)
                                        ->where('schedule_id', $predecessor->id)
                                        ->first();
                                @endphp

                                <div
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-mono font-semibold bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                                                    {{ $predecessor->code }}
                                                </span>

                                                @if ($predecessor->pivot->type !== 'FS')
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        {{ $predecessor->pivot->type }}
                                                    </span>
                                                @endif

                                                @if ($predecessor->pivot->lag_days != 0)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                        {{ $predecessor->pivot->lag_days > 0 ? '+' : '' }}{{ $predecessor->pivot->lag_days }}
                                                        days
                                                    </span>
                                                @endif

                                                @if ($predecessor->pivot->is_auto)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                        Auto
                                                    </span>
                                                @endif
                                            </div>

                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                {{ $predecessor->name }}
                                            </h4>

                                            @if ($predAssignment)
                                                <div
                                                    class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                        </svg>
                                                        {{ number_format($predAssignment->progress, 0) }}%
                                                    </div>

                                                    @if ($predAssignment->end_date)
                                                        <div class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            Due:
                                                            {{ \Carbon\Carbon::parse($predAssignment->end_date)->format('M d, Y') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        @if ($predAssignment && $predAssignment->progress >= 100)
                                            <svg class="w-6 h-6 text-green-500" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Successors Card --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 dark:from-green-700 dark:to-green-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Successors
                        </h3>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                            {{ $schedule->successors->count() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-green-100">
                        Activities waiting for this one to complete
                    </p>
                </div>

                <div class="px-6 py-4">
                    @if ($schedule->successors->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No dependents</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No other activities depend on this one
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($schedule->successors as $successor)
                                @php
                                    $succAssignment = DB::table('project_schedule_assignments')
                                        ->where('project_id', $project->id)
                                        ->where('schedule_id', $successor->id)
                                        ->first();
                                @endphp

                                <div
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-mono font-semibold bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                    {{ $successor->code }}
                                                </span>

                                                @if ($successor->pivot->type !== 'FS')
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        {{ $successor->pivot->type }}
                                                    </span>
                                                @endif

                                                @if ($successor->pivot->lag_days != 0)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                                        {{ $successor->pivot->lag_days > 0 ? '+' : '' }}{{ $successor->pivot->lag_days }}
                                                        days
                                                    </span>
                                                @endif

                                                @if ($successor->pivot->is_auto)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                        Auto
                                                    </span>
                                                @endif
                                            </div>

                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                {{ $successor->name }}
                                            </h4>

                                            @if ($succAssignment)
                                                <div
                                                    class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                        </svg>
                                                        {{ number_format($succAssignment->progress, 0) }}%
                                                    </div>

                                                    @if ($succAssignment->start_date)
                                                        <div class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            Start:
                                                            {{ \Carbon\Carbon::parse($succAssignment->start_date)->format('M d, Y') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        @if ($succAssignment && $succAssignment->progress > 0)
                                            <svg class="w-6 h-6 text-blue-500" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Dependency Legend --}}
        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">Dependency Types:</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs text-blue-800 dark:text-blue-300">
                <div>
                    <strong>FS (Finish-to-Start):</strong> Successor starts after predecessor finishes
                </div>
                <div>
                    <strong>SS (Start-to-Start):</strong> Successor starts when predecessor starts
                </div>
                <div>
                    <strong>FF (Finish-to-Finish):</strong> Successor finishes when predecessor finishes
                </div>
                <div>
                    <strong>SF (Start-to-Finish):</strong> Successor finishes before predecessor starts
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
