<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.contract.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            contracts
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.contract.show', $contract) }}"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ Str::limit($contract->title, 40) }}
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Quick
                                Update</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Row: Title (Left) & Back Button (Right) -->
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

                <!-- Back Button -->
                <div class="shrink-0 mt-4 md:mt-0">
                    <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Schedules
                    </a>
                </div>
            </div>
        </div>

        @if (!$leafSchedules->isEmpty() && isset($allSchedules))
            @php
                $totalLeafCount = $allSchedules->where('children_count', 0)->count();
                $activeLeafCount = $allSchedules->where('children_count', 0)->where('pivot.status', 'active')->count();
                $excludedCount = $totalLeafCount - $activeLeafCount;
            @endphp

            @if ($excludedCount > 0)
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Showing Active Activities Only
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <p>
                                    Displaying <strong>{{ $activeLeafCount }}</strong> out of
                                    <strong>{{ $totalLeafCount }}</strong> leaf activities.
                                    <strong>{{ $excludedCount }}</strong>
                                    {{ Str::plural('activity', $excludedCount) }} excluded
                                    (Not Needed, Cancelled, or Completed).
                                </p>
                            </div>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()"
                                class="inline-flex rounded-md bg-blue-50 dark:bg-blue-900/30 p-1.5 text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/50 focus:outline-none">
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

        @if ($missingDatesCount > 0)
            <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-400 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                            Activities Hidden Due to Missing Dates
                        </h3>
                        <div class="mt-2 text-sm text-indigo-700 dark:text-indigo-300">
                            <p>
                                <strong>{{ $missingDatesCount }}</strong> active
                                {{ Str::plural('activity', $missingDatesCount) }}
                                are hidden because planned start/end dates are not set.
                            </p>
                            <p class="mt-1">
                                Please set dates in the
                                <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                                    class="font-medium underline hover:text-indigo-900 dark:hover:text-indigo-100">
                                    Schedules List
                                </a>
                                to enable progress updates.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- EMPTY STATE: If NO activities have dates (Replaces the old empty state) --}}
        @if ($leafSchedules->isEmpty())
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            No Activities Ready for Update
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>
                                To update progress, activities must have <strong>Planned Start</strong> and
                                <strong>End</strong> dates.
                            </p>
                            <p class="mt-2">
                                Please visit the
                                <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                                    class="font-medium underline hover:text-yellow-900 dark:hover:text-yellow-100">
                                    Schedules List
                                </a>
                                to assign baseline dates.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center py-12">
                <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Go to Schedules List
                </a>
            </div>

            {{-- NORMAL STATE: List of activities with dates --}}
        @else
            <form action="{{ route('admin.contracts.schedules.bulk-update', $contract) }}" method="POST"
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
                                        <th
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 sm:pl-6 w-[10%]">
                                            Code</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[25%]">
                                            Activity Name</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[12%]">
                                            Current</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[40%]">
                                            Update Progress</th>
                                        <th
                                            class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-200 w-[13%]">
                                            Quick Set</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                    @foreach ($schedules as $schedule)
                                        @php
                                            $useQuantity = $schedule->pivot->use_quantity_tracking ?? false;
                                            $target = $schedule->pivot->target_quantity ?? 0;
                                            $completed = $schedule->pivot->completed_quantity ?? 0;
                                            $unit = $schedule->pivot->unit ?? '';
                                            $progress = $schedule->pivot->progress ?? 0;
                                            $targetExists = !is_null($target) && $target > 0;
                                        @endphp

                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            data-schedule-id="{{ $schedule->id }}">
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">
                                                {{ $schedule->code }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $schedule->name }}
                                                </div>
                                                @if ($schedule->parent && $schedule->parent->code !== substr($schedule->code, 0, 1))
                                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        {{ $schedule->parent->code }} - {{ $schedule->parent->name }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                                    <div class="progress-bar h-2.5 rounded-full text-xs font-medium text-white text-center leading-2.5 transition-all duration-300"
                                                        style="width: {{ $progress }}%">
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ number_format($progress, 0) }}%
                                                    @if ($useQuantity)
                                                        ({{ $completed }} / {{ $target }}
                                                        {{ $unit }})
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-4 text-sm">
                                                <input type="hidden"
                                                    name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][id]"
                                                    value="{{ $schedule->id }}">

                                                {{-- Tracking mode toggle --}}
                                                <div class="mb-2">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox"
                                                            name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][use_quantity_tracking]"
                                                            value="1" {{ $useQuantity ? 'checked' : '' }}
                                                            onchange="toggleQuantityMode(this)"
                                                            class="rounded border-gray-300">
                                                        <span
                                                            class="ml-2 text-xs text-gray-700 dark:text-gray-300">Track
                                                            by quantity</span>
                                                    </label>
                                                </div>

                                                <div class="quantity-mode {{ $useQuantity ? '' : 'hidden' }}">
                                                    <div class="space-y-2">
                                                        <div class="flex items-center space-x-2">
                                                            <label
                                                                class="text-xs text-gray-600 dark:text-gray-400 w-20">Completed:</label>
                                                            <input type="number"
                                                                name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][completed_quantity]"
                                                                value="{{ $completed }}" placeholder="0"
                                                                min="0" step="0.01"
                                                                class="flex-1 text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                                                oninput="calculateQuantityProgress(this)">
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <label
                                                                class="text-xs text-gray-600 dark:text-gray-400 w-20">
                                                                Target:
                                                                @if ($targetExists)
                                                                    <i class="fas fa-lock text-blue-500 ml-1"
                                                                        title="Locked"></i>
                                                                @endif
                                                            </label>
                                                            <input type="number"
                                                                name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][target_quantity]"
                                                                value="{{ $target }}" placeholder="100"
                                                                min="0" step="0.01"
                                                                {{ $targetExists ? 'readonly' : '' }}
                                                                class="flex-1 text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700
                                                                {{ $targetExists ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : '' }}"
                                                                oninput="calculateQuantityProgress(this)">
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <label
                                                                class="text-xs text-gray-600 dark:text-gray-400 w-20">Unit:</label>
                                                            <input type="text"
                                                                name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][unit]"
                                                                value="{{ $unit }}" placeholder="unit"
                                                                list="unit-suggestions"
                                                                class="flex-1 text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                        </div>

                                                        {{-- Progress Display --}}
                                                        <div
                                                            class="text-xs text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-2 rounded">
                                                            Progress: <span
                                                                class="calculated-progress font-semibold text-blue-600">{{ number_format($progress, 1) }}%</span>
                                                        </div>
                                                    </div>

                                                    {{-- Hidden field for calculated progress --}}
                                                    <input type="hidden"
                                                        name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][progress]"
                                                        class="progress-value-hidden" value="{{ $progress }}">
                                                </div>

                                                {{-- Percentage Mode (Slider) --}}
                                                <div class="percentage-mode {{ $useQuantity ? 'hidden' : '' }}">
                                                    <input type="range"
                                                        class="progress-slider w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                                        name="schedules[{{ $loop->parent->index * 100 + $loop->index }}][progress]"
                                                        min="0" max="100" step="5"
                                                        value="{{ $progress }}"
                                                        oninput="updateProgressDisplay(this)">

                                                    <div class="text-center mt-2">
                                                        <span
                                                            class="progress-value inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                            {{ number_format($progress, 0) }}%
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

                {{-- Unit suggestions datalist --}}
                <datalist id="unit-suggestions">
                    <option value="poles">
                    <option value="towers">
                    <option value="meters">
                    <option value="kilometers">
                    <option value="cubic meters">
                    <option value="foundations">
                    <option value="spans">
                    <option value="panels">
                    <option value="transformers">
                    <option value="items">
                </datalist>

                <!-- Action Buttons Footer -->
                <div
                    class="bg-white dark:bg-gray-800 shadow sm:rounded-lg px-4 py-5 sm:p-6 ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="button" onclick="resetAll()"
                                class="inline-flex items-center justify-center rounded-md border border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/30 px-4 py-2 text-sm font-medium text-yellow-800 dark:text-yellow-400 shadow-sm hover:bg-yellow-100 dark:hover:bg-yellow-900/50">
                                Reset
                            </button>
                        </div>

                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                            <button type="button" onclick="setAllProgress(0)"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                                Set All 0%
                            </button>
                            <button type="button" onclick="setAllProgress(100)"
                                class="inline-flex items-center justify-center rounded-md border border-green-600 dark:border-green-500 bg-green-50 dark:bg-green-900/30 px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 shadow-sm hover:bg-green-100 dark:hover:bg-green-900/50">
                                Set All 100%
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 hover:bg-blue-700 px-6 py-2 text-sm font-medium text-white shadow-sm">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <script>
        // Store original values for reset
        const originalValues = {};
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.progress-slider, [name*="[completed_quantity]"]').forEach(input => {
                const row = input.closest('tr');
                const scheduleId = row.dataset.scheduleId;
                if (!originalValues[scheduleId]) {
                    originalValues[scheduleId] = {};
                }
                if (input.classList.contains('progress-slider')) {
                    originalValues[scheduleId].progress = input.value;
                } else if (input.name.includes('completed_quantity')) {
                    originalValues[scheduleId].completed = input.value;
                }
            });

            // Initialize progress bar colors
            document.querySelectorAll('.progress-bar').forEach(bar => {
                const width = parseFloat(bar.style.width) || 0;
                updateProgressBarColor(bar, width);
            });
        });

        function toggleQuantityMode(checkbox) {
            const row = checkbox.closest('tr');
            const quantityMode = row.querySelector('.quantity-mode');
            const percentageMode = row.querySelector('.percentage-mode');

            if (checkbox.checked) {
                quantityMode.classList.remove('hidden');
                percentageMode.classList.add('hidden');
            } else {
                quantityMode.classList.add('hidden');
                percentageMode.classList.remove('hidden');
            }
        }

        function calculateQuantityProgress(input) {
            const row = input.closest('tr');
            const completedInput = row.querySelector('[name*="[completed_quantity]"]');
            const targetInput = row.querySelector('[name*="[target_quantity]"]');
            const progressDisplay = row.querySelector('.calculated-progress');
            const progressHidden = row.querySelector('.progress-value-hidden');

            const completed = parseFloat(completedInput.value) || 0;
            const target = parseFloat(targetInput.value) || 0;

            let progress = 0;
            if (target > 0) {
                progress = Math.min(100, Math.max(0, (completed / target) * 100));
            }

            if (progressDisplay) {
                progressDisplay.textContent = progress.toFixed(1) + '%';
            }
            if (progressHidden) {
                progressHidden.value = progress;
            }

            // Update main progress bar
            const progressBar = row.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = progress + '%';
                updateProgressBarColor(progressBar, progress);
            }
        }

        function updateProgressBarColor(element, value) {
            element.classList.remove('bg-gray-300', 'bg-blue-500', 'bg-green-500', 'bg-gray-500');

            if (value >= 100) {
                element.classList.add('bg-green-500');
            } else if (value >= 50) {
                element.classList.add('bg-blue-500');
            } else {
                element.classList.add('bg-gray-400');
            }
        }

        function updateProgressDisplay(slider) {
            const row = slider.closest('tr');
            const valueDisplay = row.querySelector('.progress-value');
            const progressBar = row.querySelector('.progress-bar');

            valueDisplay.textContent = slider.value + '%';
            if (progressBar) {
                progressBar.style.width = slider.value + '%';
                updateProgressBarColor(progressBar, slider.value);
            }
        }

        function setProgress(button, value) {
            const row = button.closest('tr');
            const quantityMode = row.querySelector('.quantity-mode');
            const isQuantityMode = !quantityMode.classList.contains('hidden');

            if (isQuantityMode) {
                // Set quantity based on target
                const targetInput = row.querySelector('[name*="[target_quantity]"]');
                const completedInput = row.querySelector('[name*="[completed_quantity]"]');
                const target = parseFloat(targetInput.value) || 0;

                if (target > 0) {
                    completedInput.value = (target * value / 100).toFixed(2);
                    calculateQuantityProgress(completedInput);
                }
            } else {
                // Set slider
                const slider = row.querySelector('.progress-slider');
                slider.value = value;
                updateProgressDisplay(slider);
            }
        }

        function setAllProgress(value) {
            if (confirm(`Are you sure you want to set ALL activities to ${value}%?`)) {
                document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                    const button = row.querySelector('button');
                    if (button) {
                        setProgress(button, value);
                    }
                });
            }
        }

        function resetAll() {
            if (confirm('Reset all changes to original values?')) {
                document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                    const scheduleId = row.dataset.scheduleId;
                    const original = originalValues[scheduleId];

                    if (!original) return;

                    const slider = row.querySelector('.progress-slider');
                    const completedInput = row.querySelector('[name*="[completed_quantity]"]');

                    if (slider && original.progress !== undefined) {
                        slider.value = original.progress;
                        updateProgressDisplay(slider);
                    }

                    if (completedInput && original.completed !== undefined) {
                        completedInput.value = original.completed;
                        calculateQuantityProgress(completedInput);
                    }
                });
            }
        }

        // Confirm before leaving with unsaved changes
        let formSubmitted = false;
        document.getElementById('quick-update-form')?.addEventListener('submit', function() {
            formSubmitted = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted) {
                let hasChanges = false;
                document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                    const scheduleId = row.dataset.scheduleId;
                    const original = originalValues[scheduleId];

                    const slider = row.querySelector('.progress-slider');
                    const completedInput = row.querySelector('[name*="[completed_quantity]"]');

                    if (slider && original?.progress && slider.value != original.progress) {
                        hasChanges = true;
                    }
                    if (completedInput && original?.completed && completedInput.value != original
                        .completed) {
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
