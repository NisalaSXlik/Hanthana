import { api } from './utils/api.js';

(function () {
    const pageShell = document.querySelector('.channel-page-shell');
    if (!pageShell) {
        return;
    }

    const groupId = Number(pageShell.dataset.groupId || window.CHANNEL_GROUP_ID || window.CURRENT_GROUP_ID || 0);
    const groupName = pageShell.dataset.groupName || 'this group';
    const isAdmin = String(pageShell.dataset.isAdmin || '0') === '1';
    const channelsContainer = document.getElementById('channelsContainer');
    const searchInput = document.getElementById('channelSearchInput');
    const filterButtons = document.querySelectorAll('.channel-filter');
    const modal = document.getElementById('createChannelModal');
    const editModal = document.getElementById('editChannelModal');
    const deleteModal = document.getElementById('deleteChannelConfirmModal');
    const openCreateBtn = document.getElementById('openCreateChannelBtn');
    const closeCreateBtn = document.getElementById('closeChannelModalBtn');
    const cancelCreateBtn = document.getElementById('cancelChannelBtn');
    const form = document.getElementById('createChannelForm');
    const editForm = document.getElementById('editChannelForm');
    const editChannelIdInput = document.getElementById('editChannelId');
    const editNameInput = document.getElementById('editChannelName');
    const editDescriptionInput = document.getElementById('editChannelDescription');
    const editDpInput = document.getElementById('editChannelDpInput');
    const editDpPreviewWrap = document.getElementById('editChannelDpPreviewWrap');
    const editDpPreviewImg = document.getElementById('editChannelDpPreviewImg');
    const editDpName = document.getElementById('editChannelDpName');
    const editTitle = document.getElementById('editChannelTitle');
    const closeEditBtn = document.getElementById('closeEditChannelModalBtn');
    const cancelEditBtn = document.getElementById('cancelEditChannelBtn');
    const deleteTitle = document.getElementById('deleteChannelTitle');
    const deleteText = document.getElementById('deleteChannelText');
    const cancelDeleteBtn = document.getElementById('cancelDeleteChannelBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteChannelBtn');
    const dpInput = document.getElementById('channelDpInput');
    const dpPreviewWrap = document.getElementById('channelDpPreviewWrap');
    const dpPreviewImg = document.getElementById('channelDpPreviewImg');
    const dpName = document.getElementById('channelDpName');

    let channels = [];
    let activeFilter = 'all';
    let searchValue = '';
    let editingChannel = null;
    let deletingChannel = null;

    function toast(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        alert(message);
    }

    async function openChannelChat(channel) {
        if (!channel || !channel.id) {
            return;
        }

        try {
            if (window.HanthanaChat && typeof window.HanthanaChat.openGroupConversation === 'function') {
                await window.HanthanaChat.openGroupConversation(channel.id);
                return;
            }

            window.dispatchEvent(new CustomEvent('hanthana:open-chat', {
                detail: {
                    type: 'group',
                    targetId: channel.id,
                    conversationId: channel.conversation_id || 0,
                }
            }));
        } catch (error) {
            toast(error?.message || 'Unable to open channel chat.', 'error');
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getDisplayImage(path, fallbackName) {
        const safePath = String(path || '').trim();
        if (safePath) {
            if (safePath.startsWith('http://') || safePath.startsWith('https://') || safePath.startsWith('/')) {
                return safePath;
            }
            return `${window.BASE_PATH || '/'}${safePath}`.replace('//', '/');
        }

        return `https://ui-avatars.com/api/?background=0EA5E9&color=fff&rounded=true&size=128&name=${encodeURIComponent((fallbackName || 'CH').slice(0, 2))}`;
    }

    function openModal() {
        if (!modal) {
            return;
        }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function openEditModal(channel) {
        if (!editModal || !editForm || !channel) {
            return;
        }

        editingChannel = channel;
        if (editChannelIdInput) editChannelIdInput.value = String(channel.id || '');
        if (editNameInput) editNameInput.value = channel.name || '';
        if (editDescriptionInput) editDescriptionInput.value = channel.description || '';

        if (editDpPreviewWrap && editDpPreviewImg && editDpName) {
            editDpPreviewImg.src = getDisplayImage(channel.display_picture, channel.name);
            editDpName.textContent = channel.name || 'Preview';
            editDpPreviewWrap.style.display = 'flex';
        }

        editModal.classList.add('active');
        editModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        if (!editModal || !editForm) {
            return;
        }

        editModal.classList.remove('active');
        editModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        editForm.reset();
        editingChannel = null;

        if (editDpPreviewWrap && editDpPreviewImg && editDpName) {
            editDpPreviewWrap.style.display = 'none';
            editDpPreviewImg.src = '';
            editDpName.textContent = 'Preview';
        }
    }

    function openDeleteModal(channel) {
        if (!deleteModal || !channel) {
            return;
        }

        deletingChannel = channel;
        if (deleteTitle) deleteTitle.textContent = `Delete ${channel.name || 'channel'}?`;
        if (deleteText) deleteText.textContent = `This will permanently remove ${channel.name || 'this channel'} and its chat history.`;
        deleteModal.classList.add('active');
        deleteModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        if (!deleteModal) {
            return;
        }

        deleteModal.classList.remove('active');
        deleteModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        deletingChannel = null;
    }

    function closeModal() {
        if (!modal || !form) {
            return;
        }

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        form.reset();

        if (dpPreviewWrap && dpPreviewImg && dpName) {
            dpPreviewWrap.style.display = 'none';
            dpPreviewImg.src = '';
            dpName.textContent = 'Preview';
        }
    }

    function normalizeChannels(rawChannels) {
        return (Array.isArray(rawChannels) ? rawChannels : []).map((channel) => {
            const isMain = Boolean(channel.is_main);

            return {
                id: Number(channel.channel_id || channel.id || 0),
                conversation_id: Number(channel.conversation_id || 0),
                group_id: Number(channel.group_id || groupId),
                name: String(channel.name || 'Untitled Channel'),
                description: String(channel.description || 'No description provided yet.'),
                display_picture: String(channel.display_picture || ''),
                member_count: Number(channel.member_count || 0),
                joined: Boolean(channel.joined),
                is_main: isMain,
                can_manage: isAdmin,
            };
        });
    }

    async function loadChannels() {
        if (!groupId) {
            toast('Missing group context. Open the page from a group first.', 'error');
            return;
        }

        try {
            const response = await api('ChannelPage', 'listChannels', { group_id: groupId });
            channels = normalizeChannels(response?.data?.channels || []);
            renderChannels();
        } catch (error) {
            toast(error.message || 'Failed to load channels.', 'error');
            channels = [];
            renderChannels();
        }
    }

    function getVisibleChannels() {
        const query = searchValue.trim().toLowerCase();
        const filtered = channels.filter((channel) => {
            const matchesSearch = !query
                || channel.name.toLowerCase().includes(query)
                || channel.description.toLowerCase().includes(query);

            const matchesFilter = activeFilter === 'all'
                || (activeFilter === 'joined' && channel.joined)
                || (activeFilter === 'available' && !channel.joined);

            return matchesSearch && matchesFilter;
        });

        return filtered.sort((a, b) => {
            if (b.member_count !== a.member_count) {
                return b.member_count - a.member_count;
            }
            return a.name.localeCompare(b.name);
        });
    }

    function renderChannels() {
        if (!channelsContainer) {
            return;
        }

        const visible = getVisibleChannels();

        if (!visible.length) {
            channelsContainer.innerHTML = `
                <div class="channel-empty">
                    <i class="uil uil-search-alt"></i>
                    <strong>No channels match your search.</strong>
                    <p style="margin: 0.35rem 0 0;">Try another keyword in ${escapeHtml(groupName)}.</p>
                </div>`;
            return;
        }

        channelsContainer.innerHTML = visible.map((channel) => {
            const joined = Boolean(channel.joined);
            const actionLabel = joined ? 'Open Chat' : 'Join';
            const actionClass = joined ? 'btn btn-primary channel-chat-btn' : 'btn btn-secondary channel-join-btn';
            const showManageMenu = isAdmin;

            return `
                <article class="channel-card" data-channel-id="${channel.id}">
                    <div class="channel-card-head">
                        <div class="channel-card-main">
                            <img class="channel-avatar" src="${escapeHtml(getDisplayImage(channel.display_picture, channel.name))}" alt="${escapeHtml(channel.name)}">
                            <div class="channel-meta">
                                <h3>${escapeHtml(channel.name)}</h3>
                                <p>${channel.member_count} member${channel.member_count === 1 ? '' : 's'}</p>
                            </div>
                        </div>
                        ${showManageMenu ? `
                        <div class="channel-menu">
                            <button type="button" class="channel-menu-trigger" aria-label="Channel actions" data-channel-menu-trigger>
                                <i class="uil uil-ellipsis-v"></i>
                            </button>
                            <div class="channel-menu-dropdown">
                                <button type="button" class="channel-menu-item" data-channel-action="edit" data-channel-id="${channel.id}">
                                    <i class="uil uil-edit-alt"></i>
                                    <span>Edit</span>
                                </button>
                                <button type="button" class="channel-menu-item danger" data-channel-action="delete" data-channel-id="${channel.id}">
                                    <i class="uil uil-trash-alt"></i>
                                    <span>Delete</span>
                                </button>
                                <button type="button" class="channel-menu-item" data-report-type="channel" data-target-id="${channel.id}" data-target-label="channel ${escapeHtml(channel.name)}">
                                    <i class="uil uil-exclamation-circle"></i>
                                    <span>Report</span>
                                </button>
                            </div>
                        </div>` : ''}
                    </div>
                    <p class="channel-description">${escapeHtml(channel.description)}</p>
                    <div class="channel-actions">
                        <button class="${actionClass}" type="button" data-channel-id="${channel.id}">${actionLabel}</button>
                        ${showManageMenu ? '' : `<button class="btn btn-secondary channel-report-btn" type="button"
                            data-report-type="channel"
                            data-target-id="${channel.id}"
                            data-target-label="channel ${escapeHtml(channel.name)}">
                            Report
                        </button>`}
                    </div>
                </article>`;
        }).join('');
    }

    function getChannel(channelId) {
        return channels.find((channel) => Number(channel.id) === Number(channelId));
    }

    openCreateBtn?.addEventListener('click', () => {
        if (!groupId) {
            toast('Missing group context. Open the page from a group first.', 'error');
            return;
        }
        openModal();
    });

    closeCreateBtn?.addEventListener('click', closeModal);
    cancelCreateBtn?.addEventListener('click', closeModal);
    closeEditBtn?.addEventListener('click', closeEditModal);
    cancelEditBtn?.addEventListener('click', closeEditModal);
    cancelDeleteBtn?.addEventListener('click', closeDeleteModal);
    confirmDeleteBtn?.addEventListener('click', async () => {
        if (!deletingChannel) {
            return;
        }

        try {
            await api('ChannelPage', 'deleteChannel', {
                channel_id: deletingChannel.id,
                group_id: groupId,
            });
            toast(`Deleted ${deletingChannel.name}.`, 'success');
            closeDeleteModal();
            await loadChannels();
        } catch (error) {
            toast(error.message || 'Failed to delete channel.', 'error');
        }
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    editModal?.addEventListener('click', (event) => {
        if (event.target === editModal) {
            closeEditModal();
        }
    });

    deleteModal?.addEventListener('click', (event) => {
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    dpInput?.addEventListener('change', (event) => {
        const file = event.target.files && event.target.files[0];
        if (!file || !dpPreviewWrap || !dpPreviewImg || !dpName) {
            if (dpPreviewWrap) {
                dpPreviewWrap.style.display = 'none';
            }
            return;
        }

        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            dpPreviewImg.src = readerEvent.target.result;
            dpName.textContent = file.name;
            dpPreviewWrap.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    });

    editDpInput?.addEventListener('change', (event) => {
        const file = event.target.files && event.target.files[0];
        if (!file || !editDpPreviewWrap || !editDpPreviewImg || !editDpName) {
            if (editDpPreviewWrap) {
                editDpPreviewWrap.style.display = 'none';
            }
            return;
        }

        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            editDpPreviewImg.src = readerEvent.target.result;
            editDpName.textContent = file.name;
            editDpPreviewWrap.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    });

    searchInput?.addEventListener('input', (event) => {
        searchValue = event.target.value;
        renderChannels();
    });

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.filter || 'all';
            filterButtons.forEach((item) => item.classList.toggle('active', item === button));
            renderChannels();
        });
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const nameInput = document.getElementById('channelName');
        const descriptionInput = document.getElementById('channelDescription');
        const name = nameInput ? nameInput.value.trim() : '';
        const description = descriptionInput ? descriptionInput.value.trim() : '';
        const file = dpInput && dpInput.files ? dpInput.files[0] : null;

        if (!name) {
            toast('Channel name is required.', 'error');
            return;
        }

        const payload = new FormData();
        payload.append('group_id', String(groupId));
        payload.append('name', name);
        payload.append('description', description);
        if (file) {
            payload.append('display_picture', file);
        }

        try {
            const response = await api('ChannelPage', 'createChannel', payload);
            const queued = Boolean(response && response.queued);
            const message = response && response.message
                ? response.message
                : (queued ? 'Channel request submitted for admin approval.' : `Channel "${name}" created in ${groupName}.`);
            toast(message, 'success');
            closeModal();

            if (searchInput) {
                searchInput.value = '';
            }
            searchValue = '';
            activeFilter = queued ? 'all' : 'joined';
            filterButtons.forEach((item) => item.classList.toggle('active', item.dataset.filter === activeFilter));
            await loadChannels();
        } catch (error) {
            toast(error.message || 'Failed to create channel.', 'error');
        }
    });

    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!editingChannel) {
            toast('Missing channel data.', 'error');
            return;
        }

        const name = editNameInput ? editNameInput.value.trim() : '';
        const description = editDescriptionInput ? editDescriptionInput.value.trim() : '';
        const file = editDpInput && editDpInput.files ? editDpInput.files[0] : null;

        if (!name) {
            toast('Channel name is required.', 'error');
            return;
        }

        const payload = new FormData();
        payload.append('channel_id', String(editingChannel.id));
        payload.append('group_id', String(groupId));
        payload.append('name', name);
        payload.append('description', description);
        if (file) {
            payload.append('display_picture', file);
        }

        try {
            await api('ChannelPage', 'editChannel', payload);
            toast(`Updated ${name}.`, 'success');
            closeEditModal();
            await loadChannels();
        } catch (error) {
            toast(error.message || 'Failed to update channel.', 'error');
        }
    });

    channelsContainer?.addEventListener('click', async (event) => {
        const actionBtn = event.target.closest('[data-channel-action]');
        if (actionBtn) {
            const channel = getChannel(actionBtn.dataset.channelId);
            if (!channel) {
                return;
            }

            const action = actionBtn.dataset.channelAction;
            if (action === 'edit') {
                openEditModal(channel);
                return;
            }

            if (action === 'delete') {
                openDeleteModal(channel);
                return;
            }
        }

        const menuTrigger = event.target.closest('[data-channel-menu-trigger]');
        if (menuTrigger) {
            event.stopPropagation();
            const menu = menuTrigger.closest('.channel-menu');
            if (menu) {
                menu.classList.toggle('is-open');
            }
            return;
        }

        document.querySelectorAll('.channel-menu.is-open').forEach((menu) => {
            if (!menu.contains(event.target)) {
                menu.classList.remove('is-open');
            }
        });

        const joinBtn = event.target.closest('.channel-join-btn');
        if (joinBtn) {
            const channel = getChannel(joinBtn.dataset.channelId);
            if (!channel) {
                return;
            }

            try {
                await api('ChannelPage', 'joinChannel', {
                    channel_id: channel.id,
                    group_id: groupId,
                });
                channel.joined = true;
                toast(`Joined ${channel.name}.`, 'success');
                renderChannels();
            } catch (error) {
                toast(error.message || 'Failed to join channel.', 'error');
            }
            return;
        }

        const chatBtn = event.target.closest('.channel-chat-btn');
        if (chatBtn) {
            const channel = getChannel(chatBtn.dataset.channelId);
            if (!channel) {
                return;
            }

            await openChannelChat(channel);
            return;
        }

        const reportBtn = event.target.closest('[data-report-type="channel"]');
        if (reportBtn) {
            return;
        }
    });

    loadChannels();
})();
