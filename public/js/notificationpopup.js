// notificationpopup.js - Handles notifications functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-update notification count every minute
    const notificationCount = document.querySelector('.notification-count');
    if (notificationCount) {
        setInterval(() => {
            const currentCount = parseInt(notificationCount.textContent);
            if (currentCount < 15) {
                const newCount = currentCount + Math.floor(Math.random() * 3);
                notificationCount.textContent = newCount > 9 ? '9+' : newCount;
            }
        }, 60000);
    }
    
    // Notification popup interactions
    const notificationIcon = document.querySelector('.notification');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function(e) {
            if (!isUserLoggedIn()) {
                e.preventDefault();
                showLoginModal();
            }
        });
    }
    
    // Handle clicking individual notifications: mark read and follow action_url if present
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const actionUrl = item.dataset.actionUrl || '';
            const notifId = item.dataset.notifId || '';
            if (notifId) {
                try {
                    await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `sub_action=mark_notification_read&notification_id=${encodeURIComponent(notifId)}`
                    });
                    // Remove the notification element from the popup UI
                    const wrap = document.querySelector(`.notification-item-wrap[data-notif-id="${notifId}"]`);
                    if (wrap) wrap.remove();
                    // decrement badge
                    const badge = document.querySelector('.notification-count');
                    if (badge) {
                        let val = badge.textContent.trim();
                        if (val === '9+' || val === '') {
                            // reload count from server would be ideal; for now try simple decrement
                            // if 9+ just clear numeric and leave blank
                            badge.textContent = '';
                        } else {
                            let num = parseInt(val) || 0;
                            num = Math.max(0, num - 1);
                            badge.textContent = num > 9 ? '9+' : (num > 0 ? num : '');
                        }
                    }
                } catch (err) {
                    console.error('Failed to mark notification read', err);
                }
            }
            if (actionUrl) {
                // navigate to actionUrl
                window.location.href = actionUrl;
            }
        });
    });

    // Dismiss single notification button
    document.querySelectorAll('.notif-dismiss').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            e.preventDefault();
            const notifId = btn.dataset.notifId;
            if (!notifId) return;
            try {
                const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=delete_notification&notification_id=${encodeURIComponent(notifId)}`
                });
                const data = await resp.json();
                if (data.success) {
                    const wrap = btn.closest('.notification-item-wrap'); if (wrap) wrap.remove();
                    // decrement badge
                    const badge = document.querySelector('.notification-count');
                    if (badge) {
                        let val = badge.textContent.trim();
                        if (val === '9+' || val === '') {
                            badge.textContent = '';
                        } else {
                            let num = parseInt(val) || 0;
                            num = Math.max(0, num - 1);
                            badge.textContent = num > 9 ? '9+' : (num > 0 ? num : '');
                        }
                    }
                } else {
                    console.warn('Failed to delete notification', data);
                }
            } catch (err) {
                console.error('Error deleting notification', err);
            }
        });
    });
});

