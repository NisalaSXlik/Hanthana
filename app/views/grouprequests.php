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
$postRequests = $postRequests ?? [];
$binRequests = $binRequests ?? [];
$channelRequests = $channelRequests ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Group - <?php echo htmlspecialchars($group['name'] ?? 'Group'); ?></title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/group-right.css">
    <link rel="stylesheet" href="./css/grouprequests.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>
            <div class="middle">
                <div class="manage-header">
                    <h1><i class="uil uil-sliders-v-alt"></i>Group Requests</h1>
                    <p>Review requests for private and secret group activity in <?php echo htmlspecialchars($group['name']); ?>.</p>
                </div>

                <div class="requests-container">
                    <div class="profile-tabs manage-tabs">
                        <ul>
                            <?php if (!$isPublicGroup): ?>
                            <li class="active"><a href="#" data-tab="join-requests">Join Requests</a></li>
                            <li><a href="#" data-tab="post-requests">Post Requests</a></li>
                            <?php endif; ?>
                            <li><a href="#" data-tab="bin-requests">Bin Requests</a></li>
                            <li><a href="#" data-tab="channel-requests">Channel Requests</a></li>
                        </ul>
                    </div>

                    <div class="manage-content">
                    <section class="manage-card tab-content active" id="join-requests-content">
                        <h3><i class="uil uil-user-plus"></i> Join Requests</h3>
                        <div class="request-feedback" id="groupRequestsFeedback" style="display:none;"></div>
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
                                            <button class="btn btn-primary approve-request" data-user-id="<?php echo (int)$req['user_id']; ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Approve</button>
                                            <button class="btn btn-secondary reject-request" data-user-id="<?php echo (int)$req['user_id']; ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Reject</button>
                                            <a class="btn btn-secondary" href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)$req['user_id']; ?>">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="manage-empty-inline">No pending join requests right now.</div>
                        <?php endif; ?>
                    </section>

                    <section class="manage-card tab-content" id="post-requests-content">
                        <h3><i class="uil uil-file-exclamation-alt"></i> Post Requests</h3>
                        <div class="requests-list">
                            <?php if (!empty($postRequests)): ?>
                            <?php foreach ($postRequests as $request): ?>
                                <div class="request-row">
                                    <div class="request-left">
                                        <div class="request-meta">
                                            <strong><?php echo htmlspecialchars((string)$request['member']); ?></strong>
                                            <div class="muted"><?php echo htmlspecialchars((string)$request['post_type']); ?> · <?php echo htmlspecialchars((string)$request['reason']); ?></div>
                                        </div>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn btn-primary approve-other-request" data-request-kind="<?php echo htmlspecialchars((string)($request['request_kind'] ?? 'post')); ?>" data-request-id="<?php echo (int)($request['request_id'] ?? $request['report_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Approve</button>
                                        <button class="btn btn-secondary reject-other-request" data-request-kind="<?php echo htmlspecialchars((string)($request['request_kind'] ?? 'post')); ?>" data-request-id="<?php echo (int)($request['request_id'] ?? $request['report_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Reject</button>
                                        <a class="btn btn-secondary" href="<?php echo BASE_PATH; ?>index.php?controller=GroupReports&action=index&group_id=<?php echo (int)$group['group_id']; ?>">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="manage-empty-inline">No pending post requests right now.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="manage-card tab-content" id="bin-requests-content">
                        <h3><i class="uil uil-folder-exclamation"></i> Bin Requests</h3>
                        <div class="requests-list">
                            <?php if (!empty($binRequests)): ?>
                            <?php foreach ($binRequests as $request): ?>
                                <div class="request-row">
                                    <div class="request-left">
                                        <div class="request-meta">
                                            <strong><?php echo htmlspecialchars((string)$request['member']); ?></strong>
                                            <div class="muted"><?php echo htmlspecialchars((string)$request['bin_name']); ?> · <?php echo htmlspecialchars((string)$request['request']); ?></div>
                                        </div>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn btn-primary approve-other-request" data-request-kind="<?php echo htmlspecialchars((string)($request['request_kind'] ?? 'bin')); ?>" data-request-id="<?php echo (int)($request['request_id'] ?? $request['report_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Approve</button>
                                        <button class="btn btn-secondary reject-other-request" data-request-kind="<?php echo htmlspecialchars((string)($request['request_kind'] ?? 'bin')); ?>" data-request-id="<?php echo (int)($request['request_id'] ?? $request['report_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Reject</button>
                                        <a class="btn btn-secondary" href="<?php echo BASE_PATH; ?>index.php?controller=GroupReports&action=index&group_id=<?php echo (int)$group['group_id']; ?>">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="manage-empty-inline">No pending bin requests right now.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="manage-card tab-content" id="channel-requests-content">
                        <h3><i class="uil uil-channel"></i> Channel Requests</h3>
                        <div class="requests-list">
                            <?php if (!empty($channelRequests)): ?>
                            <?php foreach ($channelRequests as $request): ?>
                                <div class="request-row">
                                    <div class="request-left">
                                        <div class="request-meta">
                                            <strong><?php echo htmlspecialchars((string)$request['channel']); ?></strong>
                                            <div class="muted"><?php echo htmlspecialchars((string)$request['request_type']); ?> · <?php echo htmlspecialchars((string)$request['reason']); ?></div>
                                        </div>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn btn-primary approve-other-request" data-request-kind="channel" data-request-id="<?php echo (int)($request['request_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Approve</button>
                                        <button class="btn btn-secondary reject-other-request" data-request-kind="channel" data-request-id="<?php echo (int)($request['request_id'] ?? 0); ?>" data-group-id="<?php echo (int)$group['group_id']; ?>" type="button">Reject</button>
                                        <a class="btn btn-secondary" href="<?php echo BASE_PATH; ?>index.php?controller=ChannelPage&action=index&group_id=<?php echo (int)$group['group_id']; ?>">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="manage-empty-inline">No pending channel requests right now.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>

        <?php include __DIR__ . '/templates/chat-clean.php'; ?>
        <?php include __DIR__ . '/templates/report-modal.php'; ?>
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
    <script src="./js/report.js"></script>
    <script src="./js/groupprofileview.js"></script>
    <script src="./js/grouprequests.js"></script>
</body>
</html>
