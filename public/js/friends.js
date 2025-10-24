// friends.js - Handles friend request interactions across the app
document.addEventListener('DOMContentLoaded', () => {
    setupFriendRequestList();
    setupAddFriendButtons();
});

const BASE_PATH_RAW = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '/';
const FRIENDS_BASE_PATH = BASE_PATH_RAW.endsWith('/') ? BASE_PATH_RAW : `${BASE_PATH_RAW}/`;

function notifyFriendAction(message, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else if (message) {
        const log = type === 'error' ? console.error : console.log;
        log(`[${type}] ${message}`);
    }
}

function updateFriendCountDisplays(newCount) {
    if (newCount === null || newCount === undefined) {
        return;
    }

    const numericCount = Number(newCount);
    if (Number.isNaN(numericCount)) {
        return;
    }

    const roundedCount = Math.max(0, Math.trunc(numericCount));

    const countElements = document.querySelectorAll('[data-friend-count]');
    countElements.forEach((element) => {
        element.textContent = `${roundedCount}`;
        element.dataset.friendCount = `${roundedCount}`;
    });

    const labelElements = document.querySelectorAll('[data-friend-count-label]');
    const labelText = `${roundedCount} total ${roundedCount === 1 ? 'friend' : 'friends'}`;
    labelElements.forEach((element) => {
        element.textContent = labelText;
        element.dataset.friendCountLabel = `${roundedCount}`;
    });

    const listCountElements = document.querySelectorAll('[data-friend-list-count]');
    listCountElements.forEach((element) => {
        element.textContent = `(${roundedCount})`;
    });
}

// No separate remove buttons; Friends button can be clicked to remove

