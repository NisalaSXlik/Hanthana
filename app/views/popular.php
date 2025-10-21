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
    <title>Popular - Hanthane</title>
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/general.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/navbar.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/myfeed.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/mediaquery.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/calender.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/post.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_PATH, '/'); ?>/public/css/popular.css">
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
                $activeSidebar = 'popular';
                include __DIR__ . '/templates/left-sidebar.php';
            })();
            ?>

            <div class="middle">
                <!-- Popular Page Header -->
                <div class="popular-header">
                    <h1><i class="uil uil-fire"></i> Popular</h1>
                    <p>Discover trending groups and engaging content in your community</p>
                    
                    <!-- Tab Navigation -->
                    <div class="popular-tabs">
                        <button class="popular-tab active" data-tab="groups">
                            <i class="uil uil-users-alt"></i> Popular Groups
                        </button>
                        <button class="popular-tab" data-tab="posts">
                            <i class="uil uil-chart-line"></i> Trending Posts
                        </button>
                    </div>
                </div>

                <!-- Groups Tab Content -->
                <div id="groupsTab" class="tab-content active">
                    <div id="popularGroupsContainer" class="popular-groups-grid">
                        <div class="loading-spinner">
                            <i class="uil uil-spinner-alt"></i>
                            <p>Loading popular groups...</p>
                        </div>
                    </div>
                </div>

                <!-- Posts Tab Content -->
                <div id="postsTab" class="tab-content">
                    <div id="trendingPostsContainer" class="trending-posts-grid">
                        <div class="loading-spinner">
                            <i class="uil uil-spinner-alt"></i>
                            <p>Loading trending posts...</p>
                        </div>
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

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_PATH, '/'); ?>';
        const USER_ID = <?php echo $userId; ?>;
    </script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/calender.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/general.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/friends.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/navbar.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/notificationpopup.js"></script>
    <script src="<?php echo rtrim(BASE_PATH, '/'); ?>/public/js/popular.js"></script>
</body>
</html>
