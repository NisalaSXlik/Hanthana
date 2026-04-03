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

$currentUserId = (int)$_SESSION['user_id'];
$userModel = new UserModel();
$currentUser = $userModel->findById($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Messages | Hanthana</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/group-messages.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php $activeSidebar = 'group-messages'; include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle group-messages-page">
                <section class="messages-hero">
                    <div>
                        <h1><i class="uil uil-comments"></i> Group Messages</h1>
                        <p>Latest updates from groups you joined and created.</p>
                    </div>
                    <span class="dummy-tag">Dummy Data</span>
                </section>

                <section class="messages-stats-grid">
                    <article class="messages-stat-card">
                        <span>Total Threads</span>
                        <strong><?php echo count($groupMessages); ?></strong>
                    </article>
                    <article class="messages-stat-card messages-stat-card--alert">
                        <span>Unread</span>
                        <strong><?php echo (int)$unreadTotal; ?></strong>
                    </article>
                    <article class="messages-stat-card">
                        <span>Priority Alerts</span>
                        <strong><?php echo (int)$highPriorityCount; ?></strong>
                    </article>
                </section>

                <section class="messages-list-section">
                    <div class="messages-list-header">
                        <h3>Recent Threads</h3>
                    </div>

                    <div class="messages-list">
                        <?php foreach ($groupMessages as $message): ?>
                            <?php
                            $groupUrl = isset($message['group_id'])
                                ? BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . (int)$message['group_id']
                                : '#';
                            $priorityClass = ($message['priority'] ?? 'normal') === 'high' ? 'thread-card--high' : '';
                            ?>
                            <article class="thread-card <?php echo $priorityClass; ?>">
                                <div class="thread-card__top">
                                    <h4><?php echo htmlspecialchars($message['group_name']); ?></h4>
                                    <small><?php echo htmlspecialchars($message['time']); ?></small>
                                </div>
                                <p class="thread-card__sender"><?php echo htmlspecialchars($message['sender']); ?> posted a new message</p>
                                <p class="thread-card__snippet"><?php echo htmlspecialchars($message['message']); ?></p>
                                <div class="thread-card__bottom">
                                    <?php if ((int)$message['unread_count'] > 0): ?>
                                        <span class="unread-chip"><?php echo (int)$message['unread_count']; ?> unread</span>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($groupUrl); ?>">Open Group</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <aside class="right">
                <section class="groups-updates-card">
                    <h4>Groups Latest Updates</h4>
                    <div class="groups-updates-list">
                        <?php foreach ($groupLatestUpdates as $update): ?>
                            <?php
                            $groupUrl = isset($update['group_id'])
                                ? BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . (int)$update['group_id']
                                : '#';
                            ?>
                            <article class="groups-update-item">
                                <div class="groups-update-item__top">
                                    <h5><?php echo htmlspecialchars($update['group_name']); ?></h5>
                                    <small><?php echo htmlspecialchars($update['latest_time']); ?></small>
                                </div>
                                <p class="groups-update-item__sender"><?php echo htmlspecialchars($update['latest_sender']); ?> posted last</p>
                                <p class="groups-update-item__message"><?php echo htmlspecialchars($update['latest_message']); ?></p>
                                <div class="groups-update-item__footer">
                                    <?php if ((int)$update['unread_count'] > 0): ?>
                                        <span class="unread-chip"><?php echo (int)$update['unread_count']; ?> unread</span>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($groupUrl); ?>">Open</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php
                $friendRequests = $incomingFriendRequests ?? [];
                include __DIR__ . '/templates/friend-requests.php';
                ?>
            </aside>
        </div>
    </main>

    <script src="./js/calender.js?v=20250209_syntax"></script>
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
</body>
</html>
