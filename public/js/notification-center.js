// Professional Notification Center - Dropdown Handler
document.addEventListener('DOMContentLoaded', function() {
    const notificationCenter = {
        modal: document.getElementById('notificationCenterModal'),
        overlay: document.getElementById('notificationCenterOverlay'),
        bellBtn: document.getElementById('notificationBellBtn'),
        closeBtn: document.getElementById('closeNotificationCenter'),
        contentArea: document.getElementById('notificationCenterContent'),
        markAllReadBtn: document.getElementById('markAllReadBtn'),
        clearReadBtn: document.getElementById('clearReadBtn'),
        tabs: document.querySelectorAll('.notification-tab'),
        currentTab: 'all',
        allNotifications: [],

        init() {
            this.bindEvents();
            this.loadAllNotifications();
        },

        bindEvents() {
            // Toggle notification dropdown
            this.bellBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });

            // Close button
            this.closeBtn?.addEventListener('click', () => this.closeDropdown());

            // Close when clicking overlay
            this.overlay?.addEventListener('click', () => this.closeDropdown());

            // Tab switching
            this.tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.tab;
                    this.switchTab(tabName);
                });
            });

            // Footer actions
            this.markAllReadBtn?.addEventListener('click', () => this.markAllAsRead());
            this.clearReadBtn?.addEventListener('click', () => this.clearReadNotifications());

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                const isClickInsideDropdown = e.target.closest('.notification-center-modal');
                const isClickOnBell = e.target.closest('#notificationBellBtn');
                
                if (!isClickInsideDropdown && !isClickOnBell && this.modal?.classList.contains('active')) {
                    this.closeDropdown();
                }
            });

            // Prevent dropdown from closing when clicking inside
            this.modal?.addEventListener('click', (e) => e.stopPropagation());
        },

        toggleDropdown() {
            if (this.modal?.classList.contains('active')) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },

        openDropdown() {
            this.modal?.classList.add('active');
            this.overlay?.classList.add('active');
            this.renderNotifications(this.currentTab);
        },

        closeDropdown() {
            this.modal?.classList.remove('active');
            this.overlay?.classList.remove('active');
        },

        async loadAllNotifications() {
            try {
                // Load notifications only from the original navbar quick list
                const items = document.querySelectorAll('#quickNotificationsList .notification-item-modern');
                this.allNotifications = Array.from(items).map(item => ({
                    id: item.dataset.notifId,
                    priority: item.dataset.priority,
                    rawType: item.dataset.notifType || 'system_alert',
                    type: item.querySelector('.notification-type-chip')?.textContent || 'update',
                    title: item.querySelector('.notification-title-modern')?.textContent || '',
                    message: item.querySelector('.notification-message-modern')?.textContent || '',
                    isRead: !item.classList.contains('is-unread'),
                    isFriendRequest: (item.dataset.notifType || '') === 'friend_request',
                    element: item
                }));
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        },

        switchTab(tabName) {
            this.currentTab = tabName;

            // Update active tab styling
            this.tabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.tab === tabName);
            });

            this.renderNotifications(tabName);
        },

        renderNotifications(tab = 'all') {
            let filtered = [];
            const sourceItems = Array.from(document.querySelectorAll('#quickNotificationsList .notification-item-modern'));

            if (tab === 'unread') {
                filtered = sourceItems.filter(item => item.classList.contains('is-unread'));
            } else if (tab === 'requests') {
                filtered = sourceItems.filter(item => {
                    const type = item.dataset.notifType || '';
                    return type === 'friend_request' || type === 'group_request';
                });
            } else {
                filtered = sourceItems;
            }

            if (filtered.length === 0) {
                this.contentArea.innerHTML = `
                    <div class="notification-empty-state">
                        <div class="notification-empty-state-icon">
                            <i class="uil uil-inbox"></i>
                        </div>
                        <div class="notification-empty-state-title">
                            ${tab === 'unread' ? 'All caught up!' : tab === 'requests' ? 'No requests' : 'No notifications'}
                        </div>
                        <div class="notification-empty-state-text">
                            ${tab === 'unread' ? 'All your notifications have been read' : 'No notifications at the moment'}
                        </div>
                    </div>
                `;
                return;
            }

            // Clone and display filtered notifications
            this.contentArea.innerHTML = '';
            filtered.forEach(notif => {
                const clone = notif.cloneNode(true);
                this.attachNotificationHandlers(clone);
                this.contentArea.appendChild(clone);
            });
        },

        attachNotificationHandlers(notifElement) {
            const dismissBtn = notifElement.querySelector('.notification-dismiss-modern');
            const notifId = notifElement.dataset.notifId;
            const actionUrl = notifElement.dataset.actionUrl || '';
            const quickActionButtons = notifElement.querySelectorAll('.notification-action-btn[data-action]');

            quickActionButtons.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const action = btn.dataset.action;
                    const friendshipId = Number(btn.dataset.friendshipId || notifElement.dataset.friendshipId || 0);
                    if (!friendshipId || (action !== 'accept' && action !== 'decline')) {
                        return;
                    }

                    btn.disabled = true;
                    const siblingBtn = Array.from(quickActionButtons).find(item => item !== btn);
                    if (siblingBtn) {
                        siblingBtn.disabled = true;
                    }

                    const success = await this.respondToFriendRequest(friendshipId, action, notifId);
                    if (success) {
                        const actionsWrap = notifElement.querySelector('.notification-actions');
                        if (actionsWrap) {
                            actionsWrap.innerHTML = `<span class="notification-type-chip friend">${action === 'accept' ? 'Accepted' : 'Declined'}</span>`;
                        }
                    } else {
                        btn.disabled = false;
                        if (siblingBtn) {
                            siblingBtn.disabled = false;
                        }
                    }
                });
            });

            // Click to navigate and mark as read
            notifElement.addEventListener('click', async (e) => {
                if (e.target.closest('.notification-dismiss-modern') || e.target.closest('.notification-action-btn')) return;
                
                if (notifId) {
                    await this.markAsRead(notifId);
                }
                if (actionUrl) {
                    this.closeDropdown();
                    window.location.href = actionUrl;
                }
            });

            // Dismiss button
            dismissBtn?.addEventListener('click', async (e) => {
                e.stopPropagation();
                await this.dismissNotification(notifId);
                notifElement.remove();
                this.updateBadges();
            });
        },

        async respondToFriendRequest(friendshipId, action, notifId) {
            const endpointAction = action === 'accept' ? 'acceptRequest' : 'declineRequest';
            try {
                const response = await fetch(BASE_PATH + `index.php?controller=Friend&action=${endpointAction}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `friendship_id=${encodeURIComponent(friendshipId)}`
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    alert(data.message || 'Unable to update request right now.');
                    return false;
                }

                if (notifId) {
                    await this.markAsRead(notifId);
                }
                return true;
            } catch (error) {
                console.error('Error handling friend request action:', error);
                alert('Unable to update request right now.');
                return false;
            }
        },

        async markAsRead(notifId) {
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=mark_notification_read&notification_id=${encodeURIComponent(notifId)}`
                });
                const data = await response.json();
                if (data.success) {
                    document.querySelectorAll(`[data-notif-id="${notifId}"]`).forEach(item => {
                        item.classList.remove('is-unread');
                    });
                    this.updateBadges();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async dismissNotification(notifId) {
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=delete_notification&notification_id=${encodeURIComponent(notifId)}`
                });
                const data = await response.json();
                if (data.success) {
                    document.querySelectorAll(`[data-notif-id="${notifId}"]`).forEach(el => el.remove());
                    this.updateBadges();
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
            }
        },

        async markAllAsRead() {
            const unreadItems = document.querySelectorAll('#quickNotificationsList .notification-item-modern.is-unread');
            for (const item of unreadItems) {
                const notifId = item.dataset.notifId;
                if (notifId) {
                    await this.markAsRead(notifId);
                }
            }
            this.renderNotifications(this.currentTab);
        },

        async clearReadNotifications() {
            if (!confirm('Clear all read notifications? This cannot be undone.')) return;

            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'sub_action=clear_notifications'
                });
                const data = await response.json();
                if (data.success) {
                    document.querySelectorAll('#quickNotificationsList .notification-item-modern:not(.is-unread)').forEach(el => el.remove());
                    this.renderNotifications(this.currentTab);
                    this.updateBadges();
                }
            } catch (error) {
                console.error('Error clearing notifications:', error);
            }
        },

        updateBadges() {
            const sourceItems = Array.from(document.querySelectorAll('#quickNotificationsList .notification-item-modern'));
            const allCount = sourceItems.length;
            const unreadCount = sourceItems.filter(item => item.classList.contains('is-unread')).length;
            const requestCount = sourceItems.filter(item => {
                const type = item.dataset.notifType || '';
                return type === 'friend_request' || type === 'group_request';
            }).length;

            // Update badge on bell
            const badge = document.querySelector('.notification-count');
            if (badge) {
                badge.textContent = unreadCount > 9 ? '9+' : (unreadCount > 0 ? unreadCount : '');
                badge.style.display = unreadCount > 0 ? 'flex' : 'none';
            }

            // Update tab badges
            const allBadge = document.getElementById('badgeAll');
            const unreadBadge = document.getElementById('badgeUnread');
            const requestBadge = document.getElementById('badgeRequests');

            if (allBadge) {
                allBadge.textContent = allCount;
                allBadge.style.display = allCount > 0 ? 'inline-flex' : 'none';
            }

            if (unreadBadge) {
                unreadBadge.textContent = unreadCount;
                unreadBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
            }

            if (requestBadge) {
                requestBadge.textContent = requestCount;
                requestBadge.style.display = requestCount > 0 ? 'inline-flex' : 'none';
            }
        }
    };

    // Initialize notification center
    notificationCenter.init();

    // Expose notification center to global scope for external access
    window.notificationCenter = notificationCenter;
});
