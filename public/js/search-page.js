window.toggleSearchPostMenu = function(event, trigger) {
    if (!trigger) {
        return;
    }
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const postMenu = trigger.closest('.post-menu');
    if (!postMenu) {
        return;
    }

    document.querySelectorAll('.post-menu.open').forEach((menu) => {
        if (menu !== postMenu) {
            menu.classList.remove('open');
        }
    });

    postMenu.classList.toggle('open');
};

window.toggleSearchCalendar = function(event, button) {
    if (!button || button.disabled) {
        return;
    }
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const basePathRaw = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '/';
    const basePath = basePathRaw.endsWith('/') ? basePathRaw : `${basePathRaw}/`;

    const eventId = parseInt(button.dataset.eventId || '', 10);
    if (!eventId) {
        return;
    }

    const formData = new FormData();
    formData.append('sub_action', 'toggle_event');
    formData.append('post_id', String(eventId));
    formData.append('group_id', String(parseInt(button.dataset.groupId || '0', 10) || 0));
    formData.append('event_title', button.dataset.eventTitle || 'Event');
    formData.append('event_date', button.dataset.eventDate || '');
    formData.append('event_time', button.dataset.eventTime || '');
    formData.append('event_location', button.dataset.eventLocation || '');
    formData.append('event_description', button.dataset.eventDescription || '');

    const previousHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="uil uil-spinner-alt"></i><span>Saving...</span>';

    fetch(`${basePath}index.php?controller=Calendar&action=handleAjax`, {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Failed to update calendar');
            }

            if (data.interested) {
                button.classList.add('added');
                button.innerHTML = '<i class="uis uis-bookmark"></i><span>Added</span>';
            } else {
                button.classList.remove('added');
                button.innerHTML = '<i class="uil uil-calendar-alt"></i><span>Add Calendar</span>';
            }

            if (typeof showToast === 'function') {
                showToast(data.message || 'Calendar updated', 'success');
            }
        })
        .catch((error) => {
            button.innerHTML = previousHtml;
            if (typeof showToast === 'function') {
                showToast(error.message || 'Failed to update calendar', 'error');
            }
        })
        .finally(() => {
            button.disabled = false;
        });
};

const initSearchPage = () => {
    const page = document.querySelector('.page-search');
    if (!page) {
        return;
    }

    const basePathRaw = typeof BASE_PATH !== 'undefined'
        ? BASE_PATH
        : '/';
    const basePath = basePathRaw.endsWith('/') ? basePathRaw : `${basePathRaw}/`;

    document.addEventListener('click', (event) => {
        const menuTrigger = event.target.closest('.menu-trigger');
        if (menuTrigger) {
            window.toggleSearchPostMenu(event, menuTrigger);
            return;
        }

        if (!event.target.closest('.post-menu')) {
            document.querySelectorAll('.post-menu.open').forEach((menu) => menu.classList.remove('open'));
        }

        const joinButton = event.target.closest('[data-group-join]');
        if (!joinButton) {
            const addCalendarButton = event.target.closest('.btn-add-calendar[data-event-id]');
            if (!addCalendarButton) {
                return;
            }

            if (!page.contains(addCalendarButton) || addCalendarButton.disabled) {
                return;
            }

            event.preventDefault();
            window.toggleSearchCalendar(event, addCalendarButton);

            return;
        }

        if (!page.contains(joinButton)) {
            return;
        }

        event.preventDefault();

        if (joinButton.disabled) {
            return;
        }

        const groupId = parseInt(joinButton.dataset.groupId || '', 10);
        if (!groupId) {
            return;
        }

        const originalLabel = joinButton.textContent;
        const privacy = (joinButton.dataset.groupPrivacy || 'public').toLowerCase();
        joinButton.disabled = true;
        joinButton.textContent = privacy === 'public' ? 'Joining...' : 'Requesting...';

        fetch(`${basePath}index.php?controller=Group&action=handleAjax`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `sub_action=join&group_id=${encodeURIComponent(groupId)}`,
        })
            .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                const success = ok && data && data.success;
                const message = data && data.message ? data.message : '';
                const isPending = !!(data && (data.pending || data.membership_state === 'pending'));

                if (success && isPending) {
                    joinButton.textContent = 'Requested';
                    joinButton.classList.remove('btn-primary');
                    joinButton.classList.add('btn-secondary');
                    joinButton.disabled = true;

                    if (typeof showToast === 'function') {
                        showToast(message || 'Join request sent', 'success');
                    }
                    return;
                }

                if (success || /already joined/i.test(message)) {
                    joinButton.textContent = success ? 'Joined' : 'Already joined';
                    joinButton.classList.remove('btn-primary');
                    joinButton.classList.add('btn-secondary');
                    joinButton.disabled = true;

                    if (typeof showToast === 'function') {
                        showToast(message || 'Joined group successfully', 'success');
                    }
                    return;
                }

                if (isPending || /pending request/i.test(message)) {
                    joinButton.textContent = 'Requested';
                    joinButton.classList.remove('btn-primary');
                    joinButton.classList.add('btn-secondary');
                    joinButton.disabled = true;

                    if (typeof showToast === 'function') {
                        showToast(message || 'Join request already pending', 'info');
                    }
                    return;
                }

                throw new Error(message || 'Unable to join group.');
            })
            .catch((error) => {
                joinButton.disabled = false;
                joinButton.textContent = originalLabel;
                if (typeof showToast === 'function') {
                    showToast(error.message || 'Unable to join group.', 'error');
                }
            });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSearchPage);
} else {
    initSearchPage();
}
