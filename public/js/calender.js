// Calendar functionality
const calender = document.querySelector(".calender"),
    date = document.querySelector(".date"),
    daysContainer = document.querySelector(".days"),
    prev = document.querySelector(".prev"),
    next = document.querySelector(".next"),
    todayBtn = document.querySelector(".today-btn"),
    gotoBtn = document.querySelector(".goto-btn"),
    dateInput = document.querySelector(".date-input"),
    calendarPopup = document.getElementById("calendarPopup");

let today = new Date();
let activeDay;
let month = today.getMonth();
let year = today.getFullYear();

const months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

let eventData = {};
let calendarDataLoaded = false;

function normalizeDateKey(dateStr) {
    if (!dateStr) return null;
    const dateObj = new Date(dateStr);
    if (Number.isNaN(dateObj.getTime())) {
        return null;
    }
    return `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`;
}

function formatEventTimeLabel(timeStr) {
    if (!timeStr) return 'All day';
    const [hour, minute] = timeStr.split(':');
    if (hour === undefined) return 'All day';
    let h = parseInt(hour, 10);
    const m = minute ? minute.substring(0, 2) : '00';
    const suffix = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${suffix}`;
}

async function fetchCalendarEvents() {
    if (typeof BASE_PATH === 'undefined') {
        return;
    }
    try {
        const response = await fetch(`${BASE_PATH}index.php?controller=Calendar&action=handleAjax&sub_action=list`, {
            credentials: 'same-origin'
        });
        const data = await response.json();
        if (data.success && Array.isArray(data.events)) {
            const hydrated = {};
            data.events.forEach(evt => {
                const key = normalizeDateKey(evt.event_date || evt.metadata?.event_date);
                if (!key) return;
                if (!hydrated[key]) {
                    hydrated[key] = [];
                }
                hydrated[key].push({
                    title: evt.title || 'Untitled Event',
                    event_time: evt.event_time || null,
                    location: evt.location || '',
                    description: evt.description || '',
                    post_id: evt.post_id || null
                });
            });
            eventData = hydrated;
            calendarDataLoaded = true;
            initCalendar();
        }
    } catch (err) {
        console.error('Failed to load calendar events', err);
    }
}

// Initialize calendar
function initCalendar() {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);
    const prevDays = prevLastDay.getDate();
    const lastDate = lastDay.getDate();
    const firstDayIndex = firstDay.getDay();
    const nextDays = 7 - ((firstDayIndex + lastDate) % 7);

    date.innerHTML = `${months[month]} ${year}`;
    
    let days = "";

    // Previous month days
    for (let x = firstDayIndex; x > 0; x--) {
        days += `<div class="day prev-date">${prevDays - x + 1}</div>`;
    }

    // Current month days
    for (let i = 1; i <= lastDate; i++) {
        const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const hasEvent = eventData[dateKey] && eventData[dateKey].length > 0;
        const dot = hasEvent ? `<span class="event-dot"></span>` : "";
        
        // Check if it's today
        const isToday = 
            i === today.getDate() && 
            year === today.getFullYear() && 
            month === today.getMonth();
            
        const todayClass = isToday ? "today" : "";
        
        days += `<div class="day ${todayClass}" data-date="${dateKey}">${i}${dot}</div>`;
    }

    // Next month days
    for (let j = 1; j <= nextDays; j++) {
        days += `<div class="day next-date">${j}</div>`;
    }

    daysContainer.innerHTML = days;
    addDayClickListeners();
}

// Add click event listeners to days
function addDayClickListeners() {
    const days = document.querySelectorAll('.day:not(.prev-date):not(.next-date)');
    
    days.forEach(day => {
        day.addEventListener('click', () => {
            const dateKey = day.getAttribute('data-date');
            const dayNumber = day.textContent.trim();
            showEventsPopup(month, dayNumber, year, dateKey);
        });
    });
}

// Show events popup for a specific day
function showEventsPopup(month, day, year, dateKey) {
    const popupDate = document.getElementById('popup-date');
    const eventsContainer = document.getElementById('calendarEvents');
    
    if (!popupDate || !eventsContainer) return;
    
    popupDate.textContent = `${months[month]} ${day}, ${year}`;
    eventsContainer.innerHTML = '';
    
    // Clear previous classes
    eventsContainer.classList.remove('has-events', 'no-events');
    
    const events = Array.isArray(eventData[dateKey]) ? eventData[dateKey] : [];
    
    if (events.length > 0) {
        eventsContainer.classList.add('has-events');
        
        // Add close button
        const closeBtn = document.createElement('div');
        closeBtn.className = 'close-popup';
        closeBtn.innerHTML = '<i class="uil uil-times"></i>';
        closeBtn.addEventListener('click', () => {
            if (calendarPopup) {
                calendarPopup.style.display = 'none';
            }
        });
        eventsContainer.appendChild(closeBtn);
        
        events.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = 'event-item';
            const title = event.title || 'Scheduled event';
            const timeLabel = formatEventTimeLabel(event.event_time);
            const location = event.location ? `<div class="event-location"><i class="uil uil-location-point"></i> ${event.location}</div>` : '';
            eventElement.innerHTML = `
                <div class="event-time">${timeLabel}</div>
                <div class="event-title">${title}</div>
                ${location}
            `;
            eventsContainer.appendChild(eventElement);
        });
    } else {
        eventsContainer.classList.add('no-events');
        eventsContainer.innerHTML = `
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        `;
    }
    
    if (calendarPopup) {
        calendarPopup.style.display = 'block';
    }
}
// Close popup when clicking outside
document.addEventListener('click', (e) => {
    if (calendarPopup && !e.target.closest('.day') && !e.target.closest('#calendarPopup')) {
        calendarPopup.style.display = 'none';
    }
});

// Navigation functions
function prevMonth() {
    month--;
    if (month < 0) {
        month = 11;
        year--;
    }
    initCalendar();
}

function nextMonth() {
    month++;
    if (month > 11) {
        month = 0;
        year++;
    }
    initCalendar();
}

function gotoToday() {
    today = new Date();
    month = today.getMonth();
    year = today.getFullYear();
    initCalendar();
}

function gotoDate() {
    if (!dateInput) {
        return;
    }
    const dateArr = dateInput.value.split('/');
    
    if (dateArr.length === 2) {
        if (dateArr[0] > 0 && dateArr[0] < 13 && dateArr[1].length === 4) {
            month = dateArr[0] - 1;
            year = dateArr[1];
            initCalendar();
            return;
        }
    }
    alert('Invalid date. Please use MM/YYYY format.');
}

// Event listeners
prev?.addEventListener('click', prevMonth);
next?.addEventListener('click', nextMonth);
todayBtn?.addEventListener('click', gotoToday);
gotoBtn?.addEventListener('click', gotoDate);

// Format date input safely when field exists
if (dateInput) {
    dateInput.addEventListener('input', () => {
        dateInput.value = dateInput.value.replace(/[^0-9/]/g, '');

        if (dateInput.value.length === 2 && !dateInput.value.includes('/')) {
            dateInput.value += '/';
        }

        if (dateInput.value.length > 7) {
            dateInput.value = dateInput.value.slice(0, 7);
        }
    });
}

// Initialize the calendar
initCalendar();
fetchCalendarEvents();
document.addEventListener('calendar:refresh', fetchCalendarEvents);

// Notification popup toggle
const notificationIcon = document.querySelector('.notification');
const notificationPopup = document.querySelector('.notifications-popup');

if (notificationIcon && notificationPopup) {
    notificationIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationPopup.style.display = notificationPopup.style.display === 'block' ? 'none' : 'block';
    });

    // Close notification popup when clicking outside
    document.addEventListener('click', () => {
        notificationPopup.style.display = 'none';
    });

    // Prevent notification popup from closing when clicking inside it
    notificationPopup.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

// Calendar popup toggle
const calendarIcon = document.querySelector('.calendar-icon');
const calendarPopupElement = document.querySelector('.calendar-popup');

if (calendarIcon && calendarPopupElement) {
    calendarIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        calendarPopupElement.style.display = calendarPopupElement.style.display === 'block' ? 'none' : 'block';
    });

    // Close calendar popup when clicking outside
    document.addEventListener('click', () => {
        calendarPopupElement.style.display = 'none';
    });

    // Prevent calendar popup from closing when clicking inside it
    calendarPopupElement.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

// calendar.js - Handles calendar-related functionality
document.addEventListener('DOMContentLoaded', function() {
    const calendarIcon = document.querySelector('.calendar-icon');
    
    if (calendarIcon) {
        calendarIcon.addEventListener('click', function(e) {
            // Calendar functionality here
            console.log("Calendar clicked");
            // Implement calendar logic
        });
    }
    
    // Other calendar-related functions
});