// Events page functionality
let currentFilter = 'upcoming';

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
        createBtn.addEventListener('click', showCreateEventModal);
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
        const response = await fetch(`${BASE_PATH}/index.php?controller=Events&ajax_action=getEvents&filter=${filter}`);
        const data = await response.json();
        
        if (data.success && data.events && data.events.length > 0) {
            container.innerHTML = data.events.map(event => createEventCard(event)).join('');
            
            // Add RSVP button handlers
            container.querySelectorAll('.btn-rsvp').forEach(btn => {
                btn.addEventListener('click', handleRSVP);
            });
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
    
    const interestedClass = event.user_rsvp_status === 'interested' ? 'interested' : '';
    const goingClass = event.user_rsvp_status === 'going' ? 'going' : '';
    
    return `
        <div class="event-card" data-event-id="${event.event_id}">
            <div class="event-card-header">
                <h3 class="event-card-title">${escapeHtml(event.title)}</h3>
                <div class="event-date-badge">
                    <span class="day">${day}</span>
                    <span class="month">${month}</span>
                </div>
                <p class="event-card-group">${groupInfo}</p>
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
                    <div class="event-rsvp-buttons">
                        <button class="btn-rsvp ${interestedClass}" data-event-id="${event.event_id}" data-status="interested">
                            <i class="uil uil-star"></i> Interested
                        </button>
                        <button class="btn-rsvp ${goingClass}" data-event-id="${event.event_id}" data-status="going">
                            <i class="uil uil-check"></i> Going
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Handle RSVP button click
 */
async function handleRSVP(e) {
    const btn = e.currentTarget;
    const eventId = btn.dataset.eventId;
    const status = btn.dataset.status;
    
    if (!eventId) return;
    
    // Disable all RSVP buttons for this event
    const eventCard = btn.closest('.event-card');
    const allBtns = eventCard.querySelectorAll('.btn-rsvp');
    allBtns.forEach(b => b.disabled = true);
    
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Events`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'rsvpEvent',
                event_id: eventId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update button states
            allBtns.forEach(b => {
                b.classList.remove('interested', 'going');
                b.disabled = false;
            });
            btn.classList.add(status);
            
            showNotification(`RSVP updated: ${status}`, 'success');
            
            // Reload to update counts
            setTimeout(() => loadEvents(currentFilter), 500);
        } else {
            allBtns.forEach(b => b.disabled = false);
            showNotification(data.message || 'Failed to update RSVP', 'error');
        }
    } catch (error) {
        console.error('Error updating RSVP:', error);
        allBtns.forEach(b => b.disabled = false);
        showNotification('An error occurred', 'error');
    }
}

/**
 * Show create event modal
 */
function showCreateEventModal() {
    const modal = document.getElementById('createEventModal');
    if (modal) {
        modal.style.display = 'flex';
        // Set min date to today
        const dateInput = document.getElementById('eventDate');
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
    
    const formData = {
        title: document.getElementById('eventTitle').value.trim(),
        description: document.getElementById('eventDescription').value.trim(),
        event_date: document.getElementById('eventDate').value,
        event_time: document.getElementById('eventTime').value,
        location: document.getElementById('eventLocation').value.trim()
    };
    
    if (!formData.title || !formData.event_date) {
        showNotification('Please fill in required fields', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Events`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'createEvent',
                ...formData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Event created successfully!', 'success');
            hideCreateEventModal();
            loadEvents(currentFilter);
        } else {
            showNotification(data.message || 'Failed to create event', 'error');
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
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    alert(message);
}
