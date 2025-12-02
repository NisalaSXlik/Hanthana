// Events page functionality
let currentFilter = 'upcoming';

// Helper to construct API URL safely
const getApiUrl = (queryString) => {
    const basePath = (BASE_PATH === '/' ? '' : BASE_PATH).replace(/\/$/, '');
    return `${basePath}/index.php${queryString}`;
};

document.addEventListener('DOMContentLoaded', function() {
    loadEvents(currentFilter);
    initializeEventHandlers();
});

/**
 * Initialize event handlers
 */
function initializeEventHandlers() {
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            loadEvents(currentFilter);
        });
    });
    
    // Create event button
    const createBtn = document.getElementById('createEventBtn');
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showCreateEventModal();
        });
    }
    
    // Modal close buttons
    const closeModalBtn = document.getElementById('closeEventModal');
    const cancelBtn = document.getElementById('cancelEventBtn');
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideCreateEventModal);
    if (cancelBtn) cancelBtn.addEventListener('click', hideCreateEventModal);
    
    // Create event form
    const createForm = document.getElementById('createEventForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateEvent);
    }
}

/**
 * Load events based on filter
 */
async function loadEvents(filter) {
    const container = document.getElementById('eventsContainer');
    container.innerHTML = `
        <div class="loading-spinner" style="grid-column: 1 / -1;">
            <i class="uil uil-spinner-alt"></i>
            <p>Loading events...</p>
        </div>
    `;
    
    try {
        const response = await fetch(getApiUrl(`?controller=Events&ajax_action=getEvents&filter=${filter}`));
        const data = await response.json();
        
        if (data.success && data.events && data.events.length > 0) {
            // Store events in global map
            window.eventsMap = {};
            data.events.forEach(e => window.eventsMap[e.post_id || e.event_id] = e);
            
            container.innerHTML = data.events.map(event => createEventCard(event)).join('');
            
        } else {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="uil uil-calendar-alt"></i>
                    <h3>No ${filter === 'upcoming' ? 'Upcoming' : filter === 'my_events' ? 'Your' : 'Past'} Events</h3>
                    <p>${filter === 'upcoming' ? 'No upcoming events at the moment' : filter === 'my_events' ? 'You haven\'t joined any events yet' : 'No past events'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="uil uil-exclamation-triangle"></i>
                <h3>Error Loading Events</h3>
                <p>Please try again later</p>
            </div>
        `;
    }
}

/**
 * Create event card HTML
 */
function createEventCard(event) {
    const eventDate = new Date(event.event_date);
    const day = eventDate.getDate();
    const month = eventDate.toLocaleString('default', { month: 'short' });
    
    const time = event.event_time ? formatTime(event.event_time) : 'Time TBA';
    const location = event.location || 'Location TBA';
    const description = event.description || 'No description available';
    
    const groupInfo = event.group_name 
        ? `<i class="uil uil-users-alt"></i> ${escapeHtml(event.group_name)}` 
        : `<i class="uil uil-user"></i> ${escapeHtml(event.first_name + ' ' + event.last_name)}`;
    
    // Use event_title if available, fallback to title (PostModel returns event_title)
    const title = event.event_title || event.title || 'Untitled Event';
    
    const isAdded = event.is_going == 1;
    const btnClass = isAdded ? 'btn-add-calendar added' : 'btn-add-calendar';
    const btnContent = isAdded ? '<i class="uil uil-check"></i>' : '<i class="uil uil-calendar-alt"></i>';
    
    return `
        <div class="event-card" data-event-id="${event.post_id || event.event_id}">
            <div class="event-card-header">
                <h3 class="event-card-title">${escapeHtml(title)}</h3>
                <div class="event-date-badge">
                    <span class="day">${day}</span>
                    <span class="month">${month}</span>
                </div>
                <p class="event-card-group">${groupInfo}</p>
                <button class="${btnClass}" title="${isAdded ? 'Added to Calendar' : 'Add to Calendar'}" onclick="addToCalendar(this, ${event.post_id || event.event_id})">
                    ${btnContent}
                </button>
            </div>
            <div class="event-card-body">
                <div class="event-detail">
                    <i class="uil uil-clock"></i>
                    <span>${time}</span>
                </div>
                <div class="event-detail">
                    <i class="uil uil-location-point"></i>
                    <span>${escapeHtml(location)}</span>
                </div>
                <div class="event-description">
                    ${escapeHtml(description)}
                </div>
                <div class="event-card-footer">
                    <div class="event-stats">
                        <span><i class="uil uil-check-circle"></i> ${event.going_count || 0} going</span>
                        <span><i class="uil uil-star"></i> ${event.interested_count || 0} interested</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Add event to calendar
 */
async function addToCalendar(btn, eventId) {
    // Find event data from the card or fetch it
    // For simplicity, we'll grab text from the card
    const card = btn.closest('.event-card');
    const title = card.querySelector('.event-card-title').innerText;
    const location = card.querySelector('.event-detail:nth-child(2) span').innerText;
    const description = card.querySelector('.event-description').innerText;
    // Date and time are harder to parse back from UI, ideally we pass raw data.
    // But we don't have the raw data object here easily unless we store it.
    // Let's try to fetch the event details or pass them in data attributes.
    // Or better, just send the ID and let the backend handle it?
    // The backend addToCalendar implementation I wrote expects title, date etc.
    // I should update backend to fetch details if only ID is provided.
    // OR, I can store raw date/time in data attributes.
    
    // Let's assume the backend can handle it if I send just ID, 
    // BUT I implemented it to expect data.
    // Let's update the createEventCard to store data attributes.
    
    // Actually, I'll just update the backend to fetch the post if data is missing.
    // But I can't easily update backend now without another tool call.
    // Let's try to extract from UI or use a global events map.
    
    // I'll use a global map for events data to avoid parsing HTML.
    const event = window.eventsMap && window.eventsMap[eventId];
    
    if (!event) {
        // Fallback or error
        console.error('Event data not found');
        return;
    }

    // Immediate feedback
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="uil uil-spinner-alt uil-spin"></i>';
    btn.disabled = true;

    try {
        const response = await fetch(getApiUrl('?controller=Events&ajax_action=addToCalendar'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                post_id: eventId,
                title: event.event_title || event.title,
                event_date: event.event_date,
                event_time: event.event_time,
                location: event.event_location || event.location,
                description: event.content || event.description
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('Event added to your calendar!', 'success');
            btn.classList.add('added');
            btn.innerHTML = '<i class="uil uil-check"></i>';
            // Keep disabled to prevent duplicate adds
            
            // Update going count
            if (data.going_count !== undefined) {
                const card = btn.closest('.event-card');
                const stats = card.querySelector('.event-stats');
                if (stats) {
                    // Assuming the first span is "going"
                    const goingSpan = stats.querySelector('span:first-child');
                    if (goingSpan) {
                        goingSpan.innerHTML = `<i class="uil uil-check-circle"></i> ${data.going_count} going`;
                    }
                }
            }
        } else {
            showNotification('Failed to add to calendar', 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Error adding to calendar:', error);
        showNotification('Error adding to calendar', 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// Make addToCalendar globally available
window.addToCalendar = addToCalendar;

/**
 * Show create event modal
 */
function showCreateEventModal() {
    const modal = document.getElementById('createEventModal');
    if (modal) {
        modal.style.display = 'flex';
        // Set min date to today
        const dateInput = document.getElementById('createEventDate');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
        }
    }
}

/**
 * Hide create event modal
 */
function hideCreateEventModal() {
    const modal = document.getElementById('createEventModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('createEventForm').reset();
    }
}

/**
 * Handle create event form submission
 */
async function handleCreateEvent(e) {
    e.preventDefault();
    
    const title = document.getElementById('createEventTitle').value.trim();
    const description = document.getElementById('createEventDescription').value.trim();
    const date = document.getElementById('createEventDate').value;
    const time = document.getElementById('createEventTime').value;
    const location = document.getElementById('createEventLocation').value.trim();
    
    if (!title || !date) {
        showNotification('Please fill in required fields', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('date', date);
    formData.append('time', time);
    formData.append('location', location);
    
    try {
        const response = await fetch(getApiUrl('?controller=Events&ajax_action=createEvent'), {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Event created successfully!', 'success');
            hideCreateEventModal();
            loadEvents(currentFilter);
        } else {
            showNotification(data.message || data.error || 'Failed to create event', 'error');
        }
    } catch (error) {
        console.error('Error creating event:', error);
        showNotification('An error occurred', 'error');
    }
}

/**
 * Helper: Format time
 */
function formatTime(timeString) {
    if (!timeString) return 'Time TBA';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

/**
 * Helper: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

/**
 * Helper: Show notification
 */
function showNotification(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }
    alert(message);
}
