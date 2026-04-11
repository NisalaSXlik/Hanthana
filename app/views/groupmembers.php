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

    // Keep active group context in session for related pages like File Bank.
    $resolvedGroupId = 0;
    if (isset($groupId) && (int)$groupId > 0) {
        $resolvedGroupId = (int)$groupId;
    } elseif (isset($group['group_id']) && (int)$group['group_id'] > 0) {
        $resolvedGroupId = (int)$group['group_id'];
    } elseif (isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
        $resolvedGroupId = (int)$_GET['group_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $resolvedGroupId = (int)$_GET['id'];
    }

    if ($resolvedGroupId > 0) {
        $_SESSION['current_group_id'] = $resolvedGroupId;
        $groupId = $resolvedGroupId;
    }

    $group = (isset($group) && is_array($group)) ? $group : [];
    $groupMembers = (isset($groupMembers) && is_array($groupMembers)) ? $groupMembers : [];

    $groupName = trim((string)($group['name'] ?? 'Group'));
    if ($groupName === '') {
        $groupName = 'Group';
    }

    $totalMembers = count($groupMembers);
    $profileBase = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=';

    $adminMembers = [];
    $otherMembers = [];
    foreach ($groupMembers as $member) {
        $role = strtolower((string)($member['role'] ?? 'member'));
        if ($role === 'admin') {
            $adminMembers[] = $member;
        } else {
            $otherMembers[] = $member;
        }
    }
    $orderedMembers = array_merge($adminMembers, $otherMembers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?> - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/groupmembers.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="group-members-shell" data-total-members="<?php echo (int)$totalMembers; ?>" data-group-id="<?php echo (int)$groupId; ?>" data-is-admin="<?php echo !empty($isAdmin) ? '1' : '0'; ?>" data-current-user-id="<?php echo (int)$currentUserId; ?>">
                    <div class="member-header group-members-header-shell">
                        <div class="member-header-top">
                            <div class="member-title-block">
                                <h2><i class="uil uil-users-alt"></i> Members</h2>
                                <p>People in <?php echo htmlspecialchars($groupName); ?></p>
                            </div>
                            <div class="group-members-meta">
                                <strong id="membersVisibleCount"><?php echo (int)$totalMembers; ?></strong>
                                <span>total members</span>
                            </div>
                        </div>

                        <div class="member-toolbar group-members-toolbar">
                            <div class="search-bar group-members-search">
                                <i class="uil uil-search"></i>
                                <input type="search" id="membersSearchInput" placeholder="Search members by name" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <ul class="group-members-list" id="groupMembersList">
                        <?php if (!empty($groupMembers)): ?>
                            <?php if (!empty($adminMembers)): ?>
                                <li class="member-section-header" data-section="admin">
                                    <span>Admins</span>
                                    <strong><?php echo count($adminMembers); ?></strong>
                                </li>
                                <?php foreach ($adminMembers as $index => $member): ?>
                                    <?php
                                        $memberUserId = (int)($member['user_id'] ?? 0);
                                        $fullName = trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? ''));
                                        if ($fullName === '') {
                                            $fullName = (string)($member['username'] ?? 'Unknown User');
                                        }
                                        $username = (string)($member['username'] ?? 'user');
                                        $avatar = MediaHelper::resolveMediaPath((string)($member['profile_picture'] ?? ''), 'uploads/user_dp/default.png');
                                        $profileHref = $profileBase . $memberUserId;
                                        $columnClass = ($index % 2 === 1) ? 'member-row-right-col' : 'member-row-left-col';
                                        $firstRowsClass = ($index < 2) ? 'member-row-first-in-section' : '';
                                        $canShowActions = ($memberUserId !== $currentUserId);
                                    ?>
                                    <li class="member-row member-row-clickable <?php echo $columnClass; ?> <?php echo $firstRowsClass; ?>" data-role="admin" data-name="<?php echo htmlspecialchars(strtolower($fullName), ENT_QUOTES, 'UTF-8'); ?>" data-profile-url="<?php echo htmlspecialchars($profileHref); ?>">
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                        <div class="member-main">
                                            <strong><?php echo htmlspecialchars($fullName); ?></strong>
                                            <small>@<?php echo htmlspecialchars($username); ?></small>
                                        </div>
                                        <span class="member-role admin">Admin</span>

                                        <?php if ($canShowActions): ?>
                                            <div class="member-menu" data-member-menu>
                                                <button class="member-menu-trigger" type="button" aria-label="Open member actions">
                                                    <i class="uil uil-ellipsis-v"></i>
                                                </button>
                                                <div class="member-menu-dropdown" role="menu">
                                                    <button type="button" class="member-menu-item danger" data-report-type="user" data-target-id="<?php echo $memberUserId; ?>" data-target-label="<?php echo htmlspecialchars('user ' . $fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <svg class="member-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"></circle><circle cx="12" cy="9" r="1.4" fill="currentColor"></circle><path d="M12 12.4v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>
                                                        Report user
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($otherMembers)): ?>
                                <li class="member-section-header" data-section="other">
                                    <span>Members</span>
                                    <strong><?php echo count($otherMembers); ?></strong>
                                </li>
                                <?php foreach ($otherMembers as $index => $member): ?>
                                    <?php
                                        $memberUserId = (int)($member['user_id'] ?? 0);
                                        $fullName = trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? ''));
                                        if ($fullName === '') {
                                            $fullName = (string)($member['username'] ?? 'Unknown User');
                                        }
                                        $username = (string)($member['username'] ?? 'user');
                                        $avatar = MediaHelper::resolveMediaPath((string)($member['profile_picture'] ?? ''), 'uploads/user_dp/default.png');
                                        $profileHref = $profileBase . $memberUserId;
                                        $columnClass = ($index % 2 === 1) ? 'member-row-right-col' : 'member-row-left-col';
                                        $firstRowsClass = ($index < 2) ? 'member-row-first-in-section' : '';
                                        $canShowActions = ($memberUserId !== $currentUserId);
                                    ?>
                                    <li class="member-row member-row-clickable <?php echo $columnClass; ?> <?php echo $firstRowsClass; ?>" data-role="other" data-name="<?php echo htmlspecialchars(strtolower($fullName), ENT_QUOTES, 'UTF-8'); ?>" data-profile-url="<?php echo htmlspecialchars($profileHref); ?>">
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                        <div class="member-main">
                                            <strong><?php echo htmlspecialchars($fullName); ?></strong>
                                            <small>@<?php echo htmlspecialchars($username); ?></small>
                                        </div>
                                        <span class="member-role">Member</span>

                                        <?php if ($canShowActions): ?>
                                            <div class="member-menu" data-member-menu>
                                                <button class="member-menu-trigger" type="button" aria-label="Open member actions">
                                                    <i class="uil uil-ellipsis-v"></i>
                                                </button>
                                                <div class="member-menu-dropdown" role="menu">
                                                    <?php if (!empty($isAdmin) && $memberUserId !== $currentUserId): ?>
                                                        <button type="button" class="member-menu-item danger" data-member-action="kick" data-user-id="<?php echo $memberUserId; ?>" data-user-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <svg class="member-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5h6a2 2 0 0 1 2 2v1H7V7a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="M6 8h12l-1 10a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="M10 12v4M14 12v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>
                                                            Kick member
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="member-menu-item danger" data-report-type="user" data-target-id="<?php echo $memberUserId; ?>" data-target-label="<?php echo htmlspecialchars('user ' . $fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <svg class="member-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"></circle><circle cx="12" cy="9" r="1.4" fill="currentColor"></circle><path d="M12 12.4v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>
                                                        Report user
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="member-empty">No members found.</li>
                        <?php endif; ?>
                    </ul>

                    <div class="group-members-empty" id="membersEmptyState" hidden>
                        <i class="uil uil-search"></i>
                        <p>No members match your search.</p>
                    </div>
                </div>
			</div>


            <?php include __DIR__ . '/templates/group-right.php'; ?>
        </div>

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
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <div id="kickConfirmModal" class="confirm-modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="kickConfirmTitle">
            <h3 id="kickConfirmTitle">Confirm removal</h3>
            <p id="kickConfirmText">This will remove <strong id="kickConfirmMemberName">this member</strong> from the group.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelKickBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmKickBtn">Kick member</button>
            </div>
        </div>
    </div>

    <script> const BASE_PATH = '<?php echo BASE_PATH; ?>'; </script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/groupmembers.js"></script>
    <script>
        const GROUP_ID = <?php echo (int)($groupId ?? 0); ?>;
        window.CURRENT_GROUP_ID = <?php echo (int)($groupId ?? 0); ?>;
    </script>
</body>
</html>