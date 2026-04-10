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
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/grouprequests.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="group-manage-page">
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="manage-header events-like-header">
                    <h1><i class="uil uil-sliders-v-alt"></i> Group Manage</h1>
                    <p>Review requests for private and secret group activity in <?php echo htmlspecialchars($group['name']); ?>.</p>
                </div>

                <div class="profile-tabs manage-tabs">
                    <ul>
                        <li class="active"><a href="#" data-tab="join-requests">Join Requests</a></li>
                        <li><a href="#" data-tab="post-requests">Post Requests</a></li>
                        <li><a href="#" data-tab="bin-requests">Bin Requests</a></li>
                        <li><a href="#" data-tab="channel-requests">Channel Requests</a></li>
                    </ul>
                </div>

                <div class="manage-content">
                    <section class="manage-card tab-content active" id="join-requests-content">
                        <h3><i class="uil uil-user-plus"></i> Join Requests</h3>
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
                            <div class="manage-empty-inline">No pending join requests right now.</div>
                        <?php endif; ?>

                        <div class="manage-subdivider"></div>
                    </section>

                    <section class="manage-card tab-content" id="post-requests-content">
                        <h3><i class="uil uil-file-exclamation-alt"></i> Post Requests</h3>
                        <table class="manage-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Post Type</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nethmi Perera</td>
                                    <td>Question</td>
                                    <td>Private group post approval needed</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Kavindu Jay</td>
                                    <td>Event</td>
                                    <td>Secret group event publishing request</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="manage-card tab-content" id="bin-requests-content">
                        <h3><i class="uil uil-folder-exclamation"></i> Bin Requests</h3>
                        <table class="manage-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Bin Name</th>
                                    <th>Request</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Isuri Kavya</td>
                                    <td>Semester Materials</td>
                                    <td>Create bin in secret group</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Malinda S</td>
                                    <td>Exam Archives</td>
                                    <td>Bin visibility escalation request</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="manage-card tab-content" id="channel-requests-content">
                        <h3><i class="uil uil-channel"></i> Channel Requests</h3>
                        <div class="channel-list message-thread-list">
                            <div class="channel-item">
                                <div>
                                    <strong>Channel request: #first-year-help</strong>
                                    <p>Member asked to create a new help channel.</p>
                                </div>
                                <button class="btn btn-secondary btn-sm" type="button">Review</button>
                            </div>
                            <div class="channel-item">
                                <div>
                                    <strong>Channel request: #internships</strong>
                                    <p>Member asked to open a new internships channel.</p>
                                </div>
                                <button class="btn btn-secondary btn-sm" type="button">Review</button>
                            </div>
                        </div>

                        <div class="manage-subdivider"></div>

                        <table class="manage-table">
                            <thead>
                                <tr>
                                    <th>Channel</th>
                                    <th>Request Type</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#hostel-talk</td>
                                    <td>New channel</td>
                                    <td>Repeated study discussion requests</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#project-marketplace</td>
                                    <td>New channel</td>
                                    <td>Group wants a project buying/selling space</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" type="button">Approve</button>
                                        <button class="btn btn-secondary btn-sm" type="button">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>
    </main>

    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>'; const GROUP_ID = <?php echo (int)$group['group_id']; ?>;</script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/groupprofileview.js"></script>
    <script>
        document.querySelectorAll('.manage-tabs a[data-tab]').forEach(function(tabLink) {
            tabLink.addEventListener('click', function(event) {
                event.preventDefault();

                const selectedTab = this.getAttribute('data-tab');

                document.querySelectorAll('.manage-tabs li').forEach(function(tabItem) {
                    tabItem.classList.remove('active');
                });
                this.parentElement.classList.add('active');

                document.querySelectorAll('.manage-content .tab-content').forEach(function(section) {
                    section.classList.remove('active');
                });

                const activeSection = document.getElementById(selectedTab + '-content');
                if (activeSection) {
                    activeSection.classList.add('active');
                }
            });
        });

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
