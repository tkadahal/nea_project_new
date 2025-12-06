<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.event.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.event.title') }}
            </p>
        </div>

        @can('event_create')
            <a href="{{ route('admin.event.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                  dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                {{ trans('global.add') }} {{ trans('global.new') }}
            </a>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
        <div id="calendar" class="w-full"></div>
    </div>

    @if (session('success'))
        <div class="mt-4 text-green-600 dark:text-green-400">{{ session('success') }}</div>
    @endif

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
        <style>
            #calendar {
                width: 100% !important;
                max-width: none !important;
            }

            .fc {
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
                    console.error('Calendar element not found');
                    return;
                }

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: @json($events),
                    contentHeight: 'auto',
                    dayMaxEvents: true,
                    allDaySlot: true,
                    slotDuration: '00:30:00',
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    dateClick: function(info) {
                        window.location.href = '{{ route('admin.event.create') }}?date=' + info.dateStr;
                    },
                    eventClick: function(info) {
                        console.log('Event clicked:', info.event);
                    }
                });

                try {
                    calendar.render();
                } catch (error) {
                    console.error('Error rendering calendar:', error);
                }

                // Debounce function to limit resize calls
                function debounce(fn, ms) {
                    let timeout;
                    return function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => fn.apply(this, arguments), ms);
                    };
                }

                // Update calendar size on sidebar toggle
                document.body.addEventListener('sidebarToggle', function() {
                    calendar.updateSize();
                });

                // Update calendar size on window resize
                window.addEventListener('resize', debounce(function() {
                    calendar.updateSize();
                }, 100));
            });
        </script>
    @endpush
</x-layouts.app>
