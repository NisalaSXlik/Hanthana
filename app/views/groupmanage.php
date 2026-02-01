<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['user_id'])) {
	header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
	exit();
}

$userModel = new UserModel;
$currentUser = $userModel->findById((int)$_SESSION['user_id']);
$friendRequests = $friendRequests ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Group - <?php echo htmlspecialchars($group['name'] ?? 'Group'); ?></title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="manage-header">
                    <h2>Manage Requests for <?php echo htmlspecialchars($group['name']); ?></h2>
                    <p><?php echo htmlspecialchars($group['description'] ?? ''); ?></p>
                </div>

                <div class="manage-content">
                    <?php if (!empty($pendingRequests)): ?>
                        <div class="requests-list">
                            <?php foreach ($pendingRequests as $req): ?>
                                <div class="request-row" id="request-<?php echo (int)$req['user_id']; ?>">
                                    <div class="request-left">
                                        <img class="request-dp" src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($req['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg')); ?>" alt="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>">
                                        <div class="request-meta">
                                            <strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                                            <div class="muted">@<?php echo htmlspecialchars($req['username']); ?><?php if (!empty($req['joined_at'])): ?> · <?php echo htmlspecialchars(date('M j, H:i', strtotime($req['joined_at']))); ?><?php endif; ?></div>
                                        </div>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn btn-primary approve-request" data-user-id="<?php echo (int)$req['user_id']; ?>" data-group-id="<?php echo (int)$group['group_id']; ?>">Approve</button>
                                        <button class="btn btn-secondary reject-request" data-user-id="<?php echo (int)$req['user_id']; ?>" data-group-id="<?php echo (int)$group['group_id']; ?>">Reject</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state manage-empty">
                            <div class="empty-illustration">
                                <i class="uil uil-inbox" style="font-size:48px;color:var(--color-gray);"></i>
                            </div>
                            <h3>No pending join requests</h3>
                            <p class="muted">There are currently no users waiting to join <strong><?php echo htmlspecialchars($group['name']); ?></strong>. When someone requests to join, you'll see their request here with options to approve or reject.</p>
                            <div class="empty-actions">
                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>" class="btn btn-secondary">View Group</a>
                                <button id="refreshRequestsBtn" class="btn btn-primary">Refresh</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right">
                <!-- Optionally show group summary -->
                <div class="group-summary">
                    <h4>Group Summary</h4>
                    <p><?php echo htmlspecialchars($group['name']); ?></p>
                    <small><?php echo (int)($group['member_count'] ?? 0); ?> members</small>
                </div>
            </div>
        </div>
    </main>

    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>'; const GROUP_ID = <?php echo (int)$group['group_id']; ?>;</script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/groupprofileview.js"></script>
    <script>
        // Refresh button handler for the empty state
        document.addEventListener('DOMContentLoaded', function() {
            // Handle initial refresh button if present
            const refreshBtn = document.getElementById('refreshRequestsBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => window.location.reload());
            }
        });
    </script>
</body>
</html>