function setupFriendRequestList() {
    const friendRequestsContainer = document.querySelector('.friend-requests');
    if (!friendRequestsContainer) {
        return;
    }

    const badge = friendRequestsContainer.querySelector('.badge');
    const emptyState = friendRequestsContainer.querySelector('.friend-requests-empty');

    const updateRequestCount = () => {
        if (!badge) {
            return;
        }

        const remainingRequests = friendRequestsContainer.querySelectorAll('.request').length;
        badge.textContent = `(${remainingRequests})`;
        if (remainingRequests === 0) {
            badge.style.display = 'none';
            if (emptyState) {
                emptyState.style.display = '';
            }
        }
        else {
            badge.style.display = '';
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    };

    const performAction = async (actionName, friendshipId) => {
        const endpoint = `${FRIENDS_BASE_PATH}index.php?controller=Friend&action=${actionName}`;

        let response;
        try {
            response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `friendship_id=${encodeURIComponent(friendshipId)}`,
            });
        } catch (error) {
            console.error('Network error:', error);
            throw new Error('Network error. Please check your connection and try again.');
        }

        // Get response text first for debugging
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (error) {
            console.error('JSON parse error. Response text:', responseText);
            throw new Error('Server returned an invalid response. Please try again.');
        }

        if (!response.ok) {
            const message = data && data.message ? data.message : `Server error (${response.status}). Please try again.`;
            console.error('Server error:', response.status, data);
            throw new Error(message);
        }

        if (!data || !data.success) {
            const message = data && data.message ? data.message : 'Unable to process the request.';
            console.error('Request failed:', data);
            throw new Error(message);
        }

        return data;
    };

    const handleAccept = async (button) => {
        const request = button.closest('.request');
        if (!request) {
            return;
        }

        const friendshipId = parseInt(request.dataset.friendshipId, 10);
        if (!friendshipId) {
            notifyFriendAction('Unable to process this friend request.', 'error');
            return;
        }

        const requesterName = request.querySelector('h5')?.textContent?.trim() || '';
        const requesterId = parseInt(request.dataset.requesterId, 10) || null;

        button.disabled = true;
        button.classList.add('is-loading');

        try {
            const result = await performAction('acceptRequest', friendshipId);

            if (typeof result.friend_count !== 'undefined') {
                updateFriendCountDisplays(result.friend_count);
            }

            if (typeof window.__hanthanaUpdateFriendButtonState === 'function' && requesterId) {
                window.__hanthanaUpdateFriendButtonState(requesterId, 'friends');
            }

            const successMessage = result.message || (requesterName ? `You are now friends with ${requesterName}!` : 'Friend request accepted.');
            notifyFriendAction(successMessage, 'success');

            const friendListElement = document.querySelector('[data-friends-list]');
            if (friendListElement && requesterId) {
                const existingEntry = friendListElement.querySelector(`[data-friend-user-id="${requesterId}"]`);
                if (!existingEntry) {
                    const friendProfileUrl = `${FRIENDS_BASE_PATH}index.php?controller=Profile&action=view&user_id=${requesterId}`;
                    const listItem = document.createElement('li');
                    listItem.className = 'friends-modal__item';
                    listItem.setAttribute('data-friend-user-id', `${requesterId}`);

                    const link = document.createElement('a');
                    link.className = 'friends-modal__link';
                    link.href = friendProfileUrl;

                    const avatarSrc = request.dataset.requesterAvatar || request.querySelector('.profile-picture img')?.getAttribute('src') || '';
                    const avatar = document.createElement('img');
                    avatar.className = 'friends-modal__avatar';
                    avatar.src = avatarSrc || `${FRIENDS_BASE_PATH}public/images/avatars/defaultProfilePic.png`;
                    avatar.alt = requesterName || request.dataset.requesterName || 'Friend';
                    link.appendChild(avatar);

                    const info = document.createElement('div');
                    info.className = 'friends-modal__info';

                    const nameEl = document.createElement('span');
                    nameEl.className = 'friends-modal__name';
                    nameEl.textContent = requesterName || request.dataset.requesterName || 'Friend';
                    info.appendChild(nameEl);

                    const requesterHandle = request.dataset.requesterHandle || '';
                    if (requesterHandle) {
                        const handleEl = document.createElement('span');
                        handleEl.className = 'friends-modal__handle';
                        handleEl.textContent = requesterHandle;
                        info.appendChild(handleEl);
                    }

                    link.appendChild(info);
                    listItem.appendChild(link);
                    friendListElement.appendChild(listItem);

                    const emptyMessage = document.querySelector('[data-friends-empty]');
                    if (emptyMessage) {
                        emptyMessage.style.display = 'none';
                    }

                    const noteElement = document.querySelector('[data-friends-note]');
                    if (noteElement) {
                        const listLimit = parseInt(friendListElement.dataset.friendListLimit || '0', 10);
                        const currentItems = friendListElement.querySelectorAll('.friends-modal__item').length;
                        if (listLimit > 0 && currentItems > listLimit) {
                            noteElement.style.display = '';
                            noteElement.textContent = `Showing first ${listLimit} friends.`;
                        } else {
                            noteElement.style.display = 'none';
                        }
                    }
                }
            }

            request.remove();
            updateRequestCount();
        } catch (error) {
            button.disabled = false;
            notifyFriendAction(error?.message || 'Unable to accept friend request.', 'error');
        } finally {
            button.classList.remove('is-loading');
        }
    };

    const handleDecline = async (button) => {
        const request = button.closest('.request');
        if (!request) {
            return;
        }

        const friendshipId = parseInt(request.dataset.friendshipId, 10);
        if (!friendshipId) {
            notifyFriendAction('Unable to process this friend request.', 'error');
            return;
        }

        const requesterId = parseInt(request.dataset.requesterId, 10) || null;

        button.disabled = true;
        button.classList.add('is-loading');

        try {
            const result = await performAction('declineRequest', friendshipId);

            if (typeof window.__hanthanaUpdateFriendButtonState === 'function' && requesterId) {
                window.__hanthanaUpdateFriendButtonState(requesterId, 'none');
            }

            notifyFriendAction(result.message || 'Friend request declined.', 'info');

            request.remove();
            updateRequestCount();
        } catch (error) {
            button.disabled = false;
            notifyFriendAction(error?.message || 'Unable to decline friend request.', 'error');
        } finally {
            button.classList.remove('is-loading');
        }
    };

    const acceptButtons = friendRequestsContainer.querySelectorAll('.accept-btn');
    acceptButtons.forEach((button) => {
        button.addEventListener('click', () => {
            handleAccept(button);
        });
    });

    const declineButtons = friendRequestsContainer.querySelectorAll('.decline-btn');
    declineButtons.forEach((button) => {
        button.addEventListener('click', () => {
            handleDecline(button);
        });
    });

    updateRequestCount();
}

