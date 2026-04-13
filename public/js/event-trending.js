function trendingApiUrl(query) {
    if (typeof getApiUrl === 'function') return getApiUrl(query);

    const basePath = (typeof BASE_PATH !== 'undefined' ? BASE_PATH : '/');
    const normalized = (basePath === '/' ? '' : basePath).replace(/\/$/, '');
    return `${normalized}/index.php${query}`;
}

function escapeText(value) {
    return String(value ?? '').replace(/[&<>"']/g, (match) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[match]));
}

function notifyTrending(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }
    console.log(message);
}

function renderTrendingButton(event) {
    const isGoing = Number(event.is_going || 0) === 1;
    const buttonClass = `btn btn-primary btn-add-trending btn-icon-only${isGoing ? ' added' : ''}`;
    const buttonLabel = isGoing
        ? '<i class="uis uis-bookmark"></i>'
        : '<i class="uil uil-calendar-alt"></i>';

    return `
        <button
            class="${buttonClass}"
            title="${isGoing ? 'Added' : 'Add to Calendar'}"
            data-id="${Number(event.post_id || event.id || 0)}"
            data-group-id="${event.group_id ? Number(event.group_id) : ''}"
            data-title="${escapeText(event.event_title || event.title || 'Untitled Event')}"
            data-date="${escapeText(event.event_date || '')}"
            data-time="${escapeText(event.event_time || '')}"
            data-location="${escapeText(event.event_location || event.location || '')}"
            data-description="${escapeText(event.content || event.description || '')}"
        >
            ${buttonLabel}
        </button>
    `;
}

async function openEventInRecentTab(eventId) {
    const normalizedEventId = Number(eventId || 0);
    if (!normalizedEventId || typeof loadEvents !== 'function') return;

    document.querySelectorAll('.filter-tab').forEach(tab => {
        const isRecent = tab.dataset.filter === 'recent';
        tab.classList.toggle('active', isRecent);
    });

    await loadEvents('recent');

    const card = document.querySelector(`#eventsContainer .event-card[data-event-id="${normalizedEventId}"]`);
    if (!card) {
        notifyTrending('Event not found in recent list', 'info');
        return;
    }

    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.add('event-card-focus');
    setTimeout(() => card.classList.remove('event-card-focus'), 1600);
}

async function loadTrendingEvents() {
    const listContainer = document.getElementById('trendingEventsList');
    if (!listContainer) return;

    try {
        const response = await fetch(trendingApiUrl('?controller=Events&ajax_action=getMostGoingEvents'));
        const data = await response.json();

        if (!data.success || !Array.isArray(data.events) || data.events.length === 0) {
            listContainer.innerHTML = '<div class="friend-requests-empty"><p>No events yet</p></div>';
            return;
        }

        listContainer.innerHTML = data.events.map((event) => {
            const title = escapeText(event.event_title || event.title || 'Untitled Event');
            const date = escapeText(event.event_date || 'Date TBA');
            const goingCount = Number(event.going_count || 0);
            const details = [date, `${goingCount} going`].filter(Boolean).join(' • ');

            return `
                <div class="request trending-event-item" data-event-id="${Number(event.post_id || event.id || 0)}">
                    <div class="info">
                        <div class="trending-event-text" title="${title}">
                            <h5>${title}</h5>
                            <p>${details}</p>
                        </div>
                    </div>
                    <div class="action">${renderTrendingButton(event)}</div>
                </div>
            `;
        }).join('');

        listContainer.querySelectorAll('.btn-add-trending').forEach((button) => {
            button.addEventListener('click', async (event) => {
                event.stopPropagation();
                const postId = Number(button.dataset.id || 0);
                if (!postId) {
                    notifyTrending('Invalid event', 'error');
                    return;
                }

                const originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="uil uil-spinner-alt uil-spin"></i>';

                try {
                    const response = await fetch(trendingApiUrl('?controller=Calendar&action=handleAjax'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sub_action: 'toggle_event',
                            post_id: postId,
                            group_id: button.dataset.groupId ? Number(button.dataset.groupId) : null,
                            event_title: button.dataset.title || 'Untitled Event',
                            event_date: button.dataset.date || '',
                            event_time: button.dataset.time || '',
                            event_location: button.dataset.location || '',
                            event_description: button.dataset.description || ''
                        })
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'Failed to update');
                    }

                    const nowAdded = !!result.interested;
                    button.classList.toggle('added', nowAdded);
                    button.innerHTML = nowAdded
                        ? '<i class="uis uis-bookmark"></i>'
                        : '<i class="uil uil-calendar-alt"></i>';

                    notifyTrending(
                        result.message || (nowAdded ? 'Event added to calendar' : 'Event removed from calendar'),
                        nowAdded ? 'success' : 'info'
                    );

                    document.dispatchEvent(new CustomEvent('calendar:refresh'));
                    loadTrendingEvents();
                } catch (error) {
                    console.error('Trending toggle failed:', error);
                    button.innerHTML = originalHtml;
                    notifyTrending('Failed to update calendar', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        });

        listContainer.querySelectorAll('.trending-event-item').forEach((item) => {
            item.addEventListener('click', async (event) => {
                if (event.target.closest('.btn-add-trending')) return;
                const eventId = Number(item.dataset.eventId || 0);
                await openEventInRecentTab(eventId);
            });
        });
    } catch (error) {
        console.error('Failed to load trending events:', error);
        listContainer.innerHTML = '<div class="friend-requests-empty"><p>Failed to load events</p></div>';
    }
}

document.addEventListener('DOMContentLoaded', loadTrendingEvents);
document.addEventListener('calendar:refresh', loadTrendingEvents);