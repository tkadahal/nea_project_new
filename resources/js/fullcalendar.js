import { Calendar } from "@fullcalendar/core";
import timelinePlugin from "@fullcalendar/timeline";

// Expose FullCalendar to the global scope
window.FullCalendar = { Calendar, timelinePlugin };

// Optional: Initialize a default calendar (commented out for now)
// const calendarEl = document.getElementById("ganttChart");
// const calendar = new Calendar(calendarEl, {
//     plugins: [timelinePlugin],
//     initialView: "timelineWeek",
//     events: [{ title: "Meeting", start: new Date() }],
// });
// calendar.render();