function setupAddFriendButtons() {
    const buttons = document.querySelectorAll('.add-friend-btn[data-user-id]');
    if (!buttons.length) {
        return;
    }

    const stateConfig = {
        none: {
            label: 'Add Friend',
            icon: 'uil uil-user-plus',
            disabled: false,
            variant: 'primary',
        },
        pending_outgoing: {
            label: 'Request Sent',
            icon: 'uil uil-clock',
            disabled: true,
            variant: 'secondary',
        },
        incoming_pending: {
            label: 'Request Pending',
            icon: 'uil uil-user-plus',
            disabled: true,
            variant: 'secondary',
        },
        friends: {
            label: 'Friends',
            icon: 'uil uil-user-check',
            disabled: false,
            variant: 'secondary',
        },
        blocked: {
            label: 'Unavailable',
            icon: 'uil uil-ban',
            disabled: true,
            variant: 'secondary',
        },
    };

    const applyState = (button, stateKey) => {
        const config = stateConfig[stateKey] || stateConfig.none;
        button.dataset.state = stateKey;
        button.disabled = !!config.disabled;

        button.classList.remove('btn-primary', 'btn-secondary');
        button.classList.add(config.variant === 'primary' ? 'btn-primary' : 'btn-secondary');

        const iconElement = button.querySelector('i');
        const textElement = button.querySelector('span');

        if (iconElement) {
            iconElement.className = config.icon;
        }
        if (textElement) {
            textElement.textContent = config.label;
        } else {
            button.textContent = config.label;
        }
    };

    const attachHoverHandlers = (button) => {
        if (button.dataset.hoverBound === '1') return;
        button.dataset.hoverBound = '1';
        button.addEventListener('mouseenter', () => {
            if ((button.dataset.state || 'none') === 'friends') {
                const iconElement = button.querySelector('i');
                const textElement = button.querySelector('span');
                if (iconElement) iconElement.className = 'uil uil-user-minus';
                if (textElement) textElement.textContent = 'Remove Friend';
            }
        });
        button.addEventListener('mouseleave', () => {
            if ((button.dataset.state || 'none') === 'friends') {
                const iconElement = button.querySelector('i');
                const textElement = button.querySelector('span');
                if (iconElement) iconElement.className = 'uil uil-user-check';
                if (textElement) textElement.textContent = 'Friends';
            }
        });
    };

    const updateButtonsForUser = (targetUserId, stateKey) => {
        if (!targetUserId) {
            return;
        }

        const normalizedState = stateKey && stateConfig[stateKey] ? stateKey : 'none';
        document.querySelectorAll(`.add-friend-btn[data-user-id="${targetUserId}"]`).forEach((btn) => {
            applyState(btn, normalizedState);
            attachHoverHandlers(btn);
        });
    };

    buttons.forEach((button) => {
        applyState(button, button.dataset.state || 'none');
        attachHoverHandlers(button);

        const targetId = parseInt(button.dataset.userId, 10);
        if (!targetId) {
            return;
        }

        button.addEventListener('click', async () => {
            const currentState = button.dataset.state || 'none';
            const targetId = parseInt(button.dataset.userId, 10);
            if (!targetId) return;

            if (currentState === 'friends') {
                if (!confirm('Remove this friend?')) return;
                button.disabled = true;
                button.classList.add('is-loading');
                try {
                    const resp = await fetch(`${FRIENDS_BASE_PATH}index.php?controller=Friend&action=removeFriend`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `target_user_id=${encodeURIComponent(targetId)}`,
                    });
                    const data = await resp.json();
                    if (!resp.ok || !data?.success) {
                        throw new Error(data?.message || 'Unable to remove friend.');
                    }
                    if (typeof data.friend_count !== 'undefined' && data.friend_count !== null) {
                        updateFriendCountDisplays(data.friend_count);
                    }
                    // Update button back to default state
                    applyState(button, 'none');
                    if (typeof window.__hanthanaUpdateFriendButtonState === 'function') {
                        window.__hanthanaUpdateFriendButtonState(targetId, 'none');
                    }
                    notifyFriendAction(data.message || 'Friend removed.', 'success');
                } catch (err) {
                    notifyFriendAction(err?.message || 'Unable to remove friend.', 'error');
                } finally {
                    button.classList.remove('is-loading');
                    button.disabled = false;
                }
                return;
            }

            if (currentState !== 'none') {
                if (currentState === 'incoming_pending') {
                    notifyFriendAction('Check your friend requests to respond.', 'info');
                }
                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');

            fetch(`${FRIENDS_BASE_PATH}index.php?controller=Friend&action=sendRequest`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `target_user_id=${encodeURIComponent(targetId)}`,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((data) => {
                    if (!data || !data.success) {
                        updateButtonsForUser(targetId, 'none');
                        button.disabled = false;
                        notifyFriendAction(data?.message || 'Could not send friend request.', 'error');
                        return;
                    }

                    const nextState = data.state || 'pending_outgoing';
                    updateButtonsForUser(targetId, nextState);
                    notifyFriendAction(data.message || 'Friend request sent.', 'success');
                })
                .catch(() => {
                    updateButtonsForUser(targetId, 'none');
                    button.disabled = false;
                    notifyFriendAction('Something went wrong. Please try again later.', 'error');
                })
                .finally(() => {
                    button.classList.remove('is-loading');
                });
        });

    window.__hanthanaUpdateFriendButtonState = (targetUserId, nextState) => {
        updateButtonsForUser(parseInt(targetUserId, 10), nextState);
    };
    });
}