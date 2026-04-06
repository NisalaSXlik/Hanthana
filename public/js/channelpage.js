import { api } from './utils/api.js';

(function () {
    const pageShell = document.querySelector('.channel-page-shell');
    if (!pageShell) {
        return;
    }

    const groupId = Number(pageShell.dataset.groupId || window.CHANNEL_GROUP_ID || window.CURRENT_GROUP_ID || 0);
    const groupName = pageShell.dataset.groupName || 'this group';
    const channelsContainer = document.getElementById('channelsContainer');
    const searchInput = document.getElementById('channelSearchInput');
    const filterButtons = document.querySelectorAll('.channel-filter');
    const modal = document.getElementById('createChannelModal');
    const openCreateBtn = document.getElementById('openCreateChannelBtn');
    const closeCreateBtn = document.getElementById('closeChannelModalBtn');
    const cancelCreateBtn = document.getElementById('cancelChannelBtn');
    const form = document.getElementById('createChannelForm');
    const dpInput = document.getElementById('channelDpInput');
    const dpPreviewWrap = document.getElementById('channelDpPreviewWrap');
    const dpPreviewImg = document.getElementById('channelDpPreviewImg');
    const dpName = document.getElementById('channelDpName');

    let channels = [];
    let activeFilter = 'all';
    let searchValue = '';

    function toast(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        alert(message);
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
                    </div>
                    <p class="channel-description">${escapeHtml(channel.description)}</p>
                    <div class="channel-actions">
                        <button class="${actionClass}" type="button" data-channel-id="${channel.id}">${actionLabel}</button>
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

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
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
            await api('ChannelPage', 'createChannel', payload);
            toast(`Channel "${name}" created in ${groupName}.`, 'success');
            closeModal();

            if (searchInput) {
                searchInput.value = '';
            }
            searchValue = '';
            activeFilter = 'joined';
            filterButtons.forEach((item) => item.classList.toggle('active', item.dataset.filter === 'joined'));
            await loadChannels();
        } catch (error) {
            toast(error.message || 'Failed to create channel.', 'error');
        }
    });

    channelsContainer?.addEventListener('click', async (event) => {
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

            toast(`Opening chat for ${channel.name}.`, 'info');
        }
    });

    loadChannels();
})();
