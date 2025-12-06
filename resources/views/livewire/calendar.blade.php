<div class="w-full">
    <!-- Header with Three-Dot Menu -->
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">
            {{ trans('global.event.title') }}
        </h2>
        <div class="relative">
            <button id="dropdownButton"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
            </button>
            @can('event_create')
                <div id="dropdownMenu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10">
                    <a href="{{ route('admin.event.create') }}"
                        class="block px-4 py-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        {{ trans('global.add') }} {{ trans('global.event.title_singular') }}
                    </a>
                </div>
            @endcan
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-1 sm:space-x-2">
            <button wire:click="prevMonth"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button wire:click="goToToday"
                class="text-xs sm:text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                Today
            </button>
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mx-1 sm:mx-2"
                id="calendarMonthYear">
                {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}
            </h2>
            <button wire:click="nextMonth"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Calendar and Events -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <!-- Calendar -->
        <div class="overflow-hidden">
            <div id="calendar" wire:ignore class="w-full"></div>
        </div>
        <!-- Events List -->
        <div>
            <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2 sm:mb-4">
                Events for {{ \Carbon\Carbon::parse($activeDate)->format('F j, Y') }}
            </h2>
            <div class="space-y-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                @forelse ($filteredEvents as $event)
                    <div class="truncate">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ \Carbon\Carbon::parse($event['start'])->format('g:i A') }} -
                            {{ $event['end'] ? \Carbon\Carbon::parse($event['end'])->format('g:i A') : 'No end time' }}
                        </span>
                        <div class="ml-5 truncate"><strong>{{ $event['title'] }}</strong></div>
                        <div class="ml-5 truncate">{{ $event['description'] ?? 'No description' }}</div>
                    </div>
                @empty
                    <p>
                        {{ trans('global.noRecords') }}
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
        <style>
            #calendar {
                width: 100% !important;
                max-width: none !important;
                font-size: 12px;
            }

            .fc {
                width: 100% !important;
            }

            @media (min-width: 640px) {
                #calendar {
                    font-size: 14px;
                }
            }

            #calendar .fc-daygrid-day[data-date="{{ now()->toDateString() }}"] {
                background-color: #ef4444 !important;
                color: white !important;
                border: 2px solid #b91c1c !important;
                border-radius: 4px !important;
            }

            #calendar .fc-header-toolbar {
                display: none;
            }

            #calendar .fc-daygrid-day {
                padding: 2px;
            }

            @media (min-width: 640px) {
                #calendar .fc-daygrid-day {
                    padding: 4px;
                }
            }

            #calendar .fc-daygrid-day-number {
                font-size: 10px;
                padding: 2px;
            }

            @media (min-width: 640px) {
                #calendar .fc-daygrid-day-number {
                    font-size: 12px;
                    padding: 4px;
                }
            }

            #calendar .fc-col-header-cell-cushion {
                font-size: 10px;
                padding: 1px;
            }

            @media (min-width: 640px) {
                #calendar .fc-col-header-cell-cushion {
                    font-size: 12px;
                    padding: 2px;
                }
            }

            #calendar .fc-button {
                background-color: transparent;
                border: none;
                color: #6b7280;
            }

            #calendar .fc-button:hover {
                background-color: #f3f4f6;
            }

            #calendar .fc-daygrid-day.fc-daygrid-day-selected {
                background-color: #e0e7ff !important;
                color: #1e40af !important;
            }

            #calendar .fc-daygrid-day,
            #calendar .fc-daygrid-day-frame,
            #calendar .fc-daygrid-day-top,
            #calendar .fc-daygrid-day-number,
            #calendar .fc-daygrid-day-number a {
                cursor: pointer !important;
            }

            .sidebar-hidden .main-content {
                width: 100% !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                if (!calendarEl) {
                    console.error('Calendar element not found!');
                    return;
                }

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: false,
                    events: [], // No events on calendar grid
                    dateClick: function(info) {
                        var previousSelected = calendarEl.querySelector('.fc-daygrid-day-selected');
                        if (previousSelected) {
                            previousSelected.classList.remove('fc-daygrid-day-selected');
                        }
                        info.dayEl.classList.add('fc-daygrid-day-selected');
                        window.Livewire.dispatch('setActiveDate', {
                            date: info.dateStr
                        });
                    },
                    datesSet: function(info) {
                        document.getElementById('calendarMonthYear').textContent = info.view.title;
                        highlightToday();
                    }
                });

                try {
                    calendar.render();
                } catch (error) {
                    console.error('Error rendering calendar:', error);
                }

                // Highlight today's date
                function highlightToday() {
                    var today = new Date();
                    var todayStr = today.toISOString().split('T')[0];
                    console.log('Today date:', todayStr);
                    var todayCell = calendarEl.querySelector(`[data-date="${todayStr}"]`);
                    if (todayCell) {
                        todayCell.classList.add('bg-red-500', 'text-white');
                        console.log('Today cell highlighted:', todayCell);
                    } else {
                        console.warn('Today cell not found for date:', todayStr);
                    }
                }
                highlightToday();

                // Highlight active date
                var activeDate = @json($activeDate);
                var activeCell = calendarEl.querySelector(`[data-date="${activeDate}"]`);
                if (activeCell) {
                    activeCell.classList.add('fc-daygrid-day-selected');
                }

                // Listen for changeMonth event from Livewire
                Livewire.on('changeMonth', (event) => {
                    calendar.gotoDate(event.date);
                    document.getElementById('calendarMonthYear').textContent = calendar.view.title;
                    highlightToday();
                });

                // Debounce function
                function debounce(fn, ms) {
                    let timeout;
                    return function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => fn.apply(this, arguments), ms);
                    };
                }

                // Resize calendar on sidebar toggle
                document.body.addEventListener('sidebarToggle', function() {
                    calendar.updateSize();
                    console.log('Calendar resized on sidebar toggle');
                });

                // Resize calendar on window resize
                window.addEventListener('resize', debounce(function() {
                    calendar.updateSize();
                    console.log('Calendar resized on window resize');
                }, 100));

                // Toggle dropdown menu
                const dropdownButton = document.getElementById('dropdownButton');
                const dropdownMenu = document.getElementById('dropdownMenu');
                dropdownButton.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (event) => {
                    if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.add('hidden');
                    }
                });

                // Debug cursor on hover
                calendarEl.addEventListener('mouseover', (event) => {
                    if (event.target.closest('.fc-daygrid-day')) {
                        console.log('Hovering over day cell:', event.target);
                    }
                });
            });
        </script>
    @endpush
</div>
