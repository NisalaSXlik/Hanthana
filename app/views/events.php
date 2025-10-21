<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

require_once __DIR__ . '/../models/FriendModel.php';
$friendModel = new FriendModel();
$incomingFriendRequests = $friendModel->getIncomingRequests($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Hanthane</title>
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/myfeed.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/general.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/navbar.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/mediaquery.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/calender.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/post.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/events-page.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container">
            <?php 
            // Isolate include to avoid variable collisions
            (function() {
                $activeSidebar = 'events';
                include __DIR__ . '/templates/left-sidebar.php';
            })();
            ?>

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
                        <i class="uil uil-edit"></i>
                    </div>
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages">
                    </div>
                    <div class="message-list">
                        <div class="message">
                            <div class="profile-picture">
                                <img src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/images/2.jpg" alt="Minthaka">
                                <div class="active"></div>
                            </div>
                            <div class="message-body">
                                <h5>Minthaka J.</h5>
                                <p>Are we still meeting tomorrow?</p>
                            </div>
                        </div>
                        <div class="message">
                            <div class="profile-picture">
                                <img src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/images/6.jpg" alt="Lahiru">
                            </div>
                            <div class="message-body">
                                <h5>Lahiru F.</h5>
                                <p>Sent you the event details</p>
                            </div>
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
                    <label for="eventTitle">Event Title <span class="required">*</span></label>
                    <input type="text" id="eventTitle" required placeholder="Enter event title">
                </div>

                <div class="form-group">
                    <label for="eventDescription">Description</label>
                    <textarea id="eventDescription" rows="4" placeholder="Describe your event..."></textarea>
                </div>

                <div class="form-group">
                    <label for="eventDate">Date <span class="required">*</span></label>
                    <input type="date" id="eventDate" required>
                </div>

                <div class="form-group">
                    <label for="eventTime">Time</label>
                    <input type="time" id="eventTime">
                </div>

                <div class="form-group">
                    <label for="eventLocation">Location</label>
                    <input type="text" id="eventLocation" placeholder="Enter location">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEventBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_PATH, '/'); ?>';
        const USER_ID = <?php echo $userId; ?>;
    </script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/calender.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/general.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/friends.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/navbar.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/notificationpopup.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/events.js"></script>
</body>
</html>
