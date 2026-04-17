// Events page functionality
let currentFilter = 'recent';
let eventCardGlobalHandlersBound = false;
let selectedEventCreateFile = null;
let pendingEventFocusId = null;

// Helper to construct API URL safely
const getApiUrl = (queryString) => {
    const basePath = (BASE_PATH === '/' ? '' : BASE_PATH).replace(/\/$/, '');
    return `${basePath}/index.php${queryString}`;
};

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const requestedFilter = params.get('filter');
    const requestedEventId = Number(params.get('target_event_id') || params.get('event_id') || 0);

    if (requestedFilter) {
        currentFilter = requestedFilter;
    }
    if (requestedEventId > 0) {
        pendingEventFocusId = requestedEventId;
    }

    setActiveFilterTab(currentFilter);
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
            currentFilter = this.dataset.filter;
            setActiveFilterTab(currentFilter);
            loadEvents(currentFilter);
        });
    });
    
    // Create event button
    const createBtn = document.getElementById('createEventBtn');
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showEventsCreateModal();
        });
    }

    const closeBtn = document.getElementById('eventsCreateClose');
    const cancelBtn = document.getElementById('eventsCreateCancel');
    const modal = document.getElementById('eventsCreateModal');
    const form = document.getElementById('eventsCreateForm');
    const imageUpload = document.getElementById('epEventImageUpload');
    const imageInput = document.getElementById('epEventImage');

    closeBtn?.addEventListener('click', hideEventsCreateModal);
    cancelBtn?.addEventListener('click', hideEventsCreateModal);
    form?.addEventListener('submit', submitEventsCreateForm);

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) hideEventsCreateModal();
    });

    imageUpload?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', handleEventsImageSelect);
}

function showEventsCreateModal() {
    const modal = document.getElementById('eventsCreateModal');
    const dateInput = document.getElementById('epEventDate');
    if (!modal) return;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }
}

function hideEventsCreateModal() {
    const modal = document.getElementById('eventsCreateModal');
    const form = document.getElementById('eventsCreateForm');
    const imageUpload = document.getElementById('epEventImageUpload');
    const imageLabel = document.getElementById('epEventImageLabel');
    const preview = document.getElementById('epEventImagePreview');
    const imageInput = document.getElementById('epEventImage');

    if (!modal) return;

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    form?.reset();
    selectedEventCreateFile = null;
    if (imageInput) imageInput.value = '';
    if (imageLabel) imageLabel.textContent = 'Click to add event image';
    imageUpload?.classList.remove('has-file');
    if (preview) {
        preview.style.display = 'none';
        preview.removeAttribute('src');
    }
}

