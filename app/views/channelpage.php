<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle" style="width: 100%;">
                <?php
                    $channelGroupId = 0;
                    if (isset($groupId) && (int)$groupId > 0) {
                        $channelGroupId = (int)$groupId;
                    } elseif (isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
                        $channelGroupId = (int)$_GET['group_id'];
                    } elseif (isset($_SESSION['current_group_id']) && (int)$_SESSION['current_group_id'] > 0) {
                        $channelGroupId = (int)$_SESSION['current_group_id'];
                    }

                    if ($channelGroupId > 0) {
                        $_SESSION['current_group_id'] = $channelGroupId;
                    }

                    $channelGroupName = isset($group['name']) ? $group['name'] : 'this group';
                ?>

                <style>
                    .channel-page-shell {
                        display: flex;
                        flex-direction: column;
                        gap: 1rem;
                    }

                    .channel-header {
                        background: #fff;
                        border: 1px solid var(--gray-200, #e5e7eb);
                        border-radius: 14px;
                        padding: 1.25rem 1.5rem;
                        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
                    }

                    .channel-header-top {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                        gap: 1rem;
                        flex-wrap: wrap;
                    }

                    .channel-title-block {
                        display: flex;
                        flex-direction: column;
                        gap: 0.35rem;
                    }

                    .channel-title-block h2 {
                        margin: 0;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.7rem;
                        font-size: 1.75rem;
                        color: var(--color-dark);
                    }

                    .channel-title-block h2 i {
                        color: var(--color-primary);
                        font-size: 2rem;
                    }

                    .channel-title-block p {
                        margin: 0;
                        color: var(--color-gray);
                        font-size: 0.95rem;
                    }

                    .channel-header-actions {
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        flex-wrap: wrap;
                    }

                    .channel-toolbar {
                        margin-top: 1rem;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                        flex-wrap: wrap;
                    }

                    .channel-toolbar .search-bar {
                        width: 100%;
                        max-width: 26rem;
                    }

                    .channel-filters {
                        display: flex;
                        gap: 0.5rem;
                        flex-wrap: wrap;
                    }

                    .channel-filter {
                        border: 1px solid var(--gray-200, #e5e7eb);
                        background: #fff;
                        color: var(--color-gray);
                        border-radius: 999px;
                        padding: 0.55rem 0.9rem;
                        font-size: 0.85rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: 0.2s ease;
                    }

                    .channel-filter.active,
                    .channel-filter:hover {
                        border-color: var(--color-primary);
                        color: var(--color-primary);
                        background: rgba(14, 165, 233, 0.08);
                    }

                    .channel-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                        gap: 1rem;
                    }

                    .channel-card {
                        background: #fff;
                        border: 1px solid var(--gray-200, #e5e7eb);
                        border-radius: 16px;
                        padding: 1rem;
                        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
                        display: flex;
                        flex-direction: column;
                        gap: 0.9rem;
                        min-height: 100%;
                    }

                    .channel-card-head {
                        display: flex;
                        align-items: center;
                        gap: 0.85rem;
                    }

                    .channel-avatar {
                        width: 3.5rem;
                        height: 3.5rem;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 2px solid rgba(14, 165, 233, 0.18);
                        flex-shrink: 0;
                    }

                    .channel-meta h3 {
                        margin: 0 0 0.2rem;
                        font-size: 1.05rem;
                        color: var(--color-dark);
                    }

                    .channel-meta p {
                        margin: 0;
                        color: var(--color-gray);
                        font-size: 0.82rem;
                    }

                    .channel-description {
                        margin: 0;
                        color: var(--color-gray);
                        line-height: 1.6;
                        font-size: 0.92rem;
                    }

                    .channel-status-row {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 0.75rem;
                        flex-wrap: wrap;
                    }

                    .channel-badge {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.35rem;
                        border-radius: 999px;
                        padding: 0.35rem 0.7rem;
                        font-size: 0.78rem;
                        font-weight: 700;
                        background: rgba(14, 165, 233, 0.08);
                        color: var(--color-primary);
                    }

                    .channel-actions {
                        display: flex;
                        gap: 0.65rem;
                    }

                    .channel-actions .btn {
                        flex: 1;
                        min-width: 0;
                    }

                    .channel-empty {
                        background: #fff;
                        border: 1px dashed var(--gray-200, #e5e7eb);
                        border-radius: 16px;
                        padding: 2rem;
                        text-align: center;
                        color: var(--color-gray);
                    }

                    .channel-empty i {
                        display: block;
                        margin-bottom: 0.75rem;
                        font-size: 2.5rem;
                        color: var(--color-primary);
                    }

                    .channel-modal-overlay {
                        display: none;
                        position: fixed;
                        inset: 0;
                        background: rgba(15, 23, 42, 0.7);
                        z-index: 10000;
                        align-items: center;
                        justify-content: center;
                        padding: 1rem;
                    }

                    .channel-modal-overlay.active {
                        display: flex;
                    }

                    .channel-modal-content {
                        width: min(100%, 540px);
                        background: #fff;
                        border-radius: 20px;
                        overflow: hidden;
                        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.28);
                    }

                    .channel-modal-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                        padding: 1rem 1.25rem;
                        border-bottom: 1px solid var(--gray-200, #e5e7eb);
                    }

                    .channel-modal-header h3 {
                        margin: 0;
                        font-size: 1.1rem;
                        color: var(--color-dark);
                    }

                    .channel-modal-body {
                        padding: 1.25rem;
                    }

                    .channel-modal-note {
                        margin: -0.15rem 0 1rem;
                        color: var(--color-gray);
                        font-size: 0.9rem;
                    }

                    .channel-modal-footer {
                        display: flex;
                        justify-content: flex-end;
                        gap: 0.75rem;
                        padding: 1rem 1.25rem 1.25rem;
                        border-top: 1px solid var(--gray-200, #e5e7eb);
                    }

                    .channel-form-grid {
                        display: grid;
                        gap: 1rem;
                    }

                    .channel-form-grid .form-group {
                        margin: 0;
                    }

                    .channel-form-grid label {
                        display: block;
                        margin-bottom: 0.4rem;
                        font-weight: 600;
                        color: var(--color-dark);
                    }

                    .channel-form-grid input,
                    .channel-form-grid textarea {
                        width: 100%;
                        border: 1px solid var(--gray-200, #e5e7eb);
                        border-radius: 12px;
                        padding: 0.8rem 0.9rem;
                        font: inherit;
                        color: var(--color-dark);
                        background: #fff;
                    }

                    .channel-form-grid input:focus,
                    .channel-form-grid textarea:focus {
                        outline: none;
                        border-color: var(--color-primary);
                        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
                    }

                    .channel-upload {
                        border: 1px dashed rgba(14, 165, 233, 0.35);
                        border-radius: 14px;
                        padding: 1rem;
                        background: rgba(14, 165, 233, 0.04);
                    }

                    .channel-upload-label {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.6rem 0.9rem;
                        border-radius: 999px;
                        background: rgba(14, 165, 233, 0.08);
                        color: var(--color-primary);
                        font-weight: 600;
                        cursor: pointer;
                    }

                    .channel-preview {
                        display: none;
                        margin-top: 0.85rem;
                        align-items: center;
                        gap: 0.8rem;
                    }

                    .channel-preview img {
                        width: 3.5rem;
                        height: 3.5rem;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 2px solid var(--color-primary);
                    }

                    @media (max-width: 768px) {
                        .channel-header-top,
                        .channel-toolbar {
                            flex-direction: column;
                            align-items: stretch;
                        }

                        .channel-toolbar .search-bar {
                            max-width: none;
                        }

                        .channel-actions {
                            flex-direction: column;
                        }
                    }
                </style>

                <div class="channel-page-shell" data-group-id="<?php echo (int)$channelGroupId; ?>">
                    <div class="channel-header">
                        <div class="channel-header-top">
                            <div class="channel-title-block">
                                <h2><i class="uil uil-channel"></i> Channels</h2>
                                <p>Small chats inside <?php echo htmlspecialchars($channelGroupName); ?></p>
                            </div>
                            <div class="channel-header-actions">
                                <button class="btn btn-primary" id="openCreateChannelBtn" type="button">
                                    <i class="uil uil-plus"></i> Create Channel
                                </button>
                            </div>
                        </div>

                        <div class="channel-toolbar">
                            <div class="search-bar" style="max-width: 26rem; width: 100%;">
                                <i class="uil uil-search"></i>
                                <input type="search" id="channelSearchInput" placeholder="Search channels by name or description" autocomplete="off">
                            </div>

                            <div class="channel-filters" role="tablist" aria-label="Channel filters">
                                <button class="channel-filter active" type="button" data-filter="all">All</button>
                                <button class="channel-filter" type="button" data-filter="joined">Joined</button>
                                <button class="channel-filter" type="button" data-filter="available">Available</button>
                            </div>
                        </div>
                    </div>

                    <div id="channelsContainer" class="channel-grid"></div>

                    <div id="createChannelModal" class="channel-modal-overlay" aria-hidden="true">
                        <div class="channel-modal-content" role="dialog" aria-modal="true" aria-labelledby="createChannelTitle">
                            <div class="channel-modal-header">
                                <h3 id="createChannelTitle"><i class="uil uil-plus-circle"></i> Create Channel</h3>
                                <button class="modal-close" id="closeChannelModalBtn" type="button" aria-label="Close">
                                    <i class="uil uil-times"></i>
                                </button>
                            </div>

                            <form id="createChannelForm" class="channel-modal-body">
                                <p class="channel-modal-note">This creates a small chat room inside the current group.</p>
                                <input type="hidden" name="group_id" id="channelGroupId" value="<?php echo (int)$channelGroupId; ?>">

                                <div class="channel-form-grid">
                                    <div class="form-group">
                                        <label for="channelName">Channel Name</label>
                                        <input type="text" id="channelName" maxlength="100" placeholder="e.g. Study Lounge" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="channelDescription">Description</label>
                                        <textarea id="channelDescription" rows="4" placeholder="A short description of what this chat is for"></textarea>
                                    </div>

                                    <div class="form-group channel-upload">
                                        <label>Display Picture</label>
                                        <label for="channelDpInput" class="channel-upload-label">
                                            <i class="uil uil-image"></i> Choose Image
                                        </label>
                                        <input type="file" id="channelDpInput" accept="image/*" style="display:none;">
                                        <div class="channel-preview" id="channelDpPreviewWrap">
                                            <img id="channelDpPreviewImg" alt="Channel preview">
                                            <div>
                                                <strong id="channelDpName">Preview</strong>
                                                <div style="color: var(--color-gray); font-size: 0.85rem;">Visible in the channel list</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="channel-modal-footer">
                                    <button type="button" class="btn btn-secondary" id="cancelChannelBtn">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create Channel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="toast-container" id="toastContainer"></div>
                </div>

                <script>
                    (function () {
                        const groupId = Number(document.querySelector('.channel-page-shell')?.dataset.groupId || 0);
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

                        let activeFilter = 'all';
                        let searchValue = '';

                        const channels = [
                            {
                                id: 1,
                                name: 'Study Lounge',
                                description: 'Quiet place to share notes, ask quick questions, and keep study sessions focused.',
                                display_picture: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=256&q=80',
                                joined: true,
                                members: 18
                            },
                            {
                                id: 2,
                                name: 'Project Sprint',
                                description: 'Fast chat for group assignments, task updates, and deadline coordination.',
                                display_picture: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=256&q=80',
                                joined: false,
                                members: 11
                            },
                            {
                                id: 3,
                                name: 'Exam Prep',
                                description: 'Practice questions, revision schedules, and last-minute clarifications live here.',
                                display_picture: 'https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=256&q=80',
                                joined: false,
                                members: 24
                            },
                            {
                                id: 4,
                                name: 'Announcements',
                                description: 'Read-only update space for deadlines, important reminders, and group notices.',
                                display_picture: 'https://images.unsplash.com/photo-1488229297570-58520851e868?auto=format&fit=crop&w=256&q=80',
                                joined: true,
                                members: 35
                            }
                        ].map((channel) => ({ ...channel, group_id: groupId }));

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

                        function openModal() {
                            modal.classList.add('active');
                            modal.setAttribute('aria-hidden', 'false');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeModal() {
                            modal.classList.remove('active');
                            modal.setAttribute('aria-hidden', 'true');
                            document.body.style.overflow = '';
                            form.reset();
                            dpPreviewWrap.style.display = 'none';
                            dpPreviewImg.src = '';
                            dpName.textContent = 'Preview';
                        }

                        function getVisibleChannels() {
                            const query = searchValue.trim().toLowerCase();
                            return channels.filter((channel) => {
                                const matchesSearch = !query || channel.name.toLowerCase().includes(query) || channel.description.toLowerCase().includes(query);
                                const matchesFilter = activeFilter === 'all'
                                    || (activeFilter === 'joined' && channel.joined)
                                    || (activeFilter === 'available' && !channel.joined);
                                return matchesSearch && matchesFilter;
                            });
                        }

                        function renderChannels() {
                            const visible = getVisibleChannels();

                            if (!channelsContainer) {
                                return;
                            }

                            if (!visible.length) {
                                channelsContainer.innerHTML = `
                                    <div class="channel-empty" style="grid-column: 1 / -1;">
                                        <i class="uil uil-search-alt"></i>
                                        <strong>No channels match your search.</strong>
                                        <p style="margin: 0.35rem 0 0;">Try another keyword or create a new room for this group.</p>
                                    </div>`;
                                return;
                            }

                            channelsContainer.innerHTML = visible.map((channel) => {
                                const joined = !!channel.joined;
                                return `
                                    <article class="channel-card" data-channel-id="${channel.id}">
                                        <div class="channel-card-head">
                                            <img class="channel-avatar" src="${escapeHtml(channel.display_picture)}" alt="${escapeHtml(channel.name)}">
                                            <div class="channel-meta">
                                                <h3>${escapeHtml(channel.name)}</h3>
                                                <p>${channel.members} member${channel.members === 1 ? '' : 's'}</p>
                                            </div>
                                        </div>
                                        <p class="channel-description">${escapeHtml(channel.description)}</p>
                                        <div class="channel-status-row">
                                            <span class="channel-badge">
                                                <i class="uil uil-comments-alt"></i>
                                                ${joined ? 'Joined' : 'Open chat'}
                                            </span>
                                            <div class="channel-actions">
                                                ${joined
                                                    ? `<button class="btn btn-secondary channel-joined-btn" type="button" disabled>Joined</button>
                                                       <button class="btn btn-primary channel-chat-btn" type="button" data-channel-id="${channel.id}">Chat</button>`
                                                    : `<button class="btn btn-primary channel-join-btn" type="button" data-channel-id="${channel.id}">Join</button>
                                                       <button class="btn btn-secondary channel-chat-btn" type="button" data-channel-id="${channel.id}" style="display:none;">Chat</button>`}
                                            </div>
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
                            if (!file) {
                                dpPreviewWrap.style.display = 'none';
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

                        form?.addEventListener('submit', (event) => {
                            event.preventDefault();

                            const nameInput = document.getElementById('channelName');
                            const descriptionInput = document.getElementById('channelDescription');
                            const name = nameInput.value.trim();
                            const description = descriptionInput.value.trim();
                            const file = dpInput.files && dpInput.files[0];

                            if (!name) {
                                toast('Channel name is required.', 'error');
                                return;
                            }

                            const newChannel = {
                                id: Date.now(),
                                group_id: groupId,
                                name,
                                description: description || 'No description provided yet.',
                                display_picture: file ? URL.createObjectURL(file) : `https://ui-avatars.com/api/?background=0EA5E9&color=fff&rounded=true&size=128&name=${encodeURIComponent(name.slice(0, 2))}`,
                                joined: true,
                                members: 1
                            };

                            channels.unshift(newChannel);
                            toast(`Channel "${name}" created inside this group.`, 'success');
                            closeModal();
                            renderChannels();
                            searchInput.value = '';
                            searchValue = '';
                            activeFilter = 'joined';
                            filterButtons.forEach((item) => item.classList.toggle('active', item.dataset.filter === 'joined'));
                            renderChannels();
                        });

                        channelsContainer?.addEventListener('click', (event) => {
                            const joinBtn = event.target.closest('.channel-join-btn');
                            if (joinBtn) {
                                const channel = getChannel(joinBtn.dataset.channelId);
                                if (!channel) return;

                                channel.joined = true;
                                channel.members += 1;
                                toast(`Joined ${channel.name}.`, 'success');
                                renderChannels();
                                return;
                            }

                            const chatBtn = event.target.closest('.channel-chat-btn');
                            if (chatBtn) {
                                const channel = getChannel(chatBtn.dataset.channelId);
                                if (!channel) return;

                                toast(`Opening chat for ${channel.name}.`, 'info');
                            }
                        });

                        renderChannels();
                        window.GROUP_ID = groupId;
                        window.IS_CREATOR = false;
                        window.IS_ADMIN = false;
                        window.HAS_PENDING_REQUEST = false;
                        window.MEMBERSHIP_STATE = 'joined';
                        window.GROUP_POSTS = [];
                        window.CURRENT_GROUP_ID = groupId;
                        window.CHANNEL_GROUP_ID = groupId;
                    })();
                </script>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <!-- CREATE CHANNEL MODAL (form with name, description, display picture, using existing modal styles from group modals) -->
    <div id="createChannelModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Create New Channel</h3>
                <button class="modal-close" id="closeChannelModalBtn">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="createChannelForm" class="modal-body" enctype="multipart/form-data">
                <!-- Channel Name -->
                <div class="form-group">
                    <label for="channelName">Channel Name</label>
                    <input type="text" id="channelName" name="name" maxlength="100" placeholder="e.g., Tech Talks" required>
                </div>
                <!-- Description -->
                <div class="form-group">
                    <label for="channelDescription">Description</label>
                    <textarea id="channelDescription" name="description" rows="3" placeholder="What's this channel about?"></textarea>
                </div>
                <!-- Display Picture Upload (using same image upload pattern as group edit) -->
                <div class="form-group">
                    <label>Display Picture</label><br>
                    <label for="channelDpInput" class="image-upload-label" style="display: inline-block; background: var(--color-light, #f0f2f5); padding: 0.6rem 1rem; border-radius: 30px; cursor: pointer;">
                        <i class="uil uil-user"></i> Choose Image
                    </label>
                    <input type="file" id="channelDpInput" name="display_picture" accept="image/*" style="display:none;">
                    <div id="channelDpPreview" style="margin-top: 12px; display: none;">
                        <img id="channelDpPreviewImg" src="#" alt="Preview" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary);">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelChannelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitChannelBtn">Create Channel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script> const BASE_PATH = '<?php echo BASE_PATH; ?>'; </script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/comment.js"></script>
</body>
</html>