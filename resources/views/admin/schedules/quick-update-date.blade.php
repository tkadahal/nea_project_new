<x-layouts.app>
    <div class="container-fluid px-4 sm:px-6 lg:px-8 py-6">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.contract.index') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Contracts
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                                Quick Date Update
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2
                        class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        Quick Date Update — Leaf Activities
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Set planned start and end dates for all executable activities at once.
                    </p>
                </div>
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

        {{-- Missing dates info banner --}}
        @if ($missingDatesCount > 0)
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
                            Activities Missing Dates
                        </h3>
                        <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <strong>{{ $missingDatesCount }}</strong>
                            {{ Str::plural('activity', $missingDatesCount) }}
                            currently have no start or end date. Use this page to set them.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Empty state --}}
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
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Activities Found</h3>
                        <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            There are no active leaf activities assigned to this contract.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center py-12">
                <a href="{{ route('admin.contracts.schedules.index', $contract) }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Go to Schedules List
                </a>
            </div>
        @else
            <form action="{{ route('admin.contracts.schedules.bulk-update-date', $contract) }}" method="POST"
                id="quick-date-form" class="space-y-6">
                @csrf

                @foreach ($leafSchedules as $rootCode => $schedules)
                    @php
                        // Look up the top-level schedule object from the map
                        $topLevel = $topLevelMap[$rootCode] ?? null;
                    @endphp

                    <!-- Phase / Top-Level Card -->
                    <div
                        class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 rounded-lg overflow-hidden">

                        <!-- Card Header -->
                        <div
                            class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 px-4 py-4 sm:px-6 flex flex-wrap justify-between items-center gap-2">
                            <div>
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                                    @if ($topLevel)
                                        {{ $topLevel->code }}: {{ $topLevel->name }}
                                    @else
                                        Uncategorized
                                    @endif
                                </h3>
                            </div>
                            <div class="flex items-center flex-wrap gap-2">
                                @if ($topLevel && $topLevel->weightage)
                                    <span
                                        class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10 dark:ring-indigo-400/30">
                                        Weight: {{ $topLevel->weightage }}%
                                    </span>
                                @endif
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-500/10">
                                    {{ $schedules->count() }} activities
                                </span>

                                {{-- Bulk apply button for this group --}}
                                <button type="button" onclick="togglePhasePicker('{{ $rootCode }}')"
                                    class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-400/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Apply Dates to Group
                                </button>
                            </div>
                        </div>

                        {{-- Bulk date picker panel (hidden by default) --}}
                        <div id="phase-picker-{{ $rootCode }}"
                            class="hidden border-b border-blue-100 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/10 px-4 py-3 sm:px-6">
                            <div class="flex flex-wrap items-end gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Apply
                                        Start Date</label>
                                    <input type="date" id="phase-start-{{ $rootCode }}"
                                        class="text-sm rounded border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Apply
                                        End Date</label>
                                    <input type="date" id="phase-end-{{ $rootCode }}"
                                        class="text-sm rounded border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <button type="button" onclick="applyGroupDates('{{ $rootCode }}')"
                                    class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    Apply to All Rows
                                </button>
                                <button type="button" onclick="togglePhasePicker('{{ $rootCode }}')"
                                    class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
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
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[20%]">
                                            Start Date</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[20%]">
                                            End Date</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[15%]">
                                            Duration</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 w-[10%]">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800"
                                    data-group="{{ $rootCode }}">

                                    @foreach ($schedules as $schedule)
                                        @php
                                            $startDate = $schedule->pivot->start_date ?? null;
                                            $endDate = $schedule->pivot->end_date ?? null;
                                            $fieldIndex = $loop->parent->index * 100 + $loop->index;
                                            $hasDates = $startDate && $endDate;
                                        @endphp

                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            data-schedule-id="{{ $schedule->id }}" data-group="{{ $rootCode }}">

                                            <!-- Code -->
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">
                                                {{ $schedule->code }}
                                            </td>

                                            <!-- Name -->
                                            <td class="px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $schedule->name }}
                                                </div>
                                                @if ($schedule->parent)
                                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        {{ $schedule->parent->code }} — {{ $schedule->parent->name }}
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Hidden schedule ID -->
                                            <input type="hidden" name="schedules[{{ $fieldIndex }}][id]"
                                                value="{{ $schedule->id }}">

                                            <!-- Start Date -->
                                            <td class="px-3 py-4 text-sm">
                                                <input type="date"
                                                    name="schedules[{{ $fieldIndex }}][start_date]"
                                                    value="{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : '' }}"
                                                    class="date-start w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500"
                                                    oninput="updateDuration(this)">
                                            </td>

                                            <!-- End Date -->
                                            <td class="px-3 py-4 text-sm">
                                                <input type="date" name="schedules[{{ $fieldIndex }}][end_date]"
                                                    value="{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('Y-m-d') : '' }}"
                                                    class="date-end w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500"
                                                    oninput="updateDuration(this)">
                                            </td>

                                            <!-- Duration (computed) -->
                                            <td class="px-3 py-4 text-sm">
                                                <span
                                                    class="duration-label inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                                    {{ $hasDates
                                                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                                    @if ($hasDates)
                                                        {{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) }}
                                                        days
                                                    @else
                                                        —
                                                    @endif
                                                </span>
                                            </td>

                                            <!-- Status badge -->
                                            <td class="px-3 py-4 text-sm">
                                                @if ($hasDates)
                                                    <span
                                                        class="date-status inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20">
                                                        Set
                                                    </span>
                                                @else
                                                    <span
                                                        class="date-status inline-flex items-center rounded-md bg-red-50 dark:bg-red-900/20 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20">
                                                        Missing
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <!-- Footer Actions -->
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
                            <button type="button" onclick="clearAllDates()"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                                Clear All Dates
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 hover:bg-blue-700 px-6 py-2 text-sm font-medium text-white shadow-sm">
                                Save Dates
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <script>
        // ─────────────────────────────────────────────
        // Store original values for Reset
        // ─────────────────────────────────────────────
        const originalValues = {};

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                const id = row.dataset.scheduleId;
                originalValues[id] = {
                    start: row.querySelector('.date-start')?.value ?? '',
                    end: row.querySelector('.date-end')?.value ?? '',
                };
            });
        });

        // ─────────────────────────────────────────────
        // Live duration + status update when a date changes
        // ─────────────────────────────────────────────
        function updateDuration(input) {
            const row = input.closest('tr');
            const startVal = row.querySelector('.date-start')?.value;
            const endVal = row.querySelector('.date-end')?.value;
            const label = row.querySelector('.duration-label');
            const status = row.querySelector('.date-status');

            if (startVal && endVal) {
                const start = new Date(startVal);
                const end = new Date(endVal);

                if (end < start) {
                    setDurationLabel(label, 'Invalid',
                        'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20');
                    setStatusBadge(status, 'Invalid',
                        'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20');
                    return;
                }

                const days = Math.round((end - start) / 86400000);
                setDurationLabel(label, days + ' days',
                    'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20'
                );
                setStatusBadge(status, 'Set',
                    'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20'
                );
            } else {
                setDurationLabel(label, '—', 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400');
                setStatusBadge(status, 'Missing',
                    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20');
            }
        }

        function setDurationLabel(el, text, classes) {
            if (!el) return;
            el.textContent = text;
            el.className = 'duration-label inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ' + classes;
        }

        function setStatusBadge(el, text, classes) {
            if (!el) return;
            el.textContent = text;
            el.className = 'date-status inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ' + classes;
        }

        // ─────────────────────────────────────────────
        // Toggle bulk date picker panel for a group
        // ─────────────────────────────────────────────
        function togglePhasePicker(group) {
            const picker = document.getElementById('phase-picker-' + group);
            if (picker) picker.classList.toggle('hidden');
        }

        // ─────────────────────────────────────────────
        // Apply group-level dates to every row in that group
        // ─────────────────────────────────────────────
        function applyGroupDates(group) {
            const startVal = document.getElementById('phase-start-' + group)?.value;
            const endVal = document.getElementById('phase-end-' + group)?.value;

            if (!startVal && !endVal) {
                alert('Please enter at least one date to apply.');
                return;
            }
            if (startVal && endVal && new Date(endVal) < new Date(startVal)) {
                alert('End date cannot be before start date.');
                return;
            }

            document.querySelectorAll('tbody[data-group="' + group + '"] tr[data-schedule-id]').forEach(row => {
                const startInput = row.querySelector('.date-start');
                const endInput = row.querySelector('.date-end');
                if (startInput && startVal) startInput.value = startVal;
                if (endInput && endVal) endInput.value = endVal;
                if (startInput) updateDuration(startInput);
            });

            document.getElementById('phase-picker-' + group)?.classList.add('hidden');
        }

        // ─────────────────────────────────────────────
        // Reset all rows to original DB values
        // ─────────────────────────────────────────────
        function resetAll() {
            if (!confirm('Reset all changes to original values?')) return;

            document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                const id = row.dataset.scheduleId;
                const original = originalValues[id];
                if (!original) return;

                const startInput = row.querySelector('.date-start');
                const endInput = row.querySelector('.date-end');
                if (startInput) {
                    startInput.value = original.start;
                }
                if (endInput) {
                    endInput.value = original.end;
                }
                if (startInput) updateDuration(startInput);
            });
        }

        // ─────────────────────────────────────────────
        // Clear all date inputs
        // ─────────────────────────────────────────────
        function clearAllDates() {
            if (!confirm('Are you sure you want to clear ALL dates?')) return;

            document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                const startInput = row.querySelector('.date-start');
                const endInput = row.querySelector('.date-end');
                if (startInput) startInput.value = '';
                if (endInput) endInput.value = '';
                if (startInput) updateDuration(startInput);
            });
        }

        // ─────────────────────────────────────────────
        // Validate before submit — block invalid ranges
        // ─────────────────────────────────────────────
        let formSubmitted = false;

        document.getElementById('quick-date-form')?.addEventListener('submit', function(e) {
            const invalids = [];

            document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                const startVal = row.querySelector('.date-start')?.value;
                const endVal = row.querySelector('.date-end')?.value;
                const code = row.querySelector('td:first-child')?.textContent?.trim();

                if (startVal && endVal && new Date(endVal) < new Date(startVal)) {
                    invalids.push(code);
                }
            });

            if (invalids.length > 0) {
                e.preventDefault();
                alert(
                    'The following activities have end dates before start dates:\n\n' +
                    invalids.join('\n') +
                    '\n\nPlease fix before saving.'
                );
                return;
            }

            formSubmitted = true;
        });

        // ─────────────────────────────────────────────
        // Warn on unsaved changes
        // ─────────────────────────────────────────────
        window.addEventListener('beforeunload', function(e) {
            if (formSubmitted) return;

            let hasChanges = false;
            document.querySelectorAll('tr[data-schedule-id]').forEach(row => {
                const id = row.dataset.scheduleId;
                const original = originalValues[id];
                const startVal = row.querySelector('.date-start')?.value ?? '';
                const endVal = row.querySelector('.date-end')?.value ?? '';

                if (startVal !== original?.start || endVal !== original?.end) {
                    hasChanges = true;
                }
            });

            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</x-layouts.app>