function handleEventsImageSelect(event) {
    const file = event.target.files?.[0] || null;
    const imageUpload = document.getElementById('epEventImageUpload');
    const imageLabel = document.getElementById('epEventImageLabel');
    const preview = document.getElementById('epEventImagePreview');

    selectedEventCreateFile = file;

    if (!file) {
        imageUpload?.classList.remove('has-file');
        if (imageLabel) imageLabel.textContent = 'Click to add event image';
        if (preview) {
            preview.style.display = 'none';
            preview.removeAttribute('src');
        }
        return;
    }

    if (imageLabel) imageLabel.textContent = file.name;
    imageUpload?.classList.add('has-file');

    if (preview) {
        const reader = new FileReader();
        reader.onload = (loadEvent) => {
            preview.src = loadEvent.target?.result || '';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

async function submitEventsCreateForm(event) {
    event.preventDefault();

    const title = document.getElementById('epEventTitle')?.value.trim() || '';
    const description = document.getElementById('epEventDescription')?.value.trim() || '';
    const date = document.getElementById('epEventDate')?.value || '';
    const time = document.getElementById('epEventTime')?.value || '';
    const location = document.getElementById('epEventLocation')?.value.trim() || '';

    if (!title || !date) {
        showNotification('Event title and date are required.', 'error');
        return;
    }

    const submitBtn = event.currentTarget.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('sub_action', 'create');
        formData.append('postType', 'event');
        formData.append('caption', description);
        formData.append('tags', 'event,upcoming,community,social,announcement');
        formData.append('eventTitle', title);
        formData.append('eventDate', date);
        formData.append('eventLocation', location);
        formData.append('eventTime', time);
        formData.append('is_group_post', '0');
        if (selectedEventCreateFile) {
            formData.append('image', selectedEventCreateFile);
        }

        const response = await fetch(getApiUrl('?controller=Posts&action=handleAjax'), {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            showNotification(data.message || 'Failed to create event', 'error');
            return;
        }

        showNotification('Event created successfully!', 'success');
        hideEventsCreateModal();
        loadEvents(currentFilter);
    } catch (error) {
        console.error('Error creating event:', error);
        showNotification('An error occurred', 'error');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
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
            window.eventsMap = {};
            data.events.forEach(e => window.eventsMap[e.post_id || e.event_id] = e);

            container.innerHTML = data.events.map(event => createEventCard(event)).join('');
            initializeEventCardActions();

            if (pendingEventFocusId) {
                const focusId = pendingEventFocusId;
                pendingEventFocusId = null;
                setTimeout(() => focusEventCard(focusId), 50);
            }
        } else {
            const titleMap = {
                recent: 'Recent',
                my_events: 'My',
                added_to_calendar: 'Added to Calendar'
            };

            const bodyMap = {
                recent: 'No recent events at the moment',
                my_events: 'You have not created any events yet',
                added_to_calendar: 'You have not added any events to calendar yet'
            };

            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="uil uil-calendar-alt"></i>
                    <h3>No ${titleMap[filter] || 'Events'} Events</h3>
                    <p>${bodyMap[filter] || 'No events found'}</p>
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
    
    const eventTimeRaw =
        event.event_time ||
        event.time ||
        event.eventTime ||
        extractTimeFromDate(event.event_date);
    const time = formatTime(eventTimeRaw);
    const location = (event.event_location || event.location || 'TBA').trim();
    const description = String(event.content || event.description || 'No description available').trim();
    const descriptionHtml = escapeHtml(description).replace(/\n/g, '<br>');
    const isLongDescription = description.length > 220;
    
    const hasGroupContext = !!(event.group_name && event.group_id);
    
    // Use event_title if available, fallback to title (PostModel returns event_title)
    const title = event.event_title || event.title || 'Untitled Event';
    const eventImage = resolveEventImageUrl(event.image_url || '');
    const eventId = event.post_id || event.event_id;
    const currentUserId = (typeof USER_ID !== 'undefined') ? Number(USER_ID) : Number(window.USER_ID || 0);
    const postOwnerId = Number(event.author_id || event.user_id || 0);
    const canDelete = postOwnerId === currentUserId;
    
    // User info
    const userName = [event.first_name, event.last_name].filter(Boolean).join(' ').trim() || 'Unknown User';
    const authorDisplay = hasGroupContext
        ? `<span class="event-author-creator">${escapeHtml(userName)}</span> <span class="event-author-separator">&gt;</span> <a href="${BASE_PATH || '/'}index.php?controller=Group&action=index&group_id=${Number(event.group_id)}" class="event-author-group-link">${escapeHtml(event.group_name)}</a>`
        : `<span class="event-author-creator">${escapeHtml(userName)}</span>`;
    const userProfilePath = event.profile_picture || event.avatar || 'uploads/user_dp/default_user_dp.jpg';
    const fullProfileUrl = userProfilePath.startsWith('http') ? userProfilePath : (userProfilePath.startsWith('/') ? userProfilePath : ('uploads/user_dp/default_user_dp.jpg'));
    const userId = event.author_id || event.user_id || 0;
    
    // Format creation date/time
    const createdDate = new Date(event.created_at || event.event_date);
    const createdTimeStr = createdDate.toLocaleDateString() + ' ' + createdDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    const isAdded = event.is_going == 1;
    const btnClass = isAdded ? 'btn-add-calendar added' : 'btn-add-calendar';
    const btnContent = isAdded
        ? '<i class="uis uis-bookmark"></i><span>Added</span>'
        : '<i class="uil uil-calendar-alt"></i><span>Add Calendar</span>';
    
    return `
        <div id="event-card-${eventId}" class="event-card" data-event-id="${eventId}">
            <div class="event-card-header">
                ${canDelete ? `
                <div class="event-card-menu" data-event-id="${eventId}">
                    <button type="button" class="event-card-menu-trigger" aria-label="Post menu">
                        <i class="uil uil-ellipsis-h"></i>
                    </button>
                    <div class="event-card-menu-dropdown">
                        <button type="button" class="event-card-menu-item delete" data-delete-event="${eventId}">
                            <i class="uil uil-trash-alt"></i>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
                ` : ''}
                
                <div class="event-header-content">
                    <div class="event-card-author">
                        <img src="${escapeHtml(fullProfileUrl)}" alt="${escapeHtml(userName)}" class="event-author-avatar" data-user-id="${userId}">
                        <div class="event-author-info">
                            <h4 class="event-author-name" data-user-id="${userId}">${authorDisplay}</h4>
                            <p class="event-author-time">${createdTimeStr}</p>
                        </div>
                    </div>
                    <div class="event-date-badge">
                        <span class="day">${day}</span>
                        <span class="month">${month}</span>
                    </div>
                </div>
            </div>

            <div class="event-card-body">
                <div class="event-card-content">
                    <div class="event-card-main">
                        <h3 class="event-card-title">${escapeHtml(title)}</h3>

                        <div class="event-description ${isLongDescription ? 'is-collapsed' : ''}" id="event-desc-${eventId}">
                            ${descriptionHtml}
                        </div>
                        ${isLongDescription ? `
                            <button type="button" class="event-description-toggle" data-target="event-desc-${eventId}" aria-expanded="false">See more</button>
                        ` : ''}

                        <div class="event-meta-compact">
                            <div class="event-detail">
                                <i class="uil uil-clock"></i>
                                <span><strong>Time:</strong><span class="event-detail-value">${time}</span></span>
                            </div>
                            <div class="event-detail">
                                <i class="uil uil-location-point"></i>
                                <span><strong>Location:</strong><span class="event-detail-value">${escapeHtml(location)}</span></span>
                            </div>
                        </div>
                    </div>

                    ${eventImage ? `
                        <div class="event-card-image">
                            <img src="${escapeHtml(eventImage)}" alt="Event image" onerror="this.closest('.event-card-image').style.display='none';">
                        </div>
                    ` : ''}
                </div>
                <div class="event-card-footer">
                    <div class="event-stats">
                        <span><i class="uil uil-users-alt"></i> ${event.going_count || 0} going</span>
                    </div>
                    <button class="${btnClass}" title="${isAdded ? 'Added to Calendar' : 'Add to Calendar'}" onclick="addToCalendar(this, ${eventId})">
                        ${btnContent}
                    </button>
                </div>
            </div>
        </div>
    `;
}

function initializeEventCardActions() {
    // User profile click handlers
    document.querySelectorAll('.event-author-avatar').forEach(avatar => {
        avatar.style.cursor = 'pointer';
        avatar.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const userId = avatar.getAttribute('data-user-id');
            if (userId && userId !== '0') {
                window.location.href = `${BASE_PATH || '/'}index.php?controller=Profile&action=view&user_id=${userId}`;
            }
        });
    });

    document.querySelectorAll('.event-author-name').forEach(name => {
        name.style.cursor = 'pointer';
        name.addEventListener('click', (e) => {
            if (e.target.closest('.event-author-group-link')) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const userId = name.getAttribute('data-user-id');
            if (userId && userId !== '0') {
                window.location.href = `${BASE_PATH || '/'}index.php?controller=Profile&action=view&user_id=${userId}`;
            }
        });
    });

    document.querySelectorAll('.event-card-menu-trigger').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const menu = trigger.closest('.event-card-menu');
            if (!menu) return;

            document.querySelectorAll('.event-card-menu.open').forEach(openMenu => {
                if (openMenu !== menu) openMenu.classList.remove('open');
            });

            menu.classList.toggle('open');
        });

        document.querySelectorAll('.trending-event-item').forEach(item => {
            item.style.cursor = 'pointer';
        });
    });

    document.querySelectorAll('[data-delete-event]').forEach(deleteBtn => {
        deleteBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const eventId = parseInt(deleteBtn.getAttribute('data-delete-event') || '0', 10);
            if (!eventId) return;

            const confirmed = window.confirm('Delete this event post?');
            if (!confirmed) return;

            await deleteEventPost(eventId);
        });
    });

    document.querySelectorAll('.event-description-toggle').forEach(toggleBtn => {
        if (toggleBtn.dataset.bound === '1') return;
        toggleBtn.dataset.bound = '1';

        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const targetId = toggleBtn.getAttribute('data-target');
            if (!targetId) return;

            const descriptionEl = document.getElementById(targetId);
            if (!descriptionEl) return;

            const isCollapsed = descriptionEl.classList.contains('is-collapsed');
            descriptionEl.classList.toggle('is-collapsed', !isCollapsed);
            toggleBtn.textContent = isCollapsed ? 'See less' : 'See more';
            toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'true' : 'false');
        });
    });

    if (!eventCardGlobalHandlersBound) {
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.event-card-menu')) {
                document.querySelectorAll('.event-card-menu.open').forEach(menu => menu.classList.remove('open'));
            }
        });
        eventCardGlobalHandlersBound = true;
    }
}

