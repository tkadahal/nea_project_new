import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';

// Global variable to hold the calendar instance
let taskCalendar = null;

// Function to initialize or re-render the calendar
window.renderTaskCalendar = function() {
    const calendarEl = document.getElementById('taskCalendarContent');

    if (!calendarEl) {
        console.error('Calendar element with ID "taskCalendarContent" not found.');
        return;
    }

    try {
        // Retrieve events from data attribute
        const events = JSON.parse(calendarEl.dataset.events || '[]');
        console.log('FullCalendar events data:', events);

        // If the calendar is already initialized, destroy it to avoid duplicates
        if (taskCalendar) {
            taskCalendar.destroy();
            taskCalendar = null;
        }

        // Initialize the calendar
        taskCalendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,dayGridDay'
            },
            events: events,
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            },
            eventDidMount: function(info) {
                info.el.title =
                    `${info.event.title} (Status: ${info.event.extendedProps.status}, Priority: ${info.event.extendedProps.priority})`;
            },
            height: 'auto',
            contentHeight: 'auto',
            aspectRatio: 1.8,
        });

        // Render the calendar
        taskCalendar.render();
        console.log('Task calendar rendered successfully.');
    } catch (error) {
        console.error('FullCalendar initialization error:', error);
        calendarEl.innerHTML =
            '<p class="text-red-500 dark:text-red-400 text-center p-4">Failed to load calendar: ' + error.message + '</p>';
    }
};

// Initial render on page load if the calendar view is visible
window.addEventListener('load', function() {
    const calendarView = document.getElementById('calendar-view');
    if (calendarView && !calendarView.classList.contains('hidden')) {
        window.renderTaskCalendar();
    }
});