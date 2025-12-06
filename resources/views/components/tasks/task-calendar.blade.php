@props(['events' => []])

@vite(['resources/css/fullcalendar.css', 'resources/js/taskCalendar.js'])

<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4">
    <div id="taskCalendarContent" data-events="{{ json_encode($events) }}"></div>
</div>
