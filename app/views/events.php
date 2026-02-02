<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById($_SESSION['user_id']);

require_once __DIR__ . '/../models/FriendModel.php';
$friendModel = new FriendModel();
$incomingFriendRequests = $friendModel->getIncomingRequests($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Hanthane</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/events-page.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>

<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php $activeSidebar = 'events';
            include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <!-- Events Header -->
                <div class="events-header">
                    <h1><i class="uil uil-calendar-alt"></i> Events</h1>
                    <p style="color: var(--color-gray);">Discover and join exciting events in your community</p>
                    <div class="events-header-actions">
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="upcoming">
                                <i class="uil uil-clock"></i> Upcoming
                            </button>
                            <button class="filter-tab" data-filter="my_events">
                                <i class="uil uil-user"></i> My Events
                            </button>
                            <button class="filter-tab" data-filter="past">
                                <i class="uil uil-history"></i> Past
                            </button>
                        </div>
                        <button class="btn-create-event" id="createEventBtn">
                            <i class="uil uil-plus"></i> Create Event
                        </button>
                    </div>
                </div>

                <!-- Events Grid -->
                <div id="eventsContainer" class="events-grid">
                    <div class="loading-spinner">
                        <i class="uil uil-spinner-alt"></i>
                        <p>Loading events...</p>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit" id="openChatWidget" style="cursor: pointer;"></i>
                    </div>
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages" id="sidebarChatSearch">
                    </div>
                    <div class="message-list" id="sidebarMessageList">
                        <div class="loading-messages" style="text-align: center; padding: 1rem; color: #888;">
                            <i class="uil uil-spinner-alt" style="animation: spin 1s linear infinite;"></i>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Loading messages...</p>
                        </div>
                    </div>
                </div>

                <?php
                $friendRequests = $incomingFriendRequests ?? [];
                include __DIR__ . '/templates/friend-requests.php';
                ?>

                <div class="toast-container" id="toastContainer"></div>
            </div>
        </div>
    </main>

    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-popup-header">
            <h4>Events</h4>
            <span id="popup-date">--</span>
        </div>
        <div class="calendar-popup-body" id="calendarEvents">
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <!-- Create Event Modal (placeholder) -->
    <div id="createEventModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Create New Event</h3>
                <button class="modal-close" id="closeEventModal">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="createEventForm" class="modal-body">
                <div class="form-group">
                    <label for="createEventTitle">Event Title <span class="required">*</span></label>
                    <input type="text" id="createEventTitle" required placeholder="Enter event title">
                </div>

                <div class="form-group">
                    <label for="createEventDescription">Description</label>
                    <textarea id="createEventDescription" rows="4" placeholder="Describe your event..."></textarea>
                </div>

                <div class="form-group">
                    <label for="createEventDate">Date <span class="required">*</span></label>
                    <input type="date" id="createEventDate" required>
                </div>

                <div class="form-group">
                    <label for="createEventTime">Time</label>
                    <input type="time" id="createEventTime">
                </div>

                <div class="form-group">
                    <label for="createEventLocation">Location</label>
                    <input type="text" id="createEventLocation" placeholder="Enter location">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEventBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const USER_ID = <?php echo $currentUserId; ?>;
    </script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/poll.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/events.js"></script>
    <script>
        // Load top 3 conversations for sidebar
        (async function loadSidebarMessages() {
            const listContainer = document.getElementById('sidebarMessageList');
            const searchInput = document.getElementById('sidebarChatSearch');
            const editIcon = document.getElementById('openChatWidget');

            if (!listContainer) return;

            try {
                const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Chat&action=listConversations');
                const data = await response.json();
                const conversations = Array.isArray(data) ? data : (data.data || []);

                listContainer.innerHTML = '';

                if (!conversations.length) {
                    listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>No messages yet</p></div>';
                    return;
                }

                // Show only top 3
                const top3 = conversations.slice(0, 3);

                top3.forEach(conv => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message';
                    messageDiv.style.cursor = 'pointer';

                    const avatarPath = conv.avatar || 'uploads/user_dp/default_user_dp.jpg';
                    const fullAvatar = avatarPath.startsWith('http') ? avatarPath : '<?php echo BASE_PATH; ?>' + avatarPath;

                    messageDiv.innerHTML = `
						<div class="profile-picture">
							<img src="${fullAvatar}" alt="${conv.display_name || 'User'}">
							${conv.is_online ? '<div class="active"></div>' : ''}
						</div>
						<div class="message-body">
							<h5>${conv.display_name || 'Unknown'}</h5>
							<p>${conv.last_message_preview || 'No messages yet'}</p>
						</div>
					`;

                    messageDiv.addEventListener('click', () => {
                        // Open chat widget
                        const chatIcon = document.getElementById('chatIcon');
                        if (chatIcon) chatIcon.click();
                    });

                    listContainer.appendChild(messageDiv);
                });

            } catch (error) {
                console.error('Failed to load sidebar messages:', error);
                listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>Failed to load messages</p></div>';
            }

            // Edit icon opens chat widget
            if (editIcon) {
                editIcon.addEventListener('click', () => {
                    const chatIcon = document.getElementById('chatIcon');
                    if (chatIcon) chatIcon.click();
                });
            }
        })();
    </script>
</body>

</html>
