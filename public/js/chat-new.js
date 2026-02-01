(function () {
    const portal = document.getElementById('chatPortal');
    if (!portal) {
        return;
    }

    const config = {
        baseUrl: normalizeBaseUrl(portal.dataset.baseUrl || '/'),
        pollIntervals: {
            conversations: 5000,
            messages: 2000,
        },
        user: {
            id: Number(portal.dataset.userId),
            name: portal.dataset.userName,
            avatar: portal.dataset.userAvatar,
        },
    };

    const state = {
        conversations: [],
        conversationMap: new Map(),
        latestMessageIds: new Map(),
        activeConversationId: null,
        isOpen: false,
        isMaximized: false,
        isEditMode: false,
        pollHandles: {
            conversations: null,
            messages: null,
        },
        searchFilterTerm: '',
        searchResults: [],
        searchTerm: '',
        searchTimeout: null,
        attachment: {
            file: null,
            name: '',
            size: 0,
            type: '',
            previewUrl: null,
        },
    };

    const refs = {
        icon: document.getElementById('chatIcon'),
        unreadBadge: document.getElementById('chatUnreadBadge'),
        overlay: document.getElementById('chatOverlay'),
        container: document.getElementById('chatContainer'),
        listView: document.getElementById('chat-list-view'),
        conversationView: document.getElementById('chat-conversation-view'),
        userList: document.getElementById('chat-user-list'),
        userListMax: document.getElementById('chat-user-list-max'),
        messagesArea: document.getElementById('messages-area'),
        messagesAreaMax: document.getElementById('maximized-messages-area'),
        messageInput: document.getElementById('messageInput'),
        messageInputMax: document.getElementById('maximized-message-input'),
        sendBtn: document.getElementById('sendMessageButton'),
        sendBtnMax: document.getElementById('sendMessageButtonMax'),
        backBtn: document.getElementById('chatBackBtn'),
        backBtnMax: document.getElementById('chatBackBtnMax'),
        maximizeBtns: [
            document.getElementById('chatMaximizeBtn'),
            document.getElementById('chatMaximizeBtnConversation'),
        ].filter(Boolean),
        minimizeBtns: [
            document.getElementById('chatMinimizeBtn'),
            document.getElementById('chatMinimizeBtnSecondary'),
        ].filter(Boolean),
        closeBtns: [
            document.getElementById('chatCloseBtn'),
            document.getElementById('chatCloseBtnConversation'),
            document.getElementById('chatCloseBtnMax'),
            document.getElementById('chatCloseBtnMaxSecondary'),
        ].filter(Boolean),
        primarySearchInput: document.getElementById('chatSearchInput'),
        searchInputs: [
            document.getElementById('chatSearchInput'),
            document.getElementById('chatSearchInputMax'),
        ].filter(Boolean),
        searchResults: document.getElementById('chat-search-results'),
        headerName: document.getElementById('conversation-name'),
        headerStatus: document.getElementById('conversation-status'),
        headerAvatar: document.querySelector('#conversation-avatar img'),
        headerNameMax: document.getElementById('conversation-name-max'),
        headerStatusMax: document.getElementById('conversation-status-max'),
        headerAvatarMax: document.querySelector('#conversation-avatar-max img'),
        maximizedView: document.getElementById('maximized-view'),
        maxListWrapper: document.getElementById('chat-list-maximized'),
        maxConversationWrapper: document.getElementById('chat-conversation-maximized'),
        attachmentInput: document.getElementById('chatAttachmentInput'),
        attachButtons: [
            document.getElementById('attachFileButton'),
            document.getElementById('attachFileButtonMax'),
        ].filter(Boolean),
        attachmentPreview: document.getElementById('chat-attachment-preview'),
        attachmentPreviewMax: document.getElementById('chat-attachment-preview-max'),
    };

    const api = {
        async listConversations() {
            return request('index.php?controller=Chat&action=listConversations');
        },
        async fetchMessages(conversationId, afterId) {
            const params = new URLSearchParams({ conversation_id: conversationId });
            if (afterId) {
                params.append('after_id', afterId);
            }
            return request(`index.php?controller=Chat&action=fetchMessages&${params.toString()}`);
        },
        async sendMessage(formData) {
            return request('index.php?controller=Chat&action=sendMessage', {
                method: 'POST',
                body: formData,
                headers: {},
            });
        },
        async markRead(conversationId) {
            return request('index.php?controller=Chat&action=markRead', {
                method: 'POST',
                body: JSON.stringify({ conversation_id: conversationId }),
            });
        },
        async searchFriends(term) {
            const params = new URLSearchParams({ term });
            return request(`index.php?controller=Chat&action=searchFriends&${params.toString()}`);
        },
        async searchChannels(term) {
            const params = new URLSearchParams({ term });
            return request(`index.php?controller=Chat&action=searchChannels&${params.toString()}`);
        },
        async startConversation(targetId, type) {
            return request('index.php?controller=Chat&action=startConversation', {
                method: 'POST',
                body: JSON.stringify({
                    target_id: targetId,
                    conversation_type: type
                }),
            });
        },
        async fetchSharedMedia(conversationId) {
            const params = new URLSearchParams({ conversation_id: conversationId });
            return request(`index.php?controller=Chat&action=fetchSharedMedia&${params.toString()}`);
        },
        async fetchFileStructure(conversationId, folderId) {
            const params = new URLSearchParams({ conversation_id: conversationId, folder_id: folderId });
            return request(`index.php?controller=Chat&action=fetchFileStructure&${params.toString()}`);
        },
        async createFolder(conversationId, parentFolderId, folderName) {
            return request('index.php?controller=Chat&action=createFolder', {
                method: 'POST',
                body: JSON.stringify({ conversation_id: conversationId, parent_folder_id: parentFolderId, folder_name: folderName }),
            });
        },
        async uploadFile(formData) {
            return request('index.php?controller=Chat&action=uploadFile', {
                method: 'POST',
                body: formData,
                headers: {},
            });
        },
        async deleteFolder(folderId) {
            return request('index.php?controller=Chat&action=deleteFolder', {
                method: 'POST',
                body: JSON.stringify({ folder_id: folderId }),
            });
        },
        async deleteFile(fileId) {
            return request('index.php?controller=Chat&action=deleteFile', {
                method: 'POST',
                body: JSON.stringify({ file_id: fileId }),
            });
        },
    };

    function normalizeBaseUrl(base) {
        if (!base.endsWith('/')) {
            return `${base}/`;
        }
        return base;
    }

    function buildUrl(path) {
        if (path.startsWith('http')) {
            return path;
        }
        return config.baseUrl + path.replace(/^\//, '');
    }

    async function request(path, options = {}) {
        const isFormData = options.body instanceof FormData;
        const headers = { ...(options.headers || {}) };
        if (!isFormData && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        const fetchOptions = {
            credentials: 'same-origin',
            ...options,
            headers,
        };

        if (!isFormData && fetchOptions.body && typeof fetchOptions.body === 'object') {
            fetchOptions.body = JSON.stringify(fetchOptions.body);
        }

        if (isFormData && headers['Content-Type']) {
            delete headers['Content-Type'];
        }

        const response = await fetch(buildUrl(path), fetchOptions);

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = payload.error || 'Request failed';
            throw new Error(message);
        }
        return payload.data || payload;
    }

    function openChat() {
        if (state.isOpen) {
            return;
        }
        state.isOpen = true;
        refs.container.classList.add('show');
        refs.overlay.classList.add('show');
        document.body.classList.add('chat-open');
        syncViewsWithState();
        fetchConversations();
        startPolling();
    }

    function closeChat() {
        state.isOpen = false;
        state.activeConversationId = null;
        state.isEditMode = false;
        const editBtn = document.getElementById('chatEditBtn');
        if (editBtn) {
            editBtn.classList.remove('active');
        }
        refs.container.classList.remove('show');
        refs.overlay.classList.remove('show');
        document.body.classList.remove('chat-open');
        setMaximized(false);
        hideSearchResults(true);
        clearAttachment();
        syncViewsWithState();
        stopPolling();
    }

    function setMaximized(shouldMaximize) {
        state.isMaximized = shouldMaximize;
        refs.container.classList.toggle('maximized', shouldMaximize);
        document.body.classList.toggle('chat-maximized', shouldMaximize);
        syncViewsWithState();
        
        // Update media sidebar visibility when maximizing
        if (shouldMaximize) {
            updateMediaSidebarState();
        }
    }

    function syncViewsWithState() {
        const hasMaxView = refs.maximizedView && refs.maxListWrapper && refs.maxConversationWrapper;
        if (state.isMaximized && hasMaxView) {
            refs.listView.style.display = 'none';
            refs.conversationView.style.display = 'none';
            refs.maximizedView.style.display = 'flex';
            if (state.activeConversationId) {
                refs.maxListWrapper.style.display = 'none';
                refs.maxConversationWrapper.style.display = 'flex';
            } else {
                refs.maxListWrapper.style.display = 'flex';
                refs.maxConversationWrapper.style.display = 'none';
            }
        } else {
            refs.maximizedView.style.display = 'none';
            if (state.activeConversationId) {
                refs.listView.style.display = 'none';
                refs.conversationView.style.display = 'flex';
            } else {
                refs.listView.style.display = 'flex';
                refs.conversationView.style.display = 'none';
            }
        }
    }

    function startPolling() {
        stopPolling();
        state.pollHandles.conversations = setInterval(fetchConversations, config.pollIntervals.conversations);
        state.pollHandles.messages = setInterval(() => {
            if (state.activeConversationId) {
                fetchMessages(state.activeConversationId);
            }
        }, config.pollIntervals.messages);
    }

    function stopPolling() {
        Object.keys(state.pollHandles).forEach((key) => {
            if (state.pollHandles[key]) {
                clearInterval(state.pollHandles[key]);
                state.pollHandles[key] = null;
            }
        });
    }

    function normalizeConversation(conversation) {
        const normalized = { ...conversation };
        normalized.conversation_id = Number(normalized.conversation_id);
        if (!normalized.display_name) {
            normalized.display_name = `Conversation #${normalized.conversation_id}`;
        }
        if (!normalized.last_message_at && normalized.last_message?.created_at) {
            normalized.last_message_at = normalized.last_message.created_at;
        }
        normalized.unread_count = Number(normalized.unread_count || 0);
        if (!normalized.last_message_preview && normalized.last_message?.content) {
            normalized.last_message_preview = normalized.last_message.content;
        }
        if (!normalized.last_message_type && normalized.last_message?.message_type) {
            normalized.last_message_type = normalized.last_message.message_type;
        }
        return normalized;
    }

    function replaceConversations(conversations) {
        const normalizedList = conversations.map((conversation) => normalizeConversation(conversation));
        state.conversations = normalizedList;
        state.conversationMap = new Map();
        const latestIds = new Map();

        normalizedList.forEach((conversation) => {
            state.conversationMap.set(conversation.conversation_id, conversation);
            if (conversation.last_message?.message_id) {
                latestIds.set(conversation.conversation_id, conversation.last_message.message_id);
            } else if (state.latestMessageIds.has(conversation.conversation_id)) {
                latestIds.set(conversation.conversation_id, state.latestMessageIds.get(conversation.conversation_id));
            }
        });

        state.latestMessageIds = latestIds;
        sortConversations();
        renderConversations();
        updateUnreadBadgeTotal();
    }

    function upsertConversation(conversation) {
        const normalized = normalizeConversation(conversation);
        const existing = state.conversationMap.get(normalized.conversation_id);
        const merged = existing ? {
            ...existing,
            ...normalized,
            last_message_preview: normalized.last_message_preview || existing.last_message_preview,
            last_message_type: normalized.last_message_type || existing.last_message_type,
        } : normalized;
        state.conversationMap.set(normalized.conversation_id, merged);
        const index = state.conversations.findIndex((item) => item.conversation_id === merged.conversation_id);
        if (index >= 0) {
            state.conversations.splice(index, 1, merged);
        } else {
            state.conversations.push(merged);
        }

        if (merged.last_message?.message_id) {
            state.latestMessageIds.set(merged.conversation_id, merged.last_message.message_id);
        }

        sortConversations();
        renderConversations();
        updateUnreadBadgeTotal();
    }

    function sortConversations() {
        state.conversations.sort((a, b) => {
            const aTimestamp = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
            const bTimestamp = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;
            const safeA = Number.isNaN(aTimestamp) ? 0 : aTimestamp;
            const safeB = Number.isNaN(bTimestamp) ? 0 : bTimestamp;
            return safeB - safeA;
        });
    }

    async function fetchConversations() {
        try {
            const data = await api.listConversations();
            const normalized = Array.isArray(data) ? data : [];
            replaceConversations(normalized);
        } catch (error) {
            console.error('Failed to load conversations', error);
        }
    }

    async function fetchMessages(conversationId, reset = false) {
        if (!conversationId) {
            return;
        }
        const afterId = reset ? null : state.latestMessageIds.get(conversationId);
        try {
            const data = await api.fetchMessages(conversationId, afterId);
            if (Array.isArray(data) && data.length) {
                const lastMessage = data[data.length - 1];
                state.latestMessageIds.set(conversationId, lastMessage.message_id);
                renderMessages(conversationId, data, reset);
                await api.markRead(conversationId).catch(() => {});
                updateUnreadBadge(conversationId, 0);
            } else if (reset) {
                renderMessages(conversationId, [], true);
            }
        } catch (error) {
            console.error('Failed to load messages', error);
        }
    }

    function renderConversations() {
        const filterTerm = state.searchFilterTerm.trim().toLowerCase();
        const listTarget = refs.userList;
        const listTargetMax = refs.userListMax;
        clearElement(listTarget);
        clearElement(listTargetMax);

        // If searching, don't show conversations in compact view (show friend search results instead)
        if (filterTerm && refs.primarySearchInput) {
            // Compact view shows nothing when searching (friend search dropdown will show)
            listTarget.appendChild(buildEmptyState(''));
            listTarget.style.display = 'none';
        } else {
            listTarget.style.display = '';
        }

        const filtered = state.conversations.filter((conversation) => {
            if (!filterTerm) {
                return true;
            }
            return conversation.display_name.toLowerCase().includes(filterTerm);
        });

        if (!filtered.length && !filterTerm) {
            const emptyCompact = buildEmptyState('No conversations yet');
            const emptyMax = buildEmptyState('No conversations yet');
            listTarget.appendChild(emptyCompact);
            listTargetMax.appendChild(emptyMax);
            return;
        }

        if (!filtered.length && filterTerm) {
            const emptyMax = buildEmptyState('No conversations match your search');
            listTargetMax.appendChild(emptyMax);
            return;
        }

        // Show only top 3 in compact view, all in maximized view
        const compactList = filtered.slice(0, 3);
        
        if (!filterTerm) {
            compactList.forEach((conversation) => {
                const item = buildConversationItem(conversation, false);
                listTarget.appendChild(item);
            });
        }
        
        filtered.forEach((conversation) => {
            const itemMax = buildConversationItem(conversation, true);
            listTargetMax.appendChild(itemMax);
        });
    }

    function buildConversationItem(conversation, isMaximized) {
        const wrapper = document.createElement('div');
        wrapper.className = isMaximized ? 'user-item-max flex align-center' : 'user-item flex align-center';
        wrapper.dataset.conversationId = conversation.conversation_id;
        if (conversation.conversation_id === state.activeConversationId) {
            wrapper.classList.add('selected');
        }

        const photo = document.createElement('div');
        photo.className = 'profile-photo';
        const img = document.createElement('img');
        img.src = resolveAvatar(conversation.avatar);
        img.alt = conversation.display_name;
        photo.appendChild(img);
        
        // Add online status dot only if user is online
        if (conversation.is_online) {
            const statusDot = document.createElement('span');
            statusDot.className = 'status-dot status-dot--online';
            photo.appendChild(statusDot);
        }

        const info = document.createElement('div');
        info.className = isMaximized ? 'user-info-max' : 'user-info';

        const details = document.createElement('div');
        details.className = isMaximized ? 'user-details' : '';
        const name = document.createElement('h4');
        name.textContent = conversation.display_name;
        const preview = document.createElement('p');
        preview.textContent = formatPreview(conversation);
        details.appendChild(name);
        details.appendChild(preview);
        info.appendChild(details);

        const meta = document.createElement('div');
        meta.className = isMaximized ? 'chat-meta' : 'user-status';
        
        // Add delete button if in edit mode
        if (state.isEditMode) {
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-conversation-btn';
            deleteBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
            deleteBtn.title = 'Delete conversation';
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteConversation(conversation.conversation_id);
            });
            meta.appendChild(deleteBtn);
        } else {
            const time = document.createElement('span');
            time.className = 'message-time';
            time.textContent = formatRelativeTime(conversation.last_message_at);
            meta.appendChild(time);
            if (conversation.unread_count) {
                const badge = document.createElement('div');
                badge.className = 'message-count';
                badge.textContent = conversation.unread_count;
                meta.appendChild(badge);
            }
        }
        info.appendChild(meta);

        wrapper.appendChild(photo);
        wrapper.appendChild(info);

        if (!state.isEditMode) {
            wrapper.addEventListener('click', () => selectConversation(conversation.conversation_id));
        }
        return wrapper;
    }

    function updateUnreadBadge(conversationId, unreadCount) {
        state.conversations = state.conversations.map((conversation) => {
            if (conversation.conversation_id === conversationId) {
                return { ...conversation, unread_count: unreadCount };
            }
            return conversation;
        });
        state.conversationMap.set(conversationId, {
            ...state.conversationMap.get(conversationId),
            unread_count: unreadCount,
        });
        renderConversations();
        updateUnreadBadgeTotal();
    }

    function updateUnreadBadgeTotal() {
        if (!refs.unreadBadge) {
            return;
        }
        const total = state.conversations.reduce((sum, conversation) => sum + (Number(conversation.unread_count) || 0), 0);
        if (total <= 0) {
            refs.unreadBadge.textContent = '';
            return;
        }
        refs.unreadBadge.textContent = total > 9 ? '9+' : String(total);
    }

    function resolveAvatar(path) {
        if (!path) {
            return buildUrl('images/avatars/default.png');
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        return buildUrl(path);
    }

    function formatPreview(conversation) {
        if (!conversation) {
            return 'No messages yet';
        }
        const lastMessage = conversation.last_message || null;
        const previewSource = (conversation.last_message_preview || lastMessage?.content || '').trim();
        const type = conversation.last_message_type || lastMessage?.message_type;
        const sender = lastMessage?.sender_name ? `${lastMessage.sender_name}: ` : '';

        if (previewSource) {
            return `${sender}${truncateText(previewSource, 40)}`;
        }

        if (type) {
            const label = mapAttachmentTypeToLabel(type);
            return `${sender}${label}`;
        }

        return 'No messages yet';
    }

    function mapAttachmentTypeToLabel(type) {
        switch (type) {
            case 'image':
                return 'Shared an image';
            case 'video':
                return 'Shared a video';
            case 'file':
                return 'Shared a file';
            default:
                return 'New attachment';
        }
    }

    function truncateText(text, maxLength) {
        if (!text) {
            return '';
        }
        return text.length > maxLength ? `${text.substring(0, maxLength)}…` : text;
    }

    function formatRelativeTime(timestamp) {
        if (!timestamp) {
            return '';
        }
        // Convert to string and replace space with 'T' for ISO format if needed
        const timestampStr = String(timestamp).replace(' ', 'T');
        const date = new Date(timestampStr);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        const diffSeconds = Math.floor((Date.now() - date.getTime()) / 1000);
        if (diffSeconds < 60) {
            return 'now';
        }
        if (diffSeconds < 3600) {
            return `${Math.floor(diffSeconds / 60)}m`;
        }
        if (diffSeconds < 86400) {
            return `${Math.floor(diffSeconds / 3600)}h`;
        }
        return date.toLocaleDateString();
    }

    function buildEmptyState(message) {
        const wrapper = document.createElement('div');
        wrapper.className = 'empty-state';
        if (message) {
            const paragraph = document.createElement('p');
            paragraph.textContent = message;
            wrapper.appendChild(paragraph);
        }
        return wrapper;
    }

    function removeEmptyState(container) {
        const empty = container.querySelector('.empty-state');
        if (empty) {
            empty.remove();
        }
    }

    function clearElement(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function hideSearchResults(clear = false) {
        if (!refs.searchResults) {
            return;
        }
        refs.searchResults.classList.remove('show');
        if (clear) {
            state.searchResults = [];
            state.searchTerm = '';
            clearElement(refs.searchResults);
        }
    }

    function renderSearchResults() {
        if (!refs.searchResults) {
            return;
        }

        clearElement(refs.searchResults);
        if (!state.searchResults.length) {
            const placeholder = buildEmptyState('No friends or channels match your search');
            refs.searchResults.appendChild(placeholder);
        } else {
            state.searchResults.forEach((target) => {
                const item = document.createElement('div');
                item.className = 'search-result-item';

                const avatar = document.createElement('div');
                avatar.className = 'profile-photo';
                const img = document.createElement('img');
                img.src = resolveAvatar(target.profile_picture);
                const fullName = (target.full_name || '').trim();
                img.alt = fullName || target.username;
                avatar.appendChild(img);
                
                // Add online status dot only if friend is online
                if (target.is_online) {
                    const statusDot = document.createElement('span');
                    statusDot.className = 'status-dot status-dot--online';
                    avatar.appendChild(statusDot);
                }

                const info = document.createElement('div');
                info.className = 'result-info';
                const title = document.createElement('h5');
                title.textContent = fullName || target.username;
                const username = document.createElement('span');
                username.textContent = `@${target.username}`;
                info.appendChild(title);
                info.appendChild(username);

                const action = document.createElement('button');
                action.type = 'button';
                action.textContent = 'Message';
                action.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    initiateChat(target.friend_user_id, target.conversation_type);
                });

                item.appendChild(avatar);
                item.appendChild(info);
                item.appendChild(action);

                item.addEventListener('click', () => initiateChat(target.friend_user_id, target.conversation_type));

                refs.searchResults.appendChild(item);
            });
        }

        refs.searchResults.classList.add('show');
    }

    /*function performFriendSearch(term) {
        if (!refs.primarySearchInput || !refs.searchResults) {
            return;
        }

        const trimmed = term.trim();
        state.searchTerm = trimmed;

        if (state.searchTimeout) {
            clearTimeout(state.searchTimeout);
            state.searchTimeout = null;
        }

        if (trimmed.length < 2) {
            hideSearchResults(true);
            return;
        }

        const currentTerm = trimmed;
        state.searchTimeout = setTimeout(async () => {
            try {
                const results = await api.searchFriends(currentTerm);
                console.log(results)
                if (state.searchTerm !== currentTerm) {
                    return;
                }
                state.searchResults = Array.isArray(results) ? results : [];
                renderSearchResults();
            } catch (error) {
                console.error('Friend search failed', error);
            } finally {
                state.searchTimeout = null;
            }
        }, 250);
    }

    function performChannelSearch(term) {
        console.log('entered channel search')
        if (!refs.primarySearchInput || !refs.searchResults) {
            return;
        }

        const trimmed = term.trim();
        state.searchTerm = trimmed;

        if (state.searchTimeout) {
            clearTimeout(state.searchTimeout);
            state.searchTimeout = null;
        }

        if (trimmed.length < 2) {
            hideSearchResults(true);
            return;
        }

        const currentTerm = trimmed;
        state.searchTimeout = setTimeout(async () => {
            try {
                const results = await api.searchChannels(currentTerm);
                console.log(results)
                if (state.searchTerm !== currentTerm) {
                    return;
                }
                state.searchResults = Array.isArray(results) ? results : [];
                renderSearchResults();
            } catch (error) {
                console.error('Friend search failed', error);
            } finally {
                state.searchTimeout = null;
            }
        }, 250);
    }*/

    function performGlobalSearch(term) {
        if (!refs.primarySearchInput || !refs.searchResults) {
            return;
        }

        const trimmed = term.trim();
        state.searchTerm = trimmed;

        if (state.searchTimeout) {
            clearTimeout(state.searchTimeout);
            state.searchTimeout = null;
        }

        if (trimmed.length < 2) {
            hideSearchResults(true);
            return;
        }

        const currentTerm = trimmed;
        state.searchTimeout = setTimeout(async () => {
            try {
                const [friendResults, channelResults] = await Promise.all([
                    api.searchFriends(currentTerm), api.searchChannels(currentTerm)
                ]);

                if (state.searchTerm !== currentTerm) {
                    return;
                }
                
                const friends = Array.isArray(friendResults) ? friendResults : [];
                const channels = Array.isArray(channelResults) ? channelResults : [];
                
                state.searchResults = [...friends, ...channels];
                console.log(state.searchResults);
                renderSearchResults();
            } catch (error) {
                console.error('Search failed', error);
            } finally {
                state.searchTimeout = null;
            }
        }, 250);
    }

    function handleSearchOutsideClick(event) {
        if (!refs.searchResults) {
            return;
        }
        const searchSection = refs.searchResults.parentElement;
        if (!searchSection) {
            return;
        }
        if (searchSection.contains(event.target)) {
            return;
        }
        hideSearchResults();
    }

    function openAttachmentPicker() {
        if (!state.activeConversationId) {
            alert('Open a conversation before attaching files.');
            return;
        }
        if (!refs.attachmentInput) {
            return;
        }
        refs.attachmentInput.value = '';
        refs.attachmentInput.click();
    }

    function handleAttachmentChange(event) {
        const input = event.target;
        console.log('Attachment input changed, files:', input.files);
        if (!input.files || !input.files[0]) {
            console.log('No file selected');
            return;
        }
        const file = input.files[0];
        console.log('File selected:', {
            name: file.name,
            size: file.size,
            type: file.type
        });
        const maxSize = 15 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Attachments must be smaller than 15 MB.');
            input.value = '';
            return;
        }
        setAttachment(file);
    }

    function setAttachment(file) {
        if (state.attachment.previewUrl) {
            URL.revokeObjectURL(state.attachment.previewUrl);
        }
        if (!file) {
            state.attachment = {
                file: null,
                name: '',
                size: 0,
                type: '',
                previewUrl: null,
            };
            renderAttachmentPreview();
            return;
        }

        const previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
        state.attachment = {
            file,
            name: file.name,
            size: file.size,
            type: file.type,
            previewUrl,
        };
        renderAttachmentPreview();
    }

    function clearAttachment() {
        setAttachment(null);
        if (refs.attachmentInput) {
            refs.attachmentInput.value = '';
        }
    }

    function renderAttachmentPreview() {
        const targets = [refs.attachmentPreview, refs.attachmentPreviewMax].filter(Boolean);
        if (!targets.length) {
            return;
        }

        targets.forEach((target) => {
            clearElement(target);
            const hasAttachment = Boolean(state.attachment.file);
            target.hidden = !hasAttachment;
            if (!hasAttachment) {
                target.classList.add('hidden');
                clearElement(target);
                return;
            }
            target.classList.remove('hidden');

            const chip = document.createElement('div');
            chip.className = 'attachment-chip';

            const thumb = document.createElement('div');
            thumb.className = 'attachment-thumb';
            if (state.attachment.previewUrl) {
                const img = document.createElement('img');
                img.src = state.attachment.previewUrl;
                img.alt = state.attachment.name;
                thumb.appendChild(img);
            } else {
                thumb.textContent = state.attachment.type.startsWith('video/') ? '🎬' : '📁';
            }

            const info = document.createElement('div');
            info.className = 'attachment-info';
            const title = document.createElement('h6');
            title.textContent = state.attachment.name || 'Attachment';
            const size = document.createElement('span');
            size.textContent = formatFileSize(state.attachment.size);
            info.appendChild(title);
            info.appendChild(size);

            chip.appendChild(thumb);
            chip.appendChild(info);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'attachment-remove';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', clearAttachment);

            target.appendChild(chip);
            target.appendChild(removeBtn);
        });
    }

    function formatFileSize(bytes) {
        if (!bytes) {
            return '';
        }
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }
        return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
    }

    async function initiateChat(targetId, type) {
        console.log(targetId, type);
        if (!targetId) {
            return;
        }
        try {
            const conversation = await api.startConversation(targetId, type);
            console.log(conversation)
            if (!conversation) {
                return;
            }
            hideSearchResults(true);
            if (refs.primarySearchInput) {
                refs.primarySearchInput.value = '';
            }
            state.searchFilterTerm = '';
            state.searchTerm = '';
            upsertConversation(conversation);
            selectConversation(conversation.conversation_id);
        } catch (error) {
            console.error('Unable to start conversation', error);
        }
    }

    async function startConversationInChannel() {
        
    }

    function selectConversation(conversationId) {
        if (!conversationId) {
            return;
        }
        state.activeConversationId = conversationId;
        const conversation = state.conversationMap.get(conversationId);
        if (conversation) {
            updateConversationHeader(conversation);
        }
        syncViewsWithState();
        renderConversations();
        fetchMessages(conversationId, true);
        loadSharedMedia(conversationId);
    }

    function updateConversationHeader(conversation) {
        const title = conversation.display_name;
        const avatar = resolveAvatar(conversation.avatar);
        refs.headerName.textContent = title;
        refs.headerNameMax.textContent = title;
        refs.headerAvatar.src = avatar;
        refs.headerAvatarMax.src = avatar;
        refs.headerStatus.textContent = 'Live conversation';
        refs.headerStatusMax.textContent = 'Live conversation';
    }

    function renderMessages(conversationId, messages, reset) {
        if (conversationId !== state.activeConversationId) {
            return;
        }
        if (reset) {
            clearElement(refs.messagesArea);
            clearElement(refs.messagesAreaMax);
        }

        if (!messages.length && reset) {
            refs.messagesArea.appendChild(buildEmptyState('No messages yet. Start the conversation!'));
            refs.messagesAreaMax.appendChild(buildEmptyState('No messages yet. Start the conversation!'));
            return;
        }

        removeEmptyState(refs.messagesArea);
        removeEmptyState(refs.messagesAreaMax);
        messages.forEach((message) => {
            appendMessageBubble(refs.messagesArea, message);
            appendMessageBubble(refs.messagesAreaMax, message);
        });
        scrollToBottom(refs.messagesArea);
        scrollToBottom(refs.messagesAreaMax);
    }

    function appendMessageBubble(container, message) {
        const bubble = document.createElement('div');
        bubble.className = 'message';
        if (message.is_own) {
            bubble.classList.add('own');
        }

        const avatar = document.createElement('div');
        avatar.className = 'profile-photo';
        const img = document.createElement('img');
        img.src = resolveAvatar(message.sender_avatar);
        img.alt = message.sender_name;
        avatar.appendChild(img);
        
        // Add online status dot only if sender is online
        if (message.is_online) {
            const statusDot = document.createElement('span');
            statusDot.className = 'status-dot status-dot--online';
            avatar.appendChild(statusDot);
        }

        const content = document.createElement('div');
        content.className = 'message-content';

        if (message.content) {
            const text = document.createElement('div');
            text.className = 'message-text';
            text.textContent = message.content;
            content.appendChild(text);
        }

        const attachment = buildMessageAttachment(message);
        if (attachment) {
            content.appendChild(attachment);
        }

        const meta = document.createElement('div');
        meta.className = 'message-meta';
        meta.textContent = formatTimestamp(message.created_at);
        content.appendChild(meta);

        bubble.appendChild(avatar);
        bubble.appendChild(content);

        container.appendChild(bubble);
    }

    function buildMessageAttachment(message) {
        if (!message.file_url) {
            return null;
        }
        const wrapper = document.createElement('div');
        wrapper.className = 'message-attachment';
        const url = buildUrl(message.file_url);

        if (message.message_type === 'image') {
            const img = document.createElement('img');
            img.src = url;
            img.alt = message.file_name || 'Image attachment';
            wrapper.appendChild(img);
            return wrapper;
        }

        if (message.message_type === 'video') {
            const video = document.createElement('video');
            video.src = url;
            video.controls = true;
            wrapper.appendChild(video);
            return wrapper;
        }

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'file-link';

        const icon = document.createElement('span');
        icon.className = 'file-icon';
        icon.textContent = '📎';
        const label = document.createElement('span');
        label.textContent = message.file_name || 'Download file';

        link.appendChild(icon);
        link.appendChild(label);
        wrapper.appendChild(link);

        return wrapper;
    }

    function formatTimestamp(timestamp) {
        if (!timestamp) {
            return '';
        }
        // Convert to string and replace space with 'T' for ISO format if needed
        const timestampStr = String(timestamp).replace(' ', 'T');
        const date = new Date(timestampStr);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function scrollToBottom(container) {
        if (!container) {
            return;
        }
        container.scrollTop = container.scrollHeight;
    }

    async function handleSendMessage(isMaxView = false) {
        const input = isMaxView ? refs.messageInputMax : refs.messageInput;
        const message = input.value.trim();
        if (!state.activeConversationId) {
            return;
        }
        if (!message && !state.attachment.file) {
            return;
        }
        input.value = '';
        autosize(input);
        const formData = new FormData();
        formData.append('conversation_id', state.activeConversationId);
        formData.append('content', message);
        const hadAttachment = Boolean(state.attachment.file);
        if (state.attachment.file) {
            console.log('Adding attachment to FormData:', {
                name: state.attachment.name,
                size: state.attachment.size,
                type: state.attachment.type
            });
            formData.append('attachment', state.attachment.file, state.attachment.name || 'attachment');
        }
        console.log('Sending message with attachment:', hadAttachment);
        try {
            const response = await api.sendMessage(formData);
            console.log('Message sent successfully:', response);
            state.latestMessageIds.set(state.activeConversationId, response.message_id);
            renderMessages(state.activeConversationId, [response], false);
            fetchConversations();
            if (hadAttachment) {
                clearAttachment();
            }
        } catch (error) {
            input.value = message;
            autosize(input);
            console.error('Failed to send message', error);
            alert('Failed to send message: ' + error.message);
        }
    }

    function autosize(textarea) {
        if (!textarea) {
            return;
        }
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    }

    function toggleEditMode() {
        state.isEditMode = !state.isEditMode;
        const editBtn = document.getElementById('chatEditBtn');
        if (editBtn) {
            editBtn.classList.toggle('active', state.isEditMode);
            editBtn.title = state.isEditMode ? 'Done editing' : 'Edit conversations';
        }
        renderConversations();
    }

    async function deleteConversation(conversationId) {
        if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
            return;
        }
        
        // Remove from state
        state.conversations = state.conversations.filter(c => c.conversation_id !== conversationId);
        state.conversationMap.delete(conversationId);
        state.latestMessageIds.delete(conversationId);
        
        if (state.activeConversationId === conversationId) {
            state.activeConversationId = null;
            syncViewsWithState();
        }
        
        renderConversations();
        updateUnreadBadgeTotal();
        
        // TODO: Add backend API call to delete conversation from database
        // await api.deleteConversation(conversationId);
    }

    function attachEventListeners() {
        refs.icon?.addEventListener('click', openChat);
        refs.overlay?.addEventListener('click', closeChat);
        refs.closeBtns.forEach((btn) => btn.addEventListener('click', closeChat));
        
        // Edit button to toggle edit mode
        const editBtn = document.getElementById('chatEditBtn');
        editBtn?.addEventListener('click', toggleEditMode);
        
        refs.backBtn?.addEventListener('click', () => {
            state.activeConversationId = null;
            syncViewsWithState();
        });
        refs.backBtnMax?.addEventListener('click', () => {
            state.activeConversationId = null;
            syncViewsWithState();
        });
        refs.maximizeBtns.forEach((btn) => btn.addEventListener('click', () => setMaximized(true)));
        refs.minimizeBtn?.addEventListener('click', () => setMaximized(false));
        refs.sendBtn?.addEventListener('click', () => handleSendMessage(false));
        refs.sendBtnMax?.addEventListener('click', () => handleSendMessage(true));
        refs.messageInput?.addEventListener('input', (event) => autosize(event.target));
        refs.messageInputMax?.addEventListener('input', (event) => autosize(event.target));
        refs.messageInput?.addEventListener('keypress', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                handleSendMessage(false);
            }
        });
        refs.messageInputMax?.addEventListener('keypress', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                handleSendMessage(true);
            }
        });
        refs.attachButtons.forEach((button) => button.addEventListener('click', openAttachmentPicker));
        refs.attachmentInput?.addEventListener('change', handleAttachmentChange);
        if (refs.primarySearchInput) {
            refs.primarySearchInput.addEventListener('focus', () => {
                if (state.searchResults.length) {
                    refs.searchResults?.classList.add('show');
                }
            });
        }
        refs.searchInputs.forEach((input) => {
            input.addEventListener('input', (event) => {
                const value = event.target.value;
                state.searchFilterTerm = value;
                renderConversations();
                if (input === refs.primarySearchInput) {
                    performGlobalSearch(value);
                }
            });
        });
        if (refs.searchResults) {
            document.addEventListener('click', handleSearchOutsideClick);
        }
    }
    
    async function loadSharedMedia(conversationId) {
        const mediaEmptyState = document.getElementById('media-empty-state');
        const mediaContentWrapper = document.getElementById('media-content-wrapper');
        const mediaSubtitle = document.getElementById('media-subtitle');
        
        if (!conversationId) {
            if (mediaEmptyState) mediaEmptyState.style.display = 'flex';
            if (mediaContentWrapper) mediaContentWrapper.style.display = 'none';
            if (mediaSubtitle) mediaSubtitle.textContent = 'Select a chat to view shared files';
            return;
        }

        try {
            const conversation = state.conversationMap.get(conversationId);
            const conversationName = conversation ? conversation.display_name : 'this chat';
            
            if (mediaSubtitle) mediaSubtitle.textContent = `Shared in ${conversationName}`;
            if (mediaEmptyState) mediaEmptyState.style.display = 'none';
            if (mediaContentWrapper) mediaContentWrapper.style.display = 'block';

            const media = await api.fetchSharedMedia(conversationId);
            renderSharedMedia(media);
        } catch (error) {
            console.error('Failed to load shared media:', error);
            if (mediaEmptyState) mediaEmptyState.style.display = 'flex';
            if (mediaContentWrapper) mediaContentWrapper.style.display = 'none';
        }
    }

    function renderSharedMedia(media) {
        const photosContainer = document.getElementById('media-photos');
        const videosContainer = document.getElementById('media-videos');
        const documentsContainer = document.getElementById('media-documents');

        // Calculate total storage used
        let totalBytes = 0;
        const allItems = [...(media.photos || []), ...(media.videos || []), ...(media.documents || [])];
        allItems.forEach(item => {
            totalBytes += parseInt(item.file_size || 0);
        });

        if (photosContainer) {
            clearElement(photosContainer);
            const photos = (media.photos || []);
            if (photos.length > 0) {
                photos.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'media-item';
                    div.innerHTML = `
                        <img src="${buildUrl(item.file_url)}" alt="${escapeHtml(item.file_name || 'Photo')}" loading="lazy">
                        <div class="media-item-overlay">
                            <a href="${buildUrl(item.file_url)}" target="_blank" class="media-item-action" title="View">
                                <i class="uil uil-eye"></i>
                            </a>
                        </div>
                    `;
                    photosContainer.appendChild(div);
                });
            } else {
                photosContainer.innerHTML = '<p class="empty-media">No photos shared yet</p>';
            }
        }

        if (videosContainer) {
            clearElement(videosContainer);
            const videos = (media.videos || []);
            if (videos.length > 0) {
                videos.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'media-item';
                    div.innerHTML = `
                        <video src="${buildUrl(item.file_url)}" controls></video>
                        <div class="media-item-overlay">
                            <a href="${buildUrl(item.file_url)}" target="_blank" class="media-item-action" title="View">
                                <i class="uil uil-play"></i>
                            </a>
                        </div>
                    `;
                    videosContainer.appendChild(div);
                });
            } else {
                videosContainer.innerHTML = '<p class="empty-media">No videos shared yet</p>';
            }
        }

        if (documentsContainer) {
            clearElement(documentsContainer);
            const documents = (media.documents || []);
            if (documents.length > 0) {
                documents.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'document-item';
                    const fileExt = (item.file_name || '').split('.').pop().toUpperCase();
                    const fileSize = formatFileSize(item.file_size || 0);
                    div.innerHTML = `
                        <div class="document-icon">
                            <i class="uil uil-file-alt"></i>
                            <span class="file-type">${fileExt}</span>
                        </div>
                        <div class="document-info">
                            <div class="document-name">${escapeHtml(item.file_name || 'Document')}</div>
                            <div class="document-meta">${fileSize}</div>
                        </div>
                        <a href="${buildUrl(item.file_url)}" target="_blank" class="btn btn-sm" download>
                            <i class="uil uil-download-alt"></i>
                        </a>
                    `;
                    documentsContainer.appendChild(div);
                });
            } else {
                documentsContainer.innerHTML = '<p class="empty-media">No documents shared yet</p>';
            }
        }

        // Update storage bar
        updateStorageBar(totalBytes);
    }

    function updateStorageBar(usedBytes) {
        const maxBytes = 500 * 1024 * 1024; // 500 MB
        const percentage = Math.min((usedBytes / maxBytes) * 100, 100);
        
        const usageFill = document.querySelector('.usage-fill');
        const usageText = document.querySelector('.usage-text span:first-child');
        const usagePercentage = document.querySelector('.usage-percentage');

        if (usageFill) {
            usageFill.style.width = percentage + '%';
        }

        if (usageText) {
            const usedMB = (usedBytes / (1024 * 1024)).toFixed(2);
            usageText.textContent = `${usedMB} MB used of 500 MB`;
        }

        if (usagePercentage) {
            usagePercentage.textContent = percentage.toFixed(1) + '%';
        }
    }

    function updateMediaSidebarState() {
        const mediaEmptyState = document.getElementById('media-empty-state');
        const mediaContentWrapper = document.getElementById('media-content-wrapper');
        const mediaSubtitle = document.getElementById('media-subtitle');

        if (!state.activeConversationId) {
            // No conversation selected - show empty state
            if (mediaEmptyState) mediaEmptyState.style.display = 'flex';
            if (mediaContentWrapper) mediaContentWrapper.style.display = 'none';
            if (mediaSubtitle) mediaSubtitle.textContent = 'Select a chat to view shared files';
        } else {
            // Conversation is selected - media should already be loaded by loadSharedMedia
            // Just ensure the visibility states are correct
            if (mediaEmptyState) mediaEmptyState.style.display = 'none';
            if (mediaContentWrapper) mediaContentWrapper.style.display = 'block';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    // File Management System
    let currentFolderId = 'root';
    let currentFolderName = 'All Files';
    let fileStructure = {};
    let folderHistory = [];
    let historyIndex = -1;

    function initFileManagement() {
        const mediaTabs = document.querySelectorAll('.media-tab');
        const mediaGrid = document.getElementById('media-grid');
        const filesContent = document.getElementById('files-content');
        const createFolderBtn = document.getElementById('createFolderBtn');
        const uploadFileBtn = document.getElementById('uploadFileBtn');
        const fileUploadInput = document.getElementById('fileUploadInput');
        const navBackBtn = document.getElementById('navBackBtn');
        const navForwardBtn = document.getElementById('navForwardBtn');
        const navHomeBtn = document.getElementById('navHomeBtn');

        // Tab switching
        mediaTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                mediaTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const tabType = tab.dataset.tab;
                if (tabType === 'media') {
                    if (mediaGrid) mediaGrid.style.display = 'block';
                    if (filesContent) filesContent.style.display = 'none';
                } else if (tabType === 'files') {
                    if (mediaGrid) mediaGrid.style.display = 'none';
                    if (filesContent) {
                        filesContent.style.display = 'flex';
                        // Reset to root when switching to Files tab
                        if (folderHistory.length === 0) {
                            currentFolderId = 'root';
                            currentFolderName = 'All Files';
                            folderHistory = [{ id: 'root', name: 'All Files' }];
                            historyIndex = 0;
                        }
                        // Always reload file structure when switching to Files tab
                        loadFileStructure();
                    }
                }
            });
        });

        // Navigation buttons
        if (navBackBtn) {
            navBackBtn.addEventListener('click', () => navigateBack());
        }

        if (navForwardBtn) {
            navForwardBtn.addEventListener('click', () => navigateForward());
        }

        if (navHomeBtn) {
            navHomeBtn.addEventListener('click', () => navigateHome());
        }

        // Create folder
        if (createFolderBtn) {
            createFolderBtn.addEventListener('click', () => createFolder());
        }

        // Upload file
        if (uploadFileBtn) {
            uploadFileBtn.addEventListener('click', () => {
                if (fileUploadInput) fileUploadInput.click();
            });
        }

        if (fileUploadInput) {
            fileUploadInput.addEventListener('change', (e) => {
                handleFileUpload(e.target.files);
            });
        }
    }

    async function loadFileStructure() {
        const filesGrid = document.getElementById('filesGrid');
        const currentFolderPath = document.getElementById('currentFolderPath');
        
        if (!state.activeConversationId) {
            // Show empty state when no conversation selected
            if (filesGrid) {
                filesGrid.innerHTML = `
                    <div class="empty-files">
                        <i class="uil uil-comments"></i>
                        <p>Select a conversation to manage shared files</p>
                    </div>
                `;
            }
            if (currentFolderPath) {
                currentFolderPath.textContent = 'All Files';
            }
            return;
        }

        try {
            const data = await api.fetchFileStructure(state.activeConversationId, currentFolderId);
            fileStructure = data;
            renderFileStructure();
            updateNavigationButtons();
        } catch (error) {
            console.error('Failed to load file structure:', error);
            if (filesGrid) {
                filesGrid.innerHTML = `
                    <div class="empty-files">
                        <i class="uil uil-exclamation-triangle"></i>
                        <p>Failed to load files. Please try again.</p>
                    </div>
                `;
            }
        }
    }

    function renderFileStructure() {
        const filesGrid = document.getElementById('filesGrid');
        const currentFolderPath = document.getElementById('currentFolderPath');
        if (!filesGrid) return;

        clearElement(filesGrid);

        const folders = fileStructure.folders || [];
        const files = fileStructure.files || [];

        // Update current folder name display
        if (currentFolderPath) {
            currentFolderPath.textContent = currentFolderName;
        }

        if (folders.length === 0 && files.length === 0) {
            filesGrid.innerHTML = `
                <div class="empty-files">
                    <i class="uil uil-folder-open"></i>
                    <p>No files or folders yet. Create a folder or upload files to get started.</p>
                </div>
            `;
            return;
        }

        // Render folders
        folders.forEach(folder => {
            const folderEl = document.createElement('div');
            folderEl.className = 'folder-item';
            folderEl.innerHTML = `
                <div class="item-actions">
                    <button class="action-icon-btn delete" onclick="deleteFolder(${folder.id})" title="Delete">
                        <i class="uil uil-trash-alt"></i>
                    </button>
                </div>
                <i class="uil uil-folder folder-icon"></i>
                <div class="folder-name">${escapeHtml(folder.name)}</div>
            `;
            folderEl.addEventListener('click', (e) => {
                if (!e.target.closest('.item-actions')) {
                    openFolder(folder.id, folder.name);
                }
            });
            filesGrid.appendChild(folderEl);
        });

        // Render files
        files.forEach(file => {
            const fileEl = document.createElement('div');
            fileEl.className = 'file-item-card';
            fileEl.innerHTML = `
                <div class="item-actions">
                    <a href="${buildUrl(file.file_url)}" download class="action-icon-btn" title="Download">
                        <i class="uil uil-download-alt"></i>
                    </a>
                    <button class="action-icon-btn delete" onclick="deleteFile(${file.id})" title="Delete">
                        <i class="uil uil-trash-alt"></i>
                    </button>
                </div>
                <i class="uil uil-file-alt file-icon-large"></i>
                <div class="file-name-card">${escapeHtml(file.file_name)}</div>
                <div class="file-size-card">${formatFileSize(file.file_size)}</div>
            `;
            filesGrid.appendChild(fileEl);
        });
    }

    async function createFolder() {
        const folderName = prompt('Enter folder name:');
        if (!folderName || !folderName.trim()) return;

        if (!state.activeConversationId) {
            alert('Please select a conversation first');
            return;
        }

        try {
            await api.createFolder(state.activeConversationId, currentFolderId, folderName.trim());
            loadFileStructure();
        } catch (error) {
            console.error('Failed to create folder:', error);
            alert('Failed to create folder');
        }
    }

    async function handleFileUpload(files) {
        if (!files || files.length === 0) return;
        if (!state.activeConversationId) {
            alert('Please select a conversation first');
            return;
        }

        for (const file of files) {
            try {
                const formData = new FormData();
                formData.append('conversation_id', state.activeConversationId);
                formData.append('folder_id', currentFolderId);
                formData.append('file', file);

                await api.uploadFile(formData);
            } catch (error) {
                console.error('Failed to upload file:', error);
                alert(`Failed to upload ${file.name}`);
            }
        }

        loadFileStructure();
        const fileUploadInput = document.getElementById('fileUploadInput');
        if (fileUploadInput) fileUploadInput.value = '';
    }

    function openFolder(folderId, folderName = 'Folder') {
        // Add to history when navigating to a new folder
        if (historyIndex < folderHistory.length - 1) {
            // Clear forward history if we're navigating from middle of history
            folderHistory = folderHistory.slice(0, historyIndex + 1);
        }
        folderHistory.push({ id: folderId, name: folderName });
        historyIndex = folderHistory.length - 1;
        
        currentFolderId = folderId;
        currentFolderName = folderName;
        loadFileStructure();
        updateNavigationButtons();
    }

    function navigateBack() {
        if (historyIndex > 0) {
            historyIndex--;
            const folder = folderHistory[historyIndex];
            currentFolderId = folder.id;
            currentFolderName = folder.name;
            loadFileStructure();
            updateNavigationButtons();
        }
    }

    function navigateForward() {
        if (historyIndex < folderHistory.length - 1) {
            historyIndex++;
            const folder = folderHistory[historyIndex];
            currentFolderId = folder.id;
            currentFolderName = folder.name;
            loadFileStructure();
            updateNavigationButtons();
        }
    }

    function navigateHome() {
        openFolder('root', 'All Files');
    }

    function updateNavigationButtons() {
        const navBackBtn = document.getElementById('navBackBtn');
        const navForwardBtn = document.getElementById('navForwardBtn');
        
        if (navBackBtn) {
            navBackBtn.disabled = historyIndex <= 0;
        }
        if (navForwardBtn) {
            navForwardBtn.disabled = historyIndex >= folderHistory.length - 1;
        }
    }

    async function deleteFolder(folderId) {
        if (!confirm('Delete this folder and all its contents?')) return;

        try {
            await api.deleteFolder(folderId);
            loadFileStructure();
        } catch (error) {
            console.error('Failed to delete folder:', error);
            alert('Failed to delete folder');
        }
    }

    async function deleteFile(fileId) {
        if (!confirm('Delete this file?')) return;

        try {
            await api.deleteFile(fileId);
            loadFileStructure();
        } catch (error) {
            console.error('Failed to delete file:', error);
            alert('Failed to delete file');
        }
    }

    // Make functions globally accessible for inline onclick handlers
    window.deleteFolder = deleteFolder;
    window.deleteFile = deleteFile;

    async function initChatWidget() {
        attachEventListeners();
        initFileManagement();
        try {
            const data = await api.listConversations();
            const normalized = Array.isArray(data) ? data : [];
            replaceConversations(normalized);
        } catch (error) {
            console.error('Failed to initialize chat conversations', error);
        }
    }

    initChatWidget();
})();