function focusEventCard(eventId) {
    const normalizedEventId = Number(eventId || 0);
    if (!normalizedEventId) return;

    const card = document.querySelector(`#eventsContainer .event-card[data-event-id="${normalizedEventId}"]`);
    if (!card) return;

    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.add('event-card-focus');
    setTimeout(() => card.classList.remove('event-card-focus'), 1600);
}

function setActiveFilterTab(filter) {
    const normalizedFilter = filter || 'recent';
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.filter === normalizedFilter);
    });
}

async function deleteEventPost(eventId) {
    try {
        const response = await fetch(getApiUrl('?controller=Posts&action=handleAjax'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sub_action: 'delete',
                post_id: eventId
            })
        });

        const data = await response.json();

        if (!data.success) {
            showNotification(data.message || 'Failed to delete event', 'error');
            return;
        }

        showNotification('Event deleted', 'success');
        loadEvents(currentFilter);
    } catch (error) {
        console.error('Error deleting event:', error);
        showNotification('Error deleting event', 'error');
    }
}

/**
 * Add event to calendar
 */
async function addToCalendar(btn, eventId) {
    const event = window.eventsMap && window.eventsMap[eventId];
    if (!event) return;

    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="uil uil-spinner-alt uil-spin"></i>';
    btn.disabled = true;

    try {
        const response = await fetch(getApiUrl('?controller=Calendar&action=handleAjax'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sub_action: 'toggle_event',
                post_id: eventId,
                group_id: event.group_id || null,
                event_title: event.event_title || event.title || 'Untitled Event',
                event_date: event.event_date || '',
                event_time: event.event_time || '',
                event_location: event.event_location || event.location || '',
                event_description: event.content || event.description || ''
            })
        });

        const data = await response.json();

        if (!data.success) {
            showNotification(data.message || 'Failed to update event calendar status', 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
            return;
        }

        const isAdded = !!data.interested;
        btn.classList.toggle('added', isAdded);
        btn.innerHTML = isAdded
            ? '<i class="uis uis-bookmark"></i><span>Added</span>'
            : '<i class="uil uil-calendar-alt"></i><span>Add Calendar</span>';
        btn.title = isAdded ? 'Added to Calendar' : 'Add to Calendar';
        btn.disabled = false;

        showNotification(
            data.message || (isAdded ? 'Event added to your calendar!' : 'Removed from your calendar'),
            isAdded ? 'success' : 'info'
        );

        document.dispatchEvent(new CustomEvent('calendar:refresh'));
    } catch (error) {
        console.error('Error toggling calendar event:', error);
        showNotification('Error updating calendar', 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// Make addToCalendar globally available
window.addToCalendar = addToCalendar;

function resolveEventImageUrl(rawValue) {
    const value = String(rawValue || '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value) || value.startsWith('/')) {
        return value;
    }

    const basePath = (typeof BASE_PATH === 'string' ? BASE_PATH : '/').replace(/\/$/, '');
    const normalized = value.replace(/^\/+/, '');
    return `${basePath}/${normalized}`;
}

/**
 * Helper: Format time
 */
function formatTime(timeString) {
    if (!timeString) return 'TBA';

    const text = String(timeString).trim();
    const ampmMatch = text.match(/(\d{1,2})\D(\d{2})\s*(AM|PM)/i);
    if (ampmMatch) {
        const hour = parseInt(ampmMatch[1], 10);
        const minute = ampmMatch[2];
        const suffix = ampmMatch[3].toUpperCase();
        if (Number.isNaN(hour)) return 'TBA';
        return `${hour}:${minute} ${suffix}`;
    }

    const match = text.match(/(\d{1,2})\D(\d{2})(?:\D\d{2})?/);
    if (!match) return 'TBA';

    const hour = parseInt(match[1], 10);
    const minute = match[2];
    if (Number.isNaN(hour)) return 'TBA';

    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minute} ${ampm}`;
}

function extractTimeFromDate(dateValue) {
    if (!dateValue) return '';
    const text = String(dateValue).trim();
    const match = text.match(/(\d{1,2}:\d{2})(?::\d{2})?/);
    return match ? match[1] : '';
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
